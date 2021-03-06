<?php

namespace Destiny\Common\User;

use Destiny\Common\Service;
use Destiny\Common\Application;
use Destiny\Common\Utils\Date;
use Destiny\Common\Exception;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/**
 * @method static UserService instance()
 */
class UserService extends Service {

    /**
     * @var array
     */
    protected $roles = null;

    /**
     * @var array
     */
    protected $features = null;

    /**
     * @return array
     * @throws DBALException
     */
    public function getAllRoles() {
        if ($this->roles == null) {
            $conn = Application::getDbConn();
            $stmt = $conn->prepare('SELECT roleId, roleName, roleLabel FROM `dfl_roles` ORDER BY roleId ASC');
            $stmt->execute();
            $this->roles = [];
            while ($a = $stmt->fetch()) {
                $this->roles [$a ['roleName']] = $a;
            }
        }
        return $this->roles;
    }

    /**
     * @return array <featureName, []>
     * @throws DBALException
     */
    public function getAllFeatures() {
        if ($this->features == null) {
            $conn = Application::getDbConn();
            $stmt = $conn->prepare('SELECT featureId, featureName, featureLabel FROM dfl_features ORDER BY featureId ASC');
            $stmt->execute();
            $this->features = [];
            while ($a = $stmt->fetch()) {
                $this->features [$a ['featureName']] = $a;
            }
        }
        return $this->features;
    }

    /**
     * @param string $roleName
     * @return array
     *
     * @throws DBALException
     * @throws Exception
     */
    public function getRoleIdByName($roleName) {
        $roles = $this->getAllRoles();
        if (!isset ($roles [$roleName])) {
            throw new Exception (sprintf('Invalid role name %s', $roleName));
        }
        return $roles [$roleName]['roleId'];
    }

    /**
     * Remove a role from a user
     *
     * @param int $userId
     * @param string $roleName
     * @throws DBALException
     * @throws Exception
     */
    public function removeUserRole($userId, $roleName) {
        $roleId = $this->getRoleIdByName($roleName);
        $conn = Application::getDbConn();
        $conn->delete('dfl_users_roles', [
            'userId' => $userId,
            'roleId' => $roleId
        ]);
    }

    /**
     * @param int $userId
     * @param string $roleName
     * @return string
     *
     * @throws DBALException
     * @throws Exception
     */
    public function addUserRole($userId, $roleName) {
        $roleId = $this->getRoleIdByName($roleName);
        $conn = Application::getDbConn();
        $conn->insert('dfl_users_roles', [
            'userId' => $userId,
            'roleId' => $roleId
        ]);
        return $conn->lastInsertId();
    }

    /**
     * @param string $featureName
     * @return array
     * @throws DBALException
     * @throws Exception
     */
    public function getFeatureIdByName($featureName) {
        $features = $this->getAllFeatures();
        if (!isset ($features [$featureName])) {
            throw new Exception (sprintf('Invalid feature name %s', $featureName));
        }
        return $features [$featureName]['featureId'];
    }

    /**
     * Get a list of user features
     *
     * @param int $userId
     * @return array
     * @throws DBALException
     */
    public function getFeaturesByUserId($userId) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
            SELECT DISTINCT b.featureName AS `id` FROM dfl_users_features AS a
            INNER JOIN dfl_features AS b ON (b.featureId = a.featureId)
            WHERE userId = :userId
            ORDER BY a.featureId ASC');
        $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $features = [];
        while ($feature = $stmt->fetchColumn()) {
            $features [] = $feature;
        }
        return $features;
    }

    /**
     * Add a feature to a user
     *
     * @param int $userId
     * @param string $featureName
     * @return string
     * @throws DBALException
     * @throws Exception
     */
    public function addUserFeature($userId, $featureName) {
        $featureId = $this->getFeatureIdByName($featureName);
        $conn = Application::getDbConn();
        $conn->insert('dfl_users_features', [
            'userId' => $userId,
            'featureId' => $featureId
        ]);
        return $conn->lastInsertId();
    }

    /**
     * Remove a feature from a user
     *
     * @param int $userId
     * @param string $featureName
     * @throws DBALException
     * @throws Exception
     */
    public function removeUserFeature($userId, $featureName) {
        $featureId = $this->getFeatureIdByName($featureName);
        $conn = Application::getDbConn();
        $conn->delete('dfl_users_features', [
            'userId' => $userId,
            'featureId' => $featureId
        ]);
    }

    /**
     * @param string $username
     * @param int $excludeUserId
     * @return bool
     * @throws DBALException
     */
    public function getIsUsernameTaken($username, $excludeUserId = 0) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('SELECT COUNT(*) FROM `dfl_users` WHERE username = :username AND userId != :excludeUserId');
        $stmt->bindValue('username', $username, \PDO::PARAM_STR);
        $stmt->bindValue('excludeUserId', $excludeUserId, \PDO::PARAM_INT);
        $stmt->execute();
        return ($stmt->fetchColumn() > 0) ? true : false;
    }

    /**
     * @param $email
     * @param int|string $excludeUserId
     * @return bool
     * @throws DBALException
     */
    public function getIsEmailTaken($email, $excludeUserId = 0) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('SELECT COUNT(*) FROM `dfl_users` WHERE email = :email AND userId != :excludeUserId');
        $stmt->bindValue('email', $email, \PDO::PARAM_STR);
        $stmt->bindValue('excludeUserId', $excludeUserId, \PDO::PARAM_INT);
        $stmt->execute();
        return ($stmt->fetchColumn() > 0) ? true : false;
    }

    /**
     * @param int $username
     * @return mixed
     * @throws DBALException
     */
    public function getUserByUsername($username) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('SELECT * FROM `dfl_users` WHERE username = :username LIMIT 0,1');
        $stmt->bindValue('username', $username, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * @param string $userId
     * @return mixed
     * @throws DBALException
     */
    public function getUserById($userId) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('SELECT * FROM `dfl_users` WHERE userId = :userId LIMIT 0,1');
        $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * @param array $user
     * @return string
     */
    public function addUser(array $user) {
        $conn = Application::getDbConn();
        $user ['createdDate'] = Date::getSqlDateTime();
        $user ['modifiedDate'] = Date::getSqlDateTime();
        $conn->insert('dfl_users', $user);
        return $conn->lastInsertId();
    }

    /**
     * @param int $userId
     * @param array $user
     */
    public function updateUser($userId, array $user) {
        $conn = Application::getDbConn();
        $user ['modifiedDate'] = Date::getSqlDateTime();
        $conn->update('dfl_users', $user, [
            'userId' => $userId
        ]);
    }

    /**
     * @param int $userId
     * @return array
     * @throws DBALException
     */
    public function getRolesByUserId($userId) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT b.roleName FROM dfl_users_roles AS a
          INNER JOIN dfl_roles b ON (b.roleId = a.roleId)
          WHERE a.userId = :userId
        ');
        $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $roles = [];
        while ($role = $stmt->fetchColumn()) {
            $roles [] = $role;
        }
        return $roles;
    }

    /**
     * @param string $authId
     * @param string $authProvider
     * @return array
     * @throws DBALException
     */
    public function getUserByAuthId($authId, $authProvider) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT u.* FROM dfl_users_auth AS a
          INNER JOIN dfl_users AS u ON (u.userId = a.userId)
          WHERE a.authId = :authId AND a.authProvider = :authProvider
          LIMIT 0,1
        ');
        $stmt->bindValue('authId', $authId, \PDO::PARAM_STR);
        $stmt->bindValue('authProvider', $authProvider, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * @param number $authId
     * @param string $authProvider
     * @return bool
     * @throws DBALException
     */
    public function getUserAuthProviderExists($authId, $authProvider) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT COUNT(*) FROM dfl_users_auth AS a
          INNER JOIN dfl_users AS u ON (u.userId = a.userId)
          WHERE a.authId = :authId AND a.authProvider = :authProvider
          LIMIT 1
        ');
        $stmt->bindValue('authId', $authId, \PDO::PARAM_STR);
        $stmt->bindValue('authProvider', $authProvider, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0 ? true : false;
    }

    /**
     * @param number $userId
     * @param string $authProvider
     * @return array
     * @throws DBALException
     */
    public function getUserAuthProfile($userId, $authProvider) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT a.* FROM dfl_users_auth AS a
          WHERE a.userId = :userId AND a.authProvider = :authProvider
          LIMIT 0,1
        ');
        $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('authProvider', $authProvider, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * @param number $userId
     * @return array
     * @throws DBALException
     */
    public function getAuthProfilesByUserId($userId) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT a.* FROM dfl_users_auth AS a
          WHERE a.userId = :userId
        ');
        $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param number $userId
     * @param string $authProvider
     * @param array $auth
     */
    public function updateUserAuthProfile($userId, $authProvider, array $auth) {
        $conn = Application::getDbConn();
        $auth ['modifiedDate'] = Date::getSqlDateTime();
        $conn->update('dfl_users_auth', $auth, [
            'userId' => $userId,
            'authProvider' => $authProvider
        ]);
    }

    /**
     * @param array $auth
     * @return void
     */
    public function addUserAuthProfile(array $auth) {
        $conn = Application::getDbConn();
        $conn->insert('dfl_users_auth', [
            'userId' => $auth ['userId'],
            'authProvider' => $auth ['authProvider'],
            'authId' => $auth ['authId'],
            'authCode' => $auth ['authCode'],
            'authDetail' => $auth ['authDetail'],
            'refreshToken' => $auth ['refreshToken'],
            'createdDate' => Date::getSqlDateTime(),
            'modifiedDate' => Date::getSqlDateTime()
        ], [
            \PDO::PARAM_INT,
            \PDO::PARAM_STR,
            \PDO::PARAM_INT,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \PDO::PARAM_STR
        ]);
    }

    /**
     * @param int $userId
     * @param string $authProvider
     * @throws DBALException
     */
    public function removeAuthProfile($userId, $authProvider) {
        $conn = Application::getDbConn();
        $conn->delete('dfl_users_auth', [
            'userId' => $userId,
            'authProvider' => $authProvider
        ]);
    }

    /**
     * @param string $username
     * @param int $limit
     * @param int $start
     * @return array
     * @throws DBALException
     */
    public function findUsersByUsername($username, $limit = 10, $start = 0) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT u.userId,u.username,u.email FROM dfl_users AS u
          WHERE u.username LIKE :username
          ORDER BY u.username DESC
          LIMIT :start,:limit
        ');
        $stmt->bindValue('username', $username, \PDO::PARAM_STR);
        $stmt->bindValue('start', $start, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param int $limit
     * @param int $page
     * @return array
     * @throws DBALException
     */
    public function findAll($limit, $page = 1) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT SQL_CALC_FOUND_ROWS u.userId,u.username,u.email,u.createdDate
          FROM dfl_users AS u
          ORDER BY u.userId DESC
          LIMIT :start,:limit
        ');
        $stmt->bindValue('start', ($page - 1) * $limit, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $pagination = [];
        $pagination ['list'] = $stmt->fetchAll();
        $pagination ['total'] = $conn->fetchColumn('SELECT FOUND_ROWS()');
        $pagination ['totalpages'] = ceil($pagination ['total'] / $limit);
        $pagination ['pages'] = 5;
        $pagination ['page'] = $page;
        $pagination ['limit'] = $limit;
        return $pagination;
    }

    /**
     * @param string $feature
     * @param int $limit
     * @param int $page
     * @return array
     * @throws DBALException
     */
    public function findByFeature($feature, $limit, $page=0) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT SQL_CALC_FOUND_ROWS u.userId,u.username,u.email,u.createdDate
          FROM dfl_users AS u
          INNER JOIN dfl_users_features AS uf ON (uf.userId = u.userId)
          INNER JOIN dfl_features AS f ON (f.featureId = uf.featureId)
          WHERE f.featureName = :featureName
          ORDER BY u.userId DESC
          LIMIT :start,:limit
        ');
        $stmt->bindValue('featureName', $feature, \PDO::PARAM_STR);
        $stmt->bindValue('start', ($page - 1) * $limit, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $pagination = [];
        $pagination ['list'] = $stmt->fetchAll();
        $pagination ['total'] = $conn->fetchColumn('SELECT FOUND_ROWS()');
        $pagination ['totalpages'] = ceil($pagination ['total'] / $limit);
        $pagination ['pages'] = 5;
        $pagination ['page'] = $page;
        $pagination ['limit'] = $limit;
        return $pagination;
    }

    /**
     * @param string $search
     * @param int $limit
     * @param $page
     * @return array
     * @throws DBALException
     */
    public function findBySearch($search, $limit, $page) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT SQL_CALC_FOUND_ROWS u.userId,u.username,u.email,u.createdDate FROM dfl_users AS u
          WHERE u.username LIKE :wildcard1 OR email LIKE :wildcard1
          ORDER BY CASE
          WHEN u.username LIKE :wildcard2 THEN 0
          WHEN u.username LIKE :wildcard3 THEN 1
          WHEN u.username LIKE :wildcard4 THEN 2
          ELSE 3
          END, u.username
          LIMIT :start,:limit
        ');
        $stmt->bindValue('wildcard1', '%' . $search . '%', \PDO::PARAM_STR);
        $stmt->bindValue('wildcard2', $search . ' %', \PDO::PARAM_STR);
        $stmt->bindValue('wildcard3', $search . '%', \PDO::PARAM_STR);
        $stmt->bindValue('wildcard4', '% %' . $search . '% %', \PDO::PARAM_STR);
        $stmt->bindValue('start', ($page - 1) * $limit, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $pagination = [];
        $pagination ['list'] = $stmt->fetchAll();
        $pagination ['total'] = $conn->fetchColumn('SELECT FOUND_ROWS()');
        $pagination ['totalpages'] = ceil($pagination ['total'] / $limit);
        $pagination ['pages'] = 5;
        $pagination ['page'] = $page;
        $pagination ['limit'] = $limit;
        return $pagination;
    }

    /**
     * @return array
     * @throws DBALException
     * @throws Exception
     */
    public function findByNewBDay() {
        $conn = Application::getDbConn();
        $featureId = $this->getFeatureIdByName(UserFeature::DGGBDAY);
        $stmt = $conn->prepare("
            SELECT DISTINCT u.userId, u.createdDate, TIMESTAMPDIFF(YEAR, u.createdDate, CURDATE())+1 `years` FROM dfl_users u 
            LEFT JOIN dfl_users_features uf ON (uf.userId = u.userId AND uf.featureId = :featureId)
            WHERE DATE_FORMAT(u.createdDate,'%m-%d') = DATE_FORMAT(CURDATE(),'%m-%d')
            AND u.userStatus = 'Active'
            AND TIMESTAMPDIFF(YEAR, u.createdDate, CURDATE()) > 0
            AND uf.featureId IS NULL
         ");
        $stmt->bindValue('featureId', $featureId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @return array
     * @throws DBALException
     * @throws Exception
     */
    public function findByExpiredBDay() {
        $conn = Application::getDbConn();
        $featureId = $this->getFeatureIdByName(UserFeature::DGGBDAY);
        $stmt = $conn->prepare("
            SELECT DISTINCT u.userId FROM dfl_users u 
            INNER JOIN dfl_users_features uf ON (uf.userId = u.userId AND uf.featureId = :featureId)
            WHERE DATE_FORMAT(u.createdDate,'%m-%d') <> DATE_FORMAT(CURDATE(),'%m-%d')
            AND u.userStatus = 'Active'
         ");
        $stmt->bindValue('featureId', $featureId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param $field
     * @param $value
     * @return bool|string
     * @throws DBALException
     */
    public function getUserIdByField($field, $value) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
            SELECT userId FROM dfl_users
            WHERE " . $field . " = :value
            LIMIT 1
        ");
        $stmt->bindValue('value', $value, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * @param $id
     * @return bool|string
     * @throws DBALException
     */
    public function getUserIdByDiscordId($id) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
            SELECT a.userId FROM dfl_users_auth a
            WHERE a.authId = :id AND a.authProvider = 'discord'
            LIMIT 1
        ");
        $stmt->bindValue('id', $id, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * @param $username
     * @return bool|string
     * @throws DBALException
     */
    public function getUserIdByDiscordUsername($username) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
            SELECT a.userId FROM dfl_users_auth a
            WHERE a.authDetail = :username AND a.authProvider = 'discord'
            LIMIT 1
        ");
        $stmt->bindValue('username', $username, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * @param int $userId
     * @param int $limit
     * @param int $start
     * @return mixed
     * @throws DBALException
     */
    public function getAddressByUserId($userId, $limit = 1, $start = 0) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT * FROM users_address AS a
          WHERE a.userId = :userId
          LIMIT :start,:limit
        ');
        $stmt->bindValue('userId', $userId, \PDO::PARAM_STR);
        $stmt->bindValue('start', $start, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * @param array $address
     */
    public function addAddress(array $address) {
        $conn = Application::getDbConn();
        $conn->insert('users_address', [
                'userId' => $address ['userId'],
                'fullName' => $address ['fullName'],
                'line1' => $address ['line1'],
                'line2' => $address ['line2'],
                'city' => $address ['city'],
                'region' => $address ['region'],
                'zip' => $address ['zip'],
                'country' => $address ['country'],
                'createdDate' => Date::getSqlDateTime(),
                'modifiedDate' => Date::getSqlDateTime()
            ], [
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR
        ]);
    }

    /**
     * @param array $address
     */
    public function updateAddress(array $address) {
        $conn = Application::getDbConn();
        $address ['modifiedDate'] = Date::getSqlDateTime();
        $conn->update('users_address', $address, ['id' => $address['id']]);
    }

    /**
     * @param int $userId
     * @param string $ipaddress
     * @return array
     * @throws DBALException
     */
    public function getUserActiveBan($userId, $ipaddress = "") {
        $conn = Application::getDbConn();
        if(empty($ipaddress)) {
            $stmt = $conn->prepare('
              SELECT
                b.id,
                b.userid,
                u.username,
                b.targetuserid,
                u2.username AS targetusername,
                b.ipaddress,
                b.reason,
                b.starttimestamp,
                b.endtimestamp
              FROM
                bans AS b
                INNER JOIN dfl_users AS u ON u.userId = b.userid
                INNER JOIN dfl_users AS u2 ON u2.userId = b.targetuserid
              WHERE 
                b.starttimestamp < NOW() AND 
                b.targetuserid = :userId AND
                (b.endtimestamp > NOW() OR b.endtimestamp IS NULL)
              GROUP BY b.targetuserid
              ORDER BY b.id DESC
              LIMIT 0,1
            ');
            $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
        } else {
            $stmt = $conn->prepare('
              SELECT
                b.id,
                b.userid,
                u.username,
                b.targetuserid,
                u2.username AS targetusername,
                b.ipaddress,
                b.reason,
                b.starttimestamp,
                b.endtimestamp
              FROM
                bans AS b
                INNER JOIN dfl_users AS u ON u.userId = b.userid
                INNER JOIN dfl_users AS u2 ON u2.userId = b.targetuserid
              WHERE 
                b.starttimestamp < NOW() AND 
                (b.targetuserid = :userId OR b.ipaddress = :ipaddress) AND
                (b.endtimestamp > NOW() OR b.endtimestamp IS NULL)
              GROUP BY b.targetuserid
              ORDER BY b.id DESC
              LIMIT 0,1
            ');
            $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
            $stmt->bindValue('ipaddress', $ipaddress, \PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * @param int $banId
     * @return array
     * @throws DBALException
     */
    public function getBanById($banId) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare('
          SELECT
            b.id,
            b.userid,
            u.username,
            b.targetuserid,
            u2.username AS targetusername,
            b.ipaddress,
            b.reason,
            b.starttimestamp,
            b.endtimestamp
          FROM
            bans AS b
            INNER JOIN dfl_users AS u ON u.userId = b.userid
            INNER JOIN dfl_users AS u2 ON u2.userId = b.targetuserid
          WHERE b.id = :id
          ORDER BY b.id DESC
          LIMIT 0,1
        ');
        $stmt->bindValue('id', $banId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * update an existing ban
     * @param array $ban
     */
    public function updateBan(array $ban) {
        $conn = Application::getDbConn();
        $conn->update('bans', $ban, ['id' => $ban ['id']]);
    }

    /**
     * @param array $ban
     * @return string
     */
    public function insertBan(array $ban) {
        $conn = Application::getDbConn();
        $conn->insert('bans', $ban);
        return $conn->lastInsertId();
    }

    /**
     * @param $userid
     * @return int $count The number of rows modified
     * @throws DBALException
     */
    public function removeUserBan($userid) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
          UPDATE bans
          SET endtimestamp = NOW()
          WHERE
            targetuserid = :targetuserid AND
            (
              endtimestamp IS NULL OR
              endtimestamp >= NOW()
            )
        ");
        $stmt->bindValue('targetuserid', $userid, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Get the users from the given redis keys, strip off the beginning of the keys
     * and parse the remaining string into an int, CHAT:userips-123 will be
     * transformed into (int)123 and than later users with the given ids
     * queried from the database ordered by username in ascending order
     *
     * @param array $userids
     * @return array $users The users found
     * @throws DBALException
     */
    public function getUsersByUserIds($userids) {
        if (!empty($userids)) {
            $conn = Application::getDbConn();
            $stmt = $conn->executeQuery("SELECT userId, username, email, createdDate FROM dfl_users WHERE userId IN (?) ORDER BY username", [$userids], [Connection::PARAM_STR_ARRAY]);
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * @param array $usernames
     * @return array
     * @throws DBALException
     */
    public function getUserIdsByUsernames(array $usernames) {
        if (!empty($usernames)) {
            $conn = Application::getDbConn();
            $stmt = $conn->executeQuery("SELECT u.userId `userId` FROM `dfl_users` u WHERE u.username IN (?)", [$usernames], [Connection::PARAM_STR_ARRAY]);
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * @param $userId
     * @return bool
     * @throws DBALException
     */
    public function isUserOldEnough($userId) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
          SELECT COUNT(*)
          FROM dfl_users AS u
          WHERE u.userId = :userId AND DATE_ADD(u.createdDate, INTERVAL 7 DAY) < NOW()
          LIMIT 1
        ");
        $stmt->bindValue('userId', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return !!$stmt->fetchColumn();
    }

    /**
     * @param $nick
     * @return bool|string
     * @throws DBALException
     */
    public function getTwitchIDFromNick($nick) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
          SELECT ua.authId
          FROM dfl_users_auth AS ua
          WHERE
            ua.authProvider = 'twitch' AND
            ua.authDetail   = :nick
          LIMIT 1
        ");
        $stmt->bindValue('nick', $nick, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Returns an array of active subscribers (for announcing) with the
     * key being the authid and the value being an array of user info(userid, username)
     *
     * Expects the following $data structure:
     *  [{"123":1},{"456":0}]
     *
     *  Where the key is the twitch user id (auth.authDetail) and the value is whether
     *  the user is a subscriber or not
     *
     *  Returns a user list containing only users that have their twitch sub changed.
     *
     * @param array $data
     * @return array
     * @throws DBALException
     * @throws Exception
     */
    public function updateTwitchSubscriptions(array $data) {
        if (empty($data))
            return [];

        $conn = Application::getDbConn();
        $batchsize = 100;

        $ids = [];
        foreach ($data as $authid => $subscriber) {
            if (!ctype_alnum($authid)) {
                throw new Exception("Non alpha-numeric authid found: $authid");
            }
            $ids[] = $authid;
        }

        // we get the users connected to the twitch authids so that later we can
        // update the users in batches efficiently and return the subs with
        // the required information to the caller
        $idToUser = [];
        $infosql = "
          SELECT
            u.username,
            u.userId,
            u.istwitchsubscriber,
            ua.authId
          FROM
            dfl_users_auth AS ua,
            dfl_users AS u
          WHERE
            u.userId        = ua.userId AND
            ua.authProvider = 'twitch' AND
            ua.authId       IN('%s')
        ";

        // do it in moderate batches
        foreach (array_chunk($ids, $batchsize) as $chunk) {
            $stmt = $conn->prepare(sprintf($infosql, implode("', '", $chunk)));
            $stmt->execute();
            while ($row = $stmt->fetch()) {
                $idToUser[$row['authId']] = $row;
            }
        }
        unset($ids);

        if (empty($idToUser)) {
            return [];
        }

        $changed = $subs = $nonsubs = [];
        foreach ($idToUser as $authid => $user) {
            if ($data[$authid] <> $user['istwitchsubscriber']) {
                if ($data[$authid] == 1) {
                    $subs[] = $user['userId'];
                } else if ($data[$authid] == 0) {
                    $nonsubs[] = $user['userId'];
                }
                $user['istwitchsubscriber'] = $data[$authid];
                $changed[$user['authId']] = $user;
            }
        }

        $subsql = "UPDATE dfl_users AS u SET u.istwitchsubscriber = '%s' WHERE u.userId IN('%s')";

        // update the subs first
        foreach (array_chunk($subs, $batchsize) as $chunk) {
            $conn->exec(sprintf($subsql, '1', implode("', '", $chunk)));
        }
        // update nonsubs
        foreach (array_chunk($nonsubs, $batchsize) as $chunk) {
            $conn->exec(sprintf($subsql, '0', implode("', '", $chunk)));
        }

        return $changed;
    }

    /**
     * @return array
     * @throws DBALException
     */
    public function getActiveTwitchSubscriptions() {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
          SELECT ua.authId
          FROM
            dfl_users AS u,
            dfl_users_auth AS ua
          WHERE
            u.userId             = ua.userId AND
            ua.authProvider      = 'twitch' AND
            ua.authId            IS NOT NULL AND
            u.istwitchsubscriber = 1
        ");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @param $userId
     * @param $settings
     * @return bool
     * @throws DBALException
     */
    public function saveChatSettings($userId, $settings) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
          UPDATE dfl_users SET `chatsettings` = :chatsettings
          WHERE userId = :userid LIMIT 1
        ");
        $stmt->bindValue('userid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('chatsettings', $settings, \PDO::PARAM_STR);
        $stmt->execute();
        return (bool)$stmt->rowCount();
    }

    /**
     * @param $userId
     * @return array|mixed
     * @throws DBALException
     */
    public function fetchChatSettings($userId) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
            SELECT `chatsettings` FROM dfl_users
            WHERE `userId` = :userid LIMIT 1
        ");
        $stmt->bindValue('userid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchColumn();
        return !empty($data) ? json_decode($data) : [];
    }

    /**
     * @param $userId
     * @return bool
     * @throws DBALException
     */
    public function deleteChatSettings($userId) {
        $conn = Application::getDbConn();
        $stmt = $conn->prepare("
          UPDATE dfl_users SET `chatsettings` = NULL
          WHERE userId = :userid LIMIT 1
        ");
        $stmt->bindValue('userid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->rowCount();
    }
}
