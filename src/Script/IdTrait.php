<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script;

use Lemuria\Id;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Party\Type;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Storage\Ini\Value;

trait IdTrait
{
	protected ?Id $id = null;

	private readonly string $idArgument;

	public function setArguments(string $arguments): static {
		if ($arguments) {
			$this->id         = Id::fromId($arguments);
			$this->idArgument = (string)$this->id;
		} else {
			$this->idArgument = '';
		}
		return $this;
	}

	/**
	 * @throws ParseException
	 */
	private function parseUnit(): Unit {
		if (isset($this->values['ID'])) {
			$this->id = Id::fromId((string)$this->values['ID']);
		}
		if (!$this->id) {
			throw new ParseException('No unit defined in this script.');
		}

		$mapper = $this->mapper();
		if ($mapper->has(Domain::Unit, $this->id)) {
			$unit     = $mapper->getUnit($this->id);
			$this->id = $unit->Id();
		} else {
			$unit = Unit::get($this->id);
		}
		if ($unit->Party()->Type() !== Type::NPC) {
			throw new ParseException($unit . ' is no NPC unit.');
		}

		return $unit;
	}

	public function replaceIdArgument(): void {
		$id = (string)$this->id;
		if ($this->idArgument && $this->idArgument !== $id) {
			$this->scenarioFactory->replaceArguments($this->section, $id);
		}
		if (isset($this->values['ID']) && (string)$this->values['ID'] !== $id) {
			$this->values['ID'] = new Value($id);
		}
	}
}
