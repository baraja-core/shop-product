<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Api;


use Baraja\Markdown\Renderer;
use Baraja\Shop\Brand\Entity\Brand;
use Baraja\Shop\Currency\CurrencyManagerAccessor;
use Baraja\Shop\Product\Api\DTO\ProductDTO;
use Baraja\Shop\Product\Category\Api\DTO\ProductCategoryDTO as PCItem;
use Baraja\Shop\Product\Category\Api\DTO\ProductCategoryProductItemDTO as ProductItem;
use Baraja\Shop\Product\DTO\ProductCombinationFilterResult;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductCategory;
use Baraja\Shop\Product\Entity\ProductImage;
use Baraja\Shop\Product\Entity\ProductParameter;
use Baraja\Shop\Product\Entity\ProductParameterColor;
use Baraja\Shop\Product\Entity\ProductSmartDescription;
use Baraja\Shop\Product\Entity\RelatedProduct;
use Baraja\Shop\Product\ProductCombinationFilter;
use Baraja\Shop\Product\Repository\ProductRepository;
use Baraja\StructuredApi\Attributes\PublicEndpoint;
use Baraja\StructuredApi\BaseEndpoint;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Strings;

#[PublicEndpoint]
final class ProductEndpoint extends BaseEndpoint
{
	private ProductRepository $repository;


	public function __construct(
		private EntityManagerInterface $entityManager,
		private ProductCombinationFilter $combinationFilter,
		private CurrencyManagerAccessor $currencyManagerAccessor,
		private Renderer $renderer,
	) {
		$repository = $entityManager->getRepository(Product::class);
		assert($repository instanceof ProductRepository);
		$this->repository = $repository;
	}


	public function actionDetail(string $slug): ProductDTO
	{
		$product = $this->repository->getBySlug($slug);
		$currency = $this->currencyManagerAccessor->get()->getCurrencyResolver()->getCurrency();
		$combinationFilter = $this->combinationFilter->getFilter($product);

		return new ProductDTO(
			id: $product->getId(),
			slug: $product->getSlug(),
			name: $product->getLabel(),
			mainCategory: $product->getMainCategory() !== null
				? PCItem::createFromEntity($product->getMainCategory())
				: null,
			categoryPath: $product->getMainCategory() !== null
				? $this->processCategoryPath($product->getMainCategory())
				: [],
			description: $this->renderer->render((string) $product->getDescription()),
			shortDescription: $this->renderer->render((string) $product->getShortDescription()),
			smartDescriptions: array_map(
				fn(ProductSmartDescription $description): array => $this->processSmartDescription($description),
				$product->getSmartDescriptions()->toArray(),
			),
			isVariantProduct: $product->isVariantProduct(),
			isSoldOut: $product->isSoldOut(),
			deliveryDate: null,
			mainImage: $product->getMainImage()?->toArray(),
			images: array_map(
				static fn(ProductImage $image): array => $image->toArray(),
				$product->getImages()->toArray(),
			),
			brand: $product->getBrand() !== null ? $this->processBrand($product->getBrand()) : null,
			combinationFilter: $combinationFilter,
			sameCategoryProducts: array_map(
				static fn(Product $product): ProductItem => ProductItem::createFromEntity($product, $currency),
				$this->processSameCategoryProducts($product),
			),
			parameters: $this->processParameters($product),
			relatedProducts: array_map(
				static fn(Product $product): ProductItem => ProductItem::createFromEntity($product, $currency),
				$this->processRelatedProducts($product),
			),
			colorMap: $this->processColorMap(),
		);
	}


	public function actionVariantStatus(string $slug, ?int $variantId = null): ProductCombinationFilterResult
	{
		return $this->combinationFilter->getFilter($this->repository->getBySlug($slug), $variantId);
	}


	/**
	 * @return array<int, PCItem>
	 */
	private function processCategoryPath(ProductCategory $category): array
	{
		$return = [];
		while ($category !== null) {
			$return[] = PCItem::createFromEntity($category);
			$category = $category->getParent();
		}

		return array_reverse($return);
	}


	/**
	 * @return array{id: int, slug: string, name: string, country: array{id: int, code: string, name: string}|null}
	 */
	private function processBrand(Brand $brand): array
	{
		$country = $brand->getCountry();

		return [
			'id' => $brand->getId(),
			'slug' => $brand->getSlug(),
			'name' => $brand->getName(),
			'country' => $country !== null
				? [
					'id' => $country->getId(),
					'code' => $country->getCode(),
					'name' => $country->getName(),
				] : null,
		];
	}


	/**
	 * @return Product[]
	 */
	private function processSameCategoryProducts(Product $product): array
	{
		$mainCategory = $product->getMainCategory();
		if ($mainCategory === null) {
			return [];
		}

		return $this->entityManager->getRepository(Product::class)
			->createQueryBuilder('product')
			->where('product.mainCategory = :mainCategoryId')
			->andWhere('product.id != :productId')
			->andWhere('product.active = TRUE')
			->setParameter('mainCategoryId', $mainCategory->getId())
			->setParameter('productId', $product->getId())
			->orderBy('product.position', 'DESC')
			->setMaxResults(3)
			->getQuery()
			->getResult();

	}


	/**
	 * @return non-empty-array<string, array<int, array{value: string}>>
	 */
	private function processParameters(Product $product): array
	{
		/** @var array<int, array{name: string, values: array<int, string>}> $basic */
		$basic = $this->entityManager->getRepository(ProductParameter::class)
			->createQueryBuilder('p')
			->select('PARTIAL p.{id, name, values}')
			->where('p.product = :productId')
			->setParameter('productId', $product->getId())
			->getQuery()
			->getArrayResult();

		$return = [];
		foreach ($basic as $parameter) {
			$values = [];
			foreach ($parameter['values'] as $value) {
				$values[] = ['value' => $value];
			}
			$return[Strings::firstUpper($parameter['name'])] = $values;
		}
		$return['Kód'] = [['value' => $product->getCode()]];
		$return['EAN'] = [['value' => $product->getEanForce()]];

		return $return;
	}


	/**
	 * @return array<int, Product>
	 */
	private function processRelatedProducts(Product $product): array
	{
		return array_map(
			static fn(RelatedProduct $relatedProduct): Product => $relatedProduct->getRelatedProduct(),
			$this->entityManager->getRepository(RelatedProduct::class)
				->createQueryBuilder('related')
				->select('related, relatedProduct')
				->leftJoin('related.relatedProduct', 'relatedProduct')
				->where('related.product = :productId')
				->andWhere('relatedProduct.active = TRUE')
				->setParameter('productId', $product->getId())
				->orderBy('relatedProduct.position', 'DESC')
				->setMaxResults(3)
				->getQuery()
				->getResult(),
		);
	}


	/**
	 * @return array<string, string>
	 */
	private function processColorMap(): array
	{
		/** @var array<int, array{id: int, color: string, value: string}> $map */
		$map = $this->entityManager->getRepository(ProductParameterColor::class)
			->createQueryBuilder('colorMap')
			->select('PARTIAL colorMap.{id, color, value}')
			->getQuery()
			->getArrayResult();

		$return = [];
		foreach ($map as $item) {
			$return[Strings::firstUpper($item['color'])] = $item['value'];
		}

		return $return;
	}


	/**
	 * @return array{id: int, title: string, description: string, imageUrl: string|null, color: string}
	 */
	private function processSmartDescription(ProductSmartDescription $smartDescription): array
	{
		$return = $smartDescription->toArray();
		$return['description'] = $this->renderer->render($return['description']);

		return $return;
	}
}
