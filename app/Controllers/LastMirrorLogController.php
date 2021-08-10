<?php

namespace App\Controllers;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;
use App\Classes\Mirror;

class LastMirrorLogController
{
	function get(){
		$detailsLog = Command::runSudo('tac @{:detailsLogFile}', [
						'detailsLogFile' => Mirror::getDetailsLogFile()
					]);
		if(empty($detailsLog)){
			$detailsLog = __('Kayıt bulunamadı!');
		}
		return respond($detailsLog);
	}
}
