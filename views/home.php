<?php
namespace Destiny;
use Destiny\Common\Utils\Tpl;
use Destiny\Common\Config;
?>
<!DOCTYPE html>
<html>
<head>
<title><?=Tpl::title($this->title)?></title>
<meta name="description" content="<?=Config::$a['meta']['description']?>">
<meta name="keywords" content="<?=Config::$a['meta']['keywords']?>">
<meta name="author" content="<?=Config::$a['meta']['author']?>">
<?php include 'seg/meta.php' ?>
<link href="<?=Config::cdnv()?>/web.css" rel="stylesheet" media="screen">
</head>
<body id="home" class="no-brand">
    <div id="page-wrap">
        <?php include 'seg/nav.php' ?>
        <?php include 'seg/banner.php' ?>
        <?php include 'seg/panel.reddit.php' ?>
        <?php include 'seg/panel.videos.php' ?>
        <?php include 'seg/panel.music.php' ?>
        <?php include 'seg/panel.ads.php' ?>
    </div>
    <?php include 'seg/foot.php' ?>
    <?php include 'seg/tracker.php' ?>
    <script src="<?=Config::cdnv()?>/web.js"></script>
</body>
</html>