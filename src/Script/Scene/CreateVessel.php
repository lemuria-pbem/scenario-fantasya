<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Scene;

use Lemuria\Engine\Fantasya\Factory\MessageTrait;
use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Vessel;
use Lemuria\Storage\Ini\Section;

class CreateVessel extends AbstractCreate
{
	use BuilderTrait;
	use MessageTrait;

	private ?string $ship;

	private ?Region $region = null;

	public function parse(Section $section): static {
		parent::parse($section);
		$this->region = Region::get(Id::fromId($this->getValue('Region')));
		$this->ship   = $section->Name();
		$ship         = $this->getOptionalValue('Schiff');
		if ($ship) {
			$this->ship = $ship;
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		$vessel = new Vessel();
		$id     = $this->createId(Domain::Vessel);
		$vessel->setId($id);
		$vessel->setShip($this->factory()->ship($this->ship));
		$this->region->Fleet()->add($vessel);

		$vessel->setName($this->name ?? $this->ship . ' ' . $id);
		if ($this->description) {
			$vessel->setDescription($this->description);
		}

		if ($this->id) {
			$this->mapper()->map($vessel, $this->id);
		}
		Lemuria::Log()->debug('New vessel ' . $vessel . ' created.');

		return $this;
	}
}
