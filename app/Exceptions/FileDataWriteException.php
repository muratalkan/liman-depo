<?php
namespace App\Exceptions;
use Exception;

class FileDataWriteException extends Exception
{
	protected $message = 'File data write error!';

	public function __construct()
	{
		parent::__construct($this->message);
	}
}
