<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Exception;

use Lemuria\Exception\LemuriaException;

class OffsetNotFoundException extends LemuriaException
{
	public function __construct(string $offset) {
		parent::__construct('Offset ' . $offset . ' does not exist in payload.');
	}
}
