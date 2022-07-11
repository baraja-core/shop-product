<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\DTO;


use Baraja\EcommerceStandard\DTO\PriceInterface;

final class ProductCombinationFilterResult
{
	/**
	 * @param array<int, array{
	 *     variantId: int,
	 *     hash: string,
	 *     available: bool,
	 *     price: string,
	 *     regularPrice: string,
	 *     sale: bool
	 *  }> $variantList
	 * @param array<string, array<int, ProductCombinationFilterVariantResult>> $variants
	 * @param array<string, string> $default
	 */
	public function __construct(
		public PriceInterface $price,
		public PriceInterface $regularPrice,
		public bool $sale,
		public array $variantList,
		public array $variants,
		public array $default,
	) {
	}
}
