<?php

include "./classes/nette.min.php";

$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory('classes');
$loader->register();

use \Nette\Templating\Template;

// zobrazim seznam se jmeny adresaru

// u kazdeho adresare budou tlacitka - rozbalit, stahnout jako zip, zobrazit velikost - vse ajax



$template = new Template;
$template->directories = FileSystem::directoryList('/');
$template->setSource('<!doctype html>
<html lang="cs" dir="ltr">
<head>
<meta charset="UTF-8">
<title></title>
</head>
<body>
{foreach $directories as $directory}{$directory}<br>{/foreach}
</body>
</html>

');
$template->render();
