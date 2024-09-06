<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Act;

use Lemuria\Engine\Fantasya\Exception\UnknownItemException;
use Lemuria\Engine\Fantasya\Factory\MessageTrait;
use Lemuria\Engine\Fantasya\Message\Unit\TravelMessage;
use Lemuria\Engine\Fantasya\Travel\Movement;
use Lemuria\Engine\Fantasya\Travel\Trip\Cruise;
use Lemuria\Engine\Fantasya\Travel\Trip\Seafarer;
use Lemuria\Lemuria;
use Lemuria\Model\Domain;
use Lemuria\Model\Fantasya\Extension\Quests;
use Lemuria\Model\Fantasya\Navigable;
use Lemuria\Model\Fantasya\Quantity;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Resources;
use Lemuria\Model\Fantasya\Scenario\Quest;
use Lemuria\Scenario\Fantasya\Factory\BuilderTrait;
use Lemuria\Scenario\Fantasya\Macro;
use Lemuria\Scenario\Fantasya\Quest\Controller\DemandPassage;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\Script\AbstractScene;
use Lemuria\Scenario\Fantasya\Script\TripTrait;
use Lemuria\Scenario\Fantasya\Script\VisitationTrait;

/**
 * Act: Schiffspassage(a, b, [Bezahlung])
 */
class Passage extends AbstractAct implements Seafarer
{
	use BuilderTrait;
	use MessageTrait;
	use TripTrait;
	use VisitationTrait;

	protected Region $from;

	protected Resources $payment;

	protected bool $isUnderway = true;

	private Quest $quest;

	/**
	 * @var array<Region>
	 */
	private array $route = [];

	public function __construct(AbstractScene $scene) {
		parent::__construct($scene);
		$this->payment = new Resources();
	}

	public function parse(Macro $macro): static {
		parent::parse($macro);

		$this->setStartFromUnit();
		$this->from = $this->parseDestination($macro->getParameter());
		$this->parseDestination($macro->getParameter(2));

		$factory = $this->scene->context()->Factory();
		$n       = $macro->count();
		try {
			for ($i = 3; $i <= $n; $i++) {
				$payment = explode(' ', $macro->getParameter($i));
				if (count($payment) === 2) {
					$commodity = $factory->commodity($payment[1]);
					$amount    = (int)$payment[0];
					if ($amount > 0) {
						$this->payment->add(new Quantity($commodity, $amount));
					}
				}
			}
		} catch (UnknownItemException $e) {
			$this->payment->clear();
			Lemuria::Log()->critical('Invalid payment parsed.', ['unit' => $this->unit, 'error' => $e->getMessage()]);
		}

		return $this;
	}

	public function play(): static {
		parent::play();
		if ($this->unit->Vessel()) {
			Lemuria::Log()->debug($this->unit . ' is still onboard ' . $this->unit->Vessel() . '.');
			return $this;
		}
		if ($this->start->Landscape() instanceof Navigable) {
			Lemuria::Log()->debug($this->unit . ' is still underway.');
			return $this;
		}

		if ($this->start !== $this->from) {
			$this->isUnderway = false;
			Lemuria::Log()->debug($this->unit . ' is not in starting region.');
			return $this;
		}

		$quest = $this->createQuest();
		$this->addVisitationEffect()->setPassage($quest);
		Cruise::engage($this);
		return $this;
	}

	public function getChainResult(): bool {
		return $this->isUnderway;
	}

	public function getId(): int {
		return $this->quest->Owner()->Id()->Id();
	}

	public function sailedTo(Region $region): void {
		$this->route[] = $region;
		/** @var DemandPassage $controller */
		$controller = $this->quest->Controller();
		$controller->setPayload($this->quest);
		if ($region === $controller->Destination() && $this->unit->Region() === $this->destination) {
			$this->message(TravelMessage::class, $this->unit)->p(Movement::Ship->name)->entities($this->route);
			$controller->callFrom($controller->Captain());
		}
	}

	protected function createQuest(): Quest {
		/** @var Quests $quests */
		$quests = $this->unit->Extensions()->init(Quests::class, fn() => new Quests($this->unit));
		/** @var DemandPassage $controller */
		$controller = self::createController(DemandPassage::class);
		foreach ($quests->getAll($controller) as $quest) {
			break;
		}
		if (!isset($quest)) {
			$quest = new Quest();
			$quest->setId(Lemuria::Catalog()->nextId(Domain::Quest));
			$quest->setOwner($this->unit)->setController($controller);
			$quests->add($quest);
		}
		$controller->setPayload($quest)->setDestination($this->destination)->setPayment($this->payment);
		$this->quest = $quest;
		return $quest;
	}
}
