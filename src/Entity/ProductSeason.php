<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Localization\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

/**
 * @method Translation getName(?string $locale = null)
 * @method void setName(string $content, ?string $locale = null)
 * @method Translation getDescription(?string $locale = null)
 * @method void setDescription(string $content, ?string $locale = null)
 */
#[ORM\Entity]
#[ORM\Table(name: 'shop__product_season')]
class ProductSeason
{
	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true, options: ['unsigned' => true])]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\Column(type: 'translate')]
	protected Translation $name;

	#[ORM\Column(type: 'translate', nullable: true)]
	protected ?Translation $description = null;

	#[ORM\Column(type: 'integer')]
	private int $minimalDays = 1;

	#[ORM\Column(type: 'boolean')]
	private bool $active = false;

	#[ORM\Column(type: 'datetime')]
	private \DateTimeInterface $dateFrom;

	#[ORM\Column(type: 'datetime')]
	private \DateTimeInterface $dateTo;

	/** @var Collection<Product> */
	#[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'productSeasons')]
	private Collection $products;


	public function __construct(string $name, \DateTimeInterface $dateFrom, \DateTimeInterface $dateTo)
	{
		if ($dateTo < $dateFrom) {
			[$dateTo, $dateFrom] = [$dateFrom, $dateTo];
		}
		$this->setName(Strings::firstUpper($name));
		$this->dateFrom = $dateFrom;
		$this->dateTo = $dateTo;
		$this->products = new ArrayCollection;
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getMinimalDays(): int
	{
		return $this->minimalDays;
	}


	public function setMinimalDays(int $minimalDays): void
	{
		$this->minimalDays = $minimalDays;
	}


	public function isActive(): bool
	{
		return $this->active;
	}


	public function setActive(bool $active): void
	{
		$this->active = $active;
	}


	public function getDateFrom(): \DateTimeInterface
	{
		return $this->dateFrom;
	}


	public function setDateFrom(\DateTimeInterface $dateFrom): void
	{
		if ($dateFrom > $this->dateTo) {
			$this->dateFrom = $this->dateTo;
			$this->dateTo = $dateFrom;
		} else {
			$this->dateFrom = $dateFrom;
		}
	}


	public function getDateTo(): \DateTimeInterface
	{
		return $this->dateTo;
	}


	public function setDateTo(\DateTimeInterface $dateTo): void
	{
		if ($dateTo < $this->dateFrom) {
			$this->dateTo = $this->dateFrom;
			$this->dateFrom = $dateTo;
		} else {
			$this->dateTo = $dateTo;
		}
	}


	/**
	 * @return Collection<Product>
	 */
	public function getProducts(): Collection
	{
		return $this->products;
	}


	public function addProduct(Product $product): void
	{
		$this->products[] = $product;
	}
}
