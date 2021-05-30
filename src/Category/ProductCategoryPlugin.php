<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category;


use Baraja\Doctrine\EntityManager;
use Baraja\Plugin\BasePlugin;
use Baraja\Shop\Product\Entity\ProductCategory;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class ProductCategoryPlugin extends BasePlugin
{
	public function __construct(
		private EntityManager $entityManager,
	) {
	}


	public function getName(): string
	{
		return 'Product categories';
	}


	public function actionDetail(int $id): void
	{
		try {
			/** @var ProductCategory $category */
			$category = $this->entityManager->getRepository(ProductCategory::class)
				->createQueryBuilder('category')
				->where('category.id = :id')
				->setParameter('id', $id)
				->setMaxResults(1)
				->getQuery()
				->getSingleResult();
		} catch (NoResultException | NonUniqueResultException) {
			$this->error('Product category "' . $id . '" doest not exist.');
		}

		$this->setTitle('(' . $id . ') ' . $category->getName());
	}
}
