<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Repository;


use Baraja\EcommerceStandard\DTO\ProductInterface;
use Baraja\Shop\Product\Entity\ProductVariant;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class ProductVariantRepository extends EntityRepository
{
	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getById(int $id): ProductVariant
	{
		return $this->createQueryBuilder('productVariant')
			->where('productVariant.id = :id')
			->setParameter('id', $id)
			->setMaxResults(1)
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getByEan(string $ean): ProductVariant
	{
		return $this->createQueryBuilder('productVariant')
			->where('productVariant.ean = :ean')
			->setParameter('ean', $ean)
			->setMaxResults(1)
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @return array<int, ProductVariant>
	 */
	public function getListByProduct(ProductInterface|int $product): array
	{
		/** @var array<int, ProductVariant> $variants */
		$variants = $this->createQueryBuilder('variant')
			->where('variant.product = :productId')
			->setParameter('productId', is_int($product) ? $product : $product->getId())
			->orderBy('variant.soldOut', 'DESC')
			->addOrderBy('variant.price', 'DESC')
			->addOrderBy('variant.relationHash', 'ASC')
			->getQuery()
			->getResult();

		return $variants;
	}


	/**
	 * @return array<int, array{id: int, relationHash: string, soldOut: bool}>
	 */
	public function getVariantsInfoByProduct(ProductInterface|int $product): array
	{
		/** @var array<int, array{id: int, relationHash: string, soldOut: bool}> $variantList */
		$variantList = $this->createQueryBuilder('variant')
			->select('PARTIAL variant.{id, relationHash, soldOut}')
			->andWhere('variant.product = :productId')
			->setParameter('productId', is_int($product) ? $product : $product->getId())
			->orderBy('variant.relationHash', 'ASC')
			->getQuery()
			->getArrayResult();

		return $variantList;
	}
}
