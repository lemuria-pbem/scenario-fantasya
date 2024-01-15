<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Model;

use Lemuria\Model\Fantasya\Kind;
use Lemuria\Model\Fantasya\Market\Trade;

final class GoodKinds
{
	/**
	 * @var array<string, Kind>
	 */
	private array $offer = [];

	/**
	 * @var array<string, Kind>
	 */
	private array $demand = [];

	public function Offer(): array {
		return $this->offer;
	}

	public function Demand(): array {
		return $this->demand;
	}

	public function addFrom(Trade $trade): void {
		$kind = Kind::forCommodity($trade->Goods()->Commodity());
		if ($kind) {
			$this->offer[$kind->name] = $kind;
		}
		$kind = Kind::forCommodity($trade->Price()->Commodity());
		if ($kind) {
			$this->demand[$kind->name] = $kind;
		}
	}
}