<?php

namespace Drupal\eticsearch\Factory;

class Normalizer
{
  private string $name = 'normalizer';
  private array $charFilters = [];
  private array $filters = [];

  public function __construct()
  {
  }

  public static function create(string $name, array $charFilters = [], array $filters = []): self
  {
    $instance = new self();
    $instance->_setName($name);
    $instance->_setCharFilters($charFilters);
    $instance->_setFilters($filters);

    return $instance;
  }

  public static function load(string $indexName, string $normalizerName): ?self
  {
    // todo: return instantiated index factory from the config or null if does not exists
  }

  public static function delete(string $indexName, string $normalizerName): bool
  {
    // todo: implement config delete
    // todo: implement index deletion in ES
  }

  /**
   * Formats the normalizer configuration as an array for use in ES config.
   * @return array
   */
  public function toArray(): array
  {
    $props = [
      'type' => 'custom',
    ];

    if (!empty($this->charFilters)) $props['char_filter'] = array_map(fn($cf) => $cf->getName(), $this->charFilters);
    if (!empty($this->filters)) $props['filter'] = array_map(fn($f) => $f->getName(), $this->filters);

    return $props;
  }

  public function save()
  {
    // todo: implement config save
    // todo: implement index creation in ES
  }

  private function _setName(string $name): void
  {
    $this->name = $name;
  }

  public function getName(): string
  {
    return $this->name;
  }

  private function _setCharFilters(array $charFilters): void
  {
    $this->charFilters = $charFilters;
  }

  private function _setFilters(array $filters): void
  {
    $this->filters = $filters;
  }
}
