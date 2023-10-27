<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script;

use Lemuria\Engine\Fantasya\Context;
use Lemuria\Engine\Fantasya\Factory\CommandFactory;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Party\Type;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Model\UnitMapper;
use Lemuria\Scenario\Fantasya\Scene;
use Lemuria\Storage\Ini\Lines;
use Lemuria\Storage\Ini\Section;
use Lemuria\Storage\Ini\Values;

abstract class AbstractScene implements Scene
{
	protected Section $section;

	protected Values $values;

	protected Lines $lines;

	private static ?Context $context = null;

	private static CommandFactory $factory;

	private static UnitMapper $mapper;

	public function __construct() {
		if (!self::$context) {
			self::$context = new Context(State::getInstance());
			self::$context->setParty(State::getInstance()->getTurnOptions()->Finder()->Party()->findByType(Type::NPC));
			self::$factory = self::$context->Factory();
			self::$mapper  = new UnitMapper();
		}
	}

	public function isDue(): bool {
		$round = $this->getOptionalValue('Runde');
		if ($round) {
			if (Lemuria::Calendar()->Round() !== (int)$round) {
				return false;
			}
		}
		return true;
	}

	public function parse(Section $section): static {
		$this->section = $section;
		$this->values  = $section->Values();
		$this->lines   = $section->Lines();
		return $this;
	}

	protected function context(): Context {
		return self::$context;
	}

	protected function factory(): CommandFactory {
		return self::$factory;
	}

	protected function mapper(): UnitMapper {
		return self::$mapper;
	}

	/**
	 * @throws ParseException
	 */
	protected function getValue(string $name): string {
		if ($this->values->offsetExists($name)) {
			return (string)$this->values->offsetGet($name);
		}
		throw new ParseException('Undefined value: ' . $name);
	}

	protected function getOptionalValue(string $name): ?string {
		if ($this->values->offsetExists($name)) {
			return (string)$this->values->offsetGet($name);
		}
		return null;
	}

	/**
	 * @return array<string>
	 */
	protected function getValues(string $name): array {
		if ($this->values->offsetExists($name)) {
			$values = [];
			foreach ($this->values->offsetGet($name) as $value) {
				foreach ($value as $item) {
					foreach (explode(',', $item) as $part) {
						$part = trim($part);
						if ($part) {
							$values[] = $part;
						}
					}
				}
			}
			return $values;
		}
		return [];
	}
}
