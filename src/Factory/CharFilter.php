<?php

namespace Drupal\eticsearch\Factory;

use InvalidArgumentException;

class CharFilter
{
  public const array CONFIGURABLE_CHAR_FILTER_TYPES = [
    'html_strip', 'mapping', 'pattern_replace',
  ];

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

  public static function load(string $indexName, string $charFilterName): ?self
  {
    // todo: return instantiated index factory from the config or null if does not exists
  }

  public static function delete(string $indexName, string $charFilterName): bool
  {
    // todo: implement config delete
    // todo: implement index deletion in ES
  }

  /**
   * Formats the character filter configuration as an array for use in ES config.
   * This method will only include properties relevant to the filter type.
   * @return array
   */
  public function toArray(): array
  {
    $props = [
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

  public function save()
  {
    // todo: implement config save
    // todo: implement index creation in ES
  }

  public function getName(): string
  {
    return $this->name;
  }
}
