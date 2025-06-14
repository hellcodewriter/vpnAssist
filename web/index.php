<?php
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
	
	$response = shell_exec("{$cfg['addCommand']} {$clientName} {$clientDir}");
	
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
	
	
}else{
	die('');
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

