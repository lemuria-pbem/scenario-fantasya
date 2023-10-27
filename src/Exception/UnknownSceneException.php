<?php
declare (strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Exception;

class UnknownSceneException extends ScriptException
{
	public function __construct(string $scene) {
		parent::__construct('A scene named "' . $scene . '" is not implemented yet.');
	}
}
