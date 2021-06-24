<?php
namespace App\DataManager;

use App\Exceptions\FileDataWriteException;
use Liman\Toolkit\Shell\Command;

class Data
{
	protected $file;

	public function __construct($file = null)
	{
		if ($file) {
			$this->file = $file;
		}
	}

	public function getFile()
	{
		return $this->file;
	}

	public function setFile($file)
	{
		$this->file = $file;
	}

	public function file($file)
	{
		$this->setFile($file);
		return $this;
	}

	public function read($sudo = false)
	{
		$checkFile = (bool) $this->command(
			'[ -f @{:file} ] 2>/dev/null 1>/dev/null && echo 1 || echo 0',
			[
				'file' => $this->getFile()
			],
			$sudo
		);
		if (!$checkFile) {
			return collect((object) []);
		}
		$data = $this->command(
			'cat @{:file}',
			[
				'file' => $this->getFile()
			],
			$sudo
		);
		$object = @json_decode($data);
		if ($object === null && json_last_error() !== JSON_ERROR_NONE) {
			return collect((object) []);
		}
		return collect((object) $object);
	}

	public function write($data = [], $sudo = false)
	{
		$result = $this->command(
			'mkdir -p @{:file}',
			[
				'file' => dirname($this->getFile())
			],
			$sudo
		);
		$result = (bool) $this->command(
			"bash -c \"echo @{:content} | base64 -d | tee @{:file} 2>/dev/null 1>/dev/null && echo 1 || echo 0\"",
			[
				'content' => base64_encode(
					json_encode($data, JSON_PRETTY_PRINT)
				),
				'file' => $this->getFile()
			],
			$sudo
		);
		if (!$result) {
			throw new FileDataWriteException();
		}
		return true;
	}

	private function command($command, $attributes = [], $sudo = false)
	{
		if ($sudo) {
			return Command::runSudo($command, $attributes);
		}
		return Command::run($command, $attributes);
	}

	public static function instance()
	{
		return new self();
	}
}
