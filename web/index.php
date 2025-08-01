<?php
//generate api pass
$cfgPath = __DIR__.'/../cfg.php';
$cfgContent = file_get_contents($cfgPath);
$pass = generatePass(rand(20, 32));
$cfgContent = str_replace("'vpnApiPass' => ''", "'vpnApiPass' => '{$pass}'", trim($cfgContent));
file_put_contents($cfgPath, $cfgContent);

$cfg = require_once __DIR__.'/../cfg.php';

if(strpos($_SERVER['REQUEST_URI'], 'new')){
	
	shell_exec("chmod +x ".APP_DIR.'/new-client.sh');
	
	for($i=0; $i<10; $i++){
		$clientName = 'client'.rand(10000, 10000000);
		$clientDir = $cfg['clientsDir']."/{$clientName}";
		
		if(!file_exists($clientDir))
			break;
	}
	
	if($i == 10)
		die('error1');
	
	if(!mkdir($clientDir))
		die('error2');
	
	if(!$clientIp = generateClientIp($cfg))
		die('error clientIp');
	
	$command  = "{$cfg['addCommand']} {$clientName} {$clientDir} {$clientIp}";
	
	//debug
	//die($command);
	
	$response = shell_exec($command);
	
	if(strpos($response, '!success!')!==false){

		//downloadFile();
		//echo "success for {$clientName}<br>";
		$zipFile = $cfg['runtimeDir'].'/clientCfg.zip';

		if(!zipDir($clientDir, $zipFile))
			die('error3');
		
		downloadFile($zipFile);
		unlink($zipFile);
	}else
		die('error5');
	
	
}elseif(str_contains($_SERVER['REQUEST_URI'], '/removeClient') or str_contains($_SERVER['REQUEST_URI'], '/findClient')) {
	
	if(empty($_GET['id']))
		returnJson(['error'=>'no clientId']);
	
	if(empty($cfg['vpnApiPass']))
		returnJson(['error'=>'no vpnApiPass configured']);
	
	if(empty($_GET['pass']))
		returnJson(['error'=>'your pass is empty']);
	
	if($_GET['pass'] !== $cfg['vpnApiPass'])
		returnJson(['error'=>'wrong Pass']);
	
	$wgCfgContent = @file_get_contents(WG_CFG_PATH);
	
	if(!$wgCfgContent)
		returnJson(['error'=>'wg cfg not found']);
	
	$clientId = $_GET['id'];
	
	if(!str_contains($wgCfgContent, "### Client {$clientId}"))
		returnJson(['error'=>'client not found']);
	
	if(str_contains($_SERVER['REQUEST_URI'], '/findClient'))
		returnJson(['result'=>'OK']);
	
	$wgContent = preg_replace('!### Client '.$clientId.'\s+\[Peer\]\s+PublicKey \= .+?PresharedKey \= .+?AllowedIPs = [\d\.\,/:\w]+!is', '', $wgCfgContent);
	
	if(str_contains($wgContent, '### Client '.$clientId))
		returnJson(['error'=>'client not removed']);
	
	if(file_put_contents(WG_CFG_PATH, $wgContent) === false)
		returnJson(['error'=>'error cfg file save']);
	
	$execResult = shell_exec("wg-quick down wg0;wg-quick up wg0 2>&1");
	
	if(!str_contains($execResult, 'ip link add wg0 type wireguard'))
		returnJson(['error'=>'error vpn restart'.$execResult]);
	
	returnJson(['result'=>'OK']);
	
}else{
	die('');
}

function returnJson(array $data){
	header('Content-Type: application/json');
	echo json_encode($data);
	die;
}


function downloadFile($filePath){
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=' . basename($filePath));
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($filePath));
	readfile($filePath);
}

function zipDir($dir, $zipFilePath){
	$rootPath = realpath($dir);
	
	$zip = new ZipArchive();
	
	$zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
	
	
	/** @var SplFileInfo[] $files */
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($rootPath),
		RecursiveIteratorIterator::LEAVES_ONLY
	);
	
	foreach ($files as $name => $file)
	{
		// Skip directories (they would be added automatically)
		if (!$file->isDir())
		{
			// Get real and relative path for current file
			$filePath = $file->getRealPath();
			$relativePath = substr($filePath, strlen($rootPath) + 1);
			
			// Add current file to archive
			$zip->addFile($filePath, $relativePath);
		}
	}

	// Zip archive will be created only after closing object
	$zip->close();
	
	return file_exists($zipFilePath);
}

function generateClientIp($cfg){
	if(!$cfg['wgConfigFile'] or !file_exists($cfg['wgConfigFile'])){
		echo "no wg cfg file\n";
		return null;
	}
	
	if(!$content = file_get_contents($cfg['wgConfigFile'])){
		echo "empty wg cfg file\n";
		return null;
	}
	
	if(!preg_match('!\[Interface\]\s+Address \= (\d+)\.(\d+)\.(\d+)\.(\d+)/(\d+)!is', $content, $matches)){
		echo "cant find interface ip\n";
		return null;
	}
	
	$clientIp = '';
	
	if($matches[5] == '24'){
		for($sub24 = 2; $sub24 <= 254; $sub24++){
			$clientIp = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$sub24}";
			
			if(!str_contains($content, "AllowedIPs = {$clientIp}/32"))
				break;
		}
	}elseif($matches[5] == '23'){
		//10.66.66.1/23
		//10.66.66.2-254 - 10.66.67.2-254
		for($sub23 = $matches[3]; $sub23 <= intval($matches[3])+1; $sub23++){
			for($sub24 = 2; $sub24 <= 254; $sub24++){
				$clientIp = "{$matches[1]}.{$matches[2]}.{$sub23}.{$sub24}";
				
				if(!str_contains($content, "AllowedIPs = {$clientIp}/32"))
					break 2;
			}
		}
	}else{
		echo "error subnet parse\n";
		return null;
	}
	
	if(!$clientIp){
		echo "client ip not generated\n";
		return null;
	}
	
	return $clientIp;
}

function generatePass($length = 12) {
	$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$charactersLength = strlen($characters);
	$password = '';
	
	for ($i = 0; $i < $length; $i++) {
		$randomIndex = random_int(0, $charactersLength - 1);
		$password .= $characters[$randomIndex];
	}
	
	return $password;
}