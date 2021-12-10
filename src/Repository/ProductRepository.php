<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Repository;


use Baraja\Shop\Product\Entity\Product;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class ProductRepository extends EntityRepository
{
	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getById(int $id): Product
	{
		return $this->createQueryBuilder('product')
			->select('product, image, mainImage, mainCategory')
			->leftJoin('product.images', 'image')
			->leftJoin('product.mainImage', 'mainImage')
			->leftJoin('product.mainCategory', 'mainCategory')
			->where('product.id = :id')
			->setParameter('id', $id)
			->orderBy('image.position', 'DESC')
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getBySlug(string $slug): Product
	{
		return $this->createQueryBuilder('product')
			->select('product, image')
			->leftJoin('product.images', 'image')
			->where('product.slug = :slug')
			->setParameter('slug', $slug)
			->orderBy('image.position', 'DESC')
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getByCode(string $code): Product
	{
		return $this->createQueryBuilder('product')
			->select('product, image')
			->leftJoin('product.images', 'image')
			->where('product.code = :code')
			->setParameter('code', $code)
			->orderBy('image.position', 'DESC')
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getByEan(string $ean): Product
	{
		return $this->createQueryBuilder('product')
			->where('product.ean = :ean')
			->setParameter('ean', $ean)
			->setMaxResults(1)
			->getQuery()
			->getSingleResult();
	}
}
