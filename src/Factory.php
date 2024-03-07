<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Engine\Fantasya\Context;
use Lemuria\Engine\Fantasya\Exception\UnknownItemException;
use Lemuria\Engine\Fantasya\Factory\CommandFactory;
use Lemuria\Exception\SingletonException;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Building\Cave;
use Lemuria\Model\Fantasya\Building\Ruin;
use Lemuria\Model\Fantasya\Building\Shop;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Exception\UnknownActException;
use Lemuria\Scenario\Fantasya\Exception\UnknownSceneException;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\Script\AbstractScene;
use Lemuria\Scenario\Fantasya\Script\Act\Demand;
use Lemuria\Scenario\Fantasya\Script\Act\Follow;
use Lemuria\Scenario\Fantasya\Script\Act\Hearsay;
use Lemuria\Scenario\Fantasya\Script\Act\Market;
use Lemuria\Scenario\Fantasya\Script\Act\Merchant;
use Lemuria\Scenario\Fantasya\Script\Act\Passage;
use Lemuria\Scenario\Fantasya\Script\Act\Roundtrip;
use Lemuria\Scenario\Fantasya\Script\Act\Trip;
use Lemuria\Scenario\Fantasya\Script\Scene\Create\CreateConstruction;
use Lemuria\Scenario\Fantasya\Script\Scene\Create\CreateUnicum;
use Lemuria\Scenario\Fantasya\Script\Scene\Create\CreateUnit;
use Lemuria\Scenario\Fantasya\Script\Scene\Create\CreateVessel;
use Lemuria\Scenario\Fantasya\Script\Scene\Notes;
use Lemuria\Scenario\Fantasya\Script\Scene\SetOrders;
use Lemuria\Scenario\Fantasya\Script\Scene\SpreadRumour;
use Lemuria\Storage\Ini\Section;
use function Lemuria\mbUcFirst;

class Factory
{
	/**
	 * @type array<string, string>
	 */
	protected const array SCENE = [
		'Burg'       => CreateConstruction::class,
		'Einheit'    => CreateUnit::class,
		'Gebäude'    => CreateConstruction::class,
		'Gegenstand' => CreateUnicum::class,
		'Gerücht'    => SpreadRumour::class,
		'Notizen'    => Notes::class,
		'Schiff'     => CreateVessel::class,
		'Skript'     => SetOrders::class
	];

	/**
	 * @type array<string, string>
	 */
	protected const array ACT = [
		'Ankauf'         => Demand::class,
		'Folgen'         => Follow::class,
		'Gerüchte'       => Hearsay::class,
		'Händler'        => Merchant::class,
		'Marktstand'     => Market::class,
		'Reise'          => Trip::class,
		'Rundreise'      => Roundtrip::class,
		'Schiffspassage' => Passage::class
	];

	protected const array BUILDING = [
		'Geschäft'  => Shop::class,
		'Geschaeft' => Shop::class,
		'Höhle'     => Cave::class,
		'Hoehle'    => Cave::class,
		'Ruine'     => Ruin::class
	];

	private CommandFactory $factory;

	public function __construct(private readonly Context $context) {
		$this->factory = new CommandFactory($context);
	}

	public function Context(): Context {
		return $this->context;
	}

	/**
	 * @throws ParseException
	 * @throws UnknownSceneException
	 */
	public function createScene(Script $script, Section $section): Scene {
		$name      = $section->Name();
		$arguments = '';

		$space = strpos($name, ' ');
		if ($space > 1) {
			$class = self::SCENE[substr($name, 0, $space)] ?? null;
			if ($class) {
				$arguments = trim(substr($name, $space));
			}
		} else {
			$class = self::SCENE[$name] ?? null;
		}

		if (!$class) {
			if ($this->factory->isComposition($name)) {
				$class = CreateUnicum::class;
			} else {
				$class = $this->tryBuildingCreation($name);
				if (!$class) {
					$class = $this->tryShipCreation($name);
					if (!$class) {
						throw new UnknownSceneException($name);
					}
				}
			}
		}
		/** @var AbstractScene $scene */
		$scene = new $class($this, $script);
		$scene->setArguments($arguments);
		return $scene->parse($section);
	}

	public function createAct(AbstractScene $scene, Macro $macro): Act {
		$name = $macro->getAct();
		$class = self::ACT[$name] ?? null;
		if (!$class) {
			throw new UnknownActException($name);
		}
		/** @var AbstractAct $act */
		$act = new $class($scene);
		return $act->parse($macro);
	}

	public function parseBuilding(string $name): ?string {
		$candidate = mbUcFirst(mb_strtolower($name));
		return self::BUILDING[$candidate] ?? null;
	}

	/**
	 * @throws UnknownSceneException
	 */
	public function replaceArguments(Section $section, string $arguments): void {
		$name  = $section->Name();
		$space = strpos($name, ' ');
		if ($space > 1) {
			$name = substr($name, 0, $space);
		}
		if (isset(self::SCENE[$name])) {
			$section->setName($name . ' ' . $arguments);
			Lemuria::Log()->debug('Arguments in section ' . $name . ' replaced with ' . $arguments . '.');
		} else {
			throw new UnknownSceneException($name);
		}
	}

	private function tryBuildingCreation(string $name): ?string {
		if ($this->parseBuilding($name)) {
			return CreateConstruction::class;
		}
		try {
			$this->factory->building($name);
			return CreateConstruction::class;
		} catch (SingletonException|UnknownItemException) {
			return null;
		}
	}

	private function tryShipCreation(string $name): ?string {
		try {
			$this->factory->ship($name);
			return CreateVessel::class;
		} catch (SingletonException|UnknownItemException) {
			return null;
		}
	}
}
