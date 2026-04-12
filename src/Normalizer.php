<?php

namespace Drupal\eticsearch;

use Drupal;
use InvalidArgumentException;

class Normalizer
{
  private ConfigFactory $configFactory;
  private string $name = 'normalizer';
  private array $charFilters = [];
  private array $filters = [];

  public function __construct()
  {
    $this->configFactory = Drupal::service('eticsearch.factory.config');
  }

  public static function create(string $name, array $charFilters = [], array $filters = []): self
  {
    $instance = new self();
    $instance->_setName($name);
    $instance->_setCharFilters($charFilters);
    $instance->_setFilters($filters);

    return $instance;
  }

  public static function load(string $retrieval = 'single', ?string $normalizerName = NULL): NULL|array|self
  {
    if (!in_array($retrieval, ['single', 'all'], TRUE)) {
      throw new InvalidArgumentException('load only accepts retrieval as one of: single, all');
    }

    if ($retrieval === 'all') {
      /** @var ConfigFactory $configService */
      $configService = Drupal::service('eticsearch.factory.config');
      $normalizers = $configService->getNormalizers();

      return array_map(fn($n) => self::fromArray($n), $normalizers);
    }

    if ($normalizerName === NULL) {
      throw new InvalidArgumentException('load with retrieval single requires a normalizer name');
    }

    /** @var ConfigFactory $configService */
    $configService = Drupal::service('eticsearch.factory.config');
    if (($normalizer = $configService->getNormalizers()[$normalizerName] ?? NULL) !== NULL) {
      return self::fromArray($normalizer);
    }

    return NULL;
  }

  public static function delete(string $normalizerName): bool
  {
    /** @var ConfigFactory $configService */
    $configService = Drupal::service('eticsearch.factory.config');

    // we cannot delete the normalizer if some index is using it
    $indices = $configService->getIndices();
    foreach ($indices as $index) {
      if (in_array($normalizerName, $index['normalizers'] ?? [], TRUE)) {
        return FALSE;
      }
    }

    return $configService->deleteNormalizer($normalizerName);
  }

  public static function fromArray(array $entry): self {
    return self::create(
      $entry['name'] ?? 'normalizer',
      $entry['char_filters'] ?? [],
      $entry['filters'] ?? []
    );
  }

  /**
   * Formats the normalizer configuration as an array for use in ES config.
   * @return array
   */
  public function toArray(): array
  {
    $props = [
      'name' => $this->name,
      'type' => 'custom',
    ];

    if (!empty($this->charFilters)) $props['char_filter'] = array_map(fn($cf) => $cf->getName(), $this->charFilters);
    if (!empty($this->filters)) $props['filter'] = array_map(fn($f) => $f->getName(), $this->filters);

    return $props;
  }

  public function save(): void
  {
    $normalizers = $this->configFactory->getNormalizers();
    $normalizers[$this->name] = $this->toArray();

    $this->configFactory->set('etic:normalizers', $normalizers);
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
