<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Doctrine\EntityManager;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\RelatedProduct;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class ProductManager
{
	public function __construct(
		private EntityManager $entityManager,
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
}
