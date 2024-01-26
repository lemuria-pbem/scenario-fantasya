<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Exception;

use Lemuria\Exception\LemuriaException;

class ReservedOffsetException extends LemuriaException
{
	public function __construct(string $offset) {
		parent::__construct('Offset ' . $offset . ' is reserved.');
	}
}
