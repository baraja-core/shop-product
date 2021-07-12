<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Doctrine\Identifier\IdentifierUnsigned;
use Baraja\Localization\Translation;
use Doctrine\ORM\Mapping as ORM;

/**
 * @method Translation getValue(?string $locale = null)
 */
#[ORM\Entity]
#[ORM\Table(name: 'shop__product_field')]
class ProductField
{
	use IdentifierUnsigned;

	#[ORM\ManyToOne(targetEntity: ProductFieldDefinition::class)]
	private ProductFieldDefinition $definition;

	#[ORM\ManyToOne(targetEntity: Product::class)]
	private Product $product;

	#[ORM\Column(type: 'translate', nullable: true)]
	private ?Translation $value = null;

	#[ORM\Column(type: 'datetime')]
	private \DateTimeInterface $insertedDate;

	#[ORM\Column(type: 'datetime', nullable: true)]
	private ?\DateTimeInterface $updatedDate = null;


	public function __construct(
		ProductFieldDefinition $definition,
		Product $product,
		?string $value = null
	) {
		$this->definition = $definition;
		$this->product = $product;
		$this->setValue($value);
		$this->insertedDate = new \DateTimeImmutable;
	}


	public static function validate(mixed $value, ProductFieldDefinition $definition): void
	{
		$value = ((string) $value) ?: null;
		try {
			if ($definition->isRequired() && !$value) {
				throw new \InvalidArgumentException('Value is required.');
			}
			if ($value && $definition->getLength() !== null && mb_strlen($value, 'UTF-8') > $definition->getLength()) {
				throw new \InvalidArgumentException(
					'Maximal allowed length is "' . $definition->getLength() . '", '
					. 'but string "' . $value . '" (length "' . mb_strlen($value, 'UTF-8') . '") given.',
				);
			}
			$options = $definition->getOptions() ?? [];
			if ($options !== [] && in_array($value, $options, true) === false) {
				throw new \InvalidArgumentException(
					'Value "' . $value . '" is not allowed, '
					. 'because is not in "' . implode('", "', $options) . '".',
				);
			}
			$validators = $definition->getValidators();
			foreach ($validators as $validator) {
				try {
					if (!$validator((string) $value)) {
						throw new \InvalidArgumentException(
							$validator . ' validator: Value "' . $value . '" is not valid.',
						);
					}
				} catch (\Throwable $e) {
					throw new \InvalidArgumentException(
						$validator . ' validator: Value "' . $value . '" is not valid: ' . $e->getMessage(),
						$e->getCode(),
						$e,
					);
				}
			}
		} catch (\InvalidArgumentException $e) {
			throw new \InvalidArgumentException(
				$definition->getLabel() . ': ' . $e->getMessage(),
				$e->getCode() ?: 500,
				$e,
			);
		}
	}


	public function getDefinition(): ProductFieldDefinition
	{
		return $this->definition;
	}


	public function getProduct(): Product
	{
		return $this->product;
	}


	public function getInsertedDate(): \DateTimeInterface
	{
		return $this->insertedDate;
	}


	public function getUpdatedDate(): ?\DateTimeInterface
	{
		return $this->updatedDate;
	}


	public function setValue(?string $value): void
	{
		if ($value === null) {
			$this->value = null;
		} else {
			$value = trim($value);
			self::validate($value, $this->definition);
			$translation = new Translation($value);
			if ((string) $this->getValue() !== (string) $translation->getTranslation()) {
				$this->value = $translation;
			}
		}
		$this->updatedDate = new \DateTimeImmutable;
	}
}
