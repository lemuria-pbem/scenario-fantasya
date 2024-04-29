<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Model;

class Statement
{
	protected string $key = '';

	protected string $party = '';

	protected string $region = '';

	public function __construct(protected string $rumour) {
	}

	public function Rumour(): string {
		return $this->rumour;
	}

	public function Party(): string {
		return $this->party;
	}

	public function Region(): string {
		return $this->region;
	}

	public function Key():string {
		return $this->key;
	}

	public function hasParty(): bool {
		return (bool)$this->party;
	}

	public function setParty(string $party): static {
		$this->party = $party;
		return $this;
	}

	public function hasRegion(): bool {
		return (bool)$this->region;
	}

	public function setRegion(string $region): static {
		$this->region = $region;
		return $this;
	}

	public function hasKey(): bool {
		return (bool)$this->key;
	}

	public function setKey(string $key): static {
		$this->key = $key;
		return $this;
	}
}
