<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\EcommerceStandard\DTO\ProductVariantInterface;
use Baraja\Shop\Product\Repository\ProductVariantRepository;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

#[ORM\Entity(repositoryClass: ProductVariantRepository::class)]
#[ORM\Table(name: 'shop__product_variant')]
class ProductVariant implements ProductVariantInterface
{
	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true, options: ['unsigned' => true])]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'variants')]
	private Product $product;

	#[ORM\Column(type: 'string', length: 64)]
	private string $relationHash;

	#[ORM\Column(type: 'string', length: 64, unique: true, nullable: true)]
	private ?string $ean = null;

	#[ORM\Column(type: 'string', length: 64, unique: true, nullable: true)]
	private ?string $code = null;

	/** @var numeric-string|null */
	#[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true, options: ['unsigned' => true])]
	private ?string $price = null;

	/** @var numeric-string|null */
	#[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true, options: ['unsigned' => true])]
	private ?string $priceAddition = null;

	#[ORM\Column(type: 'boolean')]
	private bool $soldOut = false;

	/** Total available quantity of this variant in all warehouses. */
	#[ORM\Column(type: 'integer')]
	private int $warehouseAllQuantity = 0;


	public function __construct(Product $product, string $relationHash)
	{
		$this->product = $product;
		$this->relationHash = $relationHash;
	}


	public function getId(): int
	{
		return $this->id;
	}


	/**
	 * @param array<string, string> $parameters
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
	 * @return array<string, string>
	 */
	public static function unserializeParameters(string $hash): array
	{
		$return = [];
		foreach (explode(';', $hash) as $parameter) {
			if (preg_match('/^([^=]+)=(.+)$/', $parameter, $parser) === 1) {
				$return[(string) $parser[1]] = (string) $parser[2];
			} else {
				throw new \InvalidArgumentException(sprintf(
					'Hash parameter "%s" is invalid, because hash "%s" given.',
					$parameter,
					$hash,
				));
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
		return sprintf('%s [%s]', $this->getName(), str_replace(';', '; ', $this->getRelationHash()));
	}


	public function getRelationHash(): string
	{
		return $this->relationHash;
	}


	public function getRealPrice(): ?string
	{
		return $this->price;
	}


	public function getDefinedPrice(bool $useSale = true): string
	{
		if ($this->price === null || $this->price === '0') {
			$return = null;
		} else {
			$return = $this->price;
		}
		$return ??= $this->product->getPrice();
		if ($useSale === true && $this->product->isSale()) {
			$return = bcsub(
				$return,
				bcmul(
					bcdiv(
						$this->product->getStandardPricePercentage() ?? '0',
						'100',
					),
					$return,
				),
			);
		}

		return $return;
	}


	public function getPrice(bool $useSale = true): string
	{
		return bcadd(
			$this->getDefinedPrice($useSale),
			$this->priceAddition ?? '0',
		);
	}


	/**
	 * @param numeric-string|null $price
	 */
	public function setPrice(?string $price): void
	{
		if ($price !== null) {
			if (ltrim($price, '-') < 0.01) {
				$price = null;
			} else {
				$sub = bcsub($this->getProduct()->getPrice(), $price);
				if (abs((float) $sub) < 0.01) {
					$price = null;
				}
			}
		}
		$this->price = $price;
	}


	public function getPriceAddition(): ?string
	{
		return $this->priceAddition;
	}


	/**
	 * @param numeric-string|null $priceAddition
	 */
	public function setPriceAddition(?string $priceAddition): void
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


	public function getWarehouseAllQuantity(): int
	{
		return $this->warehouseAllQuantity;
	}


	public function setWarehouseAllQuantity(int $warehouseAllQuantity): void
	{
		$this->warehouseAllQuantity = $warehouseAllQuantity;
	}
}
