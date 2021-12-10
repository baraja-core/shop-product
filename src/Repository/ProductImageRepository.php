<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Repository;


use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductImage;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class ProductImageRepository extends EntityRepository
{
	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getById(int $id): ProductImage
	{
		return $this->createQueryBuilder('productImage')
			->where('productImage.id = :id')
			->setParameter('id', $id)
			->setMaxResults(1)
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @param array<int, int> $ids
	 * @return array<int, ProductImage>
	 */
	public function getByIds(array $ids): array
	{
		/** @var ProductImage[] $return */
		$return = $this->createQueryBuilder('image')
			->where('image.id IN (:ids)')
			->setParameter('ids', $ids)
			->getQuery()
			->getResult();

		return $return;
	}


	/**
	 * @return array<int, ProductImage>
	 */
	public function getListByProduct(Product $product): array
	{
		/** @var array<int, ProductImage> $return */
		$return = $this->createQueryBuilder('productImage')
			->select('productImage, variant')
			->leftJoin('productImage.variant', 'variant')
			->where('productImage.product = :productId')
			->setParameter('productId', $product->getId())
			->orderBy('productImage.position', 'DESC')
			->getQuery()
			->getResult();

		return $return;
	}
}
