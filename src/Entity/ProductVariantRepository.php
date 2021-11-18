<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class ProductVariantRepository extends EntityRepository
{
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
}
