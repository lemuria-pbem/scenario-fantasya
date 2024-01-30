<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script;

use Lemuria\Engine\Fantasya\Effect\WelcomeVisitor;
use Lemuria\Engine\Fantasya\Event\Obtainment;
use Lemuria\Engine\Fantasya\Event\Support;
use Lemuria\Engine\Fantasya\State;
use Lemuria\EntitySet;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Commodity\Silver;
use Lemuria\Model\Fantasya\Composition;
use Lemuria\Model\Fantasya\Composition\AbstractComposition;
use Lemuria\Model\Fantasya\Extension\QuestsWithPerson;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\HerbalBook;
use Lemuria\Model\Fantasya\Scenario\Quest;
use Lemuria\Model\Fantasya\Spell;
use Lemuria\Model\Fantasya\Talent;
use Lemuria\Model\Fantasya\Talent\Magic;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Scenario\Fantasya\Model\Visitation;
use Lemuria\Singleton;
use Lemuria\SingletonSet;

trait VisitationTrait
{
	use BuilderTrait;

	private static ?Talent $magic = null;

	private static ?SingletonSet $obtainmentSpells = null;

	private function getDefaultDemand(): SingletonSet {
		return AbstractComposition::ownable();
	}

	private function addVisitationEffect(): Visitation {
		$effect   = new WelcomeVisitor(State::getInstance());
		$existing = Lemuria::Score()->find($effect->setUnit($this->Unit()));
		if ($existing instanceof WelcomeVisitor) {
			$effect = $existing;
		} else {
			Lemuria::Score()->add($effect);
		}
		return Visitation::register($this->Unit(), $effect);
	}

	private function getMaterialValue(EntitySet|Singleton|SingletonSet $item): int {
		if ($item instanceof Composition) {
			// Material value of a composition is amount of silver.
			foreach ($item->getMaterial() as $quantity) {
				if ($quantity->Commodity() instanceof Silver) {
					return $quantity->Count();
				}
			}
			return 0;
		}

		if ($item instanceof EntitySet) {
			if ($item instanceof HerbalBook) {
				// Value of herbal book is amount of silver needed to explore number of regions.
				return Support::SILVER * $item->count() * 2;
			}
			return 0;
		}

		// Calculate expense for learning Magic to be able to cast the spells.
		if ($item instanceof Singleton) {
			if ($item instanceof Spell) {
				$item = [$item];
			} else {
				return 0;
			}
		}
		$value = 0;
		if (!self::$magic) {
			self::$magic            = self::createTalent(Magic::class);
			self::$obtainmentSpells = Obtainment::defaultSpells();
		}
		foreach ($item as $spell) {
			if ($spell instanceof Spell) {
				if (!isset(self::$obtainmentSpells[$spell])) {
					$difficulty = $spell->Difficulty();
					for ($level = 1; $level <= $difficulty; $level++) {
						$expense  = self::$magic->getExpense($level);
						$value   += $expense;
					}
				}
			}
		}
		return $value;
	}

	private function offerQuestTo(Quest $quest, Unit $unit): void {
		$extensions = $unit->Party()->Extensions();
		if ($extensions->offsetExists(QuestsWithPerson::class)) {
			$quests = $extensions->offsetGet(QuestsWithPerson::class);
		} else {
			$quests = new QuestsWithPerson();
			$extensions->add($quests);
		}
		$quests->add($quest, $unit);
	}
}
