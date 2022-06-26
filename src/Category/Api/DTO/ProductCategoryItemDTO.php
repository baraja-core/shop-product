<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category\Api\DTO;


final class ProductCategoryItemDTO
{
	/**
	 * @param array<int, self>|null $children
	 */
	public function __construct(
		public int $id,
		public string $name,
		public string $slug,
		public ?array $children = null,
	) {
	}
}
