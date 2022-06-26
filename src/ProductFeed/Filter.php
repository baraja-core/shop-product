<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\ProductFeed;


use Baraja\Shop\Brand\Entity\Brand;
use Baraja\Shop\Product\Entity\ProductCategory;

final class Filter
{
	public const
		OrderSmart = 'smart',
		OrderCheapest = 'cheapest',
		OrderMostExpensive = 'mostExpensive';


	/**
	 * @param array<int, Brand> $brands
	 */
	public function __construct(
		private ?ProductCategory $mainCategory = null,
		private ?Brand $brand = null,
		private array $brands = [],
		private bool $findChildCategories = true,
		private bool $onlyInStock = false,
		private ?float $priceFrom = null,
		private ?float $priceTo = null,
		private string $order = self::OrderSmart,
		private int $maxResults = 24,
		private int $page = 1,
	) {
	}


	public function getMainCategory(): ?ProductCategory
	{
		return $this->mainCategory;
	}


	/**
	 * @return array<int, int>
	 */
	public function getBrandIds(): array
	{
		$return = array_map(static fn(Brand $brand): int => $brand->getId(), $this->brands);
		if ($this->brand !== null && in_array($this->brand->getId(), $return, true) === false) {
			$return[] = $this->brand->getId();
		}

		return $return;
	}


	public function isFindChildCategories(): bool
	{
		return $this->findChildCategories;
	}


	public function isOnlyInStock(): bool
	{
		return $this->onlyInStock;
	}


	public function getPriceFrom(): ?float
	{
		return $this->priceFrom;
	}


	public function getPriceTo(): ?float
	{
		return $this->priceTo;
	}


	public function getOrder(): string
	{
		return $this->order;
	}


	public function getMaxResults(): int
	{
		return $this->maxResults;
	}


	public function getPage(): int
	{
		return $this->page;
	}
}
