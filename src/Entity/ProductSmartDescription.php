<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Doctrine\Identifier\Identifier;
use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Doctrine\ORM\Mapping as ORM;

/**
 * @method Translation getDescription(?string $locale = null)
 * @method void setDescription(string $content, ?string $locale = null)
 */
#[ORM\Entity]
#[ORM\Table(name: 'shop__product_description')]
class ProductSmartDescription
{
	use Identifier;
	use TranslateObject;

	#[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'smartDescriptions')]
	private Product $product;

	#[ORM\Column(type: 'translate')]
	private Translation $description;

	#[ORM\Column(type: 'string', length: 128, nullable: true)]
	private ?string $image = null;

	#[ORM\Column(type: 'string', length: 8, nullable: true)]
	private ?string $color = null;

	#[ORM\Column(type: 'smallint')]
	private int $position = 0;


	public function __construct(Product $product, string $description)
	{
		$this->product = $product;
		$this->setDescription($description);
	}


	public function getProduct(): Product
	{
		return $this->product;
	}


	public function getImage(): ?string
	{
		return $this->image;
	}


	public function setImage(?string $image): void
	{
		$this->image = $image;
	}


	public function getImageRelativePath(): ?string
	{
		if ($this->image === null) {
			return null;
		}

		return 'product-image/description/' . $this->image;
	}


	public function getColor(): ?string
	{
		return $this->color;
	}


	public function setColor(?string $color): void
	{
		$this->color = $color ?: null;
	}


	public function getFallbackColor(): string
	{
		return $this->color ?? '#eee';
	}


	public function getPosition(): int
	{
		return $this->position;
	}


	public function setPosition(int $position): void
	{
		$this->position = $position;
	}
}
