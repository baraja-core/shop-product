<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Doctrine\Identifier\IdentifierUnsigned;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

/**
 * @ORM\Entity()
 * @ORM\Table(name="shop__product_variant")
 */
class ProductVariant
{
	use IdentifierUnsigned;

	/** @ORM\ManyToOne(targetEntity="Product", inversedBy="variants") */
	private Product $product;

	/** @ORM\Column(type="string", length=64) */
	private string $relationHash;

	/** @ORM\Column(type="string", unique=true, length=64, nullable=true) */
	private ?string $ean = null;

	/** @ORM\Column(type="string", unique=true, length=64, nullable=true) */
	private ?string $code = null;

	/** @ORM\Column(type="float", nullable=true) */
	private ?float $price = null;

	/** @ORM\Column(type="float", nullable=true) */
	private ?float $priceAddition = null;

	/** @ORM\Column(type="boolean") */
	private bool $soldOut = false;


	public function __construct(Product $product, string $relationHash)
	{
		$this->product = $product;
		$this->relationHash = $relationHash;
	}


	/**
	 * @param string[] $parameters
	 */
	public static function serializeParameters(array $parameters): string
	{
		$return = [];
		foreach ($parameters as $key => $value) {
			$return[] = Strings::firstUpper($key) . '=' . $value;
		}
		sort($return);

		return implode(';', $return);
	}


	/**
	 * @return string[]
	 */
	public static function unserializeParameters(string $hash): array
	{
		$return = [];
		foreach (explode(';', $hash) as $parameter) {
			if (preg_match('/^([^=]+)=(.+)$/', $parameter, $parser)) {
				$return[$parser[1]] = $parser[2];
			} else {
				throw new \InvalidArgumentException('Hash parameter "' . $parameter . '" is invalid, because hash "' . $hash . '" given.');
			}
		}

		return $return;
	}


	public function getProduct(): Product
	{
		return $this->product;
	}


	public function getName(): string
	{
		return (string) $this->product->getName();
	}


	public function getLabel(): string
	{
		return $this->getName() . ' [' . str_replace(';', '; ', $this->getRelationHash()) . ']';
	}


	public function getRelationHash(): string
	{
		return $this->relationHash;
	}


	public function getRealPrice(): ?float
	{
		return $this->price;
	}


	public function getDefinedPrice(bool $useSale = true): ?float
	{
		$price = (float) $this->price;
		$return = (abs($price) < 0.01 ? null : $price) ?? $this->product->getPrice();
		if ($useSale === true && $this->product->isSale()) {
			$return = $return - ($this->product->getStandardPricePercentage() / 100) * $return;
		}

		return $return;
	}


	public function getPrice(bool $useSale = true): float
	{
		return ceil($this->getDefinedPrice($useSale) + ($this->priceAddition ?? 0));
	}


	public function setPrice(?float $price): void
	{
		$price = $price === null || abs($price) < 0.01 ? null : $price;
		if (abs($this->getProduct()->getPrice() - $price) < 0.01) {
			$price = null;
		}
		$this->price = $price;
	}


	public function getPriceAddition(): ?float
	{
		return $this->priceAddition;
	}


	public function setPriceAddition(?float $priceAddition): void
	{
		if ($priceAddition === null || abs($priceAddition) < 0.01) {
			$priceAddition = null;
		}

		$this->priceAddition = $priceAddition;
	}


	public function isSoldOut(): bool
	{
		return $this->soldOut;
	}


	public function setSoldOut(bool $soldOut): void
	{
		$this->soldOut = $soldOut;
	}


	public function getEan(): ?string
	{
		return $this->ean;
	}


	public function setEan(?string $ean): void
	{
		if ($ean !== null) {
			$ean = Strings::webalize($ean);
		}
		$this->ean = $ean ?: null;
	}


	public function getCode(): ?string
	{
		return $this->code;
	}


	public function setCode(?string $code): void
	{
		if ($code !== null) {
			$code = Strings::webalize($code);
		}
		$this->code = $code;
	}
}
