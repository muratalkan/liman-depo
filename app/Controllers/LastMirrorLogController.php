<?php

namespace App\Controllers;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;
use App\Classes\Mirror;

class LastMirrorLogController
{
	function get()
	{
		$detailLog = explode(
			"\n",
			Command::runSudo('cat @{:detailsLogFile}', [
				'detailsLogFile' => Mirror::getDetailsLogFile()
			])
		);
		return respond($detailLog, 200);
	}
}
