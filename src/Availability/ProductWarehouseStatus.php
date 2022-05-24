<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Availability;


use Baraja\EcommerceStandard\DTO\ProductInterface;
use Baraja\EcommerceStandard\DTO\ProductVariantInterface;
use Baraja\EcommerceStandard\Service\ProductWarehouseStatusInterface;
use Baraja\EcommerceStandard\Service\WarehouseManagerInterface;

final class ProductWarehouseStatus implements ProductWarehouseStatusInterface
{
	public function __construct(
		private ?WarehouseManagerInterface $warehouseManager = null,
	) {
	}


	public function isAvailable(ProductInterface|ProductVariantInterface $product): bool
	{
		$coreProduct = $product instanceof ProductVariantInterface ? $product->getProduct() : $product;
		if ($coreProduct->isSoldOut()) {
			return false;
		}

		return $this->warehouseManager !== null
			? $this->warehouseManager->getRealCapacity($product) > 0
			: $product->getWarehouseAllQuantity() > 0;
	}


	public function getAvailabilityLabel(ProductInterface|ProductVariantInterface $product): string
	{
		return 'on-request';
	}
}
