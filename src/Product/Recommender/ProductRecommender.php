<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Recommender;


use Baraja\EcommerceStandard\DTO\ProductInterface;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\RelatedProduct;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class ProductRecommender
{
	public function __construct(
		private EntityManagerInterface $entityManager,
	) {
	}


	/**
	 * @param array<int, ProductInterface> $products
	 * @return array<int, ProductInterface>
	 */
	public function getRelatedByCollection(array $products, int $limit = 8): array
	{
		if ($limit < 1) {
			$limit = 1;
		}
		usort($products, static function (ProductInterface $first, ProductInterface $second): int {
			$a = $first->getPosition();
			$b = $second->getPosition();

			if ($a === $b) {
				return 0;
			}

			return $a < $b ? 1 : -1;
		});

		$usedIds = array_flip(array_map(static fn(ProductInterface $product): int => $product->getId(), $products));

		$productById = [];
		$frequencyById = [];
		$highFrequency = 0;
		foreach ($products as $product) {
			foreach ($this->getRelatedByProduct($product, $limit) as $relatedProduct) {
				$id = $relatedProduct->getId();
				if (isset($usedIds[$id])) {
					continue;
				}
				$productById[$id] = $relatedProduct;
				$frequencyById[$id] = isset($frequencyById[$id]) ? $frequencyById[$id] + 1 : 1;
				if ($frequencyById[$id] > $highFrequency) {
					$highFrequency = $frequencyById[$id];
				}
			}
		}

		if ($highFrequency === 0) {
			return [];
		}

		$return = [];
		for (; $highFrequency > 0; $highFrequency--) {
			foreach ($frequencyById as $id => $frequency) {
				if ($frequency !== $highFrequency) {
					continue;
				}
				assert(isset($productById[$id]));
				$return[] = $productById[$id];
			}
		}

		return array_slice($return, 0, $limit);
	}


	/**
	 * @return array<int, ProductInterface>
	 */
	public function getRelatedByProduct(ProductInterface $product, int $limit = 8): array
	{
		if ($limit < 1) {
			$limit = 1;
		}
		$return = $this->filter($product, $this->selectRelated($product, $limit));
		$limit -= count($return);
		if ($limit > 0) {
			$return = $this->filter($product, array_merge($return, $this->selectByCategory($product, $limit)));
			$limit -= count($return);
		}
		if ($limit > 0) {
			$return = $this->filter($product, array_merge($return, $this->selectTopProducts($limit)));
		}

		return $return;
	}


	/**
	 * @return array<int, ProductInterface>
	 */
	private function selectRelated(ProductInterface $product, int $limit): array
	{
		$repository = new EntityRepository(
			$this->entityManager,
			$this->entityManager->getClassMetadata(RelatedProduct::class),
		);

		/** @var array<int, RelatedProduct> $related */
		$related = $repository->createQueryBuilder('rel')
			->select('rel, relatedProduct')
			->join('rel.relatedProduct', 'relatedProduct')
			->where('rel.product = :productId')
			->andWhere('relatedProduct.active = TRUE')
			->setParameter('productId', $product->getId())
			->orderBy('rel.position', 'DESC')
			->addOrderBy('relatedProduct.position', 'DESC')
			->setMaxResults($limit)
			->getQuery()
			->getResult();

		return array_map(static fn(RelatedProduct $related): Product => $related->getRelatedProduct(), $related);
	}


	/**
	 * @return array<int, ProductInterface>
	 */
	private function selectByCategory(ProductInterface $product, int $limit): array
	{
		$repository = new EntityRepository(
			$this->entityManager,
			$this->entityManager->getClassMetadata(Product::class),
		);

		$return = [];
		$mainCategory = $product->getMainCategory();
		if ($mainCategory !== null) {
			/** @var array<int, Product> $products */
			$products = $repository->createQueryBuilder('product')
				->where('product.mainCategory = :mainCategoryId')
				->andWhere('product.id != :id')
				->andWhere('product.active = TRUE')
				->setParameter('mainCategoryId', $mainCategory->getId())
				->setParameter('id', $product->getId())
				->addOrderBy('product.position', 'DESC')
				->setMaxResults($limit)
				->getQuery()
				->getResult();
			$return = $products;
		}
		if (count($return) < $limit) {
			$categoryIds = [];
			foreach ($product->getCategories() as $subCategory) {
				if ($mainCategory !== null && $mainCategory->getId() === $subCategory->getId()) {
					continue;
				}
				$categoryIds[] = $subCategory->getId();
			}
			if ($categoryIds !== []) {
				/** @var array<int, Product> $products */
				$products = $repository->createQueryBuilder('product')
					->leftJoin('product.categories', 'subCategory')
					->where('subCategory.id IN (:categoryIds)')
					->andWhere('product.id != :id')
					->andWhere('product.active = TRUE')
					->setParameter('categoryIds', $categoryIds)
					->setParameter('id', $product->getId())
					->addOrderBy('product.position', 'DESC')
					->setMaxResults($limit)
					->getQuery()
					->getResult();
				$return = array_merge($return, $products);
			}
		}

		return $return;
	}


	/**
	 * @return array<int, ProductInterface>
	 */
	private function selectTopProducts(int $limit): array
	{
		$repository = new EntityRepository(
			$this->entityManager,
			$this->entityManager->getClassMetadata(Product::class),
		);

		/** @var array<int, Product> $products */
		$products = $repository->createQueryBuilder('product')
			->andWhere('product.active = TRUE')
			->addOrderBy('product.position', 'DESC')
			->setMaxResults($limit)
			->getQuery()
			->getResult();

		return $products;
	}


	/**
	 * @param array<int, ProductInterface> $products
	 * @return array<int, ProductInterface>
	 */
	private function filter(ProductInterface $product, array $products): array
	{
		$productId = $product->getId();
		$return = [];
		foreach ($products as $item) {
			$id = $item->getId();
			if (
				isset($return[$id])
				|| $item->getId() === $productId
				|| $product->isActive() === false
				|| $product->isSoldOut() === true
			) {
				continue;
			}
			$return[$id] = $item;
		}

		return array_values($return);
	}
}
