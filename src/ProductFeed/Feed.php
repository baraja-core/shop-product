<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\ProductFeed;


use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Nette\Utils\Paginator;

final class Feed
{
	private ProductRepository $productRepository;


	public function __construct(
		private EntityManagerInterface $entityManager,
	) {
		$this->productRepository = new ProductRepository(
			$this->entityManager,
			$this->entityManager->getClassMetadata(Product::class),
		);
	}


	public function fetch(?Filter $filter = null): FeedResult
	{
		$filter ??= new Filter;

		/** @var array<int, array{id: int, price: numeric-string}> $candidates */
		$candidates = $this->getSelector($filter)
			->getQuery()
			->getArrayResult();

		$priceFrom = $filter->getPriceFrom();
		$priceTo = $filter->getPriceTo();
		if ($priceFrom !== null || $priceTo !== null) {
			$filteredCandidates = array_values(
				array_filter(
					$candidates,
					fn(array $item): bool => !(($priceFrom !== null && $item['price'] < $priceFrom) || ($priceTo !== null && $item['price'] > $priceTo)),
				),
			);
		}

		$ids = array_map(static fn(array $item): int => $item['id'], $filteredCandidates ?? $candidates);
		$prices = array_map(static fn(array $item): float => (float) $item['price'], $candidates);

		$productSelection = $this->selectProductEntities(
			ids: $this->filterItemsByPaginator(
				items: $ids,
				limit: $filter->getMaxResults(),
				offset: ($filter->getPage() - 1) * $filter->getMaxResults(),
			),
		);
		$this->useOrderRules($productSelection, $filter);

		/** @var array<int, Product> $products */
		$products = $productSelection->getQuery()->getResult();

		$paginator = (new Paginator)
			->setItemCount(count($ids))
			->setItemsPerPage($filter->getMaxResults())
			->setPage($filter->getPage());

		return new FeedResult(
			products: $products,
			statistic: new FeedStatistic(
				count: count($ids),
				minimalPrice: $prices !== [] ? min($prices) : 0,
				maximalPrice: $prices !== [] ? max($prices) : 0,
			),
			paginator: $paginator,
			pages: $this->processPages($paginator),
		);
	}


	private function getSelector(Filter $filter): QueryBuilder
	{
		$qb = $this->productRepository->createQueryBuilder('product')
			->distinct()
			->select('PARTIAL product.{id, price, position}');

		$mainCategory = $filter->getMainCategory();
		if ($mainCategory !== null) {
			$qb
				->leftJoin('product.categories', 'subCategory')
				->andWhere('product.mainCategory IN (:categoryIds) OR subCategory.id IN (:categoryIds)')
				->setParameter('categoryIds', $mainCategory->getAllChildIds());
		}
		$brandIds = $filter->getBrandIds();
		if ($brandIds !== []) {
			$qb
				->andWhere('product.brand IN (:brandIds)')
				->setParameter('brandIds', $brandIds);
		}
		$this->useOrderRules($qb, $filter);

		return $qb;
	}


	private function useOrderRules(QueryBuilder $queryBuilder, Filter $filter): void
	{
		if ($filter->getOrder() === Filter::OrderCheapest) {
			$queryBuilder->orderBy('product.price', 'ASC');
		} elseif ($filter->getOrder() === Filter::OrderMostExpensive) {
			$queryBuilder->orderBy('product.price', 'DESC');
		} elseif ($filter->getOrder() === Filter::OrderSmart) {
			$queryBuilder->orderBy('product.position', 'DESC');
		}
	}


	/**
	 * @param array<int, int> $ids
	 */
	private function selectProductEntities(array $ids): QueryBuilder
	{
		return $this->productRepository->createQueryBuilder('product')
			->select('product, mainImage, tag')
			->leftJoin('product.mainImage', 'mainImage')
			->leftJoin('product.tags', 'tag')
			->where('product.id IN (:ids)')
			->setParameter('ids', $ids);
	}


	/**
	 * @param array<int, array{id: int}> $items
	 * @return array<int, array{id: int}>
	 */
	private function filterItemsByPaginator(array $items, int $limit, int $offset): array
	{
		if ($limit < 0) {
			$limit = 0;
		}
		if ($offset < 0) {
			$offset = 0;
		}

		$return = [];
		for ($i = $offset; $i < $offset + $limit; $i++) {
			if (isset($items[$i]) === true) {
				$return[] = $items[$i];
			} else {
				break;
			}
		}

		return $return;
	}


	/**
	 * @return array<int, int>
	 */
	private function processPages(Paginator $paginator): array
	{
		$first = $paginator->getFirstPage();
		$page = $paginator->getPage();
		$last = $paginator->getLastPage();

		$return = [];
		$return[] = $first;
		for ($i = $page - 2; $i <= $page + 2; $i++) {
			if ($i > $first && $i < $last) {
				$return[] = $i;
			}
		}
		if ($last > $first) {
			$return[] = $last;
		}

		return $return;
	}
}
