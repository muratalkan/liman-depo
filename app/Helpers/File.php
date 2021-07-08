<?php
namespace App\Helpers;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;

class File
{
	protected $path;

	public function __construct($path = null){
		if ($path) {
			$this->path = $path;
		}
	}

	public static function instance(){
		return new self();
	}

	public function getPath(){
		return $this->path;
	}

	public function setPath($path){
		$this->path = $path;
	}

	public function path($path){
		$this->setPath($path);
		return $this;
	}

    public function createDirectory(){
        if(!$this->checkDirectoryExists($this->path))
        {
            return $this->runCommand('mkdir -p @{:path}', [
                'path' => $this->path
            ]);
        }
    }
    
    public function createFile(){
        if(!$this->checkFileExists($this->path))
        {
            return $this->runCommand('touch @{:path}', [
                'path' => $this->$path
            ]);
        }
    }
    
    public function createSymbolicLink($linkPath){
        return $this->runCommand('ln -s @{:path} @{:linkPath}', [
            'path' => $this->path,
            'linkPath' => $linkPath
        ]);
    }
    
    public function checkDirectoryExists(){
        return $this->runCommand('[ -d @{:path} ] && echo 1 || echo 0', [
            'path' => $this->path
        ]);
    }
    
    public function checkFileExists(){
        return $this->runCommand('[ -f @{:path} ] && echo 1 || echo 0', [
            'path' => $this->path
        ]);
    }
    
    public function checkLinkExists(){
        return $this->runCommand('[ -L @{:path} ] && echo 1 || echo 0', [
            'path' => $this->path
        ]);
    }

    public function checkSymbolicLinkIsBroken(){
        if($this->checkLinkExists($this->path)){
            $check = $this->runCommand("find @{:path} -xtype l | wc -l", [
                'path' => $this->path
            ]);
            if($check !== '1'){
                return '1';
            }
        }
        return '0';
    }
    
    public function removeDirectory(){
        return $this->runCommand('rm -rf @{:path}', [
            'path' => $this->path
        ]);
    }
    
    public function removeEmptyDirectory(){
        return $this->runCommand('rmdir @{:path}', [
			'path' => $this->path
		]);
    }
    
    public function removeFile(){
        return $this->runCommand('rm '.addQuotes($this->path), [
			'path' => $this->path
		]);
    }

    public function copy($newPath){
        return $this->runCommand('cp @{:path} @{:newPath}', [
			'path' => $this->path,
            'newPath' => $newPath
		]);
    }

    public function move($newPath){ //also, this can be used to rename
        return $this->runCommand('mv '.addQuotes($this->path).' '.addQuotes($newPath));
    }

    public function size(){
        return $this->runCommand("du -sh ".addQuotes($this->path)." | awk '{print $1}'");
    }

    private function runCommand($command, $attributes = []){
		return Command::runSudo($command, $attributes);
	}


}