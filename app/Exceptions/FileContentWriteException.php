<?php
namespace App\Exceptions;
use Exception;

class FileContentWriteException extends Exception
{
	protected $message = 'File content write error!';

	public function __construct()
	{
		parent::__construct($this->message);
	}
}
