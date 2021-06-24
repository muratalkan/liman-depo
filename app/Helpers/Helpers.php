<?php

use Liman\Toolkit\Validator;
use App\ConfigManager\Config;
use App\DataManager\Data;
use Liman\Toolkit\Formatter;
use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;
use App\Classes\Mirror;
use App\Helpers\RepositoryCollection;
use App\Helpers\File;

if (!function_exists('validate')) {
	function validate($rules)
	{
		$validator = (new Validator())->make(request(), $rules);
		if ($validator->fails()) {
			$errors = $validator->errors();
			abort($errors->first(), 400);
		}
	}
}

if (!function_exists('checkPort')) {
	function checkPort($ip, $port)
	{
		restoreHandler();
		if ($port == -1) {
			return true;
		}
		$fp = @fsockopen($ip, $port, $errno, $errstr, 0.1);
		setHandler();
		if (!$fp) {
			return false;
		} else {
			fclose($fp);
			return true;
		}
	}
}

function updateAptMirrorConfig($data){
	
	Command::bindDefaultEngine();
	$config = new Config();
	$config
		->folder(Mirror::getConfigsFolderPath())
		->file($data['mirror_name'])
		->template('aptMirror_conf')
		->data(
			array_merge(
				[
					'mirror' => $data
				],
			)
		)
		->write(true);
}

function readRepos(){
	$repos = Data::instance()
		->file(Mirror::getJsonFile())
		->read(true);
	return $repos;
}

function writeRepos($repos){
	Data::instance()
		->file(Mirror::getJsonFile())
		->write($repos, true);
}

function getAptMirrorStatus($mirrorName){
	return (bool) Command::runSudo(
		"ps aux | grep apt-mirror | grep '{:mirrorName}$' | grep -v grep > /dev/null && echo 1 || echo 0",
		[
			'mirrorName' => $mirrorName
		]
	);
}

function getIpAddress(){
	return Command::runSudo('hostname -I');;
}

function isNameValid($str){ //ignore any Turkish characters and special chars.
	if (!preg_match('/^[a-zA-Z0-9-.]+$/', $str)) {
		return false;
	}
	return true;
}

function removeSpecialChar($str) {
    return str_replace( array( '\'', '"', ',', '/', ';', '<', '>' ), '', $str);
}

function parseAddress($addr){
	$address = trim($addr);
	$parts = explode(' ',basename($address));
	$external_repo_name = $parts[0]; //debian
	$code_name = $parts[1]; //buster
	$packages = implode(" ", array_slice($parts,2)); //main contrib non-free testing

	preg_match('/http:\/\/(\S+)/', $address, $matches);
	$external_repo_url = str_replace(implode(' ',$parts),"",  "$matches[0] $code_name $packages"); //http://ftp.debian.org/
	$external_repo_urlPath = $matches[1]; //ftp.debian.org/buster
	$deb_type = trim(str_replace("$matches[0] $code_name $packages","", $address)); //deb

	return [
		'ext_repo_url' => $external_repo_url,
		'ext_repo_urlPath' => $external_repo_urlPath,
		'ext_repo_name' => $external_repo_name,
		'deb_type' => ($deb_type[0] == '#') ? ltrim($deb_type, '#') : $deb_type,
		'code_name' => $code_name,
		'packages' => $packages
	];
}

function writeToAptMirrorLog($type, $description){
	 Command::runSudo(
		"sh -c \"echo $(date '+%d-%m-%Y %H:%M:%S') \| local user = $(whoami), liman user = {:limanUser} - {:type} - {:description} | tee -a @{:summaryLogFile}\"",
		[
			'limanUser' => $limanData['user']['name'],
			'type' => $type,
			'description' => $description,
			'summaryLogFile' => Mirror::getSummaryLogFile()
		]
	);
}

function changeText($oldValue, $newValue, $path){
	Command::runSudo("sed -i 's/{:oldValue}/{:newValue}/g' @{:path}",[
		'oldValue' => $oldValue,
		'newValue' => $newValue,
		'path' => $path
	]);
}

function changeLine($line, $value, $path){
	Command::runSudo('sed -i "/{:line}/c {:line} {:value}" @{:path}',[
		'line' => $line,
		"value" => $value,
		'path' => $path
	]);
}

function pathSize($path){
	return Command::runSudo("du -sh @{:path} | awk '{print $1}'", [
		'path' => $path
	]);
}

function diskInformation($path)
{ 
	$diskOutput = Command::runSudo(
		"df -hT @{:path} | tail -n +2",
		[
			'path' => $path
		]
	);

	//Filesystem Type Size Used Avail Use% Mounted on
	$diskInfo = preg_split('/[\s]+/', $diskOutput);
	$diskArr = [
		'Filesystem' => (!empty($diskInfo[0])) ? $diskInfo[0] : "null",
		'Type' => $diskInfo[1],
		'Size' => $diskInfo[2],
		'Used' => $diskInfo[3],
		'Available' => $diskInfo[4],
		'UsedPercentage' => $diskInfo[5],
		'MountedOn' => $diskInfo[6]
	];

	return $diskArr;
}

function updateCronMirrorName($oldMirrorName, $mirrorName){
	Command::runSudo(
		"sed -i 's/@{:oldMirrorName}/@{:mirrorName}/g' @{:cronMirrorFile}",
		[
			'oldMirrorName' => $oldMirrorName,
			'mirrorName' => $mirrorName,
			'cronMirrorFile' => Mirror::getCronFile()
		]
	);
}

function removeCron($mirrorName){
	$lineNumber = Command::runSudo(
		"cat -n @{:cronMirrorFile} | grep -v '^#' | egrep \"@{:path}\s+\" | awk {'print $1'}",
		[
			'cronMirrorFile' => Mirror::getCronFile(),
			'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName])
		]
	);

	Command::runSudo(
		"sh -c \"sed -i '{:lineNumber} d' @{:cronMirrorFile}\"",
		[
			'lineNumber' => $lineNumber,
			'cronMirrorFile' => Mirror::getCronFile()
		]
	);
}

function checkMirrorNameExists($mirrorName, $repos){
	$checkMirrorExists1 = File::instance()
				->path(Mirror::getConfigsFolderPath().'/'.$mirrorName)
				->checkFileExists();

	$checkMirrorExists2 = false;
	foreach ($repos as $repo) {
		foreach($repo->mirror_lists as $mirrorList){
			if($mirrorList->mirror_name == $mirrorName){
				$checkMirrorExists2 = true;
				break;
			}
		}
	}

	if ($checkMirrorExists1 || $checkMirrorExists2) { //must be unique
		return true;//exists
	}
	return false;
}

function deleteInactiveSymbolicLink($ext_repo, $link_base){
	$checkActiveStatus = false;
	foreach($ext_repo->versions as $version){
		if($version->isActive == 'true'){
			$checkActiveStatus = !$checkActiveStatus;
			break;
		}
	}

	//delete link if addresses in this repo are not active
	if(!$checkActiveStatus){
		File::instance()
				->path($link_base.'/'.$ext_repo->link_name)
				->removeFile();
		$ext_repo->link_name = "";
	}
}

function addMirrorAddressToFile($mirrorName, $address, $url){
	$checkCleanLine = (bool) Command::runSudo(
		"cat @{:path} | egrep 'clean\s+http://{:url}$' 2>/dev/null 1>/dev/null && echo 1 || echo 0",
		[
			'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
			'url' => $url
		]
	);

	if ($checkCleanLine) {
		$lineNumber = Command::runSudo(
			"cat -n @{:path} | egrep 'clean\s+' | head -1 | awk '{print $1}'",
			[
				'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName])
			]
		);
		Command::runSudo(
			"sed -i '{:lineNumber}i {:address}' @{:path}",
			[
				'lineNumber' => $lineNumber,
				'address' => $address,
				'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName])
			]
		);
	} else {
		$item = "
$address
clean http://$url
";
		Command::runSudo(
			"bash -c \"echo @{:item} | base64 -d | tee -a @{:path}\"",
			[
				'item' => base64_encode($item),
				'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName])
			]
		);
	}
}