<?php

namespace App\Controllers;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;
use App\Helpers\File;
use Illuminate\Support\Str;

class InternalRepoController
{
	protected $listJsonFile = '/etc/apt/mirror/list.json';

	function get()
	{
		$internalRepoArray = [];
		$listText = str_replace(
			"\n",
			'',
			Command::runSudo('cat {:listJsonFile}', [
				'listJsonFile' => $this->listJsonFile
			])
		);
		$listArray = json_decode($listText, true);
		$internalRepoNames = [];
		if (is_array($listArray['internal'])) {
			$internalRepoNames = array_keys($listArray['internal']);
		}
		$ipaddress = Command::runSudo('hostname -I');

		foreach ($internalRepoNames as $key => $value) {
			$codename = Command::runSudo(
				"cat {:path}/conf/distributions | grep 'Codename' | cut -d ':' -f2",
				[
					'path' => $listArray['internal'][$value]['path']
				]
			);

			$internalRepoArray[] = [
				'name' => $value,
				'path' => $listArray['internal'][$value]['path'],
				'link' =>
					"http://$ipaddress/" .
					str_replace(
						'/var/www/html/',
						'',
						$listArray['internal'][$value]['link']
					),
				'size' => $this->pathSize(
					$listArray['internal'][$value]['path']
				),
				'codename' => $codename
			];
		}
		return view('table', [
			'value' => $internalRepoArray,
			'title' => ['İsim', 'Yol', 'Link', 'Boyut', '*hidden*'],
			'display' => ['name', 'path', 'link', 'size', 'codename:codename'],
			'menu' => [
				'Sil' => [
					'target' => 'deleteInternalRepo',
					'icon' => 'fa-trash'
				]
			],
			'onclick' => 'getInternalRepoPackages'
		]);
	}

	function getPackages()
	{
		$internalRepoPath = request('internalRepoPath');
		$debArray = [];
		$packagesFileArray = explode(
			"\n",
			Command::runSudo(
				"find {:internalRepoPath}/dists -name 'Packages'",
				[
					'internalRepoPath' => $internalRepoPath
				]
			)
		);

		foreach ($packagesFileArray as $key => $file) {
			$text =
				Command::runSudo("sed -n -e  '/Package: /,/^$/ p' {:file}", [
					'file' => $file
				]) . ' Package';

			preg_match_all('/Package:\s+.*?(?=Package)/s', $text, $matches);

			foreach ($matches[0] as $id => $package) {
				preg_match('/Package:\s+(\\S+)/', $package, $packagename);
				preg_match('/Version:\s+(\\S+)/', $package, $packageversion);
				preg_match('/\s+Size:\s+(\\S+)/', $package, $packagesize);
				$pathArray = explode('/', trim($file));
				$str = [
					'name' => $packagename[1],
					'version' => $packageversion[1],
					'size' => $this->sizeFilter($packagesize[1]),
					'architecture' =>
						'binary-' . $pathArray[sizeof($pathArray) - 2]
				];
				array_push($debArray, $str);
			}
		}
		return view('table', [
			'value' => $debArray,
			'title' => ['İsim', 'Versiyon', 'Boyut', 'Mimari'],
			'display' => ['name', 'version', 'size', 'architecture'],
			'menu' => [
				'Sil' => [
					'target' => 'deletePackage',
					'icon' => 'fa-trash'
				]
			]
		]);
	}

	function sizeFilter($bytes)
	{
		$label = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
		for (
			$i = 0;
			$bytes >= 1024 && $i < count($label) - 1;
			$bytes /= 1024, $i++
		);
		return round($bytes, 2) . ' ' . $label[$i];
	}

	function addPackage()
	{
		$path = request('path');
		$name = request('name');
		$repoPath = request('repoPath');
		$codeName = request('codeName');
		$remotePath = '/tmp/' . str_replace(' ', '', request('name'));
		$output = putFile(getPath($path), $remotePath);
		if ($output !== 'ok') {
			return respond('Başarısız', 201);
		}
		$pathFile = str_replace(' ', '\ ', getPath(quotemeta($path)));
		shell_exec("rm -rf $pathFile");
		//$result = addPackagesRepo($name,$source_path,$codename,quotemeta($remote_path));

		$output = Command::runSudo(
			'reprepro --ignore=forbiddenchar -P optional -V -b {:repoPath}/ -S utils includedeb {:codeName} {:remotePath}',
			[
				'repoPath' => $repoPath,
				'codeName' => $codeName,
				'remotePath' => $remotePath
			]
		);

		if (strpos($output, 'No section') !== false) {
			return respond('Pakette section bulunamadı', 201);
		}
		if (strpos($output, 'already') !== false) {
			return respond("Bu paket zaten bulunmaktadır : $name", 201);
		}
		Command::runSudo('rm -rf {:remotePath}', [
			'remotePath' => $remotePath
		]);

		if ($this->checkPackagesRepo($repoPath, $codeName, $remotePath)) {
			return respond('Paket Eklendi', 200);
		} else {
			return respond(explode("\n", $output)[0], 201);
		}
	}

	function checkPackagesRepo($repoPath, $codeName, $remotePath)
	{
		$packageName = Command::runSudo('dpkg -f {:remotePath} Package', [
			'remotePath' => $remotePath
		]);
		$checkPackage = Command::runSudo(
			"reprepro -Vb {:repoPath} list {:codeName} | grep -i '{:packageName}' 1>/dev/null 2>/dev/null && echo 1 || echo 0",
			[
				'repoPath' => $repoPath,
				'codeName' => $codeName,
				'packageName' => $packageName
			]
		);
		if ($checkPackage == '1') {
			return true;
		} else {
			return false;
		}
	}

	function pathSize($path)
	{
		$size = Command::runSudo("du -sh {:path} | awk '{print $1}'", [
			'path' => $path
		]);
		return $size;
	}

	function deletePackage()
	{
		$packageName = request('packageName');
		$path = request('path');
		$codeName = request('codeName');
		Command::runSudo(
			'reprepro -Vb  {:path}/ remove {:codeName} {:packageName}',
			[
				'path' => $path,
				'codeName' => $codeName,
				'packageName' => $packageName
			]
		);
		$checkPackage = Command::runSudo(
			"reprepro -Vb {:path} list {:codeName} '{:packageName}' | grep '{:packageName}' 1>/dev/null 2>/dev/null && echo 1 || echo 0",
			[
				'path' => $path,
				'codeName' => $codeName,
				'packageName' => $packageName
			]
		);

		if ($checkPackage) {
			return respond('Paket Silinemedi', 201);
		} else {
			return respond('Paket silindi', 200);
		}
	}

	function delete()
	{
		validate([
			'path' => 'required|string',
			'name' => 'required|string'
		]);

		$path = request('path');
		$name = request('name');

		$listText = str_replace(
			"\n",
			'',
			Command::runSudo('cat {:listJsonFile}', [
				'listJsonFile' => $this->listJsonFile
			])
		);
		$listArray = json_decode($listText, true);

		Command::runSudo('rm -rf /var/www/html/{:name}', [
			'name' => $name
		]);

		Command::runSudo('rm -rf {:path}', [
			'path' => $path
		]);

		unset($listArray['internal'][$name]);
		Command::runSudo(
			"bash -c \"echo @{:jsonContent} | base64 -d | tee {:listJsonFile}\"",
			[
				'jsonContent' => base64_encode(
					json_encode($listArray, JSON_PRETTY_PRINT)
				),
				'listJsonFile' => $this->listJsonFile
			]
		);
		return respond('Silindi', 200);
	}

	function add()
	{
		$repoName = request('repo_name');
		$path = request('repo_path');
		$Codename = request('repo_codename');
		$Architectures = request('repo_architectures');
		$Components = request('repo_components');
		$Description = request('repo_description');

		$path = Str::start($path, '/');

		$listText = str_replace(
			"\n",
			'',
			Command::runSudo('cat {:listJsonFile}', [
				'listJsonFile' => $this->listJsonFile
			])
		);
		$listArray = json_decode($listText, true);

		File::instance()
			->path($path)
			->createDirectory();

		if (is_array($listArray['internal'][$repoName])) {
			return respond('Böyle bir depo bulunmaktadır.', 201);
		}

		$checkLink = Command::runSudo(
			"[ -L /var/www/html/$repoName ] && echo 1 || echo 0"
		);
		if ($checkLink == '1') {
			return respond("Bu link adı bulunmaktadır. -$repoName-", 201);
		}
		$signWith = $this->createGpgKey();
		$link = "/var/www/html/$repoName";
		$repoPath = $path . '/' . $repoName;

		$item = [
			'link' => $link,
			'path' => $repoPath
		];

		$listArray['internal'][$repoName] = $item;

		Command::runSudo('mkdir -p {:repoPath}', [
			'repoPath' => $repoPath
		]);

		Command::runSudo('ln -s {:repoPath} {:link}', [
			'repoPath' => $repoPath,
			'link' => $link
		]);
		$this->addDist(
			$repoPath,
			$repoName,
			$Codename,
			$Architectures,
			$Components,
			$Description,
			$signWith
		);

		if (
			Command::runSudo('[ -f  {:listJsonFile} ] && echo 1 || echo 0', [
				'listJsonFile' => $this->listJsonFile
			]) == '0'
		) {
			Command::runSudo('mkdir -p /etc/apt/mirror');
			Command::runSudo('touch {:listJsonFile}', [
				'listJsonFile' => $this->listJsonFile
			]);
		}

		Command::runSudo(
			"bash -c \"echo @{:jsonContent} | base64 -d | tee {:listJsonFile}\"",
			[
				'jsonContent' => base64_encode(
					json_encode($listArray, JSON_PRETTY_PRINT)
				),
				'listJsonFile' => $this->listJsonFile
			]
		);
		return respond('Eklendi', 200);
	}

	function addDist(
		$repoPath,
		$reponame,
		$Codename,
		$Architectures,
		$Components,
		$Description,
		$SignWith
	) {
		Command::runSudo('mkdir -p {:repoPath}/conf', [
			'repoPath' => $repoPath
		]);
		$Architectures = str_replace(',', ' ', $Architectures);
		$Components = str_replace(',', ' ', $Components);
		$string = "
Origin: $reponame
Label: $reponame
Codename: $Codename
Architectures: $Architectures
Components: $Components
Description: $Description
SignWith: $SignWith
        ";

		Command::runSudo(
			"bash -c \"echo @{:string} | base64 -d | tee {:repoPath}/conf/distributions\"",
			[
				'string' => base64_encode($string),
				'repoPath' => $repoPath
			]
		);
	}

	function createGpgKey()
	{
		$tmp = "
%no-protection
Key-Type: RSA
Key-Length: 3072
Subkey-Type: 1
Subkey-Length: 3072
Name-Real: Havelsan Liman
Name-Comment: Havelsan Liman
Name-Email: liman@havelsan.com.tr
Expire-Date: 0
";
		Command::runSudo(
			"bash -c \"echo @{:tmp} | base64 -d | tee /tmp/keyCreate\"",
			[
				'tmp' => base64_encode($tmp)
			]
		);

		$output = Command::runSudo(
			'gpg --batch --generate-key /tmp/keyCreate 2>&1'
		);
		preg_match('/(key|anahtar)\s(\S+)/', $output, $matches);
		Command::runSudo('rm -rf /tmp/keyCreate');
		return $matches[2];
	}

	function gpgKeyExport()
	{
		$repoName = request('repoName');
		$path = request('path');
		$link = request('link');
		$key = Command::runSudo(
			"cat {:path}/conf/distributions | grep 'SignWith' | cut -d ':' -f2",
			[
				'path' => $path
			]
		);
		Command::runSudo(
			'gpg --output /var/www/html/{:repoName}/public.gpg --armor --export {:key}',
			[
				'repoName' => $repoName,
				'key' => $key
			]
		);
		return respond($link . '/public.gpg', 200);
	}
}
