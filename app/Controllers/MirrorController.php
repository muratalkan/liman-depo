<?php

namespace App\Controllers;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;
use Liman\Toolkit\Validator;
use Liman\Toolkit\Formatter;
use App\DataManager\Data;
use App\Classes\Mirror;
use App\Helpers\RepositoryCollection;
use App\Helpers\File;
use Illuminate\Support\Str;

class MirrorController
{

	function get(){
		$mirrorArray = [];
		$repos = readRepos();

		foreach ($repos as $repo) {
			$path = $repo->storage_path;
			foreach($repo->mirror_lists as $mirrorList){
				$value = $mirrorList->mirror_name;
				$description = $mirrorList->mirror_description;
				$set_nthreads = $mirrorList->config_values->set_nthreads;
				$set_tilde = $mirrorList->config_values->set_tilde;
				$lastRun = Command::runSudo(
					"cat @{:summaryLogFile} | grep '{:mirrorName} repository' | grep Start | tail -1 | cut -d'|' -f1",
					[
						'summaryLogFile' => Mirror::getSummaryLogFile(),
						'mirrorName' => $value
					]
				);
				$lastRun == '' ? ($lastRun = '-') : '';
				$cronLine = Command::runSudo(
					"cat @{:cronMirrorFile} | grep -v '^#' | egrep \"@{:path}\s+\"",
					[
						'cronMirrorFile' => Mirror::getCronFile(),
						'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $value])
					]
				);
	
				if ($cronLine == '') {
					$cron = '-';
				} else {
					$cronParts = explode(' ', $cronLine);
					$cron = "$cronParts[0] $cronParts[1] $cronParts[2] $cronParts[3] $cronParts[4]";
				}
	
				$mirrorArray[] = [
					'name' => $value,
					'oldName' => $value,
					'storagePath' => $path,
					'oldStoragePath' => $path ,
					'description' => $description,
					'oldDescription' => $description,
					'cron' => $cron,
					'lastRun' => $lastRun,
					'status' => checkMirrorStatus($value),
					'operation' => checkMirrorStatus($value),
					'set_nthreads' => $set_nthreads,
					'old_set_nthreads' => $set_nthreads,
					'set_tilde' => $set_tilde,
					'old_set_tilde' => $set_tilde
				];
			}

		}

		return respond([
			'array' => $mirrorArray,
			'table' => view('components.mirror_list-table', [
				'mirrorArray' => $mirrorArray
			])
		]);
	}

	function getLinksAndPaths(){
		$mirrorName = request('mirrorName');
		$path = request('storagePath');
		
		$repos = readRepos();
		$storage = RepositoryCollection::instance()
					->repos($repos)
					->getStorageFromJson($path);
		$mirrorList = RepositoryCollection::instance()
					->repos($repos)
					->getMirrorListFromJson($path, $mirrorName);

		$linkAndPathArr = [];
		$ipaddress = getIpAddress();
		foreach ($mirrorList->external_repo_urls as $url) {
			foreach ($url->external_repos as $ext_repo) {
				if($ext_repo->link_name != ""){
					$downloadPath = $storage->storage_path.$mirrorList->mirror_path.$ext_repo->download_path;
					$linkAndPathArr[] = [
						'name' => $mirrorName,
						'storagePath' => $path,
						'extUrl' => $url->external_repo_url,
						'extRepoName' => $ext_repo->external_repo_name,
						'oldLinkName' => $ext_repo->link_name,
						'link' => "http://$ipaddress/$ext_repo->link_name",
						'downloadPath' => $downloadPath,
						'downloadSize' => (pathSize($downloadPath) !== "") ? pathSize($downloadPath) : "-",
						'checkLink' => File::instance()->path($mirrorList->link_base.'/'.$ext_repo->link_name)->checkSymbolicLinkIsBroken(),
						'checkDownload' => 	File::instance()->path($downloadPath)->checkDirectoryExists()
					];
				}
			}
		}

		return view('components.mirror_list-table', [
			'linkAndPathArr' => $linkAndPathArr
		]);
	}

	function getAddress(){
		$mirrorName = request('mirrorName');
		$path = request('storagePath');

		$repos = readRepos();
		$mirrorList = RepositoryCollection::instance()
						->repos($repos)
						->getMirrorListFromJson($path, $mirrorName);

		$mirrorAddressArr = [];
		foreach ($mirrorList->external_repo_urls as $url) {
			foreach ($url->external_repos as $ext_repo) {
				foreach($ext_repo->versions as $vers){
					$addr = "$vers->deb_type $url->external_repo_url$ext_repo->external_repo_name $vers->code_name $vers->packages";
					$activeState =  $vers->isActive;
					$address = ($activeState == 'true') ? $addr : "#$addr";
					$newAddress = str_replace('#', '', $address);
					$link = $ext_repo->link_name;
					$mirrorAddressArr[] = [
						'mirrorName' => $mirrorName,
						'storagePath' => $path,
						'activeState' => $activeState,
						'oldActiveState' => $activeState,
						'activeStateTxt' => $activeState,
						'address' => $newAddress,
						'oldAddress' => $address,
						'link' => $link,
						'oldLink' => $link,
						'linkTxt' => (!empty($link) && $activeState == 'true') ? $link : "-"
					];
				}
			}
		}

		return view('components.mirror_list-table', [
			'mirrorAddressArr' => $mirrorAddressArr
		]);
	}

	function getSourcesList(){
		$mirrorName = request('mirrorName');
		$path = request('storagePath');

		$repos = readRepos();
		$mirrorList = RepositoryCollection::instance()
						->repos($repos)
						->getMirrorListFromJson($path, $mirrorName);

		$sourcesListArr = [];
		$ipaddress = getIpAddress();
		foreach ($mirrorList->external_repo_urls as $url) {
			foreach ($url->external_repos as $ext_repo) {
				foreach($ext_repo->versions as $vers){
					$sourcesListArr[] = [
						'sourceName' => "$vers->deb_type http://$ipaddress/$ext_repo->link_name $vers->code_name $vers->packages",
					];
				}
			}
		}

		return respond($sourcesListArr);
	}

	function add(){
		$mirrorName = removeSpecialChar(trim(request('mirrorName')));
		$description = (!empty(trim(request('description')))) ? trim(request('description')) : "-";
		$path = rtrim(trim(request('path')), '/');

		$path = Str::start($path, '/');
		
		$repos = readRepos();
		if (checkMirrorNameExists($mirrorName, $repos)) { //must be unique
			return respond(__('Böyle bir aynalama zaten var!'), 201);
		}

		$item2 = [
			'mirror_name' => $mirrorName,
			'base_path' => "/$mirrorName",
			'mirror_path' => "/$mirrorName/mirror",
			'mirror_description' => $description,
			'link_base' => Mirror::getSymbolicLinkPath(),
			'config_values' => [
				'set_nthreads' => Mirror::getDefaultConfigValues()['nthreads'],
				'set_tilde' => Mirror::getDefaultConfigValues()['tilde']
			],
			'external_repo_urls' => []
		];

		$item1 = [
			'storage_path' => $path,
			'mirror_lists' => array($item2)
		];

		$storage = RepositoryCollection::instance()
					->repos($repos)
					->getStorageFromJson($path);

		if($storage == null){
			$repos->push($item1);
			$repos = $repos->unique();
		}else{
			$storage->mirror_lists[] = $item2;
		}
		writeRepos($repos);
	

		File::instance()
			->path($path.'/'.$mirrorName)
			->createDirectory();

		updateAptMirrorConfig(
			[
				'path' => $path,
				'mirror_name' => $mirrorName
			]
		);
	
		return respond(__('Aynalama eklendi'), 200);
	}

	function delete(){
		$mirrorName = request('name');
		$path = request('storagePath');

		$this->checkMirroring();

		File::instance()
			->path(Mirror::getConfigsFolderPath().'/'.$mirrorName)
			->removeDirectory();
		File::instance()
			->path("$path/$mirrorName")
			->removeEmptyDirectory();

		removeCron($mirrorName);

		$repos = readRepos();
		$mirrorList = RepositoryCollection::instance()
					->repos($repos)
					->getMirrorListFromJson($path, $mirrorName);

		//delete links
		foreach ($mirrorList->external_repo_urls as $ext_url) {
			foreach ($ext_url->external_repos as $ext_repo) {
				if($ext_repo->link_name != ""){
					File::instance()
							->path($mirrorList->link_base.'/'.$ext_repo->link_name)
							->removeFile();
				}
			}
		}

		$storage = RepositoryCollection::instance()
						->repos($repos)
						->getStorageFromJson($path);
	
		if(count($storage->mirror_lists) == 1){
			File::instance()
				->path($path)
				->removeEmptyDirectory();
			$repos = $repos->reject(function ($item, $key) use($path){
				return $item->storage_path == $path;
			});
		}else{
			for($i=0; $i<count($storage->mirror_lists); $i++){
				if($storage->mirror_lists[$i]->mirror_name == $mirrorName){
					array_splice($storage->mirror_lists, $i, 1);
					break;
				}
			}
		}
		writeRepos($repos);

		return respond(__("Aynalama silindi"), 200);
	}

	function edit(){
		$mirrorName = removeSpecialChar(trim(request('name')));
		$oldMirrorName = request('oldName');
		$path = request('storagePath');
		$description = (!empty(trim(request('description')))) ? trim(request('description')) : "-";
		$oldDescription = request('oldDescription');
		
		///configs
		$set_nthreads = request('set_nthreads');
		$old_set_nthreads = request('old_set_nthreads');
		$set_tilde = request('set_tilde');
		$old_set_tilde = request('old_set_tilde');
		

		$this->checkMirroring();

		$repos = readRepos();
		$mirrorList = RepositoryCollection::instance()
					->repos($repos)
					->getMirrorListFromJson($path, $oldMirrorName);

		if($mirrorName != $oldMirrorName){
			if (checkMirrorNameExists($mirrorName, $repos)) { //must be unique
				return respond(__('Böyle bir aynalama zaten var!'), 201);
			}

			File::instance()
					->path(join(DIRECTORY_SEPARATOR, [$path, $oldMirrorName]))
					->move(join(DIRECTORY_SEPARATOR, [$path, $mirrorName])); //rename folder
			File::instance()
					->path(join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $oldMirrorName]))
					->move(join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName])); //rename mirror config file

			$mirrorList->mirror_name = $mirrorName;
			$mirrorList->base_path = "/$mirrorName";
			$mirrorList->mirror_path = "/$mirrorName/mirror";

			changeLine('set base_path', '"'.join(DIRECTORY_SEPARATOR, [$path, $mirrorName]).'"', join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]));
			changeText($oldMirrorName.' repository', $mirrorName. ' repository', Mirror::getSummaryLogFile());
			changeText($oldMirrorName, $mirrorName, Mirror::getCronFile());
			updateCronMirrorName($oldMirrorName, $mirrorName);
		}
		
		if($description != $oldDescription){
			$mirrorList->mirror_description = $description; //change description
		}
		if($set_nthreads != $old_set_nthreads){
			$mirrorList->config_values->set_nthreads = $set_nthreads;
			changeLine('set nthreads', $set_nthreads, join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]));
		}
		if($set_tilde != $old_set_tilde){
			$mirrorList->config_values->set_tilde = $set_tilde;
			changeLine('set _tilde', $set_tilde, join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]));
		}

		writeRepos($repos);
		return respond(__("Aynalama güncellendi"), 200);
	}

	function move(){
		$mirrorName = request('mirrorName');
		$oldPath = request('oldPath');
		$newPath = rtrim(trim(request('newPath')), '/');

		if($newPath != $oldPath){
			$this->checkMirroring();

			File::instance()
					->path(join(DIRECTORY_SEPARATOR, [$OLDPath, $mirrorName]))
					->removeEmptyDirectory();

			$repos = readRepos();
			$storage = RepositoryCollection::instance()
					->repos($repos)
					->getStorageFromJson($newPath);
			$mirrorList = RepositoryCollection::instance()
					->repos($repos)
					->getMirrorListFromJson($oldPath, $mirrorName);
					
			if($storage == null){ //new storage
				$item = [
					'storage_path' => $newPath,
					'mirror_lists' => array($mirrorList)
				];
				$repos->push($item);
				//$repos = $repos->unique();
			}else{ //existing storage
				$storage->mirror_lists[] = $mirrorList;
			}

			//remove the mirrorList from oldPath
			$oldStorage = RepositoryCollection::instance()
						->repos($repos)
						->getStorageFromJson($oldPath);
			if(count($oldStorage->mirror_lists) == 1){
				$repos = $repos->reject(function ($item, $key) use($oldPath){
					return $item->storage_path == $oldPath;
				});
			}else{
				for($i=0; $i<count($oldStorage->mirror_lists); $i++){
					if($oldStorage->mirror_lists[$i]->mirror_name == $mirrorName){
						array_splice($oldStorage->mirror_lists, $i, 1);
						break;
					}
				}
			}
			writeRepos($repos);
			
			File::instance()
					->path(join(DIRECTORY_SEPARATOR, [$newPath, $mirrorName]))
					->createDirectory();

			changeLine('set base_path', '"'.join(DIRECTORY_SEPARATOR, [$newPath, $mirrorName]).'"', join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]));
			writeToAptMirrorLog('Move', "storage from '$oldPath/$mirrorName' to '$newPath/$mirrorName'");
			/*
				Since moving large folders take too much time. For now, this function will not move the folder .
			*/
			
			return respond(__("Deponun konumu güncellendi"), 200);
		}
		
		return respond(__("Depo zaten belirtilen konumda!"), 201);
	}

	function addAddress(){
		validate([
			'address' => 'required|string'
		]);

		$mirrorName = request('mirrorName');
		$path = request('storagePath');
		$activeState = request('activeState');
		$address = trim(request('address'));
		$link = removeSpecialChar(trim(request('link')));


		if(checkMirrorStatus($mirrorName) && $activeState == 'true'){
			return respond(__("Devam eden bir aynalama bulunmakta. Aynalamayı durdurduktan sonra işlem yapabilirsiniz."), 201);
		}

		if(substr($address, 0, 3) !== 'deb'){
			return respond(__('Depo adresi \'deb\' ile başlamalıdır!'), 201);
		}

		if($activeState=='false'){
			$address = "#$address";
			$link="";
		}

		$parsedAddress = parseAddress($address);
			$external_repo_url = $parsedAddress['ext_repo_url']; //http://ftp.debian.org/
			$ext_repo_urlPath = $parsedAddress['ext_repo_urlPath']; //ftp.debian.org/debian
			$external_repo_name = $parsedAddress['ext_repo_name']; //debian
			$deb_type = $parsedAddress['deb_type']; //deb
			$code_name = $parsedAddress['code_name']; //buster
			$packages = $parsedAddress['packages']; //main contrib non-free testing

		if (filter_var($external_repo_url, FILTER_VALIDATE_URL) === FALSE) {
			return respond("Depo adresi geçerli değil!", 201);
		}

		$repos = readRepos();
		$storage = RepositoryCollection::instance()
					->repos($repos)
					->getStorageFromJson($path);
		$mirrorList = RepositoryCollection::instance()
					->repos($repos)
					->getMirrorListFromJson($path, $mirrorName);
		$ext_url = RepositoryCollection::getRepoUrlFromJson($mirrorList, $external_repo_url);
		$ext_repo = null;
		if($ext_url != null){
			$ext_repo = RepositoryCollection::getRepoFromJson($ext_url, $external_repo_name);
		}

		$debAddress = "$deb_type $external_repo_url$external_repo_name $code_name"; //deb http://ftp.debian.org/debian buster
		$checkAddressStatus = (bool) Command::runSudo(
			"cat @{:path} | egrep '{:debAddress}' 2>/dev/null 1>/dev/null && echo 1 || echo 0",
			[
				'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
				'debAddress' => $debAddress
			]
		);
	
		if ($checkAddressStatus) {
			return respond(__('Bu adrese benzer bir adres bulunmaktadır!'), 201);
		}
	
		if($activeState == "true"){
			if($ext_repo != null && $link != "" && $ext_repo->link_name != ""){ //it means there is already link for that repo e.g debian
				return respond(__('Bu depoya ait sembolik link zaten var. Lütfen sembolik link alanını boş bırakınız.'), 201);
			}
			if($ext_repo == null || $ext_repo->link_name == ""){
				validate([
					'link' => 'required|string'
				]);
				$checkLink = File::instance()
							->path($mirrorList->link_base.'/'.$link)
							->checkLinkExists();
				if ($checkLink) {
					return respond(__('Bu sembolik link zaten var!'), 201);
				}else{
					if($ext_repo != null && $ext_repo->link_name == ""){
						$ext_repo->link_name = $link;
					}
					$downloadPath = $storage->storage_path.$mirrorList->mirror_path.'/'.$ext_repo_urlPath;
					File::instance()
						->path($downloadPath)
						->createDirectory();
					File::instance()
						->path($downloadPath)
						->createSymbolicLink($mirrorList->link_base.'/'.$link);
				}
			}
		}

		$item3 = [
			'deb_type' => $deb_type,
			'code_name' => $code_name,
			'packages' => $packages,
			'isActive' => $activeState,
		];
		$item2 = [
			'external_repo_name' => $external_repo_name,
			'download_path' => "/$ext_repo_urlPath",
			'link_name' => $link,
			'versions' => array($item3)
		];
		$item1 =[
			'external_repo_url' => $external_repo_url,
			'external_repos' => array($item2)
		];

		if($ext_url == null){
			$mirrorList->external_repo_urls[] = $item1; //add external url and its repo	
		}else{
			if($ext_repo == null){
				$ext_url->external_repos[] = $item2; //add only external repo info
			}else{
				$ext_repo->versions[] = $item3; //add only version info
			}
		}
		writeRepos($repos);

		addMirrorAddressToFile($mirrorName, $address, $ext_repo_urlPath);
		return respond(__("Adres eklendi"), 200);
	}

	function deleteAddress(){
		$mirrorName = request('mirrorName');
		$path = request('storagePath');
		$address = request('address');
		$link = request('link');
		$activeState = request('activeState');


		if(checkMirrorStatus($mirrorName) && $activeState == 'true'){
			return respond(__("Devam eden bir aynalama bulunmakta. Aynalamayı durdurduktan sonra işlem yapabilirsiniz."), 201);
		}

		$parsedAddress = parseAddress($address);
			$external_repo_url = $parsedAddress['ext_repo_url']; //http://ftp.debian.org/
			$ext_repo_urlPath = $parsedAddress['ext_repo_urlPath']; //ftp.debian.org/debian
			$external_repo_name = $parsedAddress['ext_repo_name']; //debian
			$deb_type = $parsedAddress['deb_type']; //deb
			$code_name = $parsedAddress['code_name']; //buster
			$packages = $parsedAddress['packages']; //main contrib non-free testing

		$lineNumber = Command::runSudo("cat -n @{:path} | egrep '{:address}' | head -1 | awk '{print $1}'",
			[
				'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
				'address' => $address
			]
		);

		Command::runSudo("sh -c \"sed -i '{:lineNumber} d' @{:path}\"",
			[
				'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
				'lineNumber' => $lineNumber
			]
		);

		$checkAddress = (bool) Command::runSudo("cat @{:path} | egrep 'deb\s+{:url}'  2>/dev/null 1>/dev/null && echo 1 || echo 0",
			[
				'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
				'url' => $external_repo_url.$external_repo_name
			]
		);

		if (!$checkAddress) {
			$cleanLineNumber = Command::runSudo("cat -n @{:path} | egrep 'clean\s+{:url}' | head -1 | awk '{print $1}'",
				[
					'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
					'url' => $external_repo_url.$external_repo_name
				]
			);
			if ($cleanLineNumber != '') {
				Command::runSudo("sh -c \"sed -i '{:cleanLineNumber} d' @{:path}\"",
					[
						'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
						'cleanLineNumber' => $cleanLineNumber
					]
				);
			}
		}

		$repos = readRepos();
		$storage = RepositoryCollection::instance()
					->repos($repos)
					->getStorageFromJson($path);
		$mirrorList = RepositoryCollection::instance()
					->repos($repos)
					->getMirrorListFromJson($path, $mirrorName);
		$ext_url = RepositoryCollection::getRepoUrlFromJson($mirrorList, $external_repo_url);
		$ext_repo = RepositoryCollection::getRepoFromJson($ext_url, $external_repo_name);

		for($i=0; $i<count($ext_repo->versions); $i++){
			if($ext_repo->versions[$i]->code_name == $code_name && $ext_repo->versions[$i]->deb_type == $deb_type){
				array_splice($ext_repo->versions, $i, 1);
				break;
			}
		}
		if($ext_repo->versions == null){
			for($i=0; $i<count($ext_url->external_repos); $i++){
				if($ext_url->external_repos[$i]->external_repo_name == $external_repo_name){
					File::instance()
							->path($mirrorList->link_base.'/'.$ext_url->external_repos[$i]->link_name)
							->removeFile();
					File::instance()
							->path($storage->storage_path.'/'.$mirrorList->mirror_path.'/'.$ext_repo_urlPath)
							->removeEmptyDirectory();
					array_splice($ext_url->external_repos, $i, 1);
					break;
				}
			}
		}
		if($ext_url->external_repos == null){
			for($i=0; $i<count($mirrorList->external_repo_urls); $i++){
				if($mirrorList->external_repo_urls[$i]->external_repo_url == $external_repo_url){
					array_splice($mirrorList->external_repo_urls, $i, 1);
					break;
				}
			}
		}

		deleteInactiveSymbolicLink($ext_repo, $mirrorList->link_base);
		writeRepos($repos);
		return respond(__("Adres silindi"), 200);
	}

	function editAddress(){
		$activeState = request('activeState');
		$mirrorName = request('mirrorName');
		$path = request('storagePath');
		$address = trim(request('address'));
		$oldActiveState = request('oldActiveState');
		$oldAddress = request('oldAddress');
		$link = removeSpecialChar(trim(request('link')));
		$oldLink = request('oldLink');


		$this->checkMirroring();

		if(substr($address, 0, 3) !== 'deb'){
			return respond(__('Depo adresi \'deb\' ile başlamalıdır'), 201);
		}

		if($activeState == "true"){
			validate([
				'link' => 'required|string'
			]);
		}else{
			$link = "";
			$address = "#$address";
		}

		$parsedAddress = parseAddress($address);
			$external_repo_url = $parsedAddress['ext_repo_url']; //http://ftp.debian.org/
			$ext_repo_urlPath = $parsedAddress['ext_repo_urlPath']; //ftp.debian.org/debian
			$external_repo_name = $parsedAddress['ext_repo_name']; //debian
			$deb_type = $parsedAddress['deb_type']; //deb
			$code_name = $parsedAddress['code_name']; //buster
			$packages = $parsedAddress['packages']; //main contrib non-free testing

		if (filter_var($external_repo_url, FILTER_VALIDATE_URL) === FALSE) {
			return respond(__("Depo URL'si geçerli değil!"), 201);
		}

		$repos = readRepos();
		$storage = RepositoryCollection::instance()
					->repos($repos)
					->getStorageFromJson($path);
		$mirrorList = RepositoryCollection::instance()
					->repos($repos)
					->getMirrorListFromJson($path, $mirrorName);

		$ext_url = RepositoryCollection::getRepoUrlFromJson($mirrorList, $external_repo_url);
		$ext_repo = null;
		if($ext_url != null){
			$ext_repo = RepositoryCollection::getRepoFromJson($ext_url, $external_repo_name);
		}

		if($ext_repo == null){
			return respond(__("Depo URL'si ve depo adı düzenlenemez! Yeni bir depo eklemek için 'Adres Ekle' butonunu kullanabilirsiniz."), 201);
		}

		if($activeState == 'true'){
			if($link != $oldLink || $oldActiveState == 'false'){
				$downloadPath = $storage->storage_path.$mirrorList->mirror_path.'/'.$ext_repo_urlPath;
				$checkLink = File::instance()
							->path($mirrorList->link_base.'/'.$link)
							->checkLinkExists();
				if ($checkLink) {
					return respond(__('Bu sembolik link zaten var!'), 201);
				} 
				//rename symbolic link name
				$ext_repo->link_name = $link;
				File::instance()
					->path($mirrorList->link_base.'/'.$oldLink)
					->removeFile();
				File::instance()
					->path($downloadPath)
					->createDirectory();
				File::instance()
					->path($downloadPath)
					->createSymbolicLink($mirrorList->link_base.'/'.$link);
			}

			$ext_version = RepositoryCollection::getRepoVersionFromJson($ext_repo, $deb_type, $code_name);
			if($ext_version != null){
				$ext_version->isActive = $activeState;
			}
			
		}

		if ($address != $oldAddress) {
			$lineNumber = Command::runSudo("cat -n @{:path} | egrep '{:oldAddress}' | head -1 | awk '{print $1}'",
				[
					'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
					'oldAddress' => $oldAddress
				]
			);
			Command::runSudo("sh -c \"sed -i '{:lineNumber} d' @{:path}\"",
				[
					'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
					'lineNumber' => $lineNumber
				]
			);

			$oldParsedAddress = parseAddress($oldAddress);
			$ext_version = RepositoryCollection::getRepoVersionFromJson($ext_repo, $oldParsedAddress['deb_type'], $oldParsedAddress['code_name'], $oldParsedAddress['packages'], $oldActiveState);
				$ext_version->deb_type = $deb_type;
				$ext_version->code_name = $code_name;
				$ext_version->packages = $packages;
				$ext_version->isActive = $activeState;

			addMirrorAddressToFile($mirrorName, $address, $ext_repo_urlPath);
		}

		deleteInactiveSymbolicLink($ext_repo, $mirrorList->link_base);

		writeRepos($repos);
		return respond(__("Adres düzenlendi"), 200);
	}

	function createLink(){
		$mirrorName =  request('mirrorName');
		$path = request('storagePath');
		$external_repo_url = request('extUrl');
		$external_repo_name = request('extRepoName');
		$downloadPath = request('downloadPath');
		
		$repos = readRepos();
		$mirrorList = RepositoryCollection::instance()
					->repos($repos)
					->getMirrorListFromJson($path, $mirrorName);
		
		$link =  $mirrorList->link_base.'/'.request('linkName');
		$oldLink = $mirrorList->link_base.'/'.request('oldLinkName');

		File::instance()
				->path($oldLink)
				->removeFile();

		$checkLinkExists = File::instance()
						->path($link)
						->checkLinkExists();

		if($checkLinkExists){
			return respond(__('Bu sembolik link zaten var'), 201);
		}else{
			File::instance()
					->path($downloadPath)
					->createSymbolicLink($link);
			
			$ext_url = RepositoryCollection::getRepoUrlFromJson($mirrorList, $external_repo_url);
			$ext_repo = RepositoryCollection::getRepoFromJson($ext_url, $external_repo_name);
			$ext_repo->link_name = request('linkName');

			writeRepos($repos);
			return respond(__("Yeni sembolik link oluşturuldu"), 200);
		}
	}

	function start(){
		$mirrorName = request('mirrorName');

		if (!checkMirrorStatus($mirrorName)) {
			Command::runSudo(
				'bash -c "sg nogroup -c \"umask 002;apt-mirror @{:path}; echo $(date \"+%d-%-m-%Y %H:%M:%S\") \| local user = $(whoami), liman user = {:limanUser} - Finish - apt-mirror @{:mirrorName} repository >> @{:summaryLogFile}\" > @{:detailsLogFile} & disown"',
				[
					'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
					'mirrorName' => $mirrorName,
					'limanUser' => getLimanUser(),
					'summaryLogFile' => Mirror::getSummaryLogFile(),
					'detailsLogFile' => Mirror::getDetailsLogFile()
				]
			);
			Command::runSudo("logger -s 'Start - apt-mirror {:mirrorName}'", [
				'mirrorName' => $mirrorName
			]);
			
			writeToAptMirrorLog('Start', 'apt-mirror '. $mirrorName . ' repository');

			return respond(__('Aynalama işlemi başlatıldı'), 200);
		} else {
			return respond(__('Mevcut aynalama işlemi zaten var!'), 201);
		}

	}

	function stop(){
		$mirrorName = request('mirrorName');

		$checkCron = (bool) Command::runSudo(
			'ps aux | grep "apt-mirror" | grep Cron |  grep -v grep 2>/dev/null 1>/dev/null && echo 1 || echo 0'
		);
		$postfix = '';
		if ($checkCron) {
			$postfix = 'Cron ';
		}
		runCommand(
			"x=$(ps aux | grep apt-mirror | grep '$mirrorName$' | awk '{print $2}');for line in \$x; do " .
				sudo() .
				" kill -9 \$line; done"
		);
		runCommand(
			"x=$(ps aux | grep wget | grep '$mirrorName/' |  awk '{print $2}');for line in \$x; do " .
				sudo() .
				" kill -9 \$line; done"
		);

		writeToAptMirrorLog('Stop', 'apt-mirror '. $mirrorName. ' repository');

		$checkAptMirror = (bool) Command::runSudo(
			"ps aux | grep apt-mirror | grep '{:mirrorName}$' | grep -v grep > /dev/null && echo 1 || echo 0",
			[
				'mirrorName' => $mirrorName
			]
		);
		if (!$checkAptMirror) {
			return respond(__("Aynalama durduruldu"), 200);
		} else {
			return respond(__("Aynalama durdurulamadı!"), 201);
		}
	}

	function getDiskInfo(){
		$mirrorName = request('mirrorName');
		$path = request('storagePath');

		$data = diskInformation($path);
		$data["InstallSize"] = (!empty(pathSize("$path/$mirrorName"))) ? pathSize("$path/$mirrorName") : "null";
		//$data["RemainingMirrorDownloadSize"] = preg_match('#[0-9]#', mirrorSize($mirrorName)) ? mirrorSize($mirrorName) : "NA";
		$data["DirectoryStatus"] =  File::instance()->path("$path/$mirrorName")->checkDirectoryExists();

		return respond($data, 200);
	}

	/*function mirrorSize($mirrorName){

	}*/

	function getSize(){
		validate([
			'mirrorName' => 'required|string'
		]);
		$mirrorName = request('mirrorName');

		$this->checkMirroring();

		$scriptPath = Mirror::getSizeScriptPath() . Mirror::getSizeScriptName();

		File::instance()
			->path(Mirror::getSizeScriptPath())
			->createDirectory();
	
		$checkFile = File::instance()
					->path($scriptPath)
					->checkFileExists();
	
		if (!$checkFile) {
			$mirrorSizeScript = base64_encode(
				file_get_contents(getPath('scripts/' . Mirror::getSizeScriptName()))
			);
			Command::runSudo(
				"bash -c 'echo @{:mirrorSizeScript} | base64 -d | tee {:mirrorSizeScriptPath}'",
				[
					'mirrorSizeScript' => $mirrorSizeScript,
					'mirrorSizeScriptPath' => $scriptPath
				]
			);
			Command::runSudo('chmod 770 {:mirrorSizeScriptPath}', [
				'mirrorSizeScriptPath' => $scriptPath
			]);
		}

		return respond(
			view('components.task', [
				'onFail' => 'onTaskFail',
				'tasks' => [
					0 => [
						'name' => 'GetMirrorSize',
						'attributes' => [
							'mirrorFilePath' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
							'mirrorSizeScriptPath' => $scriptPath
						]
					]
				]
			]),
			200
		);
	}

	private function checkMirroring(){
		if(checkMirrorStatus(request('mirrorName'))){
			abort(__("Devam eden bir aynalama bulunmakta. Aynalamayı durdurduktan sonra işlem yapabilirsiniz."), 201);
		}
	}

}