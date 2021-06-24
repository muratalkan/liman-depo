<?php
namespace App\Classes;

class Package
{
	public $name;

    function __construct($name) {
        $this->name = $name;
    }

	function setName($name){
        $this->name = $name;
    }

    function getName(){
        return $this->name;
    }

	public static function getPreInstalledPackage(){
		return [
			"'apache2'",
			"'apt-mirror'",
			"'reprepro'"
		];
	}

	public static function getPackageToInstall(){
		return [
            "apache2",
			"apt-mirror",
			"reprepro"
		];
	}

}