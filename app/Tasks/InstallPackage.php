<?php

namespace App\Tasks;

use Liman\Toolkit\Formatter;
use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\RemoteTask\Task;
use Liman\Toolkit\Shell\Command;
use App\Classes\Package;

class InstallPackage extends Task
{
	protected $description = 'Installing packages...';
	protected $sudoRequired = true;

	public function __construct(array $attrbs = [])
	{
		$packages = implode(' ', Package::getPackageToInstall());

		$this->control = Distro::debian('apt\|dpkg')
			->centos('yum install')
			->get();

		$this->command = Distro::debian(
			'DEBIAN_FRONTEND=noninteractive apt-get install '.$packages.' -y'
		)
			->centos(
				'
				yum install '.$packages.' -y
				'
			)
			->get();

		$this->attributes = $attrbs;
		$this->logFile = Formatter::run('/tmp/apt-install-depo-packages.txt');
	}

}