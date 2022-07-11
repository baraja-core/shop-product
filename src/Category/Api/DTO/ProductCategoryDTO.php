<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category\Api\DTO;


use Baraja\Shop\Product\Entity\ProductCategory;

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


	public static function createFromEntity(ProductCategory $category): self
	{
		return new self(
			id: $category->getId(),
			name: $category->getLabel(),
			description: (string) $category->getDescription(),
			slug: $category->getSlug(),
			mainPhotoUrl: $category->getMainPhotoUrl(),
			mainThumbnailUrl: $category->getMainThumbnailUrl(),
		);
	}
}
