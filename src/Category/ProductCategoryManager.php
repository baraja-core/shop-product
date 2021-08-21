<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category;


use Baraja\Doctrine\EntityManager;
use Baraja\Shop\Product\Entity\ProductCategory;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class ProductCategoryManager
{
	public function __construct(
		private EntityManager $entityManager,
	) {
	}


	public function createCategory(string $name, ?string $code = null, ?int $parentId = null): ProductCategory
	{
		$category = new ProductCategory($name, $code ?: $name);
		if ($parentId !== null) {
			$category->setParent($this->getCategoryById($parentId));
		}
		$this->entityManager->persist($category);
		$this->entityManager->flush();

		return $category;
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getCategoryById(int $id): ProductCategory
	{
		return $this->entityManager->getRepository(ProductCategory::class)
			->createQueryBuilder('category')
			->where('category.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getSingleResult();
	}
}
