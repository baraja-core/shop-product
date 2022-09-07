<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category\Api\DTO;


use Baraja\Shop\Product\ProductFeed\FeedStatistic;
use Nette\Utils\Paginator;

final class ProductCategoryResponse
{
	/**
	 * @param array<int, ProductCategoryDTO>|null $children
	 * @param array<int, ProductCategoryProductItemDTO> $products
	 * @param array<int, int> $pages
	 */
	public function __construct(
		public ProductCategoryDTO $category,
		public ?ProductCategoryDTO $parent,
		public ?array $children,
		public array $products,
		public FeedStatistic $statistic,
		public Paginator $paginator,
		public array $pages = [],
	) {
	}
}
