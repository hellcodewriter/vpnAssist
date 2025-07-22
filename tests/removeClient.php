<?php
$clientId = $argv[1];

$wgPath = 'wg0.conf';
$wgContent = @file_get_contents($wgPath);

if(!str_contains($wgContent, '### Client '.$clientId)){
	echo "\n client not found";
	die;
}

$wgContent = preg_replace('!### Client '.$clientId.'\s+\[Peer\]\s+PublicKey \= .+?PresharedKey \= .+?AllowedIPs = [\d\.\,/:\w]+!is', '', $wgContent);

if(str_contains($wgContent, '### Client '.$clientId)){
	echo "\n remove error";
	
	die($wgContent);
}

if(!@file_put_contents($wgPath, $wgContent)){
	echo "\n file save error";
	die;
}

die('OK');