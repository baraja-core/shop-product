<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category;


use Baraja\Doctrine\EntityManager;
use Baraja\Heureka\CategoryManager;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductCategory;
use Baraja\StructuredApi\BaseEndpoint;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class CmsProductCategoryEndpoint extends BaseEndpoint
{
	public function __construct(
		private EntityManager $entityManager,
		private ?CategoryManager $heurekaCategoryManager = null,
	) {
	}


	public function actionDefault(): void
	{
		$categories = $this->entityManager->getRepository(ProductCategory::class)
			->createQueryBuilder('category')
			->select('PARTIAL category.{id, name, code, heurekaCategoryId}')
			->addSelect('PARTIAL parent.{id, name}')
			->leftJoin('category.parent', 'parent')
			->orderBy('parent.name', 'ASC')
			->addOrderBy('category.name', 'ASC')
			->getQuery()
			->getArrayResult();

		$return = [];
		foreach ($categories as $category) {
			$return[] = [
				'id' => $category['id'],
				'name' => $category['name'],
				'parent' => $category['parent'],
				'code' => $category['code'],
				'heurekaCategoryId' => (function (?int $heurekaCategoryId): ?string {
					if ($this->heurekaCategoryManager === null || $heurekaCategoryId === null) {
						return null;
					}

					return $this->heurekaCategoryManager->getCategory($heurekaCategoryId)
							->getName()
						. ' (' . $heurekaCategoryId . ')';
				})(
					$category['heurekaCategoryId']
				),
			];
		}

		$this->sendJson(
			[
				'items' => $return,
				'heurekaAvailable' => class_exists(CategoryManager::class),
			]
		);
	}


	public function actionOverview(int $id): void
	{
		try {
			$category = $this->getCategory($id);
		} catch (NoResultException | NonUniqueResultException) {
			$this->sendError('Category "' . $id . '" does not exist.');
		}

		$this->sendJson(
			[
				'id' => $category->getId(),
				'name' => (string) $category->getName(),
				'parent' => function () use ($category): ?array {
					$parent = $category->getParent();
					if ($parent !== null) {
						return [
							'id' => $parent->getId(),
							'name' => (string) $parent->getName(),
						];
					}

					return null;
				},
				'code' => $category->getCode(),
				'slug' => $category->getSlug(),
				'description' => (string) $category->getDescription(),
				'heureka' => [
					'id' => $category->getHeurekaCategoryId(),
					'isAvailable' => class_exists(CategoryManager::class),
				],
			]
		);
	}


	public function actionProducts(int $id): void
	{
		try {
			$category = $this->getCategory($id);
		} catch (NoResultException | NonUniqueResultException) {
			$this->sendError('Category "' . $id . '" does not exist.');
		}

		$this->sendJson(
			[
				'products' => $this->entityManager->getRepository(Product::class)
					->createQueryBuilder('p')
					->select('PARTIAL p.{id, name, price, active}')
					->leftJoin('p.categories', 'c')
					->where('p.mainCategory = :categoryId OR c.id = :categoryId')
					->setParameter('categoryId', $category->getId())
					->orderBy('p.position', 'DESC')
					->getQuery()
					->getArrayResult(),
			]
		);
	}


	public function postCreateCategory(string $name, ?string $code = null): void
	{
		$category = new ProductCategory($name, $code ?: $name);
		$this->entityManager->persist($category);
		$this->entityManager->flush();
		$this->flashMessage('Category has been created.', self::FLASH_MESSAGE_SUCCESS);
		$this->sendJson(
			[
				'id' => $category->getId(),
			]
		);
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	private function getCategory(int $id): ProductCategory
	{
		return $this->entityManager->getRepository(ProductCategory::class)
			->createQueryBuilder('category')
			->where('category.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getSingleResult();
	}
}
