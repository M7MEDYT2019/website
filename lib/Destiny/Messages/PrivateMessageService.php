<?php
namespace Destiny\Messages;

use Destiny\Common\Application;
use Destiny\Common\Service;
use Destiny\Common\Utils\Date;
use Destiny\Common\User\UserRole;
use Doctrine\DBAL\DBALException;

/**
 * @method static PrivateMessageService instance()
 */
class PrivateMessageService extends Service {

    /**
     * Check if a user is allowed to send a message based on various criteria
     *
     * @param \Destiny\Common\SessionCredentials $user
     * @param int $targetuserid
     * @return bool
     *
     * @throws DBALException
     */
    public function canSend($user, $targetuserid) {
        if ($user->hasRole(UserRole::ADMIN))
            return true;

        $userid = $user->getUserId();
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
            SELECT
                userid,
                targetuserid,
                isread,
                UNIX_TIMESTAMP(timestamp) AS timestamp
            FROM privatemessages
            WHERE
                (
                    userid = :userid OR
                    targetuserid = :userid
                ) AND
                DATE_SUB(NOW(), INTERVAL 1 HOUR) < timestamp
            ORDER BY id ASC
        ");
        $stmt->bindValue('userid', $userid, \PDO::PARAM_INT);
        $stmt->execute();

        $now       = time();
        $cansend   = true;
        $timelimit = 60 * 60 * 1;
        $messagelimit = 3;
        
        $general_unread_count = 0;
        $target_unread_count = 0;

        while($row = $stmt->fetch()) {
            if ($row['userid'] == $userid && !$row['isread']) {
                // $userid sent a message that was NOT read
                $general_unread_count += 1;
                
                // immediately throttle if sent more than $messagelimit unread
                // messages to the same $targetuserid in the last $timelimit minutes
                // ONLY a reply can cancel this, otherwise it would `return false`
                if ($row['targetuserid'] != $targetuserid)
                    continue;
                
                $target_unread_count += 1;
                if($target_unread_count > $messagelimit && $now - $row['timestamp'] < $timelimit)
                    $cansend = false;
                
            } else if ( $row['userid'] == $targetuserid ) {
                $target_unread_count -= $messagelimit;
                $general_unread_count -= $messagelimit;
                $cansend = true;
                
                // avoid rate limiting quick replies
                // received a message in the last $timelimit minutes, reset
                if ($now - $row['timestamp'] < $timelimit)
                    return true;
            }else {
                // $userid sent a message that was read OR
                // $userid received a message from someone unrelated to this conversation
                $general_unread_count -= 2;
            }
        }
        // sent message count outweighs the received message count, deny
        // considering this is the last hour, and most people don't mark as read
        if ( $target_unread_count > 7 || $general_unread_count > 21 )
            $cansend = false;

        return $cansend;
    }

    /**
     * @param array $data
     * @return int last_insert_id()
     */
    public function addMessage(array $data){
        $conn = Application::getDbConn();
        $conn->insert ( 'privatemessages', [
            'userid' => $data['userid'],
            'targetuserid' => $data['targetuserid'],
            'message' => $data['message'],
            'timestamp' => Date::getDateTime ( 'NOW' )->format ( 'Y-m-d H:i:s' ) ,
            'isread' => 0
        ], [
            \PDO::PARAM_INT,
            \PDO::PARAM_INT,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_INT
        ]);
        return $conn->lastInsertId ();
    }

    /**
     * @param int $id
     */
    public function openMessageById($id){
        $conn = Application::getDbConn();
        $conn->update( 'privatemessages', [
            'isread' => 1
        ], [
            'id' => $id
        ]);
    }

    /**
     * @param $userid
     * @param int $start
     * @param int $limit
     * @return array
     *
     * @throws DBALException
     */
    public function getMessagesInboxByUserId($userid, $start=0, $limit=20){
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
            SELECT z.*,pm3.message FROM (
                SELECT
                    MAX(pm.id) `id`,
                    MAX(pm.timestamp) `timestamp`,
                    IF(pm.targetuserid = :userid, du.userId, tdu.userId) AS `userid`,
                    IF(pm.targetuserid = :userid, du.username, tdu.username) AS `user`,
                    SUM(IF(pm.isread=0 AND pm.targetuserid = :userid,1,0)) `unread`,
                    SUM(IF(pm.isread=1 AND pm.targetuserid = :userid,1,0)) `read`
                FROM privatemessages AS pm
                LEFT JOIN dfl_users AS du ON(du.userId = pm.userid)
                LEFT JOIN dfl_users AS tdu ON(tdu.userId = pm.targetuserid)
                WHERE pm.targetuserid = :userid OR pm.userid = :userid
                GROUP BY IF(pm.targetuserid = :userid, du.userId, tdu.userId)
            ) z
            LEFT JOIN privatemessages AS pm3 ON (pm3.id = z.id)
            ORDER BY `unread` DESC, z.timestamp DESC
            LIMIT :start,:limit
        ");
        $stmt->bindValue('userid', $userid, \PDO::PARAM_INT);
        $stmt->bindValue('start', $start, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param int $id
     * @param int $targetUserId
     * @return array
     *
     * @throws DBALException
     */
    public function getMessageByIdAndTargetUserId($id, $targetUserId){
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
            SELECT p.*, `from`.username `from` FROM privatemessages p
            LEFT JOIN `dfl_users` `from` ON (`from`.userId = p.userid)
            WHERE p.id = :id AND p.targetuserid = :targetUserId
            LIMIT 0,1
        ');
        $stmt->bindValue('id', $id, \PDO::PARAM_INT);
        $stmt->bindValue('targetUserId', $targetUserId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * @param int $userId
     * @param int $targetUserId
     * @param int $start
     * @param int $limit
     * @return array
     *
     * @throws DBALException
     */
    public function getMessagesBetweenUserIdAndTargetUserId($userId, $targetUserId, $start=0, $limit=50){
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
            SELECT
                p.*,
                `from`.username `from`,
                target.username `to`
            FROM privatemessages p
            LEFT JOIN `dfl_users` AS `from` ON (
                `from`.userId = p.userid
            )
            LEFT JOIN `dfl_users` AS `target` ON (
                target.userId = p.targetuserid
            )
            WHERE
                p.userid IN(:userId, :targetUserId) AND
                p.targetuserid IN(:userId, :targetUserId)
            ORDER BY p.id DESC
            LIMIT :start, :limit
        ');
        $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('targetUserId', $targetUserId, \PDO::PARAM_INT);
        $stmt->bindValue('start', $start, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param int $targetuserid
     *
     * @throws DBALException
     */
    public function markAllMessagesRead($targetuserid) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
            UPDATE privatemessages
            SET isread = 1
            WHERE targetuserid = :targetuserid
        ");
        $stmt->bindValue('targetuserid', $targetuserid, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @param int $targetuserid
     * @param int $fromuserid
     * @return void
     *
     * @throws DBALException
     */
    public function markMessagesRead($targetuserid, $fromuserid) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
            UPDATE privatemessages
            SET isread = 1
            WHERE
                targetuserid = :targetuserid AND
                userid       = :fromuserid
        ");
        $stmt->bindValue('targetuserid', $targetuserid, \PDO::PARAM_INT);
        $stmt->bindValue('fromuserid', $fromuserid, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @param int $messageid
     * @param int $targetuserid
     * @return boolean $success
     *
     * @throws DBALException
     */
    public function markMessageRead($messageid, $targetuserid) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
            UPDATE privatemessages
            SET isread = 1
            WHERE
                id           = :messageid AND
                targetuserid = :targetuserid
            LIMIT 1
        ");
        $stmt->bindValue('messageid', $messageid, \PDO::PARAM_INT);
        $stmt->bindValue('targetuserid', $targetuserid, \PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->rowCount();
    }

    /**
     * @param $userId
     * @param $limit
     * @return array
     *
     * @throws DBALException
     */
    public function getUnreadConversations($userId, $limit){
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
            SELECT * FROM (
              SELECT p.id `messageid`,u.username,MAX(p.timestamp) `timestamp`, COUNT(*) `unread` FROM `privatemessages` p
              INNER JOIN `dfl_users` u ON (u.userId = p.userid)
              WHERE p.targetuserid = :userId AND p.isread = 0
              GROUP BY p.userid
            ) b
            ORDER BY b.timestamp DESC, b.unread DESC
            LIMIT 0,:limit
        ");
        $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

}
