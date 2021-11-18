<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class ProductCategoryRepository extends EntityRepository
{
	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getById(int $id): ProductCategory
	{
		return $this->createQueryBuilder('category')
			->where('category.id = :id')
			->setParameter('id', $id)
			->setMaxResults(1)
			->getQuery()
			->getSingleResult();
	}
}
