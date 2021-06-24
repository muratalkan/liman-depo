<?php
namespace App\Exceptions;
use Exception;

class FileContentReadException extends Exception
{
	protected $message = 'File content read error!';

	public function __construct()
	{
		parent::__construct($this->message);
	}
}
