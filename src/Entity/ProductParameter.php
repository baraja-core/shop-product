<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Doctrine\Identifier\Identifier;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

/**
 * @ORM\Entity()
 * @ORM\Table(name="shop__product_parameter")
 */
class ProductParameter
{
	use Identifier;

	/** @ORM\ManyToOne(targetEntity="Product", inversedBy="parameters") */
	private Product $product;

	/** @ORM\Column(type="string") */
	private string $name;

	/**
	 * @var string[]
	 * @ORM\Column(type="simple_array", name="`values`")
	 */
	private array $values;

	/** @ORM\Column(type="boolean") */
	private bool $variant;


	/**
	 * @param array<int, string> $values
	 */
	public function __construct(Product $product, string $name, array $values = [], bool $variant = false)
	{
		$this->product = $product;
		$this->setName($name);
		$this->setValues($values);
		$this->setVariant($variant);
	}


	public function getProduct(): Product
	{
		return $this->product;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function setName(string $name): void
	{
		$this->name = Strings::firstUpper(trim($name, ': '));
	}


	/**
	 * @return array<int, string>
	 */
	public function getValues(): array
	{
		return $this->values;
	}


	/**
	 * @param array<int, string> $values
	 */
	public function setValues(array $values): void
	{
		$return = [];
		foreach (array_values($values) as $value) {
			$return[] = trim($value);
		}
		$this->values = $return;
	}


	public function isVariant(): bool
	{
		return $this->variant;
	}


	public function setVariant(bool $variant): void
	{
		$this->variant = $variant;
	}
}
