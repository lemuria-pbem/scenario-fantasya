<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Scene;

use Lemuria\Engine\Fantasya\Factory\MessageTrait;
use Lemuria\Engine\Fantasya\Factory\Model\AnyBuilding;
use Lemuria\Engine\Fantasya\Factory\Model\AnyCastle;
use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Building;
use Lemuria\Model\Fantasya\Building\AbstractCastle;
use Lemuria\Model\Fantasya\Building\Castle;
use Lemuria\Model\Fantasya\Construction;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Storage\Ini\Section;

class CreateConstruction extends AbstractCreate
{
	use BuilderTrait;
	use MessageTrait;

	private ?string $class;

	private Building $building;

	private int $size;

	private ?Region $region = null;

	public function parse(Section $section): static {
		parent::parse($section);
		$this->region = Region::get(Id::fromId($this->getValue('Region')));
		$this->class  = $section->Name();
		$building     = $this->getOptionalValue('Gebäude');
		if ($building) {
			$this->class = $building;
		}
		$this->size = (int)$this->getOptionalValue('Größe');
		$this->createBuilding();
		return $this;
	}

	public function play(): static {
		parent::play();
		$construction = new Construction();
		$id           = $this->createId(Domain::Construction);
		$construction->setId($id)->setBuilding($this->building);
		$this->region->Estate()->add($construction);

		$construction->setName($this->name ?? $this->building . ' ' . $id);
		if ($this->description) {
			$construction->setDescription($this->description);
		}
		$construction->setSize($this->size > 0 ? $this->size : 1);

		if ($this->id) {
			$this->mapper()->map($construction, $this->id);
		}
		Lemuria::Log()->debug('New construction ' . $construction . ' created.');

		return $this;
	}

	private function createBuilding(): void {
		$this->building = $this->factory()->building($this->class);
		if ($this->building::class === AnyBuilding::class) {
			throw new ParseException('Invalid building given: ' . $this->class);
		}
		if ($this->building::class === AnyCastle::class) {
			$this->building = AbstractCastle::forSize($this->size);
		} elseif ($this->building instanceof Castle) {
			while ($this->size < $this->building->MinSize()) {
				$this->building = $this->building->Downgrade();
			}
			while ($this->size > $this->building->MaxSize()) {
				$this->building = $this->building->Upgrade();
			}
		}
	}
}
