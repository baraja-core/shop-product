<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Shop\Entity\Currency\Currency;
use Baraja\Shop\Product\Repository\ProductPriceListRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Manually set product price list for defined currencies other than the main currency.
 * The product price is displayed in the main currency directly in the Product entity.
 * This entity is used to define the other currencies.
 * All currencies may not be available, you will only see the manually fixed prices regardless of the exchange rate.
 */
#[ORM\Entity(repositoryClass: ProductPriceListRepository::class)]
#[ORM\Table(name: 'shop__product_price_list')]
class ProductPriceList
{
	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true, options: ['unsigned' => true])]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'parameters')]
	private Product $product;

	#[ORM\ManyToOne(targetEntity: ProductVariant::class, cascade: ['persist'])]
	#[ORM\JoinColumn(name: 'variant_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
	private ?ProductVariant $variant;

	#[ORM\ManyToOne(targetEntity: Currency::class)]
	private Currency $currency;

	/** @var numeric-string */
	#[ORM\Column(type: 'decimal', precision: 15, scale: 4, options: ['unsigned' => true])]
	private string $price;


	/**
	 * @param numeric-string $price
	 */
	public function __construct(Product $product, ?ProductVariant $variant, Currency $currency, string $price)
	{
		if ($variant !== null && $variant->getId() !== $product->getId()) {
			throw new \LogicException('Variant entity is not compatible with Product entity.');
		}
		$this->product = $product;
		$this->variant = $variant;
		$this->currency = $currency;
		$this->price = $price;
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getProduct(): Product
	{
		return $this->product;
	}


	public function getVariant(): ?ProductVariant
	{
		return $this->variant;
	}


	public function getCurrency(): Currency
	{
		return $this->currency;
	}


	/**
	 * @return numeric-string
	 */
	public function getPrice(): string
	{
		return $this->price;
	}


	/**
	 * @param numeric-string $price
	 */
	public function setPrice(string $price): void
	{
		$this->price = $price;
	}
}
