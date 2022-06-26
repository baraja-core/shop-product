<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\ProductFeed;


use Baraja\Shop\Product\ProductFeed\Personalization\UserContext;
use Nette\Security\User;

final class Personalization
{
	public function __construct(
		private UserContext $userContext,
		private User $user,
	) {
	}


	/**
	 * Builds an array of products that the user is most likely to prefer
	 * and ranks them from most likely to least likely.
	 * If we do not know the user or the data is skewed, no products will be returned,
	 * or an array of bestsellers will be returned under certain circumstances.
	 *
	 * @return array<int, int>
	 */
	public function getUserPreferredProductIds(): array
	{
		$userId = $this->user->getId();

		return is_scalar($userId)
			? $this->userContext->getProductIds((int) $userId)
			: [];
	}
}
