<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Scene\Create;

use Lemuria\Engine\Fantasya\Factory\MessageTrait;
use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Construction;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Unicum;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Model\Fantasya\Vessel;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Storage\Ini\Section;

class CreateUnicum extends AbstractCreate
{
	use BuilderTrait;
	use MessageTrait;

	private ?string $composition;

	private ?Unit $unit = null;

	private ?Region $region = null;

	private ?Construction $construction = null;

	private ?Vessel $vessel = null;

	public function parse(Section $section): static {
		parent::parse($section);
		$this->composition = $section->Name();
		$composition = $this->getOptionalValue('Gegenstand');
		if ($composition) {
			$this->composition = $composition;
		}
		$unit = $this->getOptionalValue('Einheit');
		if ($unit) {
			$this->unit = Unit::get($this->mapper()->forAlias(Domain::Unit, Id::fromId($unit)));
		}
		$region = $this->getOptionalValue('Region');
		if ($region) {
			$this->region = Region::get(Id::fromId($region));
		}
		$construction = $this->getOptionalValue('Gebäude');
		if ($construction) {
			$this->construction = Construction::get($this->mapper()->forAlias(Domain::Construction, Id::fromId($construction)));
		}
		$vessel = $this->getOptionalValue('Schiff');
		if ($vessel) {
			$this->vessel = Vessel::get($this->mapper()->forAlias(Domain::Vessel, Id::fromId($vessel)));
		}
		if (!$unit && !$region && !$construction && !$vessel) {
			throw new ParseException('One of unit|region|construction|vessel must be given.');
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		$unicum = new Unicum();
		$id     = $this->createId(Domain::Unicum);
		$unicum->setId($id)->setComposition($this->factory()->composition($this->composition)->init());
		if ($this->unit) {
			$this->unit->Treasury()->add($unicum);
		} elseif ($this->construction) {
			$this->construction->Treasury()->add($unicum);
		} elseif ($this->vessel) {
			$this->vessel->Treasury()->add($unicum);
		} elseif ($this->region) {
			$this->region->Treasury()->add($unicum);
		}

		if ($this->name) {
			$unicum->setName($this->name);
		}
		if ($this->description) {
			$unicum->setDescription($this->description);
		}

		if ($this->id) {
			$this->mapper()->map($unicum, $this->id);
		}
		Lemuria::Log()->debug('New unicum ' . $unicum . ' created.');

		return $this;
	}
}
