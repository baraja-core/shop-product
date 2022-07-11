<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\DTO;


final class ProductCombinationFilterVariantResult
{
	public function __construct(
		public int $id,
		public string $hash,
		public string $text,
		public string $value,
	) {
	}
}
