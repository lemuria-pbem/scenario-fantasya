<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Model;

use Lemuria\Model\Fantasya\Landmass;

readonly class Rumour
{
	protected Landmass $area;

	protected \ArrayObject $incidents;

	public function __construct(protected Myth $myth) {
		$this->area      = new Landmass();
		$this->incidents = new \ArrayObject();
	}

	public function Myth(): Myth {
		return $this->myth;
	}

	public function Area(): Landmass {
		return $this->area;
	}

	public function Incidents(): \ArrayObject {
		return $this->incidents;
	}
}
