<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category;


use Baraja\Plugin\BasePlugin;
use Baraja\Plugin\SimpleComponent\Breadcrumb;
use Baraja\Shop\Product\Entity\ProductCategory;
use Baraja\Shop\Product\Entity\ProductCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class ProductCategoryPlugin extends BasePlugin
{
	private ProductCategoryRepository $productCategoryRepository;


	public function __construct(EntityManagerInterface $entityManager)
	{
		/** @var ProductCategoryRepository $productCategoryRepository */
		$productCategoryRepository = $entityManager->getRepository(ProductCategory::class);
		$this->productCategoryRepository = $productCategoryRepository;
	}


	public function getName(): string
	{
		return 'Product categories';
	}


	public function actionDetail(int $id): void
	{
		try {
			$category = $this->productCategoryRepository->getById($id);
		} catch (NoResultException | NonUniqueResultException) {
			$this->error(sprintf('Product category "%s" doest not exist.', $id));
		}

		foreach ($category->getPath() as $parentId => $parentName) {
			$this->addBreadcrumb(
				new Breadcrumb(
					label: $parentName,
					href: $this->link('ProductCategory:detail', ['id' => $parentId]),
				)
			);
		}

		$this->setTitle(
			'(' . $id . ') '
			. ($category->isActive() ? '' : '[hidden] ')
			. $category->getName()
		);
	}
}
