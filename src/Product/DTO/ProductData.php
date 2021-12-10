<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\DTO;


final class ProductData
{
	public function __construct(
		private int $id,
		private string $name,
		private string $code,
		private ?string $ean,
		private string $slug,
		private bool $active,
		private string $shortDescription,
		private string $description,
		private float $price,
		private int $vat,
		private ?float $standardPricePercentage,
		private string $url,
		private bool $soldOut,
		private ?array $mainImage,
		private ?int $mainCategoryId,
		private array $customFields,
		private array $smartDescriptions,
		private array $categories,
	) {
	}
}
