<?php
declare(strict_types = 1);
namespace Lemuria\Scenario\Fantasya;

class Macro implements \Countable, \Stringable
{
	/**
	 * @var array<string>
	 */
	protected array $parts;

	public static function parse(string $line): ?self {
		if (preg_match('/^[A-ZÄÖÜ][A-ZÄÖÜa-zäöüß_]+\([^()]*\)$/', $line, $matches) === 1) {
			return new self($matches);
		}
		return null;
	}

	protected function __construct(array $matches) {
		$this->parts = [$matches[1]];
		$parameters  = trim(substr($matches[2], 1, -1));
		if ($parameters) {
			foreach (explode(',', $parameters) as $parameter) {
				$parameter = trim($parameter);
				if ($parameter) {
					$this->parts[] = $parameter;
				}
			}
		}
	}

	/**
	 * Get number of parameters.
	 */
	public function count(): int {
		return count($this->parts) - 1;
	}

	/**
	 * Get the macro.
	 */
	public function __toString(): string {
		$parameters = $this->parts;
		$act        = array_shift($parameters);
		return $act . '(' . implode(', ', $parameters) . ')';
	}

	public function getAct(): string {
		return $this->parts[0];
	}

	public function getParameter(int $number = 1): string {
		if ($number < 1) {
			$number = $this->count();
		}
		return $this->parts[$number] ?? '';
	}

	public function getParameters(): array {
		return array_slice($this->parts, 1);
	}
}
