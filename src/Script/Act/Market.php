<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Engine\Fantasya\Command\Alternative;
use Lemuria\Engine\Fantasya\Command\Trespass\Enter;
use Lemuria\Engine\Fantasya\Command\Vacate\Leave;
use Lemuria\Engine\Fantasya\Phrase;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Building\Market as MarketBuilding;
use Lemuria\Model\Fantasya\Estate;
use Lemuria\Model\Fantasya\Extension\Trades;
use Lemuria\Scenario\Fantasya\Engine\Event\MarketTrade;
use Lemuria\Scenario\Fantasya\Engine\Event\TravelCommands;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\Script\Scene\SetOrders;
use Lemuria\Scenario\Fantasya\Script\VisitationTrait;
use Lemuria\Storage\Ini\Values;

/**
 * Act: Marktstand(N)
 */
class Market extends AbstractAct
{
	use VisitationTrait;

	private const string LAST_TRADE = 'LetzterHandel';

	private Values $values;

	private int $maxRounds;

	private int $lastTrade;

	public function __construct(SetOrders $scene) {
		parent::__construct($scene);
		$this->values = $scene->Section()->Values();
	}

	public function parse(Macro $macro): static {
		parent::parse($macro);
		$this->maxRounds = (int)$macro->getParameter();
		$lastTrade       = $this->values[self::LAST_TRADE] ?? null;
		$this->lastTrade = (int)($lastTrade?->__toString());
		return $this;
	}

	public function play(): static {
		parent::play();
		$context = $this->scene->context();
		$unit    = $context->Unit();
		if ($this->isInMarket()) {
			MarketTrade::register($this);
			if (!$this->getChainResult()) {
				$leave = new Leave(new Phrase('VERLASSEN'), $context);
				State::getInstance()->injectIntoTurn($leave);
			} else {
				$this->addVisitationEffect();
				TravelCommands::cancelTravelFor($unit);
			}
		} else {
			$region  = $unit->Region();
			$markets = new Estate();
			foreach ($region->Estate() as $construction) {
				if ($construction->Building() instanceof MarketBuilding) {
					$markets->add($construction);
				}
			}

			if (!$markets->isEmpty()) {
				$state  = State::getInstance();
				$market = $markets->random();
				$enter  = new Enter(new Phrase('BETRETEN ' . $market->Id()), $context);
				$state->injectIntoTurn($enter);
				Lemuria::Log()->debug('Market act: ' . $unit . ' will enter market ' . $market . '.');
				if ($markets->count() > 1) {
					// TODO: Enter logic could be improved to consider all markets.
					Lemuria::Log()->debug('Market act in ' . $region . ' chose the first market, there are more.');
				}
				$learn = new Alternative(new Phrase('ALTERNATIVE LERNEN Handeln'), $context);
				$state->injectIntoTurn($learn);
				$this->addVisitationEffect();
				TravelCommands::cancelTravelFor($unit);
				$this->lastTrade = 0;
			}
		}

		return $this->addToChain();
	}

	public function getChainResult(): bool {
		if ($this->isInMarket()) {
			return $this->maxRounds <= 0 || $this->lastTrade <= $this->maxRounds;
		}
		return false;
	}

	public function prepareNext(): static {
		unset($this->values[self::LAST_TRADE]);
		$this->values[self::LAST_TRADE] = (string)$this->lastTrade;
		return parent::prepareNext();
	}

	public function setTrades(Trades $trades): void {
		if ($trades->count() > 0) {
			$this->lastTrade = 0;
		} else {
			$this->lastTrade++;
		}
	}

	protected function isInMarket(): bool {
		return $this->unit->Construction()?->Building() instanceof MarketBuilding;
	}
}
