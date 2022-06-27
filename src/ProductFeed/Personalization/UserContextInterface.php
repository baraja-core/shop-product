<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\ProductFeed\Personalization;


interface UserContextInterface
{
	/**
	 * @return array<int, int>
	 */
	public function getProductIds(int $userId): array;
}
