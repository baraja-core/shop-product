<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


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
	use TranslateObject;

	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true)]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\Column(type: 'translate')]
	protected Translation $name;

	#[ORM\Column(type: 'string', length: 64, unique: true)]
	private string $code;

	#[ORM\Column(type: 'string', length: 7)]
	private string $color;

	/** @var Collection<Product> */
	#[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'labels')]
	private Collection $products;


	public function __construct(string $name, string $code, string $color)
	{
		$this->setName($name);
		$this->code = $code;
		$this->color = $color;
		$this->products = new ArrayCollection;
	}


	public function getId(): int
	{
		return $this->id;
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
	 * @return Collection&iterable<Product>
	 */
	public function getProducts(): Collection
	{
		return $this->products;
	}
}
