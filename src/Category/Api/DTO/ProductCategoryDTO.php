<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category\Api\DTO;


use Baraja\Shop\Product\Entity\ProductCategory;

final class ProductCategoryDTO
{
	public function __construct(
		public int $id,
		public string $name,
		public ?string $description,
		public string $slug,
		public ?string $mainPhotoUrl = null,
		public ?string $mainThumbnailUrl = null,
	) {
	}


	public static function createFromEntity(ProductCategory $category): self
	{
		$description = trim(strip_tags((string) $category->getDescription()));

		return new self(
			id: $category->getId(),
			name: $category->getLabel(),
			description: $description !== '' ? $description : null,
			slug: $category->getSlug(),
			mainPhotoUrl: $category->getMainPhotoUrl(),
			mainThumbnailUrl: $category->getMainThumbnailUrl(),
		);
	}
}
