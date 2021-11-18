<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


final class BeautifulPrice
{
	public function __construct(
		private float $price,
		private bool $preferEndZero = false,
	) {
		if ($price < 0) {
			throw new \InvalidArgumentException(sprintf('Price can not be negative, but "%s" given.', $price));
		}
	}


	public static function from(float $price): self
	{
		return new self($price);
	}


	public function toInt(): int
	{
		$price = (int) ceil($this->price);
		$this->price = $price;

		return $price;
	}


	public function smartRound(): float
	{
		$price = (int) ceil($this->price);
		$last = (int) substr((string) $price, -1);
		if ($last > 1 && $last <= 6) {
			$price -= $last;
		} else {
			$price += ($this->preferEndZero ? 10 : 9) - $last;
		}
		$this->price = $price;

		return $price;
	}
}
