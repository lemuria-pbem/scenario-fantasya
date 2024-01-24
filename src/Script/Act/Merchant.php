<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Engine\Fantasya\Effect\VisitEffect;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Scenario\Fantasya\Engine\Event\FinishMerchants;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\Script\VisitationTrait;

/**
 * Act: Händler()
 */
class Merchant extends AbstractAct
{
	use VisitationTrait;

	public function play(): static {
		parent::play();
		FinishMerchants::register($this);
		$this->addVisitationEffect();
		return $this->addToChain();
	}

	public function getChainResult(): bool {
		return true;
	}

	public function finish(): void {
		//TODO logging
		if (!$this->unit->Construction()) {
			$effect   = new VisitEffect(State::getInstance());
			$existing = Lemuria::Score()->find($effect->setUnit($this->unit));
			if ($existing instanceof VisitEffect) {
				$effect = $existing;
			} else {
				Lemuria::Score()->add($effect);
			}
			$effect->setEverybody();
		}
	}
}
