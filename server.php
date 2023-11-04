<?php
$cfg = require_once __DIR__.'/cfg.php';

$connection = @fsockopen($cfg['host'], $cfg['port'], $errno, $errstr, 3);

if (is_resource($connection)) {
	echo "\nthis port is already in use";
	die;
}

shell_exec("php -S 0.0.0.0:777 -t ".__DIR__.'/web/');
