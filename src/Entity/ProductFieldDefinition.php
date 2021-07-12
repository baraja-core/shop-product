<?php

declare(strict_types=1);

namespace Baraja\Shop\Product\Entity;


use Baraja\Doctrine\Identifier\IdentifierUnsigned;
use Baraja\Localization\Translation;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

/**
 * @method Translation getLabel(?string $locale = null)
 * @method void setLabel(?string $haystack, ?string $locale = null)
 * @method Translation getDescription(?string $locale = null)
 * @method void setDescription(?string $haystack, ?string $locale = null)
 */
#[ORM\Entity]
#[ORM\Table(name: 'shop__product_field_definition')]
class ProductFieldDefinition
{
	use IdentifierUnsigned;

	#[ORM\Column(type: 'string', length: 64, unique: true)]
	private string $name;

	#[ORM\Column(type: 'string', length: 16)]
	private string $type;

	#[ORM\Column(type: 'translate')]
	private Translation $label;

	#[ORM\Column(type: 'translate', nullable: true)]
	private ?Translation $description = null;

	#[ORM\Column(type: 'boolean')]
	private bool $required = false;

	#[ORM\Column(type: 'integer', nullable: true)]
	private ?int $length = null;

	#[ORM\Column(type: 'boolean')]
	private bool $unique = false;

	#[ORM\Column(type: 'string', length: 500, nullable: true)]
	private ?string $validators = null;

	/** @var array<int, string>|null */
	#[ORM\Column(type: 'json', nullable: true)]
	private ?array $options = null;


	public function __construct(string $name, string $type)
	{
		$this->name = $name;
		$this->type = $type;
		$this->setLabel(Strings::firstUpper($name));
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getType(): string
	{
		return $this->type;
	}


	public function isRequired(): bool
	{
		return $this->required;
	}


	public function getLength(): ?int
	{
		return $this->length;
	}


	public function isUnique(): bool
	{
		return $this->unique;
	}


	/**
	 * @return array<int, string>
	 */
	public function getValidators(): array
	{
		return $this->validators !== null
			? explode('|', $this->validators)
			: [];
	}


	/**
	 * @return array<int, string>|null
	 */
	public function getOptions(): ?array
	{
		return $this->options;
	}


	public function setName(string $name): void
	{
		$this->name = $name;
	}


	public function setType(string $type): void
	{
		$this->type = $type;
	}


	public function setRequired(bool $required): void
	{
		$this->required = $required;
	}


	public function setLength(?int $length): void
	{
		$this->length = $length;
	}


	public function setUnique(bool $unique): void
	{
		$this->unique = $unique;
	}


	public function setValidators(?array $validators): void
	{
		$this->validators = $validators !== null
			? implode('|', $validators)
			: null;
	}


	public function addValidator(string $validator): void
	{
		$this->validators = $this->validators !== null
			? $this->validators . '|' . $validator
			: $validator;
	}


	/**
	 * @param array<int, string>|null $options
	 */
	public function setOptions(?array $options): void
	{
		$this->options = $options;
	}
}
