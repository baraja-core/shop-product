<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Doctrine\Identifier\Identifier;
use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

/**
 * @method Translation getName(?string $locale = null)
 * @method void setName(string $content, ?string $locale = null)
 */
#[ORM\Entity]
#[ORM\Table(name: 'shop__product_label')]
class ProductLabel
{
	use Identifier;
	use TranslateObject;

	#[ORM\Column(type: 'translate')]
	private Translation $name;

	#[ORM\Column(type: 'string', length: 64, unique: true)]
	private string $code;

	#[ORM\Column(type: 'string', length: 7)]
	private string $color;

	/** @var Product[]|Collection */
	#[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'labels')]
	private $products;


	public function __construct(string $name, string $code, string $color)
	{
		$this->setName($name);
		$this->code = $code;
		$this->color = $color;
		$this->products = new ArrayCollection;
	}


	public function getSlug(): string
	{
		return Strings::webalize((string) $this->getName());
	}


	public function getCode(): string
	{
		return $this->code;
	}


	public function getColor(): string
	{
		return $this->color;
	}


	/**
	 * @return Product[]|Collection
	 */
	public function getProducts()
	{
		return $this->products;
	}
}
