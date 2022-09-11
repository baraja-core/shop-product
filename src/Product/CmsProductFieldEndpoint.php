<?php

declare(strict_types=1);

namespace Baraja\Shop\Product;


use Baraja\Doctrine\EntityManager;
use Baraja\Shop\Product\Entity\ProductFieldDefinition;
use Baraja\StructuredApi\BaseEndpoint;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

final class CmsProductFieldEndpoint extends BaseEndpoint
{
	public function __construct(
		private EntityManager $entityManager,
	) {
	}


	public function actionDefault(): void
	{
		$this->sendJson(
			[
				'items' => $this->entityManager->getRepository(ProductFieldDefinition::class)
					->createQueryBuilder('definition')
					->select(
						'PARTIAL definition.{id, name, type, label, description, required, length, unique, position}',
					)
					->orderBy('definition.position', 'ASC')
					->getQuery()
					->getArrayResult(),
			],
		);
	}


	public function postAddDefinition(string $name, string $type = 'string'): void
	{
		$definition = new ProductFieldDefinition($name, $type);
		try {
			$this->entityManager->getRepository(ProductFieldDefinition::class)
				->createQueryBuilder('definition')
				->where('definition.name = :name')
				->setParameter('name', $definition->getName())
				->setMaxResults(1)
				->getQuery()
				->getSingleResult();
			$this->flashMessage(
				'Field definition "' . $definition->getName() . '" already exist.',
				self::FlashMessageError,
			);
			$this->sendError('Must be unique.');
		} catch (NoResultException | NonUniqueResultException) {
			// Silence is golden.
		}
		try {
			/** @var ProductFieldDefinition $top */
			$top = $this->entityManager->getRepository(ProductFieldDefinition::class)
				->createQueryBuilder('definition')
				->orderBy('definition.position', 'DESC')
				->setMaxResults(1)
				->getQuery()
				->getSingleResult();
			$definition->setPosition($top->getPosition() + 1);
		} catch (NoResultException | NonUniqueResultException) {
			// Silence is golden.
		}
		$this->entityManager->persist($definition);
		$this->entityManager->flush();
		$this->flashMessage('Field definition has been created.', self::FlashMessageSuccess);
		$this->sendOk();
	}


	/**
	 * @param array<int, array{id: int, name: string, type: string, label: string, description: string, required: bool, length: int|null, unique: bool, position: int}> $fields
	 */
	public function postSave(array $fields): void
	{
		$this->entityManager->getRepository(ProductFieldDefinition::class)
			->createQueryBuilder('definition')
			->getQuery()
			->getResult();

		foreach ($fields as $field) {
			/** @var ProductFieldDefinition|null $definition */
			$definition = $this->entityManager->getRepository(ProductFieldDefinition::class)->find($field['id']);
			if ($definition === null) {
				continue;
			}
			$definition->setName($field['name']);
			$definition->setType($field['type']);
			$definition->setLabel($field['label'] ?: null);
			$definition->setDescription($field['description'] ?: null);
			$definition->setRequired($field['required']);
			$definition->setLength((int) $field['length']);
			$definition->setUnique($field['unique']);
			$definition->setPosition($field['position']);
		}
		$this->entityManager->flush();
		$this->flashMessage('Definitions has been updated.', self::FlashMessageSuccess);
		$this->sendOk();
	}
}
