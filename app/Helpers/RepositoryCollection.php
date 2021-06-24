<?php
namespace App\Helpers;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;

class RepositoryCollection
{
	protected $repos;

	public function __construct($repos = null){
		if ($repos) {
			$this->repos = $repos;
		}
	}

	public static function instance(){
		return new self();
	}

	public function getRepos(){
		return $this->repos;
	}

	public function setRepos($repos){
		$this->repos = $repos;
	}

	public function repos($repos){
		$this->setRepos($repos);
		return $this;
	}

    function getStorageFromJson($path){
        return $this->repos->first(function($item, $key) use($path) {
            return $item->storage_path == $path;
        });
    }
    
    function getMirrorListFromJson($path, $mirrorName){
        $storage = $this->getStorageFromJson($path);
        return array_first($storage->mirror_lists, function($item, $value) use($mirrorName){
            return $item->mirror_name == $mirrorName;
        });
    }
    
    static function getRepoUrlFromJson($mirrorList, $ext_url){
        return array_first($mirrorList->external_repo_urls, function($item, $value) use($ext_url){
            return $item->external_repo_url == $ext_url;
        });
    }

	static function getRepoFromJson($ext_url, $ext_url_name){
        return array_first($ext_url->external_repos, function($item, $value) use($ext_url_name){
            return $item->external_repo_name == $ext_url_name;
        });
    }

	static function getRepoVersionFromJson($ext_repo, $debType, $codeName){
        return array_first($ext_repo->versions, function($item, $value) use($debType, $codeName){
            return $item->deb_type == $debType && $item->code_name == $codeName;
        });
    }

	/*function setStorage(){

	}

	function setMirrorList(){
		
	}

	function setRepoUrl(){
		
	}

	function setRepo(){

	}

	function setRepoVersion(){

	}*/
    
}