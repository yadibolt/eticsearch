<?php

namespace Drupal\eticsearch\Factory;

use InvalidArgumentException;

class Tokenizer
{
  public const array CONFIGURABLE_TOKENIZER_TYPES = [
    'standard', 'ngram', 'edge_ngram', 'pattern', 'simple_pattern',
    'simple_pattern_split', 'char_group', 'path_hierarchy',
  ];

  private string $name = 'tokenizer';
  private string $type = 'standard';
  private int $maxTokenLength = 255;
  private int $minGram = 1;
  private int $maxGram = 2;
  private array $tokenChars = [];
  private ?string $customTokenChars = NULL;
  private string $pattern = '\W+';
  private ?string $flags = NULL;
  private int $group = -1;
  private array $tokenizeOnChars = [];
  private string $delimiter = '/';
  private string $replacement = '/';
  private int $skip = 0;
  private bool $reverse = FALSE;

  public function __construct()
  {
  }

  public static function create(string  $name, string $type, int $maxTokenLength = 255, int $minGram = 1, int $maxGram = 2, array $tokenChars = [],
                                ?string $customTokenChars = NULL, string $pattern = '\W+', ?string $flags = NULL, int $group = -1, array $tokenizeOnChars = [], string $delimiter = '/',
                                string  $replacement = '/', int $skip = 0, bool $reverse = FALSE): self
  {
    if (!in_array($type, self::CONFIGURABLE_TOKENIZER_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'create only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_TOKENIZER_TYPES)
      );
    }

    $instance = new self();
    $instance->_setName($name);
    $instance->_setType($type);
    $instance->_setMaxTokenLength($maxTokenLength);
    $instance->_setMinGram($minGram);
    $instance->_setMaxGram($maxGram);
    $instance->_setTokenChars($tokenChars);
    $instance->_setCustomTokenChars($customTokenChars);
    $instance->_setPattern($pattern);
    $instance->_setFlags($flags);
    $instance->_setGroup($group);
    $instance->_setTokenizeOnChars($tokenizeOnChars);
    $instance->_setDelimiter($delimiter);
    $instance->_setReplacement($replacement);
    $instance->_setSkip($skip);
    $instance->_setReverse($reverse);

    return $instance;
  }

  private function _setName(string $name): void
  {
    $this->name = $name;
  }

  private function _setType(string $type): void
  {
    if (!in_array($type, self::CONFIGURABLE_TOKENIZER_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        '_setType only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_TOKENIZER_TYPES)
      );
    }

    $this->type = $type;
  }

  private function _setMaxTokenLength(int $maxTokenLength): void
  {
    $this->maxTokenLength = $maxTokenLength;
  }

  private function _setMinGram(int $minGram): void
  {
    $this->minGram = $minGram;
  }

  private function _setMaxGram(int $maxGram): void
  {
    $this->maxGram = $maxGram;
  }

  private function _setTokenChars(array $tokenChars): void
  {
    $this->tokenChars = $tokenChars;
  }

  private function _setCustomTokenChars(?string $customTokenChars): void
  {
    $this->customTokenChars = $customTokenChars;
  }

  private function _setPattern(string $pattern): void
  {
    $this->pattern = $pattern;
  }

  private function _setFlags(?string $flags): void
  {
    $this->flags = $flags;
  }

  private function _setGroup(int $group): void
  {
    $this->group = $group;
  }

  private function _setTokenizeOnChars(array $tokenizeOnChars): void
  {
    $this->tokenizeOnChars = $tokenizeOnChars;
  }

  private function _setDelimiter(string $delimiter): void
  {
    $this->delimiter = $delimiter;
  }

  private function _setReplacement(string $replacement): void
  {
    $this->replacement = $replacement;
  }

  private function _setSkip(int $skip): void
  {
    $this->skip = $skip;
  }

  private function _setReverse(bool $reverse): void
  {
    $this->reverse = $reverse;
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
   * Formats the tokenizer configuration as an array for use in ES config.
   * This method will only include properties relevant to the tokenizer type.
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
        break;

      case 'ngram':
      case 'edge_ngram':
        $props['min_gram'] = $this->minGram;
        $props['max_gram'] = $this->maxGram;
        $props['token_chars'] = $this->tokenChars;

        if ($this->customTokenChars !== NULL) $props['custom_token_chars'] = $this->customTokenChars;
        break;
      case 'pattern':
        $props['pattern'] = $this->pattern;
        $props['group'] = $this->group;

        if ($this->flags !== NULL) $props['flags'] = $this->flags;
        break;
      case 'simple_pattern':
      case 'simple_pattern_split':
        $props['pattern'] = $this->pattern;
        break;
      case 'char_group':
        $props['tokenize_on_chars'] = $this->tokenizeOnChars;
        break;
      case 'path_hierarchy':
        $props['delimiter'] = $this->delimiter;
        $props['replacement'] = $this->replacement;
        $props['skip'] = $this->skip;
        $props['reverse'] = $this->reverse;
        break;
      default:
        throw new InvalidArgumentException(
          'toArray only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_TOKENIZER_TYPES)
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
