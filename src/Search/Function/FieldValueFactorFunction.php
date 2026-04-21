<?php

namespace Drupal\eticsearch\Search\Function;

use InvalidArgumentException;

class FieldValueFactorFunction
{
  private const array VALID_MODIFIERS = ['none', 'log', 'log1p', 'log2p', 'ln', 'ln1p', 'ln2p', 'square', 'sqrt', 'reciprocal'];

  private string $field;
  private float $factor;
  private string $modifier;
  private float|null $missing;
  private ?array $filter;

  private function __construct(string $field, float $factor, string $modifier, ?float $missing, ?array $filter)
  {
    $this->field = $field;
    $this->factor = $factor;
    $this->modifier = $modifier;
    $this->missing = $missing;
    $this->filter = $filter;
  }

  /**
   * @param string $field
   * @param float $factor
   * @param string $modifier
   * @param float|null $missing
   * @param array|null $filter
   * @return FieldValueFactorFunction
   */
  public static function create(
    string $field,
    float $factor = 1.0,
    string $modifier = 'none',
    ?float $missing = null,
    ?array $filter = null
  ): self {
    if (!in_array($modifier, self::VALID_MODIFIERS, true)) {
      throw new InvalidArgumentException("Invalid modifier '$modifier'. Valid: " . implode(', ', self::VALID_MODIFIERS));
    }

    return new self($field, $factor, $modifier, $missing, $filter);
  }

  public static function fromArray(array $data): self
  {
    $fvf = $data['field_value_factor'] ?? $data;
    return new self(
      $fvf['field'],
      (float) ($fvf['factor'] ?? 1.0),
      $fvf['modifier'] ?? 'none',
      isset($fvf['missing']) ? (float) $fvf['missing'] : null,
      $data['filter'] ?? null,
    );
  }

  public function toArray(): array
  {
    $fvf = ['field' => $this->field];

    if ($this->factor !== 1.0) $fvf['factor'] = $this->factor;
    if ($this->modifier !== 'none') $fvf['modifier'] = $this->modifier;
    if ($this->missing !== null) $fvf['missing'] = $this->missing;

    $entry = ['field_value_factor' => $fvf];

    if ($this->filter !== null) {
      $entry['filter'] = $this->filter;
    }

    return $entry;
  }
}
