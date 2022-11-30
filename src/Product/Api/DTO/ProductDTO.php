<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Api\DTO;


use Baraja\Shop\Product\Category\Api\DTO\ProductCategoryDTO;
use Baraja\Shop\Product\Category\Api\DTO\ProductCategoryProductItemDTO;
use Baraja\Shop\Product\DTO\ProductCombinationFilterResult;

final class ProductDTO
{
	/**
	 * @param array<int, ProductCategoryDTO> $categoryPath
	 * @param array<int, array{
	 *     id: int,
	 *     title: string,
	 *     description: string,
	 *     imageUrl: string|null,
	 *     color: string
	 * }> $smartDescriptions
	 * @param array{source: string, title: string, url: string}|null $mainImage
	 * @param array<int, array{source: string, title: string, url: string}> $images
	 * @param array{
	 *     id: int,
	 *     slug: string,
	 *     name: string,
	 *     country: array{id: int, code: string, name: string}|null
	 * }|null $brand
	 * @param ProductCombinationFilterResult $combinationFilter
	 * @param array<int, ProductCategoryProductItemDTO> $sameCategoryProducts
	 * @param non-empty-array<string, array<int, array{value: string}>> $parameters
	 * @param array<int, ProductCategoryProductItemDTO> $relatedProducts
	 * @param array<string, string> $colorMap
	 * @param array<int, ProductTagDTO> $tags
	 */
	public function __construct(
		public int $id,
		public string $slug,
		public string $name,
		public ?ProductCategoryDTO $mainCategory,
		public array $categoryPath,
		public string $description,
		public string $shortDescription,
		public array $smartDescriptions,
		public bool $isVariantProduct,
		public bool $isSoldOut,
		public ?array $mainImage,
		public array $images,
		public ?array $brand,
		public ProductCombinationFilterResult $combinationFilter,
		public array $sameCategoryProducts,
		public array $parameters,
		public array $tags,
		public array $relatedProducts,
		public array $colorMap,
	) {
	}
}
