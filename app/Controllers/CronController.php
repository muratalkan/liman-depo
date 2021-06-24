<?php

namespace App\Controllers;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;
use Liman\Toolkit\Formatter;
use App\Classes\Mirror;

class CronController
{

	public static function getMinutes()
	{
		$minutesArray = [];
		$minutesArray['*'] = 'Hepsi';
		for ($i = 0; $i < 60; $i++) {
			$i = str_pad($i, 2, '0', STR_PAD_LEFT);
			$minutesArray[$i] = $i;
		}
		return $minutesArray;
	}
	public static function getHours()
	{
		$hoursArray = [];
		$hoursArray['*'] = 'Hepsi';
		for ($i = 0; $i < 24; $i++) {
			$i = str_pad($i, 2, '0', STR_PAD_LEFT);
			$hoursArray[$i] = $i;
		}
		return $hoursArray;
	}

	public static function getDays()
	{
		$daysArray = [];
		$daysArray['*'] = 'Hepsi';
		for ($i = 1; $i < 32; $i++) {
			$i = str_pad($i, 2, '0', STR_PAD_LEFT);
			$daysArray[$i] = $i;
		}
		return $daysArray;
	}

	public static function getMonths()
	{
		$monthsArray = [];
		$monthsArray['*'] = 'Hepsi';
		$months = [
			'Ocak',
			'Şubat',
			'Mart',
			'Nisan',
			'Mayıs',
			'Haziran',
			'Temmuz',
			'Ağustos',
			'Eylül',
			'Ekim',
			'Kasım',
			'Aralık'
		];
		for ($i = 1; $i <= sizeof($months); $i++) {
			$monthsArray[$i] = $months[$i - 1];
		}
		return $monthsArray;
	}

	public static function getWeekDays()
	{
		return [
			'*' => 'Hepsi',
			1 => 'Pazartesi',
			2 => 'Salı',
			3 => 'Çarşamba',
			4 => 'Perşembe',
			5 => 'Cuma',
			6 => 'Cumartesi',
			0 => 'Pazar'
		];
	}

	function removeCron()
	{
		validate([
			'mirrorName' => 'required|string'
		]);

		removeCron(request('mirrorName'));

		return respond('Kaldırıldı', 200);
	}

	function addCron()
	{
		validate([
			'time' => 'required|string',
			'mirrorName' => 'required|string'
		]);

		$time = request('time');
		$mirrorName = request('mirrorName');
		$echoPath = Command::runSudo('which echo');
		$datePath = Command::runSudo('which date');
		$whoamiPath = Command::runSudo('which whoami');
		$sgPath = Command::runSudo('which sg');
		$aptMirrorPath = Command::runSudo('which apt-mirror');
		$command = Formatter::run(
			'root {:echoPath} $({:datePath} "+\%d-\%m-\%Y \%H:\%M:\%S") \| $({:whoamiPath}) user Start Cron -  apt-mirror {:mirrorName} repository >> {:summaryLogFile};{:sgPath} nogroup -c "umask 002;{:aptMirrorPath} @{:path} ";{:echoPath} $({:datePath} "+\%d-\%m-\%Y \%H:\%M:\%S") \| $({:whoamiPath}) user Finish Cron -  apt-mirror @{:mirrorName} repository >> @{:summaryLogFile}',
			[
				'echoPath' => $echoPath,
				'datePath' => $datePath,
				'whoamiPath' => $whoamiPath,
				'sgPath' => $sgPath,
				'aptMirrorPath' => $aptMirrorPath,
				'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
				'mirrorName' => $mirrorName,
				'summaryLogFile' => Mirror::getSummaryLogFile()
			]
		);
		$command = "\n$time $command\n";
		Command::runSudo(
			"bash -c \"echo @{:command} | base64 -d | tee -a @{:cronMirrorFile}\"",
			[
				'command' => base64_encode($command),
				'cronMirrorFile' => Mirror::getCronFile()
			]
		);
		return respond('Eklendi', 200);
	}

	function editCron()
	{
		validate([
			'time' => 'required|string',
			'mirrorName' => 'required|string'
		]);

		$time = request('time');
		$mirrorName = request('mirrorName');
		$echoPath = Command::runSudo('which echo');
		$datePath = Command::runSudo('which date');
		$whoamiPath = Command::runSudo('which whoami');
		$sgPath = Command::runSudo('which sg');
		$aptMirrorPath = Command::runSudo('which apt-mirror');
		$command = Formatter::run(
			'root {:echoPath} $({:datePath} "+\%d-\%m-\%Y \%H:\%M:\%S") \| $({:whoamiPath}) kullanicisi Start Cron -  apt-mirror {:mirrorName} deposu >> @{:summaryLogFile};{:sgPath} nogroup -c "umask 002;{:aptMirrorPath} @{:path} ";{:echoPath} $({:datePath} "+\%d-\%m-\%Y \%H:\%M:\%S") \| $({:whoamiPath}) kullanicisi Finish Cron -  apt-mirror {:mirrorName} deposu >> @{:summaryLogFile}',
			[
				'echoPath' => $echoPath,
				'datePath' => $datePath,
				'whoamiPath' => $whoamiPath,
				'sgPath' => $sgPath,
				'aptMirrorPath' => $aptMirrorPath,
				'path' => join(DIRECTORY_SEPARATOR, [Mirror::getConfigsFolderPath(), $mirrorName]),
				'mirrorName' => $mirrorName,
				'summaryLogFile' => Mirror::getSummaryLogFile()
			]
		);

		removeCron($mirrorName);

		$command = "\n$time $command\n";
		Command::runSudo(
			"bash -c \"echo @{:command} | base64 -d | tee -a @{:cronMirrorFile}\"",
			[
				'command' => base64_encode($command),
				'cronMirrorFile' => Mirror::getCronFile()
			]
		);
		return respond('Güncellendi', 200);
	}
}
