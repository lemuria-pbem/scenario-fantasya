<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use Lemuria\Engine\Fantasya\Context;
use Lemuria\Engine\Fantasya\Exception\UnknownItemException;
use Lemuria\Engine\Fantasya\Factory\CommandFactory;
use Lemuria\Exception\SingletonException;
use Lemuria\Lemuria;
use Lemuria\Scenario\Fantasya\Exception\ParseException;
use Lemuria\Scenario\Fantasya\Exception\UnknownActException;
use Lemuria\Scenario\Fantasya\Exception\UnknownSceneException;
use Lemuria\Scenario\Fantasya\Script\AbstractAct;
use Lemuria\Scenario\Fantasya\Script\AbstractScene;
use Lemuria\Scenario\Fantasya\Script\Act\Hearsay;
use Lemuria\Scenario\Fantasya\Script\Act\Market;
use Lemuria\Scenario\Fantasya\Script\Act\Merchant;
use Lemuria\Scenario\Fantasya\Script\Act\Roundtrip;
use Lemuria\Scenario\Fantasya\Script\Act\Trip;
use Lemuria\Scenario\Fantasya\Script\Scene\Create\CreateConstruction;
use Lemuria\Scenario\Fantasya\Script\Scene\Create\CreateUnit;
use Lemuria\Scenario\Fantasya\Script\Scene\Create\CreateVessel;
use Lemuria\Scenario\Fantasya\Script\Scene\SetOrders;
use Lemuria\Scenario\Fantasya\Script\Scene\SpreadRumour;
use Lemuria\Storage\Ini\Section;

class Factory
{
	/**
	 * @type array<string, string>
	 */
	protected const array SCENE = [
		'Burg'    => CreateConstruction::class,
		'Einheit' => CreateUnit::class,
		'Gebäude' => CreateConstruction::class,
		'Gerücht' => SpreadRumour::class,
		'Schiff'  => CreateVessel::class,
		'Skript'  => SetOrders::class
	];

	/**
	 * @type array<string, string>
	 */
	protected const array ACT = [
		'Gerüchte'   => Hearsay::class,
		'Händler'    => Merchant::class,
		'Marktstand' => Market::class,
		'Reise'      => Trip::class,
		'Rundreise'  => Roundtrip::class
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
		$name  = $section->Name();
		$space = strpos($name, ' ');
		if ($space > 1) {
			$arguments = trim(substr($name, $space));
			$name      = substr($name, 0, $space);
		} else {
			$arguments = '';
		}

		$class = self::SCENE[$name] ?? null;
		if (!$class) {
			try {
				$this->factory->building($name);
				$class = CreateConstruction::class;
			} catch (SingletonException|UnknownItemException) {
				throw new UnknownSceneException($name);
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
}
