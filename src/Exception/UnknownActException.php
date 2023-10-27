<?php
declare (strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Exception;

class UnknownActException extends ParseException
{
	public function __construct(string $act) {
		parent::__construct('An act named "' . $act . '" is not implemented yet.');
	}
}
