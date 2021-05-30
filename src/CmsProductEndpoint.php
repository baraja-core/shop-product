<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Combinations\CombinationGenerator;
use Baraja\Doctrine\EntityManager;
use Baraja\ImageGenerator\ImageGenerator;
use Baraja\Markdown\CommonMarkRenderer;
use Baraja\Search\Search;
use Baraja\SelectboxTree\SelectboxTree;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductCategory;
use Baraja\Shop\Product\Entity\ProductImage;
use Baraja\Shop\Product\Entity\ProductParameter;
use Baraja\Shop\Product\Entity\ProductSmartDescription;
use Baraja\Shop\Product\Entity\ProductVariant;
use Baraja\Shop\Product\Entity\RelatedProduct;
use Baraja\StructuredApi\BaseEndpoint;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Http\FileUpload;
use Nette\Http\Request;
use Nette\Utils\FileSystem;
use Nette\Utils\Paginator;
use Nette\Utils\Random;
use Nette\Utils\Strings;

final class CmsProductEndpoint extends BaseEndpoint
{
	public function __construct(
		private EntityManager $entityManager,
		private CommonMarkRenderer $renderer,
		private Search $search
	) {
	}


	public function actionDefault(?string $query = null, int $page = 1, int $limit = 32): void
	{
		$selection = $this->entityManager->getRepository(Product::class)
			->createQueryBuilder('product')
			->select('PARTIAL product.{id, name, code, ean, shortDescription, price, position, active, soldOut}')
			->addSelect('PARTIAL mainImage.{id, source}')
			->addSelect('PARTIAL mainCategory.{id, name}')
			->leftJoin('product.mainImage', 'mainImage')
			->leftJoin('product.mainCategory', 'mainCategory');

		if ($query !== null) {
			$selection->andWhere('product.id IN (:searchIds)')
				->setParameter(
					'searchIds',
					$this->search->search($query, [
						Product::class => [
							'name',
							'code',
							'ean',
							'shortDescription',
							'price',
							'smartDescriptions.description',
						],
					], useAnalytics: false)->getIds()
				);
		}

		$count = (int) (clone $selection)->select('COUNT(product.id)')
			->getQuery()
			->getSingleScalarResult();

		$items = $selection->orderBy('product.active', 'DESC')
			->addOrderBy('product.position', 'DESC')
			->setMaxResults($limit)
			->setFirstResult(($page - 1) * $limit)
			->getQuery()
			->getArrayResult();

		$return = [];
		foreach ($items as $item) {
			$mainImage = $item['mainImage'];
			if ($mainImage !== null) {
				$item['mainImage']['source'] = ImageGenerator::from($mainImage['source'], ['w' => 100, 'h' => 100]);
				$item['shortDescription'] = Strings::truncate(strip_tags($this->renderer->render($item['shortDescription'])), 128);
			}
			$return[] = $item;
		}

		$this->sendJson([
			'count' => $count,
			'items' => $return,
			'paginator' => (new Paginator)
				->setItemCount($count)
				->setItemsPerPage($limit)
				->setPage($page),
		]);
	}


	public function postActive(int $id): void
	{
		try {
			$product = $this->getProductById($id);
		} catch (NoResultException | NonUniqueResultException) {
			$this->sendError('Produkt "' . $id . '" neexistuje.');
		}
		$product->setActive(!$product->isActive());
		$this->entityManager->flush();
		$this->sendOk();
	}


	public function postSetPosition(int $id, int $position): void
	{
		try {
			$product = $this->getProductById($id);
		} catch (NoResultException | NonUniqueResultException) {
			$this->sendError('Produkt "' . $id . '" neexistuje.');
		}

		$product->setPosition($position);
		$this->entityManager->flush();
		$this->sendOk();
	}


	public function postCreateProduct(string $name, string $code, int $price): void
	{
		if (!$name || !$code || !$price) {
			$this->sendError('Vyplňte, prosím, všechna pole.');
		}

		$product = new Product($name, $code, $price);
		$this->entityManager->persist($product);
		$this->entityManager->flush();
		$this->sendJson([
			'id' => $product->getId(),
		]);
	}


	public function actionOverview(int $id): void
	{
		try {
			$product = $this->getProductById($id);
		} catch (NoResultException | NonUniqueResultException) {
			$this->sendError('Produkt "' . $id . '" neexistuje.');
		}

		$cat = new SelectboxTree;
		$categories = $this->entityManager->getConnection()
			->executeQuery($cat->sqlBuilder('cm__product_category'))
			->fetchAllAssociative();

		$this->sendJson([
			'id' => $product->getId(),
			'name' => (string) $product->getName(),
			'code' => $product->getCode(),
			'ean' => $product->getEan(),
			'slug' => $product->getSlug(),
			'active' => $product->isActive(),
			'shortDescription' => (string) $product->getShortDescription(),
			'price' => $product->getPrice(),
			'vat' => $product->getVat(),
			'standardPricePercentage' => $product->getStandardPricePercentage(),
			'url' => $this->link('Front:Product:detail', [
				'slug' => $product->getSlug(),
			]),
			'soldOut' => $product->isSoldOut(),
			'mainImage' => (static function (?ProductImage $image): ?array {
				if ($image === null) {
					return null;
				}

				return $image->toArray();
			})($product->getMainImage()),
			'mainCategoryId' => (static fn(?ProductCategory $category): ?int => $category === null ? null : $category->getId())($product->getMainCategory()),
			'dynamicDescriptions' => (function (array $descriptions): array {
				$return = [];
				foreach ($descriptions as $description) {
					$return[] = [
						'id' => (int) $description['id'],
						'description' => (string) $description['description'],
						'html' => $this->renderer->render((string) $description['description']),
						'image' => $description['image']
							? ImageGenerator::from('product-image/description/' . $description['image'], ['w' => 100, 'h' => 100])
							: null,
						'color' => $description['color'],
						'position' => $description['position'],
					];
				}

				return $return;
			})($this->entityManager->getRepository(ProductSmartDescription::class)
				->createQueryBuilder('description')
				->where('description.product = :productId')
				->setParameter('productId', $id)
				->orderBy('description.position', 'ASC')
				->getQuery()
				->getArrayResult()
			),
			'categories' => $this->formatBootstrapSelectArray($cat->process($categories)),
		]);
	}


	public function postSave(
		int $productId,
		string $name,
		string $code,
		?string $ean,
		string $slug,
		bool $active,
		?string $shortDescription,
		int $price,
		int $vat,
		?float $standardPricePercentage,
		bool $soldOut,
		?int $mainCategoryId = null,
	): void {
		$product = $this->getProductById($productId);
		$product->setName($name);
		$product->setCode($code);
		$product->setEan($ean);
		$product->setSlug($slug);
		$product->setActive($active);
		$product->setShortDescription($shortDescription);
		$product->setPrice($price);
		$product->setVat($vat);
		$product->setStandardPricePercentage($standardPricePercentage);
		$product->setSoldOut($soldOut);

		if ($mainCategoryId === null) {
			$product->setMainCategory(null);
		} else {
			/** @var ProductCategory $mainCategory */
			$mainCategory = $this->entityManager->getRepository(ProductCategory::class)->find($mainCategoryId);
			$product->setMainCategory($mainCategory);
		}

		$this->entityManager->flush();
		$this->flashMessage('Detail produktu byl uložen.', 'success');
		$this->sendOk();
	}


	public function actionImages(int $id): void
	{
		$product = $this->getProductById($id);
		$productImages = $this->entityManager->getRepository(ProductImage::class)
			->createQueryBuilder('productImage')
			->select('productImage, PARTIAL variant.{id, relationHash}')
			->leftJoin('productImage.variant', 'variant')
			->where('productImage.product = :productId')
			->setParameter('productId', $id)
			->orderBy('productImage.position', 'DESC')
			->getQuery()
			->getArrayResult();

		$images = [];
		foreach ($productImages as $productImage) {
			$images[] = [
				'id' => $productImage['id'],
				'source' => $productImage['source'],
				'title' => $productImage['title'] ?? null,
				'position' => $productImage['position'],
				'variant' => isset($productImage['variant'])
					? $productImage['variant']['id']
					: null,
			];
		}

		$this->sendJson([
			'images' => $images,
			'mainImageId' => ($mainImage = $product->getMainImage()) ? $mainImage->getId() : null,
			'variants' => $this->formatBootstrapSelectArray((static function (array $variants): array {
				$return = [
					[null => '--- žádná varianta ---'],
				];
				foreach ($variants as $variant) {
					$return[$variant['id']] = $variant['relationHash'];
				}

				return $return;
			})(
				$this->entityManager->getRepository(ProductVariant::class)
					->createQueryBuilder('v')
					->select('PARTIAL v.{id, relationHash}')
					->where('v.product = :productId')
					->setParameter('productId', $id)
					->getQuery()
					->getArrayResult()
			)),
		]);
	}


	/**
	 * @param array<int, array<string, mixed>> $images
	 */
	public function postSaveImages(int $productId, array $images, int $mainImageId): void
	{
		$product = $this->getProductById($productId);

		/** @var ProductImage[] $imageEntities */
		$imageEntities = $this->entityManager->getRepository(ProductImage::class)
			->createQueryBuilder('image')
			->where('image.id IN (:ids)')
			->setParameter('ids', array_map(fn(array $image): int => (int) $image['id'], $images))
			->getQuery()
			->getResult();

		$imageById = [];
		foreach ($imageEntities as $imageEntityItem) {
			$imageById[$imageEntityItem->getId()] = $imageEntityItem;
		}

		$mainImage = null;
		foreach ($images as $image) {
			$imageEntity = $imageById[$image['id']];
			$imageEntity->setTitle($image['title'] ?: null);
			$imageEntity->setPosition($image['position'] ?: 0);
			if ($image['variant'] !== null) {
				/** @var ProductVariant $variant */
				$variant = $this->entityManager->getRepository(ProductVariant::class)->find($image['variant']);
				$imageEntity->setVariant($variant);
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
		$product = $this->getProductById($productId);

		if ($image === null) {
			$this->sendError('Vyberte obrázek k uploadu.');
		}
		if ($image->isImage() === false) {
			$this->sendError('Nahrávaný soubor musí být obrázek.');
		}

		$source = date('Y-m-d') . '/' . strtolower(Random::generate(8) . '-' . $image->getSanitizedName());
		$productImage = new ProductImage($product, $source);
		$absolutePath = $this->getParameter('wwwDir') . '/' . $productImage->getRelativePath();
		$image->move($absolutePath);

		$this->entityManager->persist($productImage);
		if ($product->getMainImage() === null) {
			$product->setMainImage($productImage);
		}

		$this->entityManager->flush();
		$this->sendOk();
	}


	public function actionDeleteImage(int $id): void
	{
		/** @var ProductImage $image */
		$image = $this->entityManager->getRepository(ProductImage::class)->find($id);

		$mainImage = $image->getProduct()->getMainImage();
		if ($mainImage !== null && $mainImage->getId() === $id) {
			$image->getProduct()->setMainImage(null);
		}

		FileSystem::delete($this->getParameter('wwwDir') . '/' . $image->getRelativePath());
		$this->entityManager->remove($image);
		$this->entityManager->flush();
		$this->sendOk();
	}


	public function postAddDynamicDescription(int $productId, string $description, int $position): void
	{
		$product = $this->getProductById($productId);
		$desc = new ProductSmartDescription($product, $description);
		$desc->setPosition($position);

		$this->entityManager->persist($desc);
		$this->entityManager->flush();
		$this->sendOk();
	}


	public function postSaveDynamicDescription(): void
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
				$this->sendError('Nahrávaný soubor musí být obrázek.');
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


	public function postDeleteDynamicDescription(int $productId, int $descriptionId): void
	{
		foreach ($this->getProductById($productId)->getSmartDescriptions() as $description) {
			if ($description->getId() === $descriptionId) {
				$this->entityManager->remove($description)->flush();
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

		$this->sendJson([
			'parameters' => $parameters,
		]);
	}


	/**
	 * @param string[] $values
	 */
	public function postAddParameter(int $productId, string $name, array $values, bool $variant): void
	{
		$this->checkParameter($name, $values);
		$parameter = new ProductParameter($this->getProductById($productId), $name, $values, $variant);
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
		$this->flashMessage('Parametry byly uloženy.', 'success');
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
		/** @var array<int, array<string, mixed>> $products */
		$products = $this->entityManager->getRepository(RelatedProduct::class)
			->createQueryBuilder('r')
			->select('PARTIAL r.{id}, PARTIAL product.{id, name}, PARTIAL mainCategory.{id, name}')
			->leftJoin('r.relatedProduct', 'product')
			->leftJoin('product.mainCategory', 'mainCategory')
			->where('r.product = :productId')
			->setParameter('productId', $id)
			->orderBy('mainCategory.name', 'ASC')
			->addOrderBy('product.name', 'ASC')
			->getQuery()
			->getArrayResult();

		$this->sendJson([
			'items' => array_map(static fn(array $item): array => $item['relatedProduct'], $products),
		]);
	}


	public function actionRelatedCandidates(int $id, ?string $query = null): void
	{
		$product = $this->getProductById($id);
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
				->getArrayResult()
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

		$this->sendJson([
			'items' => array_map(static fn(array $item): array => $item['product'], $candidates),
		]);
	}


	public function actionAddRelated(int $id, int $relatedId): void
	{
		if ($id === $relatedId) {
			$this->sendError('Produkt nemůže být relevantní sám k sobě.');
		}

		try { // relation exist?
			$this->entityManager->getRepository(RelatedProduct::class)
				->createQueryBuilder('r')
				->where('r.product = :productId')
				->andWhere('r.relatedProduct = :relatedProductId')
				->setParameter('productId', $id)
				->setParameter('relatedProductId', $relatedId)
				->setMaxResults(1)
				->getQuery()
				->getSingleResult();
		} catch (NoResultException | NonUniqueResultException) {
			$this->entityManager->persist(new RelatedProduct(
				$this->getProductById($id),
				$this->getProductById($relatedId)
			));
			$this->entityManager->flush();
		}

		$this->sendOk();
	}


	public function actionDeleteRelated(int $id, int $relatedId): void
	{
		try {
			$relation = $this->entityManager->getRepository(RelatedProduct::class)
				->createQueryBuilder('r')
				->where('r.product = :productId')
				->andWhere('r.relatedProduct = :relatedProductId')
				->setParameter('productId', $id)
				->setParameter('relatedProductId', $relatedId)
				->setMaxResults(1)
				->getQuery()
				->getSingleResult();

			$this->entityManager->remove($relation);
			$this->entityManager->flush();
		} catch (NoResultException | NonUniqueResultException) {
		}

		$this->sendOk();
	}


	public function actionVariants(int $id): void
	{
		$product = $this->getProductById($id);

		/** @var ProductVariant[] $variants */
		$variants = $this->entityManager->getRepository(ProductVariant::class)
			->createQueryBuilder('variant')
			->where('variant.product = :productId')
			->setParameter('productId', $id)
			->orderBy('variant.soldOut', 'DESC')
			->addOrderBy('variant.price', 'DESC')
			->addOrderBy('variant.relationHash', 'ASC')
			->getQuery()
			->getResult();

		$variantList = [];
		foreach ($variants as $variant) {
			$variantList[] = [
				'id' => $variant->getId(),
				'relationHash' => $variant->getRelationHash(),
				'ean' => $variant->getEan(),
				'code' => $variant->getCode(),
				'parameters' => ProductVariant::unserializeParameters($variant->getRelationHash()),
				'price' => $variant->getPrice(false),
				'priceAddition' => $variant->getPriceAddition(),
				'realPrice' => $variant->getPrice(false),
				'soldOut' => $variant->isSoldOut(),
			];
		}

		$this->sendJson([
			'list' => $variantList,
			'defaultCode' => $product->getCode(),
			'productPrice' => $product->getSalePrice(),
			'variantParameters' => $variantParameters = $this->getVariantParameters($id),
			'variantCount' => \count($variantList),
			'possibleVariantCount' => (new CombinationGenerator)->countCombinations($variantParameters),
		]);
	}


	public function actionGenerateVariants(int $id): void
	{
		$product = $this->getProductById($id);

		/** @var mixed[][] $variants */
		$variants = $this->entityManager->getRepository(ProductVariant::class)
			->createQueryBuilder('variant')
			->where('variant.product = :productId')
			->setParameter('productId', $id)
			->getQuery()
			->getArrayResult();

		$variantToHash = [];
		foreach ($variants as $variantItem) {
			$variantToHash[$variantItem['relationHash']] = $variantItem['id'];
		}
		foreach ((new CombinationGenerator)->generate($this->getVariantParameters($id)) as $variantParameters) {
			$hash = ProductVariant::serializeParameters($variantParameters);
			if (isset($variantToHash[$hash]) === false) {
				$this->entityManager->persist(new ProductVariant($product, $hash));
			}
		}
		$this->entityManager->flush();
		$this->flashMessage('Varianty produktu byly vygenerovány.', 'success');
		$this->sendOk();
	}


	/**
	 * @param array<int, array<string, mixed>> $variants
	 */
	public function postSaveVariants(int $id, array $variants): void
	{
		/** @var ProductVariant[] $variantEntities */
		$variantEntities = $this->entityManager->getRepository(ProductVariant::class)
			->createQueryBuilder('variant')
			->where('variant.product = :productId')
			->setParameter('productId', $id)
			->getQuery()
			->getResult();

		$variantById = [];
		foreach ($variantEntities as $variantEntity) {
			$variantById[$variantEntity->getId()] = $variantEntity;
		}
		foreach ($variants as $variant) {
			if (isset($variantById[$variant['id']]) === false) {
				$this->sendError('Variant "' . $variant['id'] . '" does not exist.');
			}
			/** @var ProductVariant $entity */
			$entity = $variantById[$variant['id']];
			$entity->setPrice((float) $variant['price']);
			$entity->setPriceAddition((float) $variant['priceAddition']);
			$entity->setSoldOut((bool) $variant['soldOut']);
			$entity->setEan($variant['ean']);
			$entity->setCode($variant['code']);
		}

		$this->entityManager->flush();
		$this->sendOk();
	}


	public function postRemoveVariant(int $id): void
	{
		/** @var ProductVariant $variant */
		$variant = $this->entityManager->getRepository(ProductVariant::class)->find($id);
		$this->entityManager->remove($variant);
		$this->entityManager->flush();
		$this->sendOk();
	}


	public function actionCategories(int $id): void
	{
		$product = $this->getProductById($id);

		$categories = [];
		foreach ($product->getCategories() as $category) {
			$categories[] = [
				'id' => $category->getId(),
				'name' => (string) $category->getName(),
			];
		}

		$mainCategory = $product->getMainCategory();
		$this->sendJson([
			'mainCategory' => $mainCategory !== null
				? (string) $mainCategory->getName()
				: 'No main category',
			'categories' => $categories,
		]);
	}


	public function actionRelatedCategories(int $id): void
	{
		$product = $this->getProductById($id);

		$selection = $this->entityManager->getRepository(ProductCategory::class)
			->createQueryBuilder('category')
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

		$this->sendJson([
			'items' => $return,
		]);
	}


	public function actionAddCategory(int $productId, int $categoryId): void
	{
		$product = $this->getProductById($productId);

		/** @var ProductCategory $category */
		$category = $this->entityManager->getRepository(ProductCategory::class)->find($categoryId);

		$product->addCategory($category);
		$this->entityManager->flush();
		$this->sendOk();
	}


	public function actionDeleteCategory(int $productId, int $categoryId): void
	{
		$product = $this->getProductById($productId);

		/** @var ProductCategory $category */
		$category = $this->entityManager->getRepository(ProductCategory::class)->find($categoryId);

		$product->removeCategory($category);
		$this->entityManager->flush();
		$this->sendOk();
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	private function getProductById(int $id): Product
	{
		return $this->entityManager->getRepository(Product::class)
			->createQueryBuilder('product')
			->select('product, mainImage,mainCategory')
			->leftJoin('product.mainImage', 'mainImage')
			->leftJoin('product.mainCategory', 'mainCategory')
			->where('product.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getSingleResult();
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
	 * @param string[] $values
	 */
	private function checkParameter(string $name, array $values): void
	{
		$name = strtolower($name);
		if ($name === 'barva' || $name === 'color') {
			$notInclude = [];
			foreach ($values as $value) {
				$value = strtolower($value);
				if (\in_array($value, $this->getExcludeMap(), true) === false) {
					$notInclude[] = $value;
				}
			}
			if ($notInclude !== []) {
				throw new \InvalidArgumentException(
					'Barvy "' . implode('", "', $notInclude) . '" nebyly nalezeny. Jsou založeny v tabulce barev?',
				);
			}
		}
	}


	/**
	 * @return string[]
	 */
	private function getExcludeMap(): array
	{
		return [];
	}
}
