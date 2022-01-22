<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Repository;


use Baraja\EcommerceStandard\DTO\CurrencyInterface;
use Baraja\EcommerceStandard\DTO\ProductInterface;
use Baraja\EcommerceStandard\DTO\ProductVariantInterface;
use Baraja\Shop\Product\Entity\ProductPriceList;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

final class ProductPriceListRepository extends EntityRepository
{
	/**
	 * @return array<string, ProductPriceList>
	 */
	public function findAllManualSetPrices(
		ProductInterface $product,
		?ProductVariantInterface $variant,
	): array {
		/** @var array<int, ProductPriceList> $databaseResult */
		$databaseResult = $this->createProductAndVariantQueryBuilder($product, $variant)
			->getQuery()
			->getResult();

		$return = [];
		foreach ($databaseResult as $priceListItem) {
			$return[$priceListItem->getCurrency()->getCode()] = $priceListItem;
		}

		return $return;
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function findManualSetPrice(
		ProductInterface $product,
		?ProductVariantInterface $variant,
		CurrencyInterface $currency,
	): ProductPriceList {
		$return = $this->createProductAndVariantQueryBuilder($product, $variant)
			->andWhere('pl.currency = :currency')
			->setParameter('currency', $currency->getId())
			->setMaxResults(1)
			->getQuery()
			->getSingleResult();
		assert($return instanceof ProductPriceList);

		return $return;
	}


	private function createProductAndVariantQueryBuilder(
		ProductInterface $product,
		?ProductVariantInterface $variant,
	): QueryBuilder {
		$qb = $this->createQueryBuilder('pl')
			->where('pl.product = :product')
			->setParameter('product', $product->getId());

		if ($variant === null) {
			$qb->andWhere('pl.variant IS NULL');
		} else {
			$qb->andWhere('pl.variant = :variant')
				->setParameter('variant', $variant->getId());
		}

		return $qb;
	}
}
