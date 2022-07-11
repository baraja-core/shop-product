<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category\Api;


use Baraja\Shop\Currency\CurrencyManagerAccessor;
use Baraja\Shop\Product\Category\Api\DTO\ProductCategoryDTO as PCItem;
use Baraja\Shop\Product\Category\Api\DTO\ProductCategoryItemDTO;
use Baraja\Shop\Product\Category\Api\DTO\ProductCategoryProductItemDTO as ProductItem;
use Baraja\Shop\Product\Category\Api\DTO\ProductCategoryResponse;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductCategory;
use Baraja\Shop\Product\ProductFeed\Feed;
use Baraja\Shop\Product\ProductFeed\Filter;
use Baraja\Shop\Product\Repository\ProductCategoryRepository;
use Baraja\StructuredApi\Attributes\PublicEndpoint;
use Baraja\StructuredApi\BaseEndpoint;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

#[PublicEndpoint]
final class ProductCategoryEndpoint extends BaseEndpoint
{
	private ProductCategoryRepository $repository;


	public function __construct(
		EntityManagerInterface $entityManager,
		private Feed $productFeed,
		private CurrencyManagerAccessor $currencyManagerAccessor,
	) {
		$repository = $entityManager->getRepository(ProductCategory::class);
		assert($repository instanceof ProductCategoryRepository);
		$this->repository = $repository;
	}


	private static function renderWarehouseQuantity(int $value): string
	{
		if ($value <= 0) {
			return '0';
		}
		if ($value <= 5) {
			return (string) $value;
		}

		return '>5';
	}


	/**
	 * @return array<int, ProductCategoryItemDTO>
	 */
	public function actionDefault(): array
	{
		return $this->repository->getFeed();
	}


	public function actionDetail(
		string $slug,
		int $page = 1,
		?string $filter = null,
		?int $priceFrom = null,
		?int $priceTo = null,
	): ProductCategoryResponse {
		try {
			$category = $this->repository->getBySlugForFrontend($slug);
		} catch (NoResultException|NonUniqueResultException) {
			$this->sendError(sprintf('Category "%s" does not exist.', $slug));
		}

		$children = $category->getChild();
		$parent = $category->getParent();
		$feed = $this->productFeed->fetch(
			new Filter(
				mainCategory: $category,
				priceFrom: $priceFrom,
				priceTo: $priceTo,
				order: $filter ?? Filter::OrderSmart,
				page: $page,
			),
		);

		$currency = $this->currencyManagerAccessor->get()->getCurrencyResolver()->getCurrency();

		return new ProductCategoryResponse(
			category: PCItem::createFromEntity($category),
			parent: $parent !== null ? PCItem::createFromEntity($parent) : null,
			children: array_map(
				static fn(ProductCategory $categoryItem): PCItem => PCItem::createFromEntity($categoryItem),
				$children->toArray(),
			),
			products: array_map(
				static fn(Product $product): ProductItem => ProductItem::createFromEntity($product, $currency),
				$feed->getProducts(),
			),
			statistic: $feed->getStatistic(),
			paginator: $feed->getPaginator(),
			pages: $feed->getPages(),
		);
	}
}
