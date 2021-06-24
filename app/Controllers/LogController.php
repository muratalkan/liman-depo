<?php

namespace App\Controllers;

use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\Shell\Command;
use App\Classes\Mirror;

class LogController
{
	function getDates()
	{
		$text = Command::runSudo(
			"cat @{:summaryLogFile} | awk '{print $1}' | uniq",
			[
				'summaryLogFile' => Mirror::getSummaryLogFile()
			]
		);
		if (empty($text)) {
			return respond([]);
		}
		$textArray = explode("\n", $text);
		$array = [];
		foreach ($textArray as $key => $value) {
			$array[] = date('Y-m-d', strtotime($value));
		}
		return respond($array);
	}

	function get()
	{
		$date = request('date');
		$date = date('d-m-Y', strtotime($date));
		$text = trim(
			base64_decode(
				Command::runSudo(
					'cat @{:summaryLogFile}  | grep ' . $date . ' | base64 ',
					[
						'summaryLogFile' => Mirror::getSummaryLogFile()
					]
				)
			)
		);
		
		$dict = [];
		$textArray = explode("\n", $text);
		
		foreach ($textArray as $key => $value) {
			$fetch = explode('|', $value);
			if(!empty($fetch)){
				$dict[] = [
					'name' => $fetch[1],
					'date' => $fetch[0]
				];
			}
		}

		return view('table', [
			'value' => array_reverse($dict),
			'title' => ['Log', 'Tarih'],
			'display' => ['name', 'date']
		]);
	}
}
