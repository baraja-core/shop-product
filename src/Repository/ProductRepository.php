<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Repository;


use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductSmartDescription;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

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
			->select('product, image, tag')
			->leftJoin('product.images', 'image')
			->leftJoin('product.tags', 'tag')
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


	/**
	 * Prepares QueryBuilder for fast product search based on scalar input.
	 *
	 * @param array<int, string|int> $searchIds
	 */
	public function getFeedCandidates(array $searchIds = []): QueryBuilder
	{
		$selection = $this->createQueryBuilder('product')
			->select('PARTIAL product.{id, name, code, ean, shortDescription, price, position, active, soldOut}')
			->addSelect('PARTIAL mainImage.{id, source}')
			->addSelect('PARTIAL mainCategory.{id, name}')
			->addSelect('PARTIAL brand.{id, name}')
			->leftJoin('product.mainImage', 'mainImage')
			->leftJoin('product.mainCategory', 'mainCategory')
			->leftJoin('product.brand', 'brand');

		if ($searchIds !== []) {
			$selection->andWhere('product.id IN (:searchIds)')
				->setParameter('searchIds', $searchIds);
		}

		return $selection;
	}


	/**
	 * @param Product $product
	 * @return array<int, ProductSmartDescription>
	 */
	public function getSmartDescriptions(Product $product): array
	{
		$changed = false;
		$return = [];
		foreach ($product->getSmartDescriptions() as $position => $description) {
			if ($description->getPosition() !== $position) {
				$description->setPosition($position);
				$changed = true;
			}
			$return[] = $description;
		}
		if ($changed) {
			$this->_em->flush();
		}

		return $return;
	}
}
