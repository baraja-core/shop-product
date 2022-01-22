<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\EcommerceStandard\DTO\CurrencyInterface;
use Baraja\EcommerceStandard\DTO\PriceInterface;
use Baraja\EcommerceStandard\DTO\ProductInterface;
use Baraja\EcommerceStandard\DTO\ProductVariantInterface;
use Baraja\Shop\Currency\CurrencyManagerAccessor;
use Baraja\Shop\Currency\ExchangeRateConvertor;
use Baraja\Shop\Price\Price;
use Baraja\Shop\Product\Entity\ProductPriceList;
use Baraja\Shop\Product\Entity\ProductVariant;
use Baraja\Shop\Product\Repository\ProductPriceListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class ProductPriceManager
{
	private ProductPriceListRepository $priceListRepository;


	public function __construct(
		private EntityManagerInterface $entityManager,
		private CurrencyManagerAccessor $currencyManager,
		private ExchangeRateConvertor $exchangeRateConvertor,
	) {
		$priceListRepository = $entityManager->getRepository(ProductPriceList::class);
		assert($priceListRepository instanceof ProductPriceListRepository);
		$this->priceListRepository = $priceListRepository;
	}


	public function getPrice(
		ProductInterface $product,
		?ProductVariant $variant = null,
		?CurrencyInterface $currency = null,
	): PriceInterface {
		$currency ??= $this->currencyManager->get()->getMainCurrency();
		try {
			$price = $this->priceListRepository->findManualSetPrice($product, $variant, $currency)->getPrice();
		} catch (NoResultException | NonUniqueResultException) {
			$value = $variant !== null ? $variant->getPrice() : $product->getPrice();
			$price = $this->convertPriceToCurrency($value, $currency);
		}

		return new Price($price, $currency);
	}


	/**
	 * Returns a quick list of all product or variant prices by all available currencies in the store.
	 * For each price, a toggle is provided to see if it was set manually
	 * or calculated using the currency exchange rate.
	 *
	 * @return array<string, array{
	 *     currency: CurrencyInterface,
	 *     price: numeric-string,
	 *     isManual: bool
	 * }>
	 */
	public function getPriceList(ProductInterface $product, ?ProductVariantInterface $variant = null): array
	{
		$mainCurrencyCode = $this->currencyManager->get()->getMainCurrency()->getCode();
		$manualPrices = $this->priceListRepository->findAllManualSetPrices($product, $variant);

		$return = [];
		foreach ($this->currencyManager->get()->getCurrencies() as $currency) {
			$currencyCode = $currency->getCode();
			$isMainCurrency = $currencyCode === $mainCurrencyCode;
			if ($isMainCurrency) {
				$price = $variant !== null ? $variant->getPrice() : $product->getPrice();
			} elseif (isset($manualPrices[$currencyCode])) {
				$price = $manualPrices[$currencyCode]->getPrice();
			} else {
				$value = $variant !== null ? $variant->getPrice() : $product->getPrice();
				$price = $this->convertPriceToCurrency($value, $currency);
			}
			$return[$currencyCode] = [
				'currency' => $currency,
				'price' => $price,
				'isManual' => isset($manualPrices[$currencyCode]) || $isMainCurrency,
			];
		}

		return $return;
	}


	/**
	 * @param numeric-string $price
	 * @return numeric-string
	 */
	public function convertPriceToCurrency(
		string $price,
		CurrencyInterface $currency,
		?CurrencyInterface $fromCurrency = null,
	): string {
		$fromCurrency ??= $this->currencyManager->get()->getMainCurrency();

		return Price::normalize(
			$this->exchangeRateConvertor->convert(
				$price,
				$fromCurrency->getCode(),
				$currency->getCode(),
			),
		);
	}
}
