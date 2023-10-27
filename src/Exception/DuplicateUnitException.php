<?php
declare (strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Exception;

use Lemuria\Id;

class DuplicateUnitException extends ScriptException
{
	public function __construct(Id $alias) {
		parent::__construct('A unit with alias ' . $alias . ' has already been mapped.');
	}
}
