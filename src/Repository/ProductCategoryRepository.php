<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Repository;


use Baraja\Shop\Product\Category\Api\DTO\ProductCategoryItemDTO;
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
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getBySlug(string $slug): ProductCategory
	{
		return $this->createQueryBuilder('category')
			->where('category.slug = :slug')
			->setParameter('slug', $slug)
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getBySlugForFrontend(string $slug): ProductCategory
	{
		return $this->createQueryBuilder('category')
			->where('category.slug = :slug')
			->andWhere('category.active = TRUE')
			->andWhere('category.internal = FALSE')
			->andWhere('category.deleted = FALSE')
			->setParameter('slug', $slug)
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @return array<int, ProductCategoryItemDTO>
	 */
	public function getFeed(): array
	{
		/** @var ProductCategory[] $categories */
		$categories = $this->createQueryBuilder('pc')
			->select('PARTIAL pc.{id, name, slug, active, internal, deleted}')
			->addSelect('PARTIAL child.{id, name, slug, active, internal, deleted}')
			->leftJoin('pc.child', 'child')
			->andWhere('pc.parent IS NULL')
			->andWhere('pc.active = TRUE')
			->andWhere('child.active = TRUE')
			->orderBy('pc.position', 'ASC')
			->addOrderBy('child.position', 'ASC')
			->getQuery()
			->getResult();

		$return = [];
		foreach ($categories as $category) {
			if ($category->isActive() === false || $category->isInternal() || $category->isDeleted()) {
				continue;
			}
			$children = [];
			foreach ($category->getChild() as $subCategory) {
				if ($subCategory->isActive() === false || $subCategory->isInternal() || $subCategory->isDeleted()) {
					continue;
				}
				$children[] = new ProductCategoryItemDTO(
					id: $subCategory->getId(),
					name: $subCategory->getLabel(),
					slug: $subCategory->getSlug(),
				);
			}
			$return[] = new ProductCategoryItemDTO(
				id: $category->getId(),
				name: $category->getLabel(),
				slug: $category->getSlug(),
				children: $children,
			);
		}

		return $return;
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
