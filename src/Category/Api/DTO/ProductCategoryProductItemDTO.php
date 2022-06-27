<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category\Api\DTO;


use Baraja\EcommerceStandard\DTO\PriceInterface;

final class ProductCategoryProductItemDTO
{
	/**
	 * @param array{source: string, title: string, url: string}|null $mainImage
	 * @param array{source: string, title: string, url: string}|null $secondaryImage
	 */
	public function __construct(
		public int $id,
		public string $name,
		public string $description,
		public string $slug,
		public ?array $mainImage,
		public ?array $secondaryImage,
		public PriceInterface $price,
		public string $pricePercentage,
		public string $warehouse,
	) {
	}
}
