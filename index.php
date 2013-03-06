<?php

include "./classes/nette.min.php";
include "./classes/CreateZipFile.php";
include "./classes/CreateZipDirectory.php";

include "./classes/FileSystem.php";
include "./classes/Ftp.php";
include "./templates.php";

//\Nette\Caching::setCacheStorage(\Nette\Caching\Storages\DevNullStorage);

/*$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory('classes');
$loader->register();*/

\Nette\Diagnostics\Debugger::enable();





// zobrazim seznam se jmeny adresaru

// u kazdeho adresare budou tlacitka - rozbalit, stahnout jako zip, zobrazit velikost - vse ajax

if (!isset($_GET['action'])) {
    $_GET['action'] = 'homepage';
}

$template = new \Skoumal\Template();
// zde rozhodnu co se bude dit

switch ($_GET['action']) {
    case 'detail': $template->detail($_GET['directory']); break;
    case 'download': $template->download($_GET['directory']); break;
    default: $template->homepage();
}

$template->render();




