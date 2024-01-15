<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

use function Lemuria\getClass;
use Lemuria\Engine\Fantasya\Factory\GrammarTrait;
use Lemuria\Engine\Fantasya\Message\Casus;
use Lemuria\Item;
use Lemuria\Singleton;

trait TranslateTrait
{
	use GrammarTrait;

	protected function translateReplace(string $translation, string $variable, Singleton|string $object): ?string {
		$name = str_replace('$', '\\$', $variable);
		if (preg_match('|{([gr])/([a-z]+):([^:]+):' . $name . '}|', $translation, $matches) === 1) {
			$match = $matches[0];
			$casus = Casus::from($matches[2]);
			if ($matches[1] === 'g') {
				return str_replace($match, $this->replaceGrammar($casus, $matches[3], $object), $translation);
			}
			return str_replace($match, $this->replaceGrammarSingleton($casus, $matches[3], $object), $translation);
		}
		if (preg_match('/({[^:]+:' . $name . ')+/', $translation, $matches) === 1) {
			$match = $matches[1];
			return str_replace($match, $this->replacePrefix($match, $object), $translation);
		}
		if (preg_match('/({' . $name . ':[^}]+})+/', $translation, $matches) === 1) {
			$match = $matches[1];
			return str_replace($match, $this->replaceSuffix($match, $object), $translation);
		}
		if (preg_match('/({[^=]+=' . $name . '})+/', $translation, $matches) === 1) {
			$match = $matches[1];
			return str_replace($match, $this->replace($match, $object), $translation);
		}
		return str_replace($variable, (string)$object, $translation);
	}

	protected function translateKey(string $keyPath, ?int $index = null): ?string {
		$this->initDictionary();
		$translation = $this->dictionary->get($keyPath, $index);
		if ($index !== null) {
			$keyPath .= '.' . $index;
		}
		return $translation === $keyPath ? null : $translation;
	}

	private function replacePrefix(string $match, mixed $object): string {
		$parts = explode(':', substr($match, 1, strlen($match) - 2));
		$key   = $parts[0];
		if ($object instanceof Singleton) {
			return $this->translateKey('replace.' . $key . '.' . getClass($object)) . ' ' . $parts[1];
		}
		if ($object instanceof Item) {
			return $this->translateKey('replace.' . $key, $object->Count() === 1 ? 0 : 1) . ' ' . $parts[1];
		}
		if (is_int($object)) {
			return $this->translateKey('replace.' . $key, $object === 1 ? 0 : 1) . ' ' . $parts[1];
		}
		return '{' . $parts[0] . '}' . ' ' . $parts[1];
	}

	private function replaceSuffix(string $match, mixed $object): string {
		$parts = explode(':', substr($match, 1, strlen($match) - 2));
		$key   = $parts[1];
		if (is_int($object)) {
			return $parts[0] . ' ' . $this->translateKey('replace.' . $key, $object === 1 ? 0 : 1);
		}
		if ($object instanceof Item) {
			return $parts[0] . ' ' . $this->translateKey('replace.' . $key, $object->Count() === 1 ? 0 : 1);
		}
		return $parts[0] . ' ' . '{' . $parts[1] . '}';
	}

	private function replaceGrammar(Casus $casus, string $search, mixed $object): string {
		if (!($object instanceof Singleton)) {
			$object = (string)$object;
		}
		return $this->combineGrammar($object, $search, $casus);
	}

	private function replaceGrammarSingleton(Casus $casus, string $search, mixed $object): string {
		if (!($object instanceof Singleton)) {
			$object = (string)$object;
		}
		return $this->replaceSingleton($object, $search, $casus);
	}

	private function replace(string $match, mixed $object): string {
		$parts = explode('=', substr($match, 1, strlen($match) - 2));
		$key   = $parts[0];
		if ($object instanceof Singleton) {
			return $this->translateKey('replace.' . $key . '.' . getClass($object));
		}
		return '{' . $parts[0] . '}' . ' ' . $parts[1];
	}
}
