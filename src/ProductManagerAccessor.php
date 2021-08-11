<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


interface ProductManagerAccessor
{
	public function get(): ProductManager;
}
