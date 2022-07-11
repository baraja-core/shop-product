<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category\Api\DTO;


use Baraja\EcommerceStandard\DTO\CurrencyInterface;
use Baraja\EcommerceStandard\DTO\PriceInterface;
use Baraja\EcommerceStandard\DTO\ProductInterface;
use Baraja\Shop\Price\Price;

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


	public static function createFromEntity(ProductInterface $product, CurrencyInterface $currency): self
	{
		return new ProductCategoryProductItemDTO(
			id: $product->getId(),
			name: $product->getLabel(),
			description: (string) $product->getShortDescription(),
			slug: $product->getSlug(),
			mainImage: $product->getMainImage()?->toArray(),
			secondaryImage: $product->getSecondaryImage()?->toArray(),
			price: new Price($product->getPrice(), $currency),
			pricePercentage: $product->getStandardPrice(),
			warehouse: self::renderWarehouseQuantity($product->getWarehouseAllQuantity()),
		);
	}


	private static function renderWarehouseQuantity(int $value): string
	{
		if ($value <= 0) {
			return '0';
		}
		if ($value <= 5) {
			return (string) $value;
		}

		return '>5';
	}
}
