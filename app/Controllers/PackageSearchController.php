<?php

namespace App\Controllers;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;
use App\Classes\Mirror;
use App\Helpers\RepositoryCollection;
use App\Helpers\File;

class PackageSearchController
{
	protected $listJsonFile = '/etc/apt/mirror/list.json';

	function getMirrorNames(){
		$mirrorNames = [];
		$repos = readRepos();
		if($repos != null){
			foreach($repos as $repo){
				foreach($repo->mirror_lists as $mirrorList){
					$mirrorNames[] = [
						'path' => $repo->storage_path,
						'id' => $mirrorList->mirror_name,
						'text' => $mirrorList->mirror_name
					];
				}
			}
		}

		return respond($mirrorNames);
	}

	function getMirrorList(){
		$path = request('storagePath');
		$mirrorName = request('mirrorName');

		$repos = readRepos();
		$mirrorList = RepositoryCollection::instance()
					->repos($repos)
					->getMirrorListFromJson($path, $mirrorName);

		return respond($mirrorList);
	}

	function getMirrorSearchResult(){
		$storagePath = request('storagePath');
		$mirrorName = request('select_repoList');
		$currentPage = request('currentPage');
		$perPage = 100;
		
		$repo_url = request('select_repoUrl');
		$repo_name = request('select_repoName');
		$repo_codeName = request('select_repoCodename');
		$repo_package = (!empty(request("select_repoPackage"))) ? request("select_repoPackage") : "All";  //not required
		$repo_architecture = (!empty(request("select_repoArch"))) ? request("select_repoArch") : "All"; //not required
		$packageName = request('packagename');

		$repos = readRepos();
		$mirrorList = RepositoryCollection::instance()
					->repos($repos)
					->getMirrorListFromJson($storagePath, $mirrorName);
		$ext_url = RepositoryCollection::getRepoUrlFromJson($mirrorList, $repo_url );
		$ext_repo = RepositoryCollection::getRepoFromJson($ext_url, $repo_name);
		$ipaddress = getIpAddress();

		$packagesFilePath = $storagePath.'/'.$mirrorName . '/skel/' . str_replace('http://', '', $repo_url.$repo_name) . '/dists/' . $repo_codeName;
		
		if ($repo_package == 'All') {
			if (!$repo_architecture == 'All') {
				$packagesFilePath .= '/*/' . $repo_architecture;
			}
		} else {
			if ($repo_architecture == 'All') {
				$packagesFilePath .= '/' . $repo_package;
			} else {
				$packagesFilePath .= '/' . $repo_architecture;
			}
		}

		$packagesFile = Command::runSudo(
			"find @{:packagesFilePath} | grep \"Packages$\" ",
			[
				'packagesFilePath' => $packagesFilePath
			]
		);

		$packagesArray = [];
		if ($packagesFile != '') {
			$packagesFileArr = explode("\n", $packagesFile);
			$packagesArea = '';
			foreach ($packagesFileArr as $key => $value) {
				$packagesArea .=
					Command::runSudo(
						"sed -n -e '/^Package: {:packageName}/,/^$/ p' @{:value}",
						[
							'packageName' => $packageName,
							'value' => $value
						]
					) . ' Package';
			}

			$packagesArea = str_replace("\n", ' ', $packagesArea);
			preg_match_all('/Package:\s+.*?(?=\s+Package)/', $packagesArea, $packages);
			$i=0;$j=0;
			$size=count($packages[0]);
			if($size == 0){
				return respond(__("Paket Bulunamadı!"), 201);
			}
			foreach ($packages[0] as $key => $package) {
				if($i >= (intval($currentPage)-1) * $perPage && $i < (intval($currentPage)-1) * $perPage + $perPage){
					preg_match('/Package:\s+(\S+)/', $package, $packageName);
					preg_match('/Version:\s+(\S+)/', $package, $version);
					preg_match('/Filename:\s+(\S+)/', $package, $filePath);
					$packageSize = pathSize(Mirror::getSymbolicLinkPath()."/$ext_repo->link_name/$filePath[1]");
					$packagesArray[] = [
						'name' => $packageName[1],
						'version' => $version[1],
						'filePath' => $filePath[1],
						'fileSize' => empty($packageSize) ? '0K' : $packageSize,
						'linkPath' => "http://$ipaddress/$ext_repo->link_name/$filePath[1]",
						'checkLink' => File::instance()->path($mirrorList->link_base.'/'.$ext_repo->link_name.'/'.$filePath[1])->checkFileExists()
					];
					$j++;
				}
				$i++;
				if($j==$perPage){ break; }
			}
		}

		$table = view('table', [
			'value' => $packagesArray,
			"startingNumber" => $currentPage ? (intval($currentPage)-1) * $perPage : 0,
			'title' => ['Paket Adı', 'Versiyon', 'Dosya Yolu', 'Dosya Boyutu', '*hidden*', '*hidden*'],
			'display' => ['name', 'version', 'filePath', 'fileSize', 'linkPath:linkPath', 'checkLink:checkLink'],
			'onclick' => 'openPackagePath',
			'menu' => [
				'İndirme Bağlantısını Kopyala' => [
					'target' => 'copyPackagePath',
					'icon' => 'fas fa-copy'
				]
			]
		]);

		$pagination = view('components.pagination',[
			"current" => $currentPage ? $currentPage : "1",
			"count" =>  ($size / $perPage ) ? ceil($size / $perPage) : "1",
			"onclick" => "getMirrorSearchResult"
		]);

		return respond($table . $pagination);
	}

	function getInternalRepoNames(){
		$internalRepoArray = [];
		$listText = str_replace("\n", '',
			Command::runSudo('cat {:listJsonFile}', [
				'listJsonFile' => $this->listJsonFile
			])
		);
		$listArray = json_decode($listText, true);
		$internalRepoNames = [];
		if (is_array($listArray['internal'])) {
			$internalRepoNames = array_keys($listArray['internal']);
		}

		foreach ($internalRepoNames as $key => $value) {
			$internalRepoArray[] = [
				'id' => $value,
				'text' => $value
			];
		}

		return respond($internalRepoArray);
	}

	function getInternalRepoSearchResult(){
		$internalRepoName = request('internalRepoName');
		$packageName = request('packagename');
		$currentPage = request('currentPage');
		$perPage = 100;

		$listText = str_replace("\n", '',
			Command::runSudo('cat {:listJsonFile}', [
				'listJsonFile' => $this->listJsonFile
			])
		);
		$listArray = json_decode($listText, true);
		$internalRepoPath =  $listArray['internal'][$internalRepoName]['path'];
		
		$packagesFile = Command::runSudo(
			"find {:internalRepoPath}/dists -name 'Packages'",
			[
				'internalRepoPath' => $internalRepoPath
			]
		);

		$packagesFileArr = explode("\n", $packagesFile);
		$ipaddress = getIpAddress();

		
		$packagesArray = [];
		foreach ($packagesFileArr as $key => $file) {
			$text = Command::runSudo(
				"sed -n -e '/^Package: {:packageName}/,/^$/ p' @{:value}",
				[
					'packageName' => $packageName,
					'value' => $file
				]
			) . ' Package';

			$i=0;$j=0;
			preg_match_all('/Package:\s+.*?(?=Package)/s', $text, $matches);
			$size=count($matches[0]);
			if($size == 0){
				return respond("Paket Bulunamadı!", 201);
			}
			foreach ($matches[0] as $id => $package) {
				if($i >= (intval($currentPage)-1) * $perPage && $i < (intval($currentPage)-1) * $perPage + $perPage){
					preg_match('/Package:\s+(\\S+)/', $package, $packagename);
					preg_match('/Version:\s+(\\S+)/', $package, $packageversion);
					preg_match('/\s+Size:\s+(\\S+)/', $package, $packagesize);
					preg_match('/Filename:\s+(\S+)/', $package, $filePath);
					$pathArray = explode('/', trim($file));
					$packagesArray[] = [
						'name' => $packagename[1],
						'version' => $packageversion[1],
						'filePath' => $filePath[1],
						'fileSize' => pathSize($listArray['internal'][$internalRepoName]['link'].'/'.$filePath[1]),
						'linkPath' => "http://$ipaddress/" .str_replace('/var/www/html/','',$listArray['internal'][$internalRepoName]['link']).'/'.$filePath[1],
						'checkLink' => '1'
					];
					$j++;
				}
				$i++;
				if($j==$perPage){ break; }
			}
		}
		
		$table =  view('table', [
			'value' => $packagesArray,
			"startingNumber" => $currentPage ? (intval($currentPage)-1) * $perPage : 0,
			'title' => ['Paket Adı', 'Versiyon', 'Dosya Yolu', 'Dosya Boyutu', '*hidden*', '*hidden*'],
			'display' => ['name', 'version','filePath', 'fileSize', 'linkPath:linkPath', 'checkLink:checkLink'],
			'onclick' => 'openPackagePath',
			'menu' => [
				'İndirme Bağlantısını Kopyala' => [
					'target' => 'copyPackagePath',
					'icon' => 'fas fa-copy'
				]
			]
		]);
		$pagination = view('components.pagination',[
			"current" => $currentPage ? $currentPage : "1",
			"count" =>  ($size / $perPage ) ? ceil($size / $perPage) : "1",
			"onclick" => "getInternalRepoResult"
		]);

		return respond($table . $pagination);
	}
}