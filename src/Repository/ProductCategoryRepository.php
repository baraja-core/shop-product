<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Repository;


use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductCategory;
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


	/**
	 * @return array<int, array{id: int, name: string}>
	 */
	public function getRelated(Product $product): array
	{
		$selection = $this->createQueryBuilder('category')
			->select('PARTIAL category.{id, name}')
			->orderBy('category.name', 'ASC');

		$mainCategory = $product->getMainCategory();
		if ($mainCategory !== null) {
			$selection->andWhere('category.id != :mainCategoryId')
				->setParameter('mainCategoryId', $mainCategory->getId());
		}
		$subCategoryIds = [];
		foreach ($product->getCategories() as $subCategory) {
			$subCategoryIds[] = $subCategory->getId();
		}
		if ($subCategoryIds !== []) {
			$selection->andWhere('category.id NOT IN (:ids)')
				->setParameter('ids', $subCategoryIds);
		}

		$return = [];
		foreach ($selection->getQuery()->getArrayResult() as $category) {
			$return[] = [
				'id' => $category['id'],
				'name' => $category['name'],
			];
		}

		return $return;
	}
}
