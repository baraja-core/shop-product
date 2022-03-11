<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Combinations\CombinationGenerator;
use Baraja\Doctrine\EntityManager;
use Baraja\ImageGenerator\ImageGenerator;
use Baraja\Localization\Localization;
use Baraja\Markdown\CommonMarkRenderer;
use Baraja\SelectboxTree\SelectboxTree;
use Baraja\Shop\Brand\Entity\Brand;
use Baraja\Shop\Currency\CurrencyManagerAccessor;
use Baraja\Shop\Product\DTO\ProductData;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductCategory;
use Baraja\Shop\Product\Entity\ProductImage;
use Baraja\Shop\Product\Entity\ProductParameter;
use Baraja\Shop\Product\Entity\ProductSmartDescription;
use Baraja\Shop\Product\Entity\ProductVariant;
use Baraja\Shop\Product\Entity\RelatedProduct;
use Baraja\Shop\Product\Repository\ProductCategoryRepository;
use Baraja\Shop\Product\Repository\ProductImageRepository;
use Baraja\Shop\Product\Repository\ProductRepository;
use Baraja\Shop\Product\Repository\ProductVariantRepository;
use Baraja\Shop\Product\Repository\RelatedProductRepository;
use Baraja\StructuredApi\BaseEndpoint;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Http\FileUpload;
use Nette\Http\Request;
use Nette\Utils\Random;
use Nette\Utils\Strings;

final class CmsProductEndpoint extends BaseEndpoint
{
	private ProductRepository $productRepository;

	private ProductCategoryRepository $productCategoryRepository;

	private ProductVariantRepository $productVariantRepository;

	private RelatedProductRepository $relatedProductRepository;

	private ProductImageRepository $productImageRepository;


	public function __construct(
		private EntityManager $entityManager,
		private CommonMarkRenderer $renderer,
		private ProductFieldManager $productFieldManager,
		private ProductManagerAccessor $productManager,
		private CurrencyManagerAccessor $currencyManager,
		private ProductFeedFacade $productFeedFacade,
		private ProductPriceManager $priceManager,
	) {
		$productRepository = $entityManager->getRepository(Product::class);
		$productCategoryRepository = $entityManager->getRepository(ProductCategory::class);
		$productVariantRepository = $entityManager->getRepository(ProductVariant::class);
		$relatedProductRepository = $entityManager->getRepository(RelatedProduct::class);
		$productImageRepository = $entityManager->getRepository(ProductImage::class);
		assert($productRepository instanceof ProductRepository);
		assert($productCategoryRepository instanceof ProductCategoryRepository);
		assert($productVariantRepository instanceof ProductVariantRepository);
		assert($relatedProductRepository instanceof RelatedProductRepository);
		assert($productImageRepository instanceof ProductImageRepository);
		$this->productRepository = $productRepository;
		$this->productCategoryRepository = $productCategoryRepository;
		$this->productVariantRepository = $productVariantRepository;
		$this->relatedProductRepository = $relatedProductRepository;
		$this->productImageRepository = $productImageRepository;
	}


	public function actionDefault(?string $query = null, int $page = 1, int $limit = 32): void
	{
		$this->sendJson($this->productFeedFacade->getFeed(
			$query,
			$page,
			$limit,
		));
	}


	public function postActive(int $id): void
	{
		try {
			$product = $this->productRepository->getById($id);
		} catch (NoResultException | NonUniqueResultException) {
			$this->sendError(sprintf('Product "%d" does not exist.', $id));
		}
		$this->productManager->get()->setActive($product, !$product->isActive());
		$this->sendOk();
	}


	public function postSetPosition(int $id, int $position): void
	{
		try {
			$product = $this->productRepository->getById($id);
		} catch (NoResultException | NonUniqueResultException) {
			$this->sendError(sprintf('Product "%d" does not exist.', $id));
		}
		$this->productManager->get()->setPosition($product, $position);
		$this->sendOk();
	}


	public function postCreateProduct(string $name, string $code, int $price): void
	{
		try {
			$product = $this->productManager->get()->create($name, $code, $price);
		} catch (\InvalidArgumentException $e) {
			$this->sendError($e->getMessage());
		}
		$this->sendJson(
			[
				'id' => $product->getId(),
			],
		);
	}


	public function actionOverview(int $id): ProductData
	{
		try {
			$product = $this->productRepository->getById($id);
		} catch (NoResultException | NonUniqueResultException) {
			$this->sendError(sprintf('Product "%d" does not exist.', $id));
		}

		$cat = new SelectboxTree;
		/** @var array<int, array{id: int|string, name: string, parent_id: int|string|null}> $categories */
		$categories = $this->entityManager->getConnection()
			->executeQuery($cat->sqlBuilder('shop__product_category'))
			->fetchAllAssociative();

		/** @var array<int, array{id: int, name: Localization}> $brands */
		$brands = $this->entityManager->getRepository(Brand::class)
			->createQueryBuilder('brand')
			->select('PARTIAL brand.{id, name}')
			->getQuery()
			->getArrayResult();

		$mainImage = $product->getMainImage();
		$mainCategory = $product->getMainCategory();

		/** @var array<int, ProductSmartDescription> $smartDescriptionsData */
		$smartDescriptionsData = $this->entityManager->getRepository(ProductSmartDescription::class)
			->createQueryBuilder('description')
			->where('description.product = :productId')
			->setParameter('productId', $id)
			->orderBy('description.position', 'ASC')
			->getQuery()
			->getResult();

		$smartDescriptions = [];
		$smartDescriptionPositionChanged = false;
		foreach ($smartDescriptionsData as $smartDescriptionPosition => $description) {
			if ($description->getPosition() !== $smartDescriptionPosition) {
				$description->setPosition($smartDescriptionPosition);
				$smartDescriptionPositionChanged = true;
			}
			$smartDescriptionImage = $description->getImage();
			$smartDescriptions[] = [
				'id' => $description->getId(),
				'description' => (string) $description->getDescription(),
				'html' => $this->renderer->render((string) $description->getDescription()),
				'image' => $smartDescriptionImage !== null
					? ImageGenerator::from(
						sprintf('product-image/description/%s', $smartDescriptionImage),
						['w' => 100, 'h' => 100],
					)
					: null,
				'color' => $description->getColor(),
				'position' => $description->getPosition(),
			];
		}
		if ($smartDescriptionPositionChanged) {
			$this->entityManager->flush();
		}

		/** @var array<int, array{value: int, text: string}> $categoryList */
		$categoryList = $this->formatBootstrapSelectArray($cat->process($categories));

		$brandList = [
			['value' => null, 'text' => '--- No brand ---'],
		];
		foreach ($brands as $brand) {
			$brandList[$brand['id']] = (string) $brand['name'];
		}

		return new ProductData(
			id: $product->getId(),
			name: (string) $product->getName(),
			code: $product->getCode(),
			ean: $product->getEan(),
			slug: $product->getSlug(),
			active: $product->isActive(),
			shortDescription: (string) $product->getShortDescription(),
			description: (string) $product->getDescription(),
			price: $product->getPrice(),
			vat: $product->getVat(),
			standardPricePercentage: $product->getStandardPricePercentage(),
			url: $this->linkSafe('Front:Product:detail', ['slug' => $product->getSlug()]),
			soldOut: $product->isSoldOut(),
			mainCurrency: $this->currencyManager->get()->getMainCurrency()->getCode(),
			mainImage: $mainImage?->toArray(),
			mainCategoryId: $mainCategory?->getId(),
			brandId: $product->getBrand()?->getId(),
			customFields: $this->productFieldManager->getFieldsInfo($product),
			smartDescriptions: $smartDescriptions,
			categories: $categoryList,
			brands: $brandList,
		);
	}


	public function postSave(ProductData $productData): void
	{
		$product = $this->productRepository->getById($productData->id);
		$product->setName($productData->name);
		$product->setCode($productData->code);
		$product->setEan($productData->ean);
		$product->setSlug($productData->slug);
		$product->setActive($productData->active);
		$product->setShortDescription($productData->shortDescription);
		$product->setDescription($productData->description);
		$product->setPrice($productData->price);
		$product->setVat($productData->vat);
		$product->setStandardPricePercentage($productData->standardPricePercentage);
		$product->setSoldOut($productData->soldOut);

		if ($productData->mainCategoryId === null) {
			$product->setMainCategory(null);
		} else {
			$product->setMainCategory($this->productCategoryRepository->getById($productData->mainCategoryId));
		}
		if ($productData->brandId === null) {
			$product->setBrand(null);
		} else {
			/** @var Brand $brand */
			$brand = $this->entityManager->getRepository(Brand::class)->find($productData->brandId);
			$product->setBrand($brand);
		}
		if ($productData->customFields !== []) {
			$saveFields = [];
			foreach ($productData->customFields as $customField) {
				$saveFields[$customField['name']] = $customField['value'];
			}
			$this->productFieldManager->setFields($product, $saveFields);
		}

		$this->entityManager->flush();
		$this->flashMessage('Product has been saved.', 'success');
		$this->sendOk();
	}


	public function actionImages(int $id): void
	{
		$product = $this->productRepository->getById($id);
		$mainImage = $product->getMainImage();

		$images = [];
		foreach ($this->productImageRepository->getListByProduct($product) as $productImage) {
			$productImageVariant = $productImage->getVariant();
			$images[] = [
				'id' => $productImage->getId(),
				'source' => $productImage->getSource(),
				'title' => $productImage->getTitle(),
				'position' => $productImage->getPosition(),
				'variant' => $productImageVariant?->getId(),
			];
		}

		$variants = [null => '--- no variant ---'];
		foreach ($this->productVariantRepository->getListByProduct($product) as $variant) {
			$variants[$variant->getId()] = $variant->getRelationHash();
		}

		$this->sendJson(
			[
				'images' => $images,
				'mainImageId' => $mainImage?->getId(),
				'variants' => $variants,
			],
		);
	}


	/**
	 * @param array<int, array<string, mixed>> $images
	 */
	public function postSaveImages(int $productId, array $images, int $mainImageId): void
	{
		$product = $this->productRepository->getById($productId);
		$ids = array_map(static fn(array $image): int => (int) $image['id'], $images);

		$imageById = [];
		foreach ($this->productImageRepository->getByIds($ids) as $imageEntityItem) {
			$imageById[$imageEntityItem->getId()] = $imageEntityItem;
		}

		$mainImage = null;
		foreach ($images as $image) {
			$imageEntity = $imageById[$image['id']];
			$imageEntity->setTitle($image['title'] ?: null);
			$imageEntity->setPosition($image['position'] ?: 0);
			if ($image['variant'] !== null) {
				$imageEntity->setVariant($this->productVariantRepository->getById($image['variant']));
			}
			if ($image['id'] === $mainImageId) {
				$mainImage = $imageEntity;
			}
		}

		$product->setMainImage($mainImage);
		$this->entityManager->flush();
		$this->sendOk();
	}


	public function postUploadImage(): void
	{
		/** @var Request $request */
		$request = $this->container->getByType(Request::class);
		$productId = (int) $request->getPost('productId');

		/** @var FileUpload|null $image */
		$image = $request->getFile('mainImage');
		$product = $this->productRepository->getById($productId);

		if ($image === null) {
			$this->sendError('Please select media to upload.');
		}
		try {
			$this->productManager->get()->addImage(
				$product,
				$image->getTemporaryFile(),
				$image->getSanitizedName(),
			);
		} catch (\InvalidArgumentException $e) {
			$this->sendError($e->getMessage());
		}

		$this->sendOk();
	}


	public function actionDeleteImage(int $id): void
	{
		$image = $this->productImageRepository->getById($id);
		$this->productManager->get()->removeImage($image);
		$this->sendOk();
	}


	public function postAddSmartDescription(int $productId, string $description, int $position): void
	{
		$product = $this->productRepository->getById($productId);
		$desc = new ProductSmartDescription($product, $description);
		$desc->setPosition($position);

		$this->entityManager->persist($desc);
		$this->entityManager->flush();
		$this->sendOk();
	}


	public function postSaveSmartDescription(): void
	{
		/** @var Request $request */
		$request = $this->container->getByType(Request::class);

		/** @var ProductSmartDescription $desc */
		$desc = $this->entityManager->getRepository(ProductSmartDescription::class)
			->createQueryBuilder('description')
			->where('description.id = :id')
			->setParameter('id', (int) $request->getPost('id'))
			->setMaxResults(1)
			->getQuery()
			->getSingleResult();

		$desc->setDescription((string) $request->getPost('description'));
		$desc->setPosition((int) $request->getPost('position'));
		$desc->setColor(((string) $request->getPost('color')) ?: null);

		/** @var FileUpload|null $image */
		$image = $request->getFile('image');
		if ($image !== null) {
			if ($image->isImage() === false) {
				$this->sendError('Uploaded file must be a image.');
			}

			$desc->setImage(
				date('Y-m-d')
				. '/' . strtolower(Random::generate(8) . '-' . $image->getSanitizedName()),
			);
			$image->move($this->getParameter('wwwDir') . '/' . $desc->getImageRelativePath());
		}

		$this->entityManager->flush();
		$this->sendOk();
	}


	public function postDeleteSmartDescription(int $productId, int $descriptionId): void
	{
		foreach ($this->productRepository->getById($productId)->getSmartDescriptions() as $description) {
			if ($description->getId() === $descriptionId) {
				$this->entityManager->remove($description);
				$this->entityManager->flush();
				break;
			}
		}

		$this->sendOk();
	}


	public function actionParameters(int $productId): void
	{
		$parameters = $this->entityManager->getRepository(ProductParameter::class)
			->createQueryBuilder('param')
			->where('param.product = :productId')
			->setParameter('productId', $productId)
			->getQuery()
			->getArrayResult();

		$this->sendJson(
			[
				'parameters' => $parameters,
			],
		);
	}


	/**
	 * @param string[] $values
	 */
	public function postAddParameter(int $productId, string $name, array $values, bool $variant): void
	{
		$this->checkParameter($name, $values);
		$parameter = new ProductParameter($this->productRepository->getById($productId), $name, $values, $variant);
		$this->entityManager->persist($parameter);
		$this->entityManager->flush();
		$this->sendOk();
	}


	/**
	 * @param array<int, array<string, mixed>> $parameters
	 */
	public function postSaveParameters(array $parameters): void
	{
		$entityById = [];
		/** @var ProductParameter[] $parameterEntities */
		$parameterEntities = $this->entityManager->getRepository(ProductParameter::class)
			->createQueryBuilder('param')
			->where('param.id IN (:ids)')
			->setParameter('ids', array_map(static fn(array $item): int => (int) $item['id'], $parameters))
			->getQuery()
			->getResult();

		foreach ($parameterEntities as $parameterEntity) {
			$entityById[$parameterEntity->getId()] = $parameterEntity;
		}
		foreach ($parameters as $parameter) {
			/** @var ProductParameter|null $entity */
			$entity = $entityById[$parameter['id']] ?? null;
			$this->checkParameter($parameter['name'], $parameter['values']);
			if ($entity !== null) {
				$entity->setName((string) $parameter['name']);
				$entity->setValues((array) $parameter['values']);
				$entity->setVariant((bool) $parameter['variant']);
			}
		}

		$this->entityManager->flush();
		$this->flashMessage('Parameters has been saved.', 'success');
		$this->sendOk();
	}


	public function postDeleteParameter(string $id): void
	{
		/** @var ProductParameter $parameter */
		$parameter = $this->entityManager->getRepository(ProductParameter::class)
			->createQueryBuilder('param')
			->where('param.id = :id')
			->setParameter('id', $id)
			->setMaxResults(1)
			->getQuery()
			->getSingleResult();

		$this->entityManager->remove($parameter);
		$this->entityManager->flush();
		$this->sendOk();
	}


	public function actionRelated(int $id): void
	{
		$product = $this->productRepository->getById($id);
		$relatedList = $this->relatedProductRepository->getRelatedList($product);

		$this->sendJson(
			[
				'items' => array_map(static fn(array $item): array => $item['relatedProduct'], $relatedList),
			],
		);
	}


	public function actionRelatedCandidates(int $id, ?string $query = null): void
	{
		$product = $this->productRepository->getById($id);
		$productCategoryId = null;
		$mainCategory = $product->getMainCategory();
		if ($mainCategory !== null) {
			$productCategoryId = $mainCategory->getId();
		}

		$relatedIds = array_map(
			static fn(array $item): int => (int) $item['id'],
			$this->entityManager->getRepository(Product::class)
				->createQueryBuilder('p')
				->select('PARTIAL p.{id}')
				->leftJoin('p.productRelatedRelated', 'relation')
				->leftJoin('relation.product', 'product')
				->where('product.id = :productId')
				->setParameter('productId', $id)
				->getQuery()
				->getArrayResult(),
		);
		$relatedIds[] = $id;

		$selection = $this->entityManager->getRepository(Product::class)
			->createQueryBuilder('product')
			->select('PARTIAL product.{id, name}, PARTIAL mainCategory.{id, name}')
			->leftJoin('product.mainCategory', 'mainCategory')
			->andWhere('product.id NOT IN (:relatedIds)')
			->setParameter('relatedIds', $relatedIds)
			->orderBy('mainCategory.name', 'ASC')
			->addOrderBy('product.name', 'ASC')
			->setMaxResults(128);

		if ($query !== null) {
			$selection->andWhere('product.name LIKE :query')
				->setParameter('query', '%' . $query . '%');
		}

		$candidates = [];
		foreach ($selection->getQuery()->getArrayResult() as $candidate) {
			$score = 0;
			if (
				isset($candidate['mainCategory'])
				&& $productCategoryId === $candidate['mainCategory']['id']
			) { // same main category
				$score += 2;
			}

			$candidates[] = [
				'score' => $score,
				'product' => $candidate,
			];
		}

		usort(
			$candidates,
			static fn(array $a, array $b): int => $a['score'] < $b['score'] ? 1 : -1,
		);

		$this->sendJson(
			[
				'items' => array_map(static fn(array $item): array => $item['product'], $candidates),
			],
		);
	}


	public function actionAddRelated(int $id, int $relatedId): void
	{
		if ($id === $relatedId) {
			$this->sendError('The product cannot be relevant to itself.');
		}

		$product = $this->productRepository->getById($id);
		$relatedProduct = $this->productRepository->getById($relatedId);
		if ($this->relatedProductRepository->isRelationExist($product, $relatedProduct) === false) {
			$this->entityManager->persist(new RelatedProduct($product, $relatedProduct));
			$this->entityManager->flush();
		}

		$this->sendOk();
	}


	public function actionDeleteRelated(int $id, int $relatedId): void
	{
		try {
			$product = $this->productRepository->getById($id);
			$relatedProduct = $this->productRepository->getById($relatedId);
			$this->entityManager->remove($this->relatedProductRepository->getRelation($product, $relatedProduct));
			$this->entityManager->flush();
		} catch (NoResultException | NonUniqueResultException) {
		}

		$this->sendOk();
	}


	public function actionVariants(int $id): void
	{
		$product = $this->productRepository->getById($id);

		$variantList = [];
		foreach ($this->productVariantRepository->getListByProduct($product) as $variant) {
			$variantList[] = [
				'id' => $variant->getId(),
				'relationHash' => $variant->getRelationHash(),
				'ean' => $variant->getEan(),
				'code' => $variant->getCode(),
				'parameters' => ProductVariant::unserializeParameters($variant->getRelationHash()),
				'price' => $variant->getDefinedPrice(false),
				'priceAddition' => $variant->getPriceAddition(),
				'realPrice' => $variant->getPrice(false),
				'soldOut' => $variant->isSoldOut(),
				'warehouseAllQuantity' => $variant->getWarehouseAllQuantity(),
			];
		}

		$this->sendJson(
			[
				'mainCurrency' => $this->currencyManager->get()->getMainCurrency()->getCode(),
				'list' => $variantList,
				'defaultCode' => $product->getCode(),
				'productPrice' => $product->getSalePrice(),
				'variantParameters' => $variantParameters = $this->getVariantParameters($id),
				'variantCount' => \count($variantList),
				'possibleVariantCount' => (new CombinationGenerator)->countCombinations($variantParameters),
			],
		);
	}


	public function actionGenerateVariants(int $id): void
	{
		$product = $this->productRepository->getById($id);
		$variantToHash = [];
		foreach ($this->productVariantRepository->getListByProduct($id) as $variantItem) {
			$variantToHash[$variantItem->getRelationHash()] = $variantItem->getId();
		}
		foreach ((new CombinationGenerator)->generate($this->getVariantParameters($id)) as $variantParameters) {
			$hash = ProductVariant::serializeParameters($variantParameters);
			if (isset($variantToHash[$hash]) === false) {
				$this->entityManager->persist(new ProductVariant($product, $hash));
			}
		}
		$this->entityManager->flush();
		$this->flashMessage('Product variants has been created.', 'success');
		$this->sendOk();
	}


	/**
	 * @param array<int, array<string, mixed>> $variants
	 */
	public function postSaveVariants(int $id, array $variants): void
	{
		$variantById = [];
		foreach ($this->productVariantRepository->getListByProduct($id) as $variantEntity) {
			$variantById[$variantEntity->getId()] = $variantEntity;
		}
		foreach ($variants as $variant) {
			if (isset($variantById[$variant['id']]) === false) {
				$this->sendError(sprintf('Variant "%d" does not exist.', $variant['id']));
			}
			/** @var ProductVariant $entity */
			$entity = $variantById[$variant['id']];
			/** @phpstan-ignore-next-line */
			$entity->setPrice((string) $variant['price']);
			/** @phpstan-ignore-next-line */
			$entity->setPriceAddition((string) $variant['priceAddition']);
			$entity->setSoldOut((bool) $variant['soldOut']);
			$entity->setEan($variant['ean']);
			$entity->setCode($variant['code']);
			$entity->setWarehouseAllQuantity((int) $variant['warehouseAllQuantity']);
		}

		try {
			$this->entityManager->flush();
			$this->flashMessage('Variants has been saved.', self::FLASH_MESSAGE_ERROR);
		} catch (\Throwable $e) {
			$this->flashMessage(
				'Variants can not be saved, because some fields is duplicated. '
				. 'Please check this error: ' . $e->getMessage(),
				self::FLASH_MESSAGE_ERROR,
			);
		}
		$this->sendOk();
	}


	public function postRemoveVariant(int $id): void
	{
		$variant = $this->productVariantRepository->getById($id);
		$this->productManager->get()->removeVariant($variant);
		$this->sendOk();
	}


	public function actionStock(int $id): void
	{
		$product = $this->productRepository->getById($id);

		$this->sendJson(
			[
				'weight' => $product->getWeight(),
				'size' => [
					'width' => $product->getSizeWidth(),
					'length' => $product->getSizeLength(),
					'thickness' => $product->getSizeThickness(),
					'min' => $product->getMinimalSize(),
					'max' => $product->getMaximalSize(),
				],
			],
		);
	}


	public function postStock(int $id, ?int $weight, ?float $width, ?float $length, ?float $thickness): void
	{
		$product = $this->productRepository->getById($id);
		$product->setWeight($weight);
		$product->setSizeWidth($width);
		$product->setSizeLength($length);
		$product->setSizeThickness($thickness);

		$this->entityManager->flush();
		$this->flashMessage('Stock settings has been saved.', self::FLASH_MESSAGE_SUCCESS);
		$this->sendOk();
	}


	public function actionCategories(int $id): void
	{
		$product = $this->productRepository->getById($id);

		$categories = [];
		foreach ($product->getCategories() as $category) {
			$categories[] = [
				'id' => $category->getId(),
				'name' => (string) $category->getName(),
			];
		}

		$mainCategory = $product->getMainCategory();
		$this->sendJson(
			[
				'mainCategory' => $mainCategory !== null
					? (string) $mainCategory->getName()
					: 'No main category',
				'categories' => $categories,
			],
		);
	}


	public function actionRelatedCategories(int $id): void
	{
		$this->sendJson(
			[
				'items' => $this->productCategoryRepository->getRelated(
					$this->productRepository->getById($id),
				),
			],
		);
	}


	public function actionAddCategory(int $productId, int $categoryId): void
	{
		$product = $this->productRepository->getById($productId);
		$category = $this->productCategoryRepository->getById($categoryId);

		$product->addCategory($category);
		$this->entityManager->flush();
		$this->sendOk();
	}


	public function actionDeleteCategory(int $productId, int $categoryId): void
	{
		$product = $this->productRepository->getById($productId);
		$category = $this->productCategoryRepository->getById($categoryId);

		$product->removeCategory($category);
		$this->entityManager->flush();
		$this->sendOk();
	}


	public function postClone(int $id, string $name, string $code, string $slug): void
	{
		try {
			$product = $this->productManager->get()->cloneProduct($id, $name, $code, $slug);
		} catch (\InvalidArgumentException $e) {
			$this->flashMessage($e->getMessage(), self::FLASH_MESSAGE_ERROR);
			$this->sendError($e->getMessage());
		}
		$this->flashMessage(sprintf('Product "%s" has been cloned.', (string) $product->getName()), self::FLASH_MESSAGE_SUCCESS);
		$this->sendJson(
			[
				'id' => $product->getId(),
			],
		);
	}


	public function actionPriceList(int $id): void
	{
		$product = $this->productRepository->getById($id);
		$mainCurrency = $this->currencyManager->get()->getMainCurrency();

		$variantList = [];
		foreach ($this->productVariantRepository->getListByProduct($product) as $variant) {
			$variantPriceList = $this->priceManager->getPriceList($product, $variant);
			$variantList[] = [
				'id' => $variant->getId(),
				'label' => $variant->getLabel(),
				'priceList' => $this->formatPriceList($variantPriceList),
			];
		}

		$currencies = [];
		foreach ($this->currencyManager->get()->getCurrencies() as $currency) {
			$currencies[] = $currency->getCode();
		}

		$this->sendJson([
			'mainCurrency' => $mainCurrency->getCode(),
			'productPriceList' => $this->formatPriceList(
				$this->priceManager->getPriceList($product),
			),
			'productPriceListVariant' => $variantList,
			'currencies' => $currencies,
		]);
	}


	/**
	 * @param array<string, array{
	 *     currency: \Baraja\EcommerceStandard\DTO\CurrencyInterface,
	 *     price: numeric-string,
	 *     isManual: bool
	 * }> $list
	 * @return array<string, array{
	 *     currency: string,
	 *     price: numeric-string,
	 *     originalPrice: numeric-string,
	 *     isManual: bool
	 * }>
	 */
	private function formatPriceList(array $list): array
	{
		$return = [];
		foreach ($list as $currency => $item) {
			$item['originalPrice'] = $item['price'];
			$item['currency'] = $currency;
			$return[$currency] = $item;
		}

		return $return;
	}


	/**
	 * @return string[][]
	 */
	private function getVariantParameters(int $productId): array
	{
		/** @var array<int, array<string, string|array<string, string>>> $parameters */
		$parameters = $this->entityManager->getRepository(ProductParameter::class)
			->createQueryBuilder('parameter')
			->andWhere('parameter.product = :productId')
			->andWhere('parameter.variant = TRUE')
			->setParameter('productId', $productId)
			->orderBy('parameter.name', 'ASC')
			->getQuery()
			->getArrayResult();

		$return = [];
		foreach ($parameters as $parameter) {
			/** @phpstan-ignore-next-line */
			$return[(string) $parameter['name']] = array_map(
				static fn(string $value): string => Strings::firstUpper($value),
				(array) $parameter['values'],
			);
		}

		return $return;
	}


	/**
	 * @param array<int, string> $values
	 */
	private function checkParameter(string $name, array $values): void
	{
		if ($this->isEnumerableParameter($name) === false) {
			return;
		}
		$notInclude = [];
		foreach ($values as $value) {
			$value = strtolower($value);
			if (\in_array($value, $this->getExcludeMap(), true) === false) {
				$notInclude[] = $value;
			}
		}
		if ($notInclude !== []) {
			throw new \InvalidArgumentException(
				sprintf('Colors "%s" no found. Are they created on the color settings?', implode('", "', $notInclude)),
			);
		}
	}


	private function isEnumerableParameter(string $name): bool
	{
		$name = strtolower($name);
		$items = ['barva', 'color'];

		return in_array($name, $items, true);
	}


	/**
	 * @return array<int, string>
	 */
	private function getExcludeMap(): array
	{
		return [];
	}
}
