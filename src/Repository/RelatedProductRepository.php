<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Repository;


use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\RelatedProduct;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class RelatedProductRepository extends EntityRepository
{
	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getRelation(Product $product, Product $relatedProduct): RelatedProduct
	{
		/** @var RelatedProduct $relation */
		$relation = $this->createQueryBuilder('r')
			->where('r.product = :productId')
			->andWhere('r.relatedProduct = :relatedProductId')
			->setParameter('productId', $product->getId())
			->setParameter('relatedProductId', $relatedProduct->getId())
			->setMaxResults(1)
			->getQuery()
			->getSingleResult();

		return $relation;
	}


	public function isRelationExist(Product $product, Product $relatedProduct): bool
	{
		try {
			$this->getRelation($product, $relatedProduct);
		} catch (NoResultException | NonUniqueResultException) {
			return false;
		}

		return true;
	}


	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function getRelatedList(Product $product): array
	{
		/** @var array<int, array<string, mixed>> $products */
		$products = $this->createQueryBuilder('r')
			->select('PARTIAL r.{id}, PARTIAL product.{id, name}, PARTIAL mainCategory.{id, name}')
			->leftJoin('r.relatedProduct', 'product')
			->leftJoin('product.mainCategory', 'mainCategory')
			->where('r.product = :productId')
			->setParameter('productId', $product->getId())
			->orderBy('mainCategory.name', 'ASC')
			->addOrderBy('product.name', 'ASC')
			->getQuery()
			->getArrayResult();

		return $products;
	}
}
