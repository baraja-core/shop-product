<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\ProductFeed;


final class FeedStatistic
{
	public function __construct(
		private int $count,
		private float $minimalPrice,
		private float $maximalPrice,
	) {
	}


	public function getCount(): int
	{
		return $this->count;
	}


	public function getMinimalPrice(): float
	{
		return $this->minimalPrice;
	}


	public function getMaximalPrice(): float
	{
		return $this->maximalPrice;
	}
}
