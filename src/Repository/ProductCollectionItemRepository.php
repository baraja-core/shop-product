<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Repository;


use Baraja\EcommerceStandard\DTO\ProductInterface;
use Baraja\Shop\Product\Entity\ProductCollectionItem;
use Doctrine\ORM\EntityRepository;

final class ProductCollectionItemRepository extends EntityRepository
{
	/**
	 * @return array<int, ProductCollectionItem>
	 */
	public function getByProduct(ProductInterface $product): array
	{
		/** @var array<int, ProductCollectionItem> $items */
		$items = $this->createQueryBuilder('collection')
			->select('collection, relevantProduct, relevantProductVariant')
			->join('collection.relevantProduct', 'relevantProduct')
			->leftJoin('collection.relevantProductVariant', 'relevantProductVariant')
			->where('collection.baseProduct = :productId')
			->setParameter('productId', $product->getId())
			->orderBy('collection.position', 'ASC')
			->getQuery()
			->getResult();

		return $items;
	}


	public function isCollection(ProductInterface $product): bool
	{
		return $this->getByProduct($product) !== [];
	}
}
