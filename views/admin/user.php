<?php
use Destiny\Common\Session;
use Destiny\Common\User\UserFeature;
use Destiny\Common\Utils\Date;
use Destiny\Common\Utils\Tpl;
use Destiny\Common\Utils\Country;
use Destiny\Common\Config;
use Destiny\Common\User\UserRole;
use Destiny\Commerce\SubscriptionStatus;
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
    <?php include 'seg/alerts.php' ?>
    <?php include 'seg/admin.nav.php' ?>

    <section class="container">

        <h3 class="collapsed" data-toggle="collapse" data-target="#details-content" style="display:flex; align-items: center;">
            <span style="flex: 1;">Details <small>(<?=Tpl::out($this->user['username'])?>)</small></span>
            <a class="btn-show-all" style="font-size: 1rem; margin: 0.5em 1em;">Show all</a>
        </h3>
        <div id="details-content" class="content content-dark clearfix collapse">

            <form action="/admin/user/<?=Tpl::out($this->user['userId'])?>/edit" method="post">
                <input type="hidden" name="id" value="<?=Tpl::out($this->user['userId'])?>" />

                <div class="ds-block">
                    <div class="form-group">
                        <label class="control-label" for="inputUsername">Username / Nickname</label>
                        <div class="controls">
                            <input type="text" class="form-control" name="username" id="inputUsername" value="<?=Tpl::out($this->user['username'])?>" placeholder="Username">
                            <span class="help-block">Normally the requirements are that the nick should not begin with a letter that an emote begins with, plus it can contain only A-z 0-9 and underscores. Must contain at least 3 and at most 20 characters. Admins do not have such restrictions.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="inputEmail">Email</label>
                        <div class="controls">
                            <input type="text" class="form-control" name="email" id="inputEmail" value="<?=Tpl::out($this->user['email'])?>" placeholder="Email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Country</label>
                        <select name="country" class="form-control">
                            <option value="">Select your country</option>
                            <?$countries = Country::getCountries();?>
                            <option value="">&nbsp;</option>
                            <option value="US" <?php if($this->user['country'] == 'US'): ?>
                                selected="selected" <?php endif;?>>United States</option>
                            <option value="GB" <?php if($this->user['country'] == 'GB'): ?>
                                selected="selected" <?php endif;?>>United Kingdom</option>
                            <option value="">&nbsp;</option>
                            <?php foreach($countries as $country): ?>
                                <option value="<?=$country['alpha-2']?>" <?php if($this->user['country'] != 'US' && $this->user['country'] != 'GB' && $this->user['country'] == $country['alpha-2']):?>selected="selected" <?php endif;?>><?=Tpl::out($country['name'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Twitch Subscriber</label>
                        <select class="form-control" name="istwitchsubscriber">
                            <option value="1"<?php if($this->user['istwitchsubscriber'] == 1):?> selected="selected"<?php endif;?>>Yes</option>
                            <option value="0"<?php if($this->user['istwitchsubscriber'] == 0):?> selected="selected"<?php endif;?>>No</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Accept Gifts</label>
                        <select class="form-control" name="allowGifting">
                            <option value="1"<?php if($this->user['allowGifting'] == 1):?> selected="selected"<?php endif;?>>Yes, accept gifts</option>
                            <option value="0"<?php if($this->user['allowGifting'] == 0):?> selected="selected"<?php endif;?>>No, do not accept gifts</option>
                        </select>
                    </div>


                    <h4 style="margin: 2em 0 0 0;">Discord</h4>
                    <hr style="margin-top: 0.3em;" />

                    <div class="form-group">
                        <label class="control-label" for="inputEmail">Discord name</label>
                        <div class="controls">
                            <input type="text" class="form-control" name="discordname" id="inputDiscordname" value="<?=Tpl::out($this->user['discordname'])?>" placeholder="Discord name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="inputEmail">Discord UUID</label>
                        <div class="controls">
                            <input type="text" class="form-control" name="discorduuid" id="inputDiscorduuid" value="<?=Tpl::out($this->user['discorduuid'])?>" placeholder="Discord UUID">
                        </div>
                    </div>

                    <h4 style="margin: 2em 0 0 0;">Minecraft</h4>
                    <hr style="margin-top: 0.3em;" />

                    <div class="form-group">
                        <label class="control-label" for="inputEmail">Minecraft name</label>
                        <div class="controls">
                            <input type="text" class="form-control" name="minecraftname" id="inputMinecraftname" value="<?=Tpl::out($this->user['minecraftname'])?>" placeholder="Minecraft name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="inputEmail">Minecraft UUID</label>
                        <div class="controls">
                            <input type="text" class="form-control" name="minecraftuuid" id="inputMinecraftuuid" value="<?=Tpl::out($this->user['minecraftuuid'])?>" placeholder="Minecraft UUID">
                        </div>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="/admin/users" class="btn">Cancel</a>
                </div>

            </form>
        </div>
    </section>

    <section class="container">
        <h3 class="collapsed" data-toggle="collapse" data-target="#flairs-content">Flairs</h3>
        <div id="flairs-content" class="content content-dark clearfix collapse">
            <div class="ds-block">
                <div id="flairs-selection" data-user="<?=Tpl::out($this->user['userId'])?>" class="form-group">
                    <?php foreach($this->features as $featureName => $f): ?>
                        <?php if(!in_array($f['featureName'], UserFeature::$UNASSIGNABLE)): ?>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="features[]" value="<?=$f['featureName']?>" <?=(in_array($featureName, $this->user['features']))?'checked="checked"':''?>>
                                    <?=Tpl::out($f['featureLabel'])?>
                                </label>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <?php if(Session::hasRole(UserRole::ADMIN)): ?>
    <section class="container">
        <h3 class="collapsed" data-toggle="collapse" data-target="#roles-content">Roles</h3>
        <div id="roles-content" class="content content-dark clearfix collapse">
            <div class="ds-block">
                <div id="roles-selection" data-user="<?=Tpl::out($this->user['userId'])?>" class="form-group">
                    <?php foreach($this->roles as $role): ?>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="roles[]" value="<?=$role['roleName']?>" <?=(in_array($role['roleName'], $this->user['roles']))?'checked="checked"':''?>>
                                <?=Tpl::out($role['roleLabel'])?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if(Session::hasRole(UserRole::ADMIN)): ?>
    <section class="container">
        <h3 class="collapsed" data-toggle="collapse" data-target="#address-content">Address</h3>
        <div id="address-content" class="content content-dark clearfix collapse">

            <?php if(!empty($this->address)): ?>
                <div class="vcard ds-block">
                    <div class="fn"><?=Tpl::out($this->address['fullName'])?></div>
                    <br />
                    <div class="adr">
                        <div class="street-address">
                            <?=Tpl::out($this->address['line1'])?>, <?=Tpl::out($this->address['line2'])?>
                        </div>
                        <div>
                            <span class="city"><?=Tpl::out($this->address['city'])?></span>,
                            <span class="region"><?=Tpl::out($this->address['region'])?></span>,
                            <span class="postal-code"><?=Tpl::out($this->address['zip'])?></span>
                            <?php
                            $country = Country::getCountryByCode ( $this->address['country'] );
                            if(!empty($country)):
                                ?>
                                <br />
                                <abbr class="country"><?=Tpl::out($country['name'])?> <small>(<?=Tpl::out($country['alpha-2'])?>)</small></abbr>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="ds-block">
                    <p>No address available</p>
                </div>
            <?php endif ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="container">
        <h3 class="collapsed" data-toggle="collapse" data-target="#subscription-content">Subscriptions</h3>
        <div id="subscription-content" class="content content-dark clearfix collapse">
            <div class="ds-block">
                <a href="/admin/user/<?=Tpl::out($this->user['userId'])?>/subscription/add" class="btn btn-primary">New subscription</a>
            </div>
            <?php if(!empty($this->subscriptions)): ?>
                <table class="grid">
                    <thead>
                    <tr>
                        <td>Subscription Type</td>
                        <td>Status</td>
                        <td>Gifter</td>
                        <td>Created</td>
                        <td>Ending</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($this->subscriptions as $sub): ?>
                        <tr>
                            <td>
                                <a href="/admin/user/<?=Tpl::out($this->user['userId'])?>/subscription/<?=Tpl::out($sub['subscriptionId'])?>/edit">Tier <?=Tpl::out($sub['subscriptionTier'])?></a>
                                <?php if($sub['recurring'] == '1'): ?>(Recurring)<?php endif ?>
                            </td>
                            <td>
                                <?php if(strcasecmp($sub['status'], SubscriptionStatus::ACTIVE) === 0): ?>
                                    <span class="badge badge-success"><?=Tpl::out($sub['status'])?></span>
                                <?php else: ?>
                                    <span><?=Tpl::out($sub['status'])?></span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if(!empty($sub['gifter'])): ?>
                                    <a href="/admin/user/<?=$sub['gifter']?>/edit"><?=Tpl::out($this->gifters[$sub['gifter']]['username'])?></a>
                                <?php endif ?>
                            </td>
                            <td><?=Tpl::moment(Date::getDateTime($sub['createdDate']), Date::STRING_FORMAT_YEAR)?></td>
                            <td><?=Tpl::moment(Date::getDateTime($sub['endDate']), Date::STRING_FORMAT_YEAR)?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="ds-block">
                    <p>No active subscriptions</p>
                </div>
            <?php endif ?>
        </div>
    </section>

    <section class="container">
        <h3 class="collapsed" data-toggle="collapse" data-target="#gift-content">Gifts</h3>
        <div id="gift-content" class="content content-dark clearfix collapse">
            <?php if(!empty($this->gifts)): ?>
                <table class="grid">
                    <thead>
                    <tr>
                        <td>Subscription Type</td>
                        <td>Status</td>
                        <td>Gifted To</td>
                        <td>Created</td>
                        <td>Ending</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($this->gifts as $sub): ?>
                        <tr>
                            <td>
                                <a href="/admin/user/<?=Tpl::out($this->user['userId'])?>/subscription/<?=Tpl::out($sub['subscriptionId'])?>/edit">TIER <?=Tpl::out($sub['subscriptionTier'])?></a>
                                <?php if($sub['recurring'] == '1'): ?>(Recurring)<?php endif ?>
                            </td>
                            <td>
                                <?php if(strcasecmp($sub['status'], SubscriptionStatus::ACTIVE) === 0): ?>
                                    <span class="badge badge-success"><?=Tpl::out($sub['status'])?></span>
                                <?php else: ?>
                                    <span><?=Tpl::out($sub['status'])?></span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if(!empty($sub['userId'])): ?>
                                    <a href="/admin/user/<?=$sub['userId']?>/edit"><?=Tpl::out($this->recipients[$sub['userId']]['username'])?></a>
                                <?php endif ?>
                            </td>
                            <td><?=Tpl::moment(Date::getDateTime($sub['createdDate']), Date::STRING_FORMAT_YEAR)?></td>
                            <td><?=Tpl::moment(Date::getDateTime($sub['endDate']), Date::STRING_FORMAT_YEAR)?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="ds-block">
                    <p>No active subscription gifts</p>
                </div>
            <?php endif ?>
        </div>
    </section>

    <section class="container">
        <h3 class="collapsed" data-toggle="collapse" data-target="#smurf-content">Smurfs</h3>
        <div id="smurf-content" class="content content-dark collapse clearfix">
            <div class="ds-block">
                Smurfs are alternative accounts of the user based on the fact that the
                user is using the same IP address on every one of them.<br/>
                The algorithm is the following:<br/>
                We know the last 3 IP addresses of the user and we go and search for any
                other user who has at least one in common.<br/>
                This is <b>not</b> a sure thing.
            </div>
            <?php if(!empty($this->smurfs)): ?>
                <table class="grid">
                    <thead>
                    <tr>
                        <td>Username</td>
                        <td>Email</td>
                        <td>Created</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($this->smurfs as $user): ?>
                        <tr>
                            <td><a href="/admin/user/<?=$user['userId']?>/edit"><?=Tpl::out($user['username'])?></a></td>
                            <td><?=Tpl::out($user['email'])?></td>
                            <td><?=Tpl::moment(Date::getDateTime($user['createdDate']), Date::STRING_FORMAT_YEAR)?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="ds-block">
                    <p>No smurfs found</p>
                </div>
            <?php endif ?>
        </div>
    </section>

    <section class="container">
        <h3 class="collapsed" data-toggle="collapse" data-target="#ip-content">IPs</h3>
        <div id="ip-content" class="content content-dark clearfix collapse">
            <div class="ds-block">
                <p>The last seen 3 IP addresses of the user (as seen by the chat)</p>
            </div>
            <?php if(!empty($this->user['ips'])): ?>
                <table class="grid">
                    <thead>
                    <tr>
                        <td>IP</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($this->user['ips'] as $ip): ?>
                        <tr>
                            <td><a target="_blank" href="http://www.iplocation.net/?query=<?=rawurlencode($ip)?>"><?=Tpl::out($ip)?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="ds-block">
                    <p>No IPs found</p>
                </div>
            <?php endif ?>
        </div>
    </section>

    <section class="container">
        <h3 class="collapsed" data-toggle="collapse" data-target="#ban-content">Ban / Mute</h3>
        <div id="ban-content" class="content content-dark clearfix collapse">

            <?php if(empty($this->ban)): ?>

                <div class="ds-block">
                    <p>No active bans found</p>
                </div>

                <div class="form-actions">
                    <a href="/admin/user/<?=$this->user['userId']?>/ban" class="btn btn-danger">Ban user</a>
                </div>

            <?php else: ?>
                <div class="ds-block">
                    <p>
                        <?php if(!empty($this->ban['ipaddress'])): ?>
                            Ip: <a target="_blank" href="http://freegeoip.net/json/<?=$this->ban['ipaddress']?>"><?=$this->ban['ipaddress']?></a>
                        <?php else: ?>
                            Ip: Not set
                        <?php endif ?>
                    </p>
                    <p>
                        <?=Tpl::moment(Date::getDateTime($this->ban['starttimestamp']), Date::STRING_FORMAT)?>
                        <?php if(!empty($this->ban['endtimestamp'])): ?>
                            - <?=Tpl::moment(Date::getDateTime($this->ban['endtimestamp']), Date::STRING_FORMAT)?>
                        <?php endif ?>
                    </p>
                    <blockquote>
                        <p style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?=Tpl::out($this->ban['reason'])?></p>
                        <small><?=Tpl::out((!empty($this->ban['username'])) ? $this->ban['username']:'System')?></small>
                    </blockquote>
                </div>

                <div class="form-actions">
                    <a href="/admin/user/<?=$this->user['userId']?>/ban/<?=$this->ban['id']?>/edit" class="btn btn-primary">Edit ban</a>
                    <a onclick="return confirm('Are you sure?');" href="/admin/user/<?=$this->user['userId']?>/ban/remove" class="btn btn-danger">Remove ban</a>
                </div>

            <?php endif ?>
        </div>
    </section>

    <?php if(!empty($this->authSessions)): ?>
        <form id="admin-form-auth-sessions" method="post">
            <section class="container collapsible">
                <h3 class="collapsed" data-toggle="collapse" data-target="#authentication-content">Authentication</h3>
                <div id="authentication-content" class="content content-dark clearfix collapse">
                    <table class="grid">
                        <thead>
                        <tr>
                            <td>Type</td>
                            <td></td>
                            <td style="width:100%;">Detail</td>
                            <td>Created</td>
                            <td>Modified</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($this->authSessions as $auth): ?>
                            <tr>
                                <td><a href="/admin/user/<?=$this->user['userId']?>/auth/<?= $auth['authProvider'] ?>/delete" class="btn btn-danger btn-xs btn-post">Delete</a></td>
                                <td><?= $auth['authProvider'] ?></td>
                                <td><?= (!empty($auth['authDetail'])) ? Tpl::out($auth['authDetail']):Tpl::out($auth['authId']) ?></td>
                                <td><?=Tpl::moment(Date::getDateTime($auth['createdDate']), Date::STRING_FORMAT_YEAR)?></td>
                                <td><?=Tpl::moment(Date::getDateTime($auth['modifiedDate']), Date::STRING_FORMAT_YEAR)?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </form>
    <?php endif ?>


</div>

<?php include 'seg/foot.php' ?>
<?php include 'seg/tracker.php' ?>
<script src="<?=Config::cdnv()?>/web.js"></script>
<script src="<?=Config::cdnv()?>/admin.js"></script>
<script>
    $('.btn-post').on('click', function(){
        var a = $(this), form = $(this).closest('form');
        form.attr("action", a.attr("href"));
        form.trigger('submit');
        return false;
    });
</script>

</body>
</html>