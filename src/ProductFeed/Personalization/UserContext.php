<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\ProductFeed\Personalization;


use Baraja\Shop\Cart\Entity\Cart;
use Baraja\Shop\Cart\Entity\CartRepository;
use Baraja\Shop\Order\Entity\Order;
use Baraja\Shop\Order\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class UserContext
{
	private OrderRepository $orderRepository;

	private CartRepository $cartRepository;


	public function __construct(
		private EntityManagerInterface $entityManager,
	) {
	}


	/**
	 * @return array<int, int>
	 */
	public function getProductIds(int $userId): array
	{
		if (class_exists(Cart::class) === false || class_exists(Order::class) === false) {
			return [];
		}

		return [];
	}


	/**
	 * @return array<int, int>
	 */
	private function findInOrders(int $userId): array
	{
		$repository = new EntityRepository($this->entityManager, $this->entityManager->getClassMetadata(Order::class));
		$repository->createQueryBuilder('o')
			->select('PARTIAL o.{id}, PARTIAL item.{id}, PARTIAL product.{id}')
			->join('o.customer', 'customer')
			->join('o.items', 'item')
			->join('item.product', 'product')
			->where('customer.id = :customerId')
			->setParameter('customerId', $userId)
			->orderBy('c.insertedDate', 'DESC')
			->getQuery()
			->getArrayResult();

		return [];
	}


	/**
	 * @return array<int, int>
	 */
	private function findInCarts(int $userId): array
	{
		$repository = new EntityRepository($this->entityManager, $this->entityManager->getClassMetadata(Cart::class));
		$repository->createQueryBuilder('c')
			->select('PARTIAL c.{id}, PARTIAL item.{id}, PARTIAL product.{id}')
			->join('c.customer', 'customer')
			->join('c.items', 'item')
			->join('item.product', 'product')
			->where('customer.id = :customerId')
			->setParameter('customerId', $userId)
			->orderBy('c.insertedDate', 'DESC')
			->getQuery()
			->getArrayResult();

		return [];
	}
}
