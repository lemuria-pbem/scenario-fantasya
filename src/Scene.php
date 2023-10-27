<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Exception\ScriptException;
use Lemuria\Storage\Ini\Section;

interface Scene
{
	/**
	 * @throws ParseException
	 */
	public function parse(Section $section): static;

	public function isDue(): bool;

	/**
	 * @throws ScriptException
	 */
	public function play(): static;
}
