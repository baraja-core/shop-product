<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Localization\TranslateObject;
use Baraja\Localization\Translation;
use Doctrine\ORM\Mapping as ORM;

/**
 * @method Translation getValue(?string $locale = null)
 */
#[ORM\Entity]
#[ORM\Table(name: 'shop__product_field')]
class ProductField
{
	use TranslateObject;

	#[ORM\Id]
	#[ORM\Column(type: 'integer', unique: true, options: ['unsigned' => true])]
	#[ORM\GeneratedValue]
	protected int $id;

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
		?string $value = null,
	) {
		$this->definition = $definition;
		$this->product = $product;
		$this->setValue($value);
		$this->insertedDate = new \DateTimeImmutable;
	}


	public function getId(): int
	{
		return $this->id;
	}


	public static function validate(?string $value, ProductFieldDefinition $definition): void
	{
		$value = ((string) $value) ?: null;
		try {
			if ($value !== null && is_numeric($value) === false && $definition->getType() === 'int') {
				throw new \InvalidArgumentException(sprintf('Value must be a number, but "%s" given.', $value));
			}
			if ($value === null && $definition->isRequired()) {
				throw new \InvalidArgumentException('Value is required.');
			}
			$length = $definition->getLength();
			if ($value !== null && $length !== null && mb_strlen($value, 'UTF-8') > $length) {
				throw new \InvalidArgumentException(sprintf(
					'Maximal allowed length is "%d", but string "%s" (length "%d") given.',
					$length,
					$value,
					mb_strlen($value, 'UTF-8'),
				));
			}
			$options = $definition->getOptions() ?? [];
			if ($options !== [] && in_array($value, $options, true) === false) {
				throw new \InvalidArgumentException(sprintf('Value "%s" is not allowed, because is not in "%s".', $value, implode('", "', $options)));
			}
			$validators = $definition->getValidators();
			foreach ($validators as $validator) {
				try {
					if (is_callable($validator) === false) {
						throw new \LogicException(sprintf('Validator "%s" must be callable.', $validator));
					}
					if (!$validator((string) $value)) { // must be falsifiable
						throw new \InvalidArgumentException(sprintf('%s validator: Value "%s" is not valid.', $validator, $value));
					}
				} catch (\Throwable $e) {
					throw new \InvalidArgumentException(
						sprintf('%s validator: Value "%s" is not valid: %s', $validator, $value, $e->getMessage()),
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


	public function getName(): string
	{
		return $this->definition->getName();
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
			self::validate(null, $this->definition);
			$this->value = null;
		} else {
			$value = trim($value);
			self::validate($value, $this->definition);
			$translation = new Translation($value);
			if ((string) $this->getValue() !== (string) $translation->getTranslation()) {
				$this->value = $translation;
			}
		}
		if ((string) $this->value !== (string) $value) {
			$this->updatedDate = new \DateTimeImmutable;
		}
	}
}
