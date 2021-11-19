<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category;


use Baraja\Doctrine\EntityManager;
use Baraja\Heureka\CategoryManager;
use Baraja\Shop\Product\Entity\Product;
use Baraja\StructuredApi\BaseEndpoint;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class CmsProductCategoryEndpoint extends BaseEndpoint
{
	public function __construct(
		private EntityManager $entityManager,
		private ProductCategoryManagerAccessor $categoryManager,
	) {
	}


	public function actionDefault(): void
	{
		$rootCategories = $this->categoryManager->get()->getCategoriesByParent();

		$this->sendJson(
			[
				'dataExist' => $rootCategories !== [],
				'tree' => $this->formatBootstrapSelectArray(
					[null => '- root -'] + $this->categoryManager->get()->getTree()
				),
			]
		);
	}


	public function actionDefaultTree(?int $parentId = null): void
	{
		$return = $this->categoryManager->get()->getFeed($parentId);
		$openChildren = [];
		foreach ($return as $category) {
			$openChildren[$category['id']] = false;
		}

		$this->sendJson(
			[
				'items' => array_values($return),
				'openChildren' => $openChildren,
			]
		);
	}


	public function actionOverview(int $id): void
	{
		try {
			$category = $this->categoryManager->get()->getCategoryById($id);
		} catch (NoResultException | NonUniqueResultException) {
			$this->sendError('Category "' . $id . '" does not exist.');
		}

		$this->sendJson(
			[
				'category' => [
					'id' => $category->getId(),
					'name' => (string) $category->getName(),
					'parentId' => $category->getParentId(),
					'code' => $category->getCode(),
					'slug' => $category->getSlug(),
					'description' => (string) $category->getDescription(),
					'active' => $category->isActive(),
					'heureka' => [
						'id' => $category->getHeurekaCategoryId(),
						'isAvailable' => class_exists(CategoryManager::class),
					],
				],
				'tree' => $this->formatBootstrapSelectArray(
					[null => '- root -'] + $this->categoryManager->get()->getTree()
				),
			]
		);
	}


	public function postSave(
		int $id,
		string $name,
		string $code,
		string $slug,
		?int $parentId,
		string $description,
		bool $active,
	): void {
		try {
			$category = $this->categoryManager->get()->getCategoryById($id);
		} catch (NoResultException | NonUniqueResultException) {
			$this->sendError(sprintf('Category "%s" does not exist.', $id));
		}

		$category->setName($name);
		$category->setCode($code);
		$category->setSlug($slug);
		$category->setDescription($description);

		if ($category->isActive() !== $active) {
			$this->categoryManager->get()->setActive($category, $active);
		}
		if ($parentId !== null) {
			$category->setParent($this->categoryManager->get()->getCategoryById($parentId));
		} else {
			$category->setParent(null);
		}

		$this->entityManager->flush();
		$this->flashMessage('Category has been updated.', self::FLASH_MESSAGE_SUCCESS);
		$this->sendOk();
	}


	public function actionProducts(int $id): void
	{
		try {
			$category = $this->categoryManager->get()->getCategoryById($id);
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


	public function postCreateCategory(string $name, ?string $code = null, ?int $parentId = null): void
	{
		$category = $this->categoryManager->get()->createCategory($name, $code, $parentId);
		$this->flashMessage('Category has been created.', self::FLASH_MESSAGE_SUCCESS);
		$this->sendJson(
			[
				'id' => $category->getId(),
			]
		);
	}
}
