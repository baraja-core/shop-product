<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Doctrine\EntityManager;
use Baraja\Shop\Product\Entity\Product;
use Baraja\Shop\Product\Entity\ProductField;
use Baraja\Shop\Product\Entity\ProductFieldDefinition;

final class ProductFieldManager
{
	public function __construct(
		private EntityManager $entityManager,
	) {
	}


	/**
	 * @return array<string, array{id: int|null, name: string, label: string, value: string|null}>
	 */
	public function getFields(Product $product): array
	{
		/** @var ProductField[] $fields */
		$fields = $this->entityManager->getRepository(ProductField::class)
			->createQueryBuilder('field')
			->select('PARTIAL field.{id, value}')
			->addSelect('PARTIAL definition.{id, name, label}')
			->leftJoin('field.definition', 'definition')
			->where('field.product = :productId')
			->setParameter('productId', $product->getId())
			->getQuery()
			->getArrayResult();

		$return = [];
		foreach ($fields as $field) { // set current default values
			$name = (string) $field['definition']['name'];
			$return[$name] = [
				'id' => $field['id'],
				'name' => $name,
				'label' => (string) $field['definition']['label'],
				'value' => $field['value'],
			];
		}
		if (count($return) !== $this->getDefinitionsCount()) {
			foreach ($this->getDefinitions() as $definition) { // generate missing fields
				$name = $definition->getName();
				if (isset($return[$name]) === true) {
					continue;
				}
				$return[$name] = [
					'id' => null,
					'name' => $name,
					'label' => (string) $definition->getLabel(),
					'value' => null,
				];
			}
		}

		return $return;
	}


	/**
	 * @param array<string, string|null> $fields
	 */
	public function setFields(Product $product, array $fields): void
	{
		/** @var ProductField[] $databaseFields */
		$databaseFields = $this->entityManager->getRepository(ProductField::class)
			->createQueryBuilder('field')
			->select('field, definition')
			->leftJoin('field.definition', 'definition')
			->where('field.product = :productId')
			->setParameter('productId', $product->getId())
			->getQuery()
			->getResult();

		$needFlush = false;
		foreach ($databaseFields as $databaseField) {
			$name = $databaseField->getName();
			if (isset($fields[$name]) === true) {
				$databaseField->setValue($fields[$name]);
				unset($fields[$name]);
				$needFlush = true;
			}
		}
		foreach ($this->getDefinitions() as $definition) {
			$name = $definition->getName();
			if (isset($fields[$name]) === true) {
				$field = new ProductField($definition, $product, $fields[$name]);
				$this->entityManager->persist($field);
				$needFlush = true;
			}
		}
		if ($needFlush === true) {
			$this->entityManager->flush();
		}
	}


	public function getDefinitionsCount(bool $flush = false): int
	{
		return count($this->getDefinitions($flush));
	}


	/**
	 * @return ProductFieldDefinition[]
	 */
	public function getDefinitions(bool $flush = false): array
	{
		static $cache;
		if ($flush === true || $cache === null) {
			$cache = $this->entityManager->getRepository(ProductFieldDefinition::class)
				->createQueryBuilder('def')
				->getQuery()
				->getResult();
		}

		return $cache;
	}
}
