<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Doctrine\Identifier\IdentifierUnsigned;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="cm__related_product")
 */
class RelatedProduct
{
	use IdentifierUnsigned;

	/** @ORM\ManyToOne(targetEntity="Product", inversedBy="productRelatedBasic") */
	private Product $product;

	/** @ORM\ManyToOne(targetEntity="Product", inversedBy="productRelatedRelated") */
	private Product $relatedProduct;


	public function __construct(Product $product, Product $relatedProduct)
	{
		$this->product = $product;
		$this->relatedProduct = $relatedProduct;
	}


	public function getProduct(): Product
	{
		return $this->product;
	}


	public function getRelatedProduct(): Product
	{
		return $this->relatedProduct;
	}
}