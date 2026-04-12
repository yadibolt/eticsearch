<?php

namespace Drupal\eticsearch;

use Drupal;
use InvalidArgumentException;

class Tokenizer
{
  public const array CONFIGURABLE_TOKENIZER_TYPES = [
    'standard', 'ngram', 'edge_ngram', 'pattern', 'simple_pattern',
    'simple_pattern_split', 'char_group', 'path_hierarchy',
  ];

  private ConfigFactory $configFactory;
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
    $this->configFactory = Drupal::service('eticsearch.factory.config');
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

  public static function load(string $retrieval = 'single', ?string $tokenizerName = NULL): NULL|array|self
  {
    if (!in_array($retrieval, ['single', 'all'], TRUE)) {
      throw new InvalidArgumentException('load only accepts retrieval as one of: single, all');
    }

    if ($retrieval === 'all') {
      /** @var ConfigFactory $configService */
      $configService = Drupal::service('eticsearch.factory.config');
      $tokenizers = $configService->getTokenizers();

      return array_map(fn($t) => self::fromArray($t), $tokenizers);
    }

    if ($tokenizerName === NULL) {
      throw new InvalidArgumentException('load with retrieval single requires a tokenizer name');
    }

    /** @var ConfigFactory $configService */
    $configService = Drupal::service('eticsearch.factory.config');
    if (($tokenizer = $configService->getTokenizers()[$tokenizerName] ?? NULL) !== NULL) {
      return self::fromArray($tokenizer);
    }

    return NULL;
  }

  public static function delete(string $tokenizerName): bool
  {
    /** @var ConfigFactory $configService */
    $configService = Drupal::service('eticsearch.factory.config');

    // we cannot delete the tokenizer if some index is using it
    $indices = $configService->getIndices();
    foreach ($indices as $index) {
      if (in_array($tokenizerName, $index['tokenizers'] ?? [], TRUE)) {
        return FALSE;
      }
    }

    return $configService->deleteTokenizer($tokenizerName);
  }

  public static function fromArray(array $entry): self {
    return self::create(
      $entry['name'] ?? 'tokenizer',
      $entry['type'] ?? 'standard',
      $entry['max_token_length'] ?? 255,
      $entry['min_gram'] ?? 1,
      $entry['max_gram'] ?? 2,
      $entry['token_chars'] ?? [],
      $entry['custom_token_chars'] ?? NULL,
      $entry['pattern'] ?? '\W+',
      $entry['flags'] ?? NULL,
      $entry['group'] ?? -1,
      $entry['tokenize_on_chars'] ?? [],
      $entry['delimiter'] ?? '/',
      $entry['replacement'] ?? '/',
      $entry['skip'] ?? 0,
      $entry['reverse'] ?? FALSE
    );
  }

  /**
   * Formats the tokenizer configuration as an array for use in ES config.
   * This method will only include properties relevant to the tokenizer type.
   * @return array
   */
  public function toArray(): array
  {
    $props = [
      'name' => $this->name,
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

  public function save(): void
  {
    $tokenizers = $this->configFactory->getTokenizers();
    $tokenizers[$this->name] = $this->toArray();

    $this->configFactory->set('etic:tokenizers', $tokenizers);
  }

  private function _setName(string $name): void
  {
    $this->name = $name;
  }

  public function getName(): string
  {
    return $this->name;
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
}
