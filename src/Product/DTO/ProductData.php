<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\DTO;


final class ProductData
{
	/**
	 * @param numeric-string $price
	 * @param numeric-string $vat
	 * @param numeric-string|null $standardPricePercentage
	 * @param array{source: string, title: string, url: string}|null $mainImage
	 * @param array<int, array{id: int|null, name: string, type: string, label: non-empty-string|null, value: non-empty-string|null, description: non-empty-string|null, required: bool}> $customFields
	 * @param array<int, array{id: int, description: string, html: string, image: string|null, color: string|null, position: int}> $smartDescriptions
	 * @param array<int, array{value: int|null, text: string}> $categories
	 * @param array<int, array{value: int|null, text: string}> $brands
	 */
	public function __construct(
		public int $id,
		public string $name,
		public string $code,
		public ?string $ean,
		public string $slug,
		public bool $active,
		public string $shortDescription,
		public string $description,
		public string $price,
		public string $vat,
		public ?string $standardPricePercentage,
		public ?string $url,
		public bool $soldOut,
		public bool $showInFeed,
		public string $mainCurrency,
		public ?array $mainImage = null,
		public ?int $mainCategoryId = null,
		public ?int $brandId = null,
		public array $customFields = [],
		public array $smartDescriptions = [],
		public array $categories = [],
		public array $brands = [],
	) {
	}
}
