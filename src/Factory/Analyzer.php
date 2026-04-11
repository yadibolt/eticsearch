<?php

namespace Drupal\eticsearch\Factory;

use InvalidArgumentException;

class Analyzer
{
  public const array CONFIGURABLE_ANALYZER_TYPES = [
    'standard', 'stop', 'pattern', 'fingerprint', 'language', 'custom',
  ];

  // todo: hardcoded list from the docs - maybe other way to get this?
  public const array LANGUAGE_TYPES = [
    'arabic', 'armenian', 'basque', 'bengali', 'brazilian', 'bulgarian',
    'catalan', 'cjk', 'czech', 'danish', 'dutch', 'english', 'estonian',
    'finnish', 'french', 'galician', 'german', 'greek', 'hindi', 'hungarian',
    'indonesian', 'irish', 'italian', 'latvian', 'lithuanian', 'norwegian',
    'persian', 'portuguese', 'romanian', 'russian', 'serbian', 'sorani',
    'spanish', 'swedish', 'turkish', 'thai',
  ];

  private string $name = 'analyzer';
  private string $type = 'standard';
  private null|string|array $stopwords = NULL;
  private int $maxTokenLength = 255;
  private ?string $pattern = NULL;
  private ?string $flags = NULL;
  private bool $lowercase = TRUE;
  private string $separator = ' ';
  private int $maxOutputSize = 255;
  private ?string $language = NULL;
  private array $stemExclusion = [];
  private ?Tokenizer $tokenizer = NULL;
  private array $charFilters = [];
  private array $filters = [];

  public function __construct()
  {
  }

  public static function create(string $name, string $type, null|string|array $stopwords = NULL, int $maxTokenLength = 255, ?string $pattern = NULL,
                                string $flags = NULL, bool $lowercase = TRUE, string $separator = ' ', int $maxOutputSize = 255, ?string $language = NULL,
                                array  $stemExclusion = [], ?Tokenizer $tokenizer = NULL, array $charFilters = [], array $filters = []): self
  {
    if (!in_array($type, self::CONFIGURABLE_ANALYZER_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'create only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_ANALYZER_TYPES)
      );
    }

    $instance = new self();
    $instance->_setName($name);
    $instance->_setType($type);
    $instance->_setStopwords($stopwords);
    $instance->_setMaxTokenLength($maxTokenLength);
    $instance->_setPattern($pattern);
    $instance->_setFlags($flags);
    $instance->_setLowercase($lowercase);
    $instance->_setSeparator($separator);
    $instance->_setMaxOutputSize($maxOutputSize);
    $instance->_setLanguage($language);
    $instance->_setStemExclusion($stemExclusion);
    $instance->_setTokenizer($tokenizer);
    $instance->_setCharFilters($charFilters);
    $instance->_setFilters($filters);

    return $instance;
  }

  private function _setName(string $name): void
  {
    $this->name = $name;
  }

  private function _setType(string $type): void
  {
    if (!in_array($type, self::CONFIGURABLE_ANALYZER_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        '_setType only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_ANALYZER_TYPES)
      );
    }

    $this->type = $type;
  }

  private function _setStopwords(null|string|array $stopwords): void
  {
    if (is_string($stopwords)) {
      if (!preg_match('/^_[a-z]+_$/', $stopwords)) {
        throw new InvalidArgumentException(
          '_setStopwords only accepts stopwords as string in the format _language_ or array of strings'
        );
      }
    }

    $this->stopwords = $stopwords;
  }

  private function _setMaxTokenLength(int $maxTokenLength): void
  {
    $this->maxTokenLength = $maxTokenLength;
  }

  private function _setPattern(?string $pattern): void
  {
    $this->pattern = $pattern;
  }

  private function _setFlags(?string $flags): void
  {
    $this->flags = $flags;
  }

  private function _setLowercase(bool $lowercase): void
  {
    $this->lowercase = $lowercase;
  }

  private function _setSeparator(string $separator): void
  {
    $this->separator = $separator;
  }

  private function _setMaxOutputSize(int $maxOutputSize): void
  {
    $this->maxOutputSize = $maxOutputSize;
  }

  private function _setLanguage(?string $language): void
  {
    if ($language !== NULL && !in_array($language, self::LANGUAGE_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        '_setLanguage only accepts language as null or one of: ' . implode(', ', self::LANGUAGE_TYPES)
      );
    }

    $this->language = $language;
  }

  private function _setStemExclusion(array $stemExclusion): void
  {
    $this->stemExclusion = $stemExclusion;
  }

  private function _setTokenizer(?Tokenizer $tokenizer): void
  {
    $this->tokenizer = $tokenizer;
  }

  private function _setCharFilters(array $charFilters): void
  {
    $this->charFilters = $charFilters;
  }

  private function _setFilters(array $filters): void
  {
    $this->filters = $filters;
  }

  public static function load(string $indexName): ?self
  {
    // todo: return instantiated index factory from the config or null if does not exists
  }

  public static function delete(string $indexName): bool
  {
    // todo: implement config delete
    // todo: implement index deletion in ES
  }

  /**
   * Formats the analyzer configuration as an array for use in ES config.
   * This method will only include properties relevant to the analyzer type.
   * @return array
   */
  public function toArray(): array
  {
    $props = [
      'type' => $this->type,
    ];

    switch ($this->type) {
      case 'standard':
        $props['max_token_length'] = $this->maxTokenLength;

        if ($this->stopwords !== NULL) $props['stopwords'] = $this->stopwords;
        break;
      case 'stop':
        if ($this->stopwords !== NULL) $props['stopwords'] = $this->stopwords;
        break;
      case 'pattern':
        $props['lowercase'] = $this->lowercase;

        if ($this->stopwords !== NULL) $props['stopwords'] = $this->stopwords;
        if ($this->pattern) $props['pattern'] = $this->pattern;
        if ($this->flags) $props['flags'] = $this->flags;
        break;
      case 'fingerprint':
        $props['separator'] = $this->separator;
        $props['max_output_size'] = $this->maxOutputSize;

        if ($this->stopwords !== NULL) $props['stopwords'] = $this->stopwords;
        break;
      case 'language':
        if ($this->stopwords !== NULL) $props['stopwords'] = $this->stopwords;
        if ($this->language) $props['language'] = $this->language;
        if (!empty($this->stemExclusion)) $props['stem_exclusion'] = $this->stemExclusion;
        break;
      case 'custom':
        if ($this->tokenizer) $props['tokenizer'] = $this->tokenizer->getName();
        if (!empty($this->charFilters)) $props['char_filter'] = array_map(fn($cf) => $cf->getName(), $this->charFilters);
        if (!empty($this->filters)) $props['filter'] = array_map(fn($f) => $f->getName(), $this->filters);
        break;
      default:
        throw new InvalidArgumentException(
          'toArray only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_ANALYZER_TYPES)
        );
    };

    return $props;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function save()
  {
    // todo: implement config save
    // todo: implement index creation in ES
  }
}
