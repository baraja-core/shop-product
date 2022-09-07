<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Shop\Currency\CurrencyManagerAccessor;
use Baraja\Shop\Price\Price;
use Baraja\Shop\Product\DTO\ProductCombinationFilterResult;
use Baraja\Shop\Product\DTO\ProductCombinationFilterVariantResult;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductVariant;
use Baraja\Shop\Product\Repository\ProductVariantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class ProductCombinationFilter
{
	private ProductVariantRepository $variantRepository;


	public function __construct(
		EntityManagerInterface $entityManager,
		private CurrencyManagerAccessor $currencyManagerAccessor,
	) {
		$variantRepository = $entityManager->getRepository(ProductVariant::class);
		assert($variantRepository instanceof ProductVariantRepository);
		$this->variantRepository = $variantRepository;
	}


	public function getFilter(Product $product, ?int $variantId = null): ProductCombinationFilterResult
	{
		/** @var array<string, array<int, ProductCombinationFilterVariantResult>> $variantsFeed */
		$variantsFeed = [];
		$usedVariants = [];
		$defaultGlobally = [];
		$defaultAvailable = [];
		foreach ($this->variantRepository->getVariantsInfoByProduct($product) as $variantItem) {
			$variantParameters = ProductVariant::unserializeParameters($variantItem['relationHash']);
			if ($variantParameters === []) {
				continue;
			}
			if ($defaultGlobally === []) {
				$defaultGlobally = $variantParameters;
			}
			if ($defaultAvailable === [] && $variantItem['soldOut'] === false) {
				$defaultAvailable = $variantParameters;
			}
			foreach ($variantParameters as $variantParameterKey => $variantParameterValue) {
				if (isset($usedVariants[$variantParameterKey][$variantParameterValue])) {
					continue;
				}
				$usedVariants[$variantParameterKey][$variantParameterValue] = true;
				if (isset($variantsFeed[$variantParameterKey]) === false) {
					$variantsFeed[$variantParameterKey] = [];
				}
				$variantsFeed[$variantParameterKey][] = new ProductCombinationFilterVariantResult(
					id: $variantItem['id'],
					hash: $variantItem['relationHash'],
					text: $variantParameterValue,
					value: $variantParameterValue,
				);
			}
		}

		$price = null;
		$regularPrice = null;
		if ($variantId !== null) {
			try {
				$productVariant = $this->variantRepository->getById($variantId);
			} catch (NoResultException|NonUniqueResultException) {
				throw new \OutOfRangeException(
					sprintf('Product "%d" does not contain variant "%d".', $product->getId(), $variantId),
				);
			}
			$variantRealParameters = ProductVariant::unserializeParameters($productVariant->getRelationHash());
			[$defaultGlobally, $defaultAvailable] = [$variantRealParameters, $variantRealParameters];
			$price = $productVariant->getPrice();
			$regularPrice = $productVariant->getPrice(false);
		}
		$currency = $this->currencyManagerAccessor->get()->getCurrencyResolver()->getCurrency();

		/** @var array<int, array{variantId: int, hash: string, available: bool, price: string, regularPrice: string, sale: bool}> $variantList */
		$variantList = [];

		foreach ($product->getVariants() as $variantItem) {
			$variantList[] = [
				'variantId' => $variantItem->getId(),
				'hash' => $variantItem->getRelationHash(),
				'available' => $variantItem->isSoldOut() === false,
				'price' => (new Price($variantItem->getPrice(), $currency))->render(true),
				'regularPrice' => (new Price($variantItem->getPrice(false), $currency))->render(true),
				'sale' => $variantItem->getProduct()->isSale(),
			];
		}

		return new ProductCombinationFilterResult(
			price: new Price($price ?? $product->getSalePrice(), $currency),
			regularPrice: new Price($regularPrice ?? $product->getPrice(), $currency),
			sale: $product->isSale(),
			variantList: $variantList,
			variants: $variantsFeed,
			default: $defaultAvailable !== [] ? $defaultAvailable : $defaultGlobally,
		);
	}
}
