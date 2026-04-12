<?php

namespace Drupal\eticsearch\Factory;

use InvalidArgumentException;

class MappingFactory
{
  private bool|string $dynamic = TRUE;
  private bool $date_detection = TRUE;
  private bool $numeric_detection = FALSE;
  private array $properties = [];

  public function __construct()
  {
  }

  public static function create(bool|string $dynamic = TRUE, bool $date_detection = TRUE, bool $numericDetection = FALSE, array $fields = []): self
  {
    if (!is_bool($dynamic)) {
      if (!in_array($dynamic, ['strict', 'runtime'], TRUE)) {
        throw new InvalidArgumentException(
          'create only accepts dynamic as boolean or one of: strict, runtime'
        );
      }
    }

    $instance = new self();
    $instance->_setDynamic($dynamic);
    $instance->_setDateDetection($date_detection);
    $instance->_setNumericDetection($numericDetection);
    $instance->_setProperties($fields);

    return $instance;
  }

  public static function fromArray(array $entry): self {
    return self::create(
      $entry['dynamic'] ?? TRUE,
      $entry['date_detection'] ?? TRUE,
      $entry['numeric_detection'] ?? FALSE,
      $entry['properties'] ?? []
    );
  }

  /**
   * Formats the mapping configuration as array for use in ES config.
   * @return array
   */
  public function toArray(): array
  {
    return [
      'mappings' => [
        'dynamic' => $this->dynamic,
        'date_detection' => $this->date_detection,
        'numeric_detection' => $this->numeric_detection,
        'properties' => $this->properties,
      ]
    ];
  }

  private function _setDynamic(bool|string $dynamic): void
  {
    if (!is_bool($dynamic)) {
      if (!in_array($dynamic, ['strict', 'runtime'], TRUE)) {
        throw new InvalidArgumentException(
          '_setDynamic only accepts dynamic as boolean or one of: strict, runtime'
        );
      }
    }

    $this->dynamic = $dynamic;
  }

  private function _setDateDetection(bool $date_detection): void
  {
    $this->date_detection = $date_detection;
  }

  private function _setNumericDetection(bool $numericDetection): void
  {
    $this->numeric_detection = $numericDetection;
  }

  private function _setProperties(array $fields): void
  {
    $this->properties = $fields;
  }
}
