<?php

namespace Drupal\eticsearch;

use Drupal;
use InvalidArgumentException;

class CharFilter
{
  public const array CONFIGURABLE_CHAR_FILTER_TYPES = [
    'html_strip', 'mapping', 'pattern_replace',
  ];

  private ConfigFactory $configFactory;
  private string $name = 'char_filter';
  private string $type = 'html_strip';
  private array $escapedTags = [];
  private array $mappings = [];
  private ?string $mappingsPath = NULL;
  private ?string $pattern = NULL;
  private string $replacement = '';
  private ?string $flags = NULL;

  public function __construct()
  {
    $this->configFactory = Drupal::service('eticsearch.factory.config');
  }

  public static function create(string  $name, string $type, array $escapedTags = [], array $mappings = [], ?string $mappingsPath = NULL,
                                ?string $pattern = NULL, string $replacement = '', ?string $flags = NULL): self
  {
    if (!in_array($type, self::CONFIGURABLE_CHAR_FILTER_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'create only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_CHAR_FILTER_TYPES)
      );
    }

    $instance = new self();
    $instance->_setName($name);
    $instance->_setType($type);
    $instance->_setEscapedTags($escapedTags);
    $instance->_setMappings($mappings);
    $instance->_setMappingsPath($mappingsPath);
    $instance->_setPattern($pattern);
    $instance->_setReplacement($replacement);
    $instance->_setFlags($flags);

    return $instance;
  }

  private function _setName(string $name): void
  {
    $this->name = $name;
  }

  private function _setType(string $type): void
  {
    if (!in_array($type, self::CONFIGURABLE_CHAR_FILTER_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        '_setType only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_CHAR_FILTER_TYPES)
      );
    }

    $this->type = $type;
  }

  private function _setEscapedTags(array $escapedTags): void
  {
    $this->escapedTags = $escapedTags;
  }

  private function _setMappings(array $mappings): void
  {
    $this->mappings = $mappings;
  }

  private function _setMappingsPath(?string $mappingsPath): void
  {
    $this->mappingsPath = $mappingsPath;
  }

  private function _setPattern(?string $pattern): void
  {
    $this->pattern = $pattern;
  }

  private function _setReplacement(string $replacement): void
  {
    $this->replacement = $replacement;
  }

  private function _setFlags(?string $flags): void
  {
    $this->flags = $flags;
  }

  public static function load(string $retrieval = 'single', ?string $charFilterName = NULL): NULL|array|self
  {
    if (!in_array($retrieval, ['single', 'all'], TRUE)) {
      throw new InvalidArgumentException('load only accepts retrieval as one of: single, all');
    }

    if ($retrieval === 'all') {
      /** @var ConfigFactory $configService */
      $configService = Drupal::service('eticsearch.factory.config');
      $charFilters = $configService->getCharFilters();

      return array_map(fn($cf) => self::fromArray($cf), $charFilters);
    }

    if ($charFilterName === NULL) {
      throw new InvalidArgumentException('load with retrieval single requires a char filter name');
    }

    /** @var ConfigFactory $configService */
    $configService = Drupal::service('eticsearch.factory.config');
    if (($charFilter = $configService->getCharFilters()[$charFilterName] ?? NULL) !== NULL) {
      return self::fromArray($charFilter);
    }

    return NULL;
  }

  public static function delete(string $charFilterName): bool
  {
    /** @var ConfigFactory $configService */
    $configService = Drupal::service('eticsearch.factory.config');

    // we cannot delete the filter if some index is using it
    $indices = $configService->getIndices();
    foreach ($indices as $index) {
      if (in_array($charFilterName, $index['char_filters'] ?? [], TRUE)) {
        return FALSE;
      }
    }

    return $configService->deleteCharFilter($charFilterName);
  }

  public static function fromArray(array $entry): self {
    return self::create(
      $entry['name'] ?? 'char_filter',
      $entry['type'] ?? 'html_strip',
      $entry['escaped_tags'] ?? [],
      $entry['mappings'] ?? [],
      $entry['mappings_path'] ?? NULL,
      $entry['pattern'] ?? NULL,
      $entry['replacement'] ?? '',
      $entry['flags'] ?? NULL
    );
  }

  /**
   * Formats the character filter configuration as an array for use in ES config.
   * This method will only include properties relevant to the filter type.
   * @return array
   */
  public function toArray(): array
  {
    $props = [
      'name' => $this->name,
      'type' => $this->type,
    ];

    switch ($this->type) {
      case 'html_strip':
        if (!empty($this->escapedTags)) $props['escaped_tags'] = $this->escapedTags;
        break;
      case 'mapping':
        if (!empty($this->mappings)) $props['mappings'] = $this->mappings;
        if ($this->mappingsPath !== NULL) $props['mappings_path'] = $this->mappingsPath;
        break;
      case 'pattern_replace':
        $props['replacement'] = $this->replacement;

        if ($this->pattern !== NULL) $props['pattern'] = $this->pattern;
        if ($this->flags !== NULL) $props['flags'] = $this->flags;
        break;
      default:
        throw new InvalidArgumentException(
          'toArray only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_CHAR_FILTER_TYPES)
        );
    }

    return $props;
  }

  public function save(): void
  {
    $charFilters = $this->configFactory->getCharFilters();
    $charFilters[$this->name] = $this->toArray();

    $this->configFactory->set('etic:char_filters', $charFilters);
  }

  public function getName(): string
  {
    return $this->name;
  }
}
