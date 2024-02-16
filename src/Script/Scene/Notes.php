<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya\Script\Scene;

use Lemuria\Scenario\Fantasya\Script\AbstractScene;
use Lemuria\Storage\Ini\Section;

/**
 * Notes are just comments to be placed in script files.
 */
class Notes extends AbstractScene
{
	public function prepareNext(): ?Section {
		return $this->section;
	}
}
