<?php
declare (strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Exception;

use Lemuria\Id;

class UnmappedAliasException extends ScriptException
{
	public function __construct(Id $alias) {
		parent::__construct('Unknown alias ' . $alias . ' - no unit mapped.');
	}
}
