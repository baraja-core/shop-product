<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\DTO;


final class ProductData
{
	/**
	 * @param array{source: string, title: string, url: string}|null $mainImage
	 * @param array<int, array{id: int|null, name: string, type: string, label: non-empty-string|null, value: non-empty-string|null, description: non-empty-string|null, required: bool}> $customFields
	 * @param array<int, array{id: int, description: string, html: string, image: string|null, color: string|null, position: int}> $smartDescriptions
	 * @param array<int, array{value: int, text: string}> $categories
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
		public float $price,
		public float $vat,
		public ?float $standardPricePercentage,
		public ?string $url,
		public bool $soldOut,
		public ?array $mainImage = null,
		public ?int $mainCategoryId = null,
		public array $customFields = [],
		public array $smartDescriptions = [],
		public array $categories = [],
	) {
	}
}
