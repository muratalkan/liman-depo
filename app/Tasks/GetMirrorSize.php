<?php

namespace App\Tasks;

use Liman\Toolkit\Formatter;
use Liman\Toolkit\OS\Distro;
use Liman\Toolkit\RemoteTask\Task;
use Liman\Toolkit\Shell\Command;

class GetMirrorSize extends Task
{
	protected $description = 'Boyut hesaplanıyor...';
	protected $command = 'perl {:mirrorSizeScriptPath} {:mirrorFilePath}';
	protected $sudoRequired = true;
	protected $control = 'perl';

	public function __construct(array $attributes = [])
	{
		if (!isset($attributes['mirrorSizeScriptPath'])) {
			throw new \Exception('filename is required');
		}

		if (!isset($attributes['mirrorFilePath'])) {
			throw new \Exception('filename is required');
		}

		$this->attributes = $attributes;
		$this->logFile = Formatter::run('/tmp/get-mirror-size');
	}

}
