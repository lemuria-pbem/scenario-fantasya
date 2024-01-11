<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Scene;

use Lemuria\Exception\IdException;
use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Domain;
use Lemuria\Scenario\Fantasya\Exception\DuplicateUnitException;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Script\AbstractScene;
use Lemuria\Storage\Ini\Section;

abstract class AbstractCreate extends AbstractScene
{
	protected ?Id $id = null;

	protected ?string $name = null;

	protected ?string $description = null;

	public function parse(Section $section): static {
		parent::parse($section);
		$this->id          = $this->parseId($this->getOptionalValue('ID'));
		$this->name        = $this->getOptionalValue('Name');
		$this->description = $this->getOptionalValue('Beschreibung');
		return $this;
	}

	public function prepareNext(): ?Section {
		if ($this->hasRound()) {
			return $this->Section();
		}
		return null;
	}

	/**
	 * @throws ParseException
	 */
	protected function parseId(?string $id): ?Id {
		if ($id) {
			$lcId = strtolower($id);
			try {
				return Id::fromId($lcId);
			} catch (IdException $e) {
				throw new ParseException('Invalid ID: ' . $id, previous: $e);
			}
		}
		return null;
	}

	/**
	 * @throws DuplicateUnitException
	 */
	protected function createId(Domain $domain): Id {
		if ($this->id) {
			if ($this->mapper()->has($domain, $this->id)) {
				throw new DuplicateUnitException($this->id);
			}
			if (!Lemuria::Catalog()->has($this->id, $domain)) {
				return $this->id;
			}
		}
		return Lemuria::Catalog()->nextId($domain);
	}
}
