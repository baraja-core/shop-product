<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category;


use Baraja\Doctrine\EntityManager;
use Baraja\Heureka\CategoryManager as HeurekaCategoryManager;
use Baraja\SelectboxTree\SelectboxTree;
use Baraja\Shop\Product\Entity\ProductCategory;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class ProductCategoryManager
{
	public function __construct(
		private EntityManager $entityManager,
		protected ?HeurekaCategoryManager $heurekaCategoryManager = null,
	) {
	}


	public function createCategory(string $name, ?string $code = null, ?int $parentId = null): ProductCategory
	{
		$category = new ProductCategory($name, $code ?: $name);
		if ($parentId !== null) {
			$category->setParent($this->getCategoryById($parentId));
		}
		$this->entityManager->persist($category);
		$this->recountPositions($parentId, $category);
		$this->entityManager->flush();

		return $category;
	}


	/**
	 * @return array<int|string, string>
	 */
	public function getTree(): array
	{
		$cat = new SelectboxTree;
		/** @var array<int, array{id: int|string, name: string, parent_id: int|string|null}> $categories */
		$categories = $this->entityManager->getConnection()
			->executeQuery($cat->sqlBuilder('shop__product_category', orderCol: 'position'))
			->fetchAllAssociative();

		return $cat->process($categories);
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


	/**
	 * @return ProductCategory[]
	 */
	public function getCategoriesByParent(?int $parentId = null): array
	{
		$selector = $this->entityManager->getRepository(ProductCategory::class)
			->createQueryBuilder('category')
			->orderBy('category.active', 'DESC')
			->addOrderBy('category.position', 'ASC');

		if ($parentId === null) {
			$selector->where('category.parent IS NULL');
		} else {
			$selector->where('category.parent = :parentId')
				->setParameter('parentId', $parentId);
		}

		/** @var ProductCategory[] $categories */
		$categories = $selector->getQuery()->getResult();

		return $categories;
	}


	/**
	 * @return array<int, array{id: int, name: string, code: string, hasChildren: bool}>
	 */
	public function getFeed(?int $parentId): array
	{
		$return = [];
		$needRecountPosition = false;
		foreach ($this->getCategoriesByParent($parentId) as $category) {
			$id = $category->getId();
			$return[$id] = [
				'id' => $id,
				'name' => (string) $category->getName(),
				'code' => $category->getCode(),
				'active' => $category->isActive(),
				'hasChildren' => false,
			];
			if ($category->getPosition() < 1) {
				$needRecountPosition = true;
			}
		}

		try {
			/** @var array<int, array{id: int}> $childrenMapper */
			$childrenMapper = $this->entityManager->getConnection()
				->executeQuery(
					'SELECT `parent_id` AS `id`
					FROM `shop__product_category`
					WHERE `parent_id` IN (' . implode(', ', array_keys($return)) . ')
					GROUP BY `parent_id`',
				)
				->fetchAllAssociative();

			foreach ($childrenMapper as $childrenItem) {
				if (isset($return[$childrenItem['id']])) {
					$return[$childrenItem['id']]['hasChildren'] = true;
				}
			}
		} catch (\Throwable) {
			// Silence is golden.
		}

		if ($needRecountPosition) {
			$this->recountPositions($parentId);
			$this->entityManager->flush();
		}

		return $return;
	}


	public function setActive(ProductCategory $category, bool $active): void
	{
		if ($active === true) {
			foreach ($category->getAllParents() as $parent) {
				if ($parent->isActive() === false && $parent->getId() !== $category->getId()) {
					throw new \InvalidArgumentException(sprintf(
						'Category "%s" (id "%s") can not be marked as active, because parent category "%s" (id "%s") is hidden.',
						(string) $category->getName(),
						$category->getId(),
						(string) $parent->getName(),
						$parent->getId(),
					));
				}
			}
			$category->setActive(true);
			$this->entityManager->flush();
			return;
		}
		foreach (array_merge($category->getMainProducts(), $category->getProducts()) as $product) {
			if ($product->isActive()) {
				throw new \InvalidArgumentException(sprintf(
					'Category "%s" (id "%s") can not be marked as hidden, because contain active product "%s" (id "%s").',
					(string) $category->getName(),
					$category->getId(),
					(string) $product->getName(),
					$product->getId(),
				));
			}
		}
		foreach ($category->getAllChildren() as $child) {
			$child->setActive(false);
		}

		$category->setActive(false);
		$this->entityManager->flush();
	}


	public function recountPositions(?int $parentId = null, ?ProductCategory $newCategory = null): void
	{
		$categories = $this->getCategoriesByParent($parentId);
		if ($newCategory !== null) {
			$categories[] = $newCategory;
		}

		$position = 1;
		foreach ($categories as $category) {
			$category->setPosition($position);
			$position++;
		}
	}
}
