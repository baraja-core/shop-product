<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Category;


interface ProductCategoryManagerAccessor
{
	public function get(): ProductCategoryManager;
}
