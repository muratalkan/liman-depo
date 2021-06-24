<?php
namespace App\Classes;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;

class Mirror
{
	protected $basePath;

	public function __construct($basePath = null){
		if ($basePath) {
			$this->basePath = $basePath;
		}
	}

	public static function instance(){
		return new self();
	}

	public function getBasePath(){
		return $this->basePath;
	}

	public function setBasePath($basePath){
		$this->basePath = $basePath;
	}

	public function mirror($basePath){
		$this->setBasePath($basePath);
		return $this;
	}

	public function createConfig(){

	}

	public static function getDefaultConfigValues(){
		return array(
			'nthreads' => '20',
			'tilde' => '0'
		);
	}

    public static function getConfigsFolderPath(){
		return '/etc/apt/mirrorConfigs';
	}
    public static function getJsonFile(){
		return '/etc/apt/mirrorList.json';
	}
	public static function getSymbolicLinkPath(){
		return '/var/www/html';
	}
	public static function getSizeScriptPath(){
		return '/var/spool/repository/';
	}
	public static function getSizeScriptName(){
		return 'apt-mirror_size';
	}
	public static function getSummaryLogFile(){
		return '/var/spool/apt-mirror/summary.log';
	}
	public static function getDetailsLogFile(){
		return '/var/spool/apt-mirror/details.log';
	}
	public static function getCronFile(){
		return '/etc/cron.d/apt-mirror';
	}

}