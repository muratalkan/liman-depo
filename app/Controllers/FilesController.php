<?php

namespace App\Controllers;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;
use phpseclib\Net\SFTP;
use Illuminate\Support\Str;
use App\Helpers\File;

class FilesController
{
	protected $allowedFileExts = [];
	protected $path = '/home/';
	protected $uploadFolder = '';
	protected $symbolicFolder = 'Files2Share';
	protected $symbolicPath = '/var/www/html/';


	private	function getUploadPath(){
		return $this->path . extensionDb("clientUsername") . '/';
	}
	
	private function getSymbolicPath(){
		return $this->symbolicPath . $this->symbolicFolder;
	}

	function getUploadedFiles(){
		$files = explode("\n", Command::runSudo('ls ' . $this->getSymbolicPath()));

		$filesArr = [];
		foreach($files as $file){
			if(!empty($file)){
				$size = File::instance()
							->path(join(DIRECTORY_SEPARATOR, [$this->getSymbolicPath(), $file]))
							->size();
				$filesArr[] = [
					'fileName' => $file,
					'filePath' => $this->getSymbolicPath(),
					'fileSize' => $size
				];
			}
		}

		return respond([
			'filesLink' => 'http://'. join(DIRECTORY_SEPARATOR, [getIpAddress(), $this->symbolicFolder]),
			'filesTable' => view('table', [
					'value' => $filesArr,
					'title' => ['Dosya Adı', 'Dosya Dizini', 'Dosya Boyutu'],
					'display' => ['fileName', 'filePath', 'fileSize'],
					'menu' => [
						'Sil' => [
							'target' => 'deleteFile',
							'icon' => 'fa-trash'
						]
					]
				]
			
			)
		]);
	}

	function uploadFile(){
		$fileName = request('fileName');
		$filePath = request('filePath');
		$fileSize = trim(request('fileSize'));

		if(
			$fileSize  >= $this->getTotalSize($this->getUploadPath())
			||
			$fileSize  >= $this->getTotalSize($this->symbolicPath)
		){
			return respond(__('Sunucuda yeterli alan yok!'), 201);
		}

		File::instance()
				->path($this->getSymbolicPath())
				->createDirectory();
		 
		$sftp = new SFTP(getIpAddress());
        if(!$sftp->login(extensionDb("clientUsername"), extensionDb("clientPassword"))){
            $sftp->isConnected() ? abort(__("SSH bağlantısı kurulamadı!"), 201) : abort(__("Sunucuya erişim sağlanamadı!"), 201);
        }
    
        $filePath = getPath(request("filePath"));
        $flag = $sftp->put($this->getUploadPath() . basename($filePath), $filePath, SFTP::SOURCE_LOCAL_FILE);
        unlink($filePath);
		
		$result = File::instance()
					->path($this->getUploadPath() . basename($filePath))
					->move($this->getSymbolicPath());
		return respond(__('Dosya yüklendi'));
	}

	function removeFile(){
		$result = File::instance()
					->path(join(DIRECTORY_SEPARATOR, [$this->getSymbolicPath(), request('fileName')]))
					->removeFile();
		return respond(__('Dosya silindi'));
	}

	function getFilesDiskInfo(){
		$data = diskInformation($this->getSymbolicPath());
		$data["InstallSize"] = (!empty(pathSize($this->getSymbolicPath()))) ? pathSize($this->getSymbolicPath()) : "null";
		$data["DirectoryStatus"] = File::instance()->path($this->getSymbolicPath())->checkDirectoryExists();
		return respond($data, 200);
	}

	private function getTotalSize($path){
		$size = Command::runSudo("df -B1 @{:path} | tail -1 | awk '{print $4}'", [
			'path' => $path
		]);
		return trim($size);
	}

}
