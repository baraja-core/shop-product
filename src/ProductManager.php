<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Doctrine\EntityManager;
use Baraja\DynamicConfiguration\Configuration;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductImage;
use Baraja\Shop\Product\Entity\ProductParameter;
use Baraja\Shop\Product\Entity\ProductSmartDescription;
use Baraja\Shop\Product\Entity\ProductVariant;
use Baraja\Shop\Product\Entity\RelatedProduct;
use Baraja\Shop\Product\FileSystem\ProductFileSystem;
use Baraja\Shop\Product\FileSystem\ProductImageFileSystem;
use Baraja\Shop\Product\Repository\ProductRepository;
use Baraja\Shop\Product\Repository\ProductVariantRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Utils\Random;

final class ProductManager
{
	private ProductFileSystem $fileSystem;

	private ProductRepository $productRepository;

	private ProductVariantRepository $productVariantRepository;


	public function __construct(
		private EntityManager $entityManager,
		private Configuration $configuration,
		?ProductFileSystem $fileSystem = null,
	) {
		$this->fileSystem = $fileSystem ?? new ProductImageFileSystem;
		$productRepository = $entityManager->getRepository(Product::class);
		assert($productRepository instanceof ProductRepository);
		$productVariantRepository = $entityManager->getRepository(ProductVariant::class);
		assert($productVariantRepository instanceof ProductVariantRepository);
		$this->productRepository = $productRepository;
		$this->productVariantRepository = $productVariantRepository;
	}


	public function getProductByEan(string $ean): Product|ProductVariant|null
	{
		try {
			return $this->productRepository->getByEan($ean);
		} catch (NoResultException | NonUniqueResultException) {
			// Silence is golden.
		}
		try {
			return $this->productVariantRepository->getByEan($ean);
		} catch (NoResultException | NonUniqueResultException) {
			// Silence is golden.
		}

		return null;
	}


	/**
	 * @return array<int, Product>
	 */
	public function getRelatedProducts(Product $product): array
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


	public function getWeight(Product $product): int
	{
		return $product->getWeight() ?? $this->getDefaultWeight();
	}


	public function getDefaultWeight(): int
	{
		$value = $this->configuration->get('default-weight', 'shop');
		if ($value === null) {
			$value = 300;
			$this->configuration->save('default-weight', (string) $value, 'shop');
		}

		return (int) $value;
	}


	public function create(string $name, string $code, int $price): Product
	{
		if ($name === '' || $code === '') {
			throw new \InvalidArgumentException('Please enter all fields.');
		}
		if ($this->codeExist($code)) {
			throw new \InvalidArgumentException(sprintf('Product with code "%s" already exist.', $code));
		}

		$product = new Product($name, $code, (string) $price);
		$this->entityManager->persist($product);
		$this->entityManager->flush();

		return $product;
	}


	public function setPosition(Product $product, int $position): void
	{
		$product->setPosition($position);
		$this->entityManager->flush();
	}


	public function setActive(Product $product, bool $active): void
	{
		$product->setActive($active);
		$this->entityManager->flush();
	}


	public function addImage(Product $product, string $path, ?string $sanitizedName = null): void
	{
		if (is_file($path) === false) {
			throw new \InvalidArgumentException(sprintf('Given file does not exist. Path "%s" given.', $path));
		}
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		/** @phpstan-ignore-next-line */
		$type = finfo_file($finfo, $path);
		if (in_array($type, ['image/gif', 'image/png', 'image/jpeg', 'image/webp'], true) === false) {
			throw new \InvalidArgumentException(sprintf('Given file must be a image. Path "%s" given.', $path));
		}

		if ($sanitizedName === null) {
			$sanitizedName = basename($path);
		}
		$source = date('Y-m-d') . '/' . strtolower(Random::generate(8) . '-' . $sanitizedName);
		$productImage = new ProductImage($product, $source);
		$this->fileSystem->save($productImage, $path);
		$this->entityManager->persist($productImage);
		if ($product->getMainImage() === null) {
			$product->setMainImage($productImage);
		}
		$this->entityManager->flush();
	}


	public function removeImage(ProductImage $image): void
	{
		$mainImage = $image->getProduct()->getMainImage();
		if ($mainImage !== null && $mainImage->getId() === $image->getId()) {
			$image->getProduct()->setMainImage(null);
		}

		$this->fileSystem->delete($image);
		$this->entityManager->remove($image);
		$this->entityManager->flush();
	}


	public function removeVariant(ProductVariant $variant): void
	{
		$this->entityManager->remove($variant);
		$this->entityManager->flush();
	}


	public function codeExist(string $code): bool
	{
		try {
			$this->productRepository->getByCode($code);
		} catch (NoResultException | NonUniqueResultException) {
			return false;
		}

		return true;
	}


	public function slugExist(string $slug): bool
	{
		try {
			$this->productRepository->getBySlug($slug);
		} catch (NoResultException | NonUniqueResultException) {
			return false;
		}

		return true;
	}


	public function cloneProduct(int $id, string $name, string $code, string $slug): Product
	{
		if ($this->codeExist($code)) {
			throw new \InvalidArgumentException(sprintf('Product with code "%s" already exist.', $code));
		}
		if ($this->slugExist($slug)) {
			throw new \InvalidArgumentException(sprintf('Product with slug "%s" already exist.', $slug));
		}
		$original = $this->productRepository->getById($id);

		$product = new Product($name, $code, $original->getPrice());
		$product->setSlug($slug);
		$product->setMainImage($original->getMainImage());
		$product->setShortDescription((string) $original->getShortDescription());
		$product->setDescription((string) $original->getDescription());
		$product->setStandardPricePercentage($original->getStandardPricePercentage());
		$product->setPosition($original->getPosition());
		$product->setShowInFeed($original->isShowInFeed());
		$product->setSoldOut($original->isSoldOut());
		$product->setVat($original->getVat());
		$product->setMainCategory($original->getMainCategory());
		$product->setManufacturer($original->getManufacturer());
		$product->setSizeLength($original->getSizeLength());
		$product->setSizeWidth($original->getSizeWidth());
		$product->setSizeThickness($original->getSizeThickness());
		$product->setWeight($original->getWeight());
		$this->entityManager->persist($product);

		foreach ($original->getCategories() as $category) {
			$product->addCategory($category);
		}
		foreach ($original->getLabels() as $label) {
			$product->addLabel($label);
		}
		foreach ($original->getSmartDescriptions() as $smartDescription) {
			$desc = new ProductSmartDescription($product, (string) $smartDescription->getDescription());
			$desc->setPosition($smartDescription->getPosition());
			$desc->setImage($smartDescription->getImage());
			$desc->setColor($smartDescription->getColor());
			$this->entityManager->persist($desc);
		}
		foreach ($original->getParameters() as $parameter) {
			$this->entityManager->persist(
				new ProductParameter(
					product: $product,
					name: $parameter->getName(),
					values: $parameter->getValues(),
					variant: $parameter->isVariant(),
				),
			);
		}
		foreach ($original->getProductRelatedBasic() as $relatedProduct) {
			$this->entityManager->persist(
				new RelatedProduct(
					product: $product,
					relatedProduct: $relatedProduct->getRelatedProduct(),
				),
			);
		}
		$this->entityManager->flush();

		return $product;
	}
}
