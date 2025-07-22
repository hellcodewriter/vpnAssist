<?php
set_time_limit(0);
date_default_timezone_set("Europe/Moscow");
error_reporting(E_ALL);
const APP_DIR = __DIR__;
const WG_CFG_PATH = '/etc/wireguard/wg0.conf';

return [
	'host'=>'0.0.0.0',
	'port' => '777',
	'addCommand' => __DIR__.'/new-client.sh',
	'clientsDir' => __DIR__.'/clients',
	'runtimeDir' => __DIR__.'/web/runtime',
	'wgConfigFile' => '/etc/wireguard/wg0.conf',
	'vpnApiPass' => ''
];


