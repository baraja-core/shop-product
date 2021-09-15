<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Doctrine\EntityManager;
use Baraja\DynamicConfiguration\Configuration;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductImage;
use Baraja\Shop\Product\Entity\ProductParameter;
use Baraja\Shop\Product\Entity\ProductSmartDescription;
use Baraja\Shop\Product\Entity\RelatedProduct;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Utils\FileSystem;
use Nette\Utils\Random;

final class ProductManager
{
	public function __construct(
		private string $wwwDir,
		private EntityManager $entityManager,
		private Configuration $configuration,
	) {
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getProductById(int $id): Product
	{
		return $this->entityManager->getRepository(Product::class)
			->createQueryBuilder('product')
			->select('product, image')
			->leftJoin('product.images', 'image')
			->where('product.id = :id')
			->setParameter('id', $id)
			->orderBy('image.position', 'DESC')
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getProductBySlug(string $slug): Product
	{
		return $this->entityManager->getRepository(Product::class)
			->createQueryBuilder('product')
			->select('product, image')
			->leftJoin('product.images', 'image')
			->where('product.slug = :slug')
			->setParameter('slug', $slug)
			->orderBy('image.position', 'DESC')
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getProductByCode(string $code): Product
	{
		return $this->entityManager->getRepository(Product::class)
			->createQueryBuilder('product')
			->select('product, image')
			->leftJoin('product.images', 'image')
			->where('product.code = :code')
			->setParameter('code', $code)
			->orderBy('image.position', 'DESC')
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @return Product[]
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
				->getResult()
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
		if (!$name || !$code || !$price) {
			throw new \InvalidArgumentException('Please enter all fields.');
		}
		if ($this->codeExist($code)) {
			throw new \InvalidArgumentException('Product with code "' . $code . '" already exist.');
		}

		$product = new Product($name, $code, $price);
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
			throw new \InvalidArgumentException('Given file does not exist. Path "' . $path . '" given.');
		}
		$type = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
		if (in_array($type, ['image/gif', 'image/png', 'image/jpeg', 'image/webp'], true) === false) {
			throw new \InvalidArgumentException('Given file must be a image. Path "' . $path . '" given.');
		}

		if ($sanitizedName === null) {
			$sanitizedName = basename($path);
		}
		$source = date('Y-m-d') . '/' . strtolower(Random::generate(8) . '-' . $sanitizedName);
		$productImage = new ProductImage($product, $source);
		$diskPath = $this->wwwDir . '/' . $productImage->getRelativePath();
		FileSystem::copy($path, $diskPath);

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

		FileSystem::delete($this->wwwDir . '/' . $image->getRelativePath());
		$this->entityManager->remove($image);
		$this->entityManager->flush();
	}


	public function codeExist(string $code): bool
	{
		try {
			$this->getProductByCode($code);
		} catch (NoResultException | NonUniqueResultException) {
			return false;
		}

		return true;
	}


	public function slugExist(string $slug): bool
	{

		try {
			$this->getProductBySlug($slug);
		} catch (NoResultException | NonUniqueResultException) {
			return false;
		}

		return true;
	}


	public function cloneProduct(int $id, string $name, string $code, string $slug): Product
	{
		if ($this->codeExist($code)) {
			throw new \InvalidArgumentException('Product with code "' . $code . '" already exist.');
		}
		if ($this->slugExist($slug)) {
			throw new \InvalidArgumentException('Product with slug "' . $slug . '" already exist.');
		}
		$original = $this->getProductById($id);

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
					variant: $parameter->isVariant()
				)
			);
		}
		foreach ($original->getProductRelatedBasic() as $relatedProduct) {
			$this->entityManager->persist(
				new RelatedProduct(
					product: $product,
					relatedProduct: $relatedProduct->getRelatedProduct()
				)
			);
		}
		$this->entityManager->flush();

		return $product;
	}
}
