<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category\Api\DTO;


use Baraja\Shop\Product\ProductFeed\FeedStatistic;
use Nette\Utils\Paginator;

final class ProductCategoryDTO
{
	/**
	 * @param array<int, mixed> $category
	 * @param array<int, mixed>|null $children
	 * @param array<int, mixed> $products
	 * @param array<int, int> $pages
	 */
	public function __construct(
		public array $category,
		public ?array $children,
		public array $products,
		public FeedStatistic $statistic,
		public Paginator $paginator,
		public array $pages = [],
	) {
	}
}
