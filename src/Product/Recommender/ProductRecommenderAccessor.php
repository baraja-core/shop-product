<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Recommender;


interface ProductRecommenderAccessor
{
	public function get(): ProductRecommender;
}
