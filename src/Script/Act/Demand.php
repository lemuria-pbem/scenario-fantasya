<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Lemuria;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\Script\VisitationTrait;
use Lemuria\SingletonSet;

/**
 * Act: Ankauf(…)
 */
class Demand extends AbstractAct
{
	use VisitationTrait;

	private SingletonSet $compositions;

	public function parse(Macro $macro): static {
		parent::parse($macro);
		$this->compositions = new SingletonSet();
		$factory            = $this->scene->context()->Factory();
		foreach ($macro->getParameters() as $parameter) {
			if ($factory->isComposition($parameter)) {
				$composition = $factory->composition($parameter);
				$this->compositions->add($composition);
				Lemuria::Log()->debug('Unit ' . $this->unit . ' will make offers for ' . $composition . '.');
			} else {
				Lemuria::Log()->critical('Parameter ' . $parameter . ' is no Composition.');
			}
		}
		if ($this->compositions->isEmpty()) {
			$this->compositions->fill($this->getDefaultDemand());
			Lemuria::Log()->debug('Unit ' . $this->unit . ' will make offers for any unicum.');
		}
		return $this;
	}

	public function play(): static {
		parent::play();
		$this->addVisitationEffect()->Demand()->fill($this->compositions);
		return $this;
	}

	public function getChainResult(): bool {
		return true;
	}
}
