<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category\Api\DTO;


final class ProductCategoryDTO
{
	public function __construct(
		public int $id,
		public string $name,
		public string $description,
		public string $slug,
		public ?string $mainPhotoUrl = null,
		public ?string $mainThumbnailUrl = null,
	) {
	}
}
