<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Model;

class Statement
{
	protected string $key = '';

	public function __construct(protected string $rumour) {
	}

	public function Rumour(): string {
		return $this->rumour;
	}

	public function Key():string {
		return $this->key;
	}

	public function hasKey(): bool {
		return (bool)$this->key;
	}

	public function setKey(string $key): static {
		$this->key = $key;
		return $this;
	}
}
