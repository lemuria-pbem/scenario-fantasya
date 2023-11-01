<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Exception\ScriptException;

interface Act
{
	/**
	 * @throws ParseException
	 */
	public function parse(Macro $macro): static;

	/**
	 * @throws ScriptException
	 */
	public function play(): static;

	public function getChainResult(): bool;

	public function prepareNext(): static;
}
