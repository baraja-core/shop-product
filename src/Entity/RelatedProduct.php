<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Shop\Product\Repository\RelatedProductRepository;
use Doctrine\ORM\Mapping as ORM;

/** Relevant products to the product (but based on manual settings). */
#[ORM\Entity(repositoryClass: RelatedProductRepository::class)]
#[ORM\Table(name: 'shop__related_product')]
class RelatedProduct
{
	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true, options: ['unsigned' => true])]
	#[ORM\GeneratedValue]
	protected int $id;

	#[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'productRelatedBasic')]
	private Product $product;

	#[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'productRelatedRelated')]
	private Product $relatedProduct;

	#[ORM\Column(type: 'integer', options: ['unsigned' => true])]
	private int $position = 0;


	public function __construct(Product $product, Product $relatedProduct)
	{
		$this->product = $product;
		$this->relatedProduct = $relatedProduct;
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getProduct(): Product
	{
		return $this->product;
	}


	public function getRelatedProduct(): Product
	{
		return $this->relatedProduct;
	}


	public function getPosition(): int
	{
		return $this->position;
	}


	public function setPosition(int $position): void
	{
		if ($position < 0) {
			$position = 0;
		}
		$this->position = $position;
	}
}
