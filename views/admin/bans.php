<?php
use Destiny\Common\Utils\Date;
use Destiny\Common\Utils\Tpl;
use Destiny\Common\Config;
?>
<!DOCTYPE html>
<html>
<head>
    <title><?=Tpl::title($this->title)?></title>
    <?php include 'seg/meta.php' ?>
    <link href="<?=Config::cdnv()?>/web.css" rel="stylesheet" media="screen">
</head>
<body id="admin" class="no-contain">
<div id="page-wrap">
    <?php include 'seg/nav.php' ?>
    <?php include 'seg/admin.nav.php' ?>

    <section class="container">
        <h3 class="collapsed" data-toggle="collapse" data-target="#remove-bans-content">Remove Bans</h3>
        <div id="remove-bans-content" class="content content-dark clearfix collapse">
            <div class="ds-block">
                <p>Remove all bans, this cannot be undone.</p>
                <a onclick="return confirm('Are you sure?');" class="btn btn-danger" href="/admin/bans/purgeall">Remove all bans</a>
            </div>
        </div>
    </section>

    <section class="container">
        <h3 class="in" data-toggle="collapse" data-target="#details-content"><?=Tpl::out( sprintf('Active bans (%d)', count( $this->activeBans ) ) )?></h3>
        <div id="details-content" class="content content-dark clearfix collapse in">
            <table class="grid">
                <thead>
                <tr>
                    <td style="width: 200px;">User</td>
                    <td>Reason</td>
                    <td style="width: 80px;">Created on</td>
                    <td style="width: 80px;">Ends on</td>
                    <td></td>
                </tr>
                </thead>
                <tbody>
                <?php foreach($this->activeBans as $ban): ?>
                    <tr>
                        <td><a href="/admin/user/<?=$ban['targetuserid']?>/edit"><?=Tpl::out($ban['targetusername'])?></a></td>
                        <td class="wrap">Banned by <?=Tpl::out($ban['banningusername'])?> with reason: <?=Tpl::out($ban['reason'])?></td>
                        <td><?=Tpl::moment(Date::getDateTime($ban['starttimestamp']), Date::STRING_FORMAT)?></td>
                        <td>
                            <?php if ( !$ban['endtimestamp'] )
                                echo "Permanent";
                            else
                                echo Tpl::moment(Date::getDateTime($ban['endtimestamp']), Date::STRING_FORMAT);
                            ?>
                        </td>
                        <td><a class="btn btn-danger btn-xs" href="/admin/user/<?=$ban['targetuserid']?>/ban/remove?follow=<?=rawurlencode( $_SERVER['REQUEST_URI'] )?>">Remove</a>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php include 'seg/foot.php' ?>
<?php include 'seg/tracker.php' ?>
<script src="<?=Config::cdnv()?>/web.js"></script>
<script src="<?=Config::cdnv()?>/admin.js"></script>

</body>
</html>