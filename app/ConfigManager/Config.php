<?php
namespace App\ConfigManager;

use App\Exceptions\FileContentReadException;
use App\Exceptions\FileContentWriteException;
use App\Exceptions\FileDataWriteException;
use Jenssegers\Blade\Blade;
use Liman\Toolkit\Shell\Command;

class Config
{
	protected $blade;
	protected $template;
	protected $folder;
	protected $file;
	protected $data = [];
	const HEADER_MESSAGE = "#### DO NOT CHANGE THIS FILE, WILL BE OVERWRITTEN BY LIMAN ####\n";

	public function __construct(
		$template = null,
		$folder = null,
		$file = null,
		$data = null
	) {
		$path = '/tmp/liman/';
		if (!is_dir($path)) {
			mkdir($path);
		}
		$this->blade = new Blade([getPath('configs')], $path);
		if ($template) {
			$this->template = $template;
		}
		if ($folder) {
			$this->folder = $folder;
		}
		if ($file) {
			$this->file = $file;
		}
		if ($data) {
			$this->data = $data;
		}
	}

	public function getTemplate()
	{
		return $this->template;
	}

	public function setTemplate($template)
	{
		$this->template = $template;
	}

	public function getFolder()
	{
		return $this->folder;
	}

	public function setFolder($folder)
	{
		$this->folder = $folder;
	}

	public function getFile()
	{
		return $this->file;
	}

	public function setFile($file)
	{
		$this->file = $file;
	}

	public function getData()
	{
		$defaults = [];
		if (
			file_exists(
				join(DIRECTORY_SEPARATOR, [
					getPath('configs'),
					$this->template . '.defaults.php'
				])
			)
		) {
			$defaults = include join(DIRECTORY_SEPARATOR, [
				getPath('configs'),
				$this->template . '.defaults.php'
			]);
		}
		return array_merge($defaults, $this->data);
	}

	public function setData($data)
	{
		$this->data = $data;
	}

	public function template($template)
	{
		$this->setTemplate($template);
		return $this;
	}

	public function folder($folder)
	{
		$this->setFolder($folder);
		return $this;
	}

	public function file($file)
	{
		$this->setFile($file);
		return $this;
	}

	public function data($data)
	{
		$this->setData($data);
		return $this;
	}

	public function readList()
	{
		Command::run('mkdir -p ~/.liman/configs');
		$rawOutput = Command::run('ls ~/.liman/configs');
		$list = [];
		if (empty($rawOutput)) {
			return $list;
		}
		foreach (explode("\n", $rawOutput) as $row) {
			$row = base64_decode($row);
			if (str_starts_with($row, $this->getFolder())) {
				$fetch = explode('-', $row);
				$list[] = $fetch[1];
			}
		}
		return $list;
	}

	public function read()
	{
		$checkFile = (bool) Command::run(
			'[ -f ~/.liman/configs/{:file} ] 2>/dev/null 1>/dev/null && echo 1 || echo 0',
			[
				'file' => base64_encode(
					$this->getFolder() . ' - ' . $this->getFile()
				)
			]
		);
		if (!$checkFile) {
			throw new FileContentReadException();
		}
		$file = Command::run('cat ~/.liman/configs/{:file}', [
			'file' => base64_encode(
				$this->getFolder() . ' - ' . $this->getFile()
			)
		]);
		return json_decode($file);
	}

	public function write($sudo = false)
	{
		$content =
			self::HEADER_MESSAGE .
			$this->blade->render($this->getTemplate(), $this->getData());
		$this->command('mkdir -p @{:folder}', [
			'folder' => $this->getFolder()
		], $sudo);
		$result = (bool) $this->command(
			"bash -c \"echo @{:content} | base64 -d | tee @{:file_path} 2>/dev/null 1>/dev/null && echo 1 || echo 0\"",
			[
				'content' => base64_encode($content),
				'file_path' => join(DIRECTORY_SEPARATOR, [
					$this->getFolder(),
					$this->getFile()
				])
			],
			$sudo
		);
		if (!$result) {
			throw new FileContentWriteException();
		}
		$this->command('mkdir -p ~/.liman/configs');
		$result = (bool) $this->command(
			'echo @{:content} | base64 -d | tee ~/.liman/configs/{:file} 2>/dev/null 1>/dev/null && echo 1 || echo 0',
			[
				'content' => base64_encode(
					json_encode($this->getData(), JSON_PRETTY_PRINT)
				),
				'file' => base64_encode(
					$this->getFolder() . ' - ' . $this->getFile()
				)
			]
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
}
