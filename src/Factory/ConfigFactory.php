<?php

namespace Drupal\eticsearch\Factory;

use Drupal\Core\Config\ConfigFactoryInterface;
use InvalidArgumentException;

class ConfigFactory
{
  public const string CONFIG_KEY = 'eticsearch.configuration';
  public const string THIRD_PARTY_KEY = 'eticsearch_settings';
  /**
   * All allowed config keys with their expected types
   * @var array<string, string>
   */
  private const array TYPED_KEYS = [
    /* Module Specific */
    'etic:analyzers' => 'array',
    'etic:char_filters' => 'array',
    'etic:filters' => 'array',
    'etic:normalizers' => 'array',
    'etic:tokenizers' => 'array',
    'etic:similarities' => 'array',
    'etic:indices' => 'array',
    /* ElasticSearch Specific */
    'es:host' => 'string',
    'es:port' => 'integer',
    'es:auth_method' => 'string',
    'es:username' => 'string',
    'es:password' => 'string',
    'es:api_key_id' => 'string',
    'es:api_key_secret' => 'string',
    'es:verify_ssl' => 'boolean',
    'es:ca_cert' => 'string',
  ];

  public function __construct(private readonly ConfigFactoryInterface $configFactory) {}

  public function get(): array {
    $config = $this->configFactory->get(self::CONFIG_KEY);

    $result = [];
    foreach (self::TYPED_KEYS as $key => $type) {
      $result[$key] = $config->get($key);
    }
    return $result;
  }

  public function set(string $key, mixed $value): void {
    $this->_checkVal($key, $value);
    $config = $this->configFactory->getEditable(self::CONFIG_KEY);
    $config->set($key, $value)->save();
  }

  public function delete(string $key): void {
    $this->_checkKey($key);
    $config = $this->configFactory->getEditable(self::CONFIG_KEY);
    $config->clear($key)->save();
  }

  public function getIndices(): array {
    $config = $this->configFactory->get(self::CONFIG_KEY);
    return $config->get('etic:indices') ?? [];
  }

  public function getCharFilters(): array {
    $config = $this->configFactory->get(self::CONFIG_KEY);
    return $config->get('etic:char_filters') ?? [];
  }

  public function deleteCharFilter(string $charFilterName): bool {
    $config = $this->configFactory->getEditable(self::CONFIG_KEY);
    $charFilters = $config->get('etic:char_filters') ?? [];
    if (!array_key_exists($charFilterName, $charFilters)) {
      return FALSE;
    }

    unset($charFilters[$charFilterName]);
    $config->set('etic:char_filters', $charFilters)->save();
    return TRUE;
  }

  public function getFilters(): array {
    $config = $this->configFactory->get(self::CONFIG_KEY);
    return $config->get('etic:filters') ?? [];
  }

  public function deleteFilter(string $filterName): bool {
    $config = $this->configFactory->getEditable(self::CONFIG_KEY);
    $filters = $config->get('etic:filters') ?? [];
    if (!array_key_exists($filterName, $filters)) {
      return FALSE;
    }

    unset($filters[$filterName]);
    $config->set('etic:filters', $filters)->save();
    return TRUE;
  }

  public function getNormalizers(): array {
    $config = $this->configFactory->get(self::CONFIG_KEY);
    return $config->get('etic:normalizers') ?? [];
  }

  public function deleteNormalizer(string $normalizerName): bool {
    $config = $this->configFactory->getEditable(self::CONFIG_KEY);
    $normalizers = $config->get('etic:normalizers') ?? [];
    if (!array_key_exists($normalizerName, $normalizers)) {
      return FALSE;
    }

    unset($normalizers[$normalizerName]);
    $config->set('etic:normalizers', $normalizers)->save();
    return TRUE;
  }

  public function getTokenizers(): array {
    $config = $this->configFactory->get(self::CONFIG_KEY);
    return $config->get('etic:tokenizers') ?? [];
  }

  public function deleteTokenizer(string $tokenizerName): bool {
    $config = $this->configFactory->getEditable(self::CONFIG_KEY);
    $tokenizers = $config->get('etic:tokenizers') ?? [];
    if (!array_key_exists($tokenizerName, $tokenizers)) {
      return FALSE;
    }

    unset($tokenizers[$tokenizerName]);
    $config->set('etic:tokenizers', $tokenizers)->save();
    return TRUE;
  }

  public function getSimilarities(): array {
    $config = $this->configFactory->get(self::CONFIG_KEY);
    return $config->get('etic:similarities') ?? [];
  }

  public function deleteSimilarity(string $similarityName): bool {
    $config = $this->configFactory->getEditable(self::CONFIG_KEY);
    $similarities = $config->get('etic:similarities') ?? [];
    if (!array_key_exists($similarityName, $similarities)) {
      return FALSE;
    }

    unset($similarities[$similarityName]);
    $config->set('etic:similarities', $similarities)->save();
    return TRUE;
  }

  public function getAnalyzers(): array {
    $config = $this->configFactory->get(self::CONFIG_KEY);
    return $config->get('etic:analyzers') ?? [];
  }

  public function deleteAnalyzer(string $analyzerName): bool {
    $config = $this->configFactory->getEditable(self::CONFIG_KEY);
    $analyzers = $config->get('etic:analyzers') ?? [];
    if (!array_key_exists($analyzerName, $analyzers)) {
      return FALSE;
    }

    unset($analyzers[$analyzerName]);
    $config->set('etic:analyzers', $analyzers)->save();
    return TRUE;
  }

  private function _checkVal(string $key, mixed $value): void {
    $this->_checkKey($key);

    if (!array_key_exists($key, self::TYPED_KEYS)) {
      throw new InvalidArgumentException(sprintf('Invalid configuration key: %s', $key));
    }

    if (gettype($value) !== self::TYPED_KEYS[$key]) {
      throw new InvalidArgumentException(sprintf('Invalid value type for key %s, got %s', $key, gettype($value)));
    }
  }

  private function _checkKey(string $key): void {
    $keys = array_keys(self::TYPED_KEYS);
    if (!in_array($key, $keys, TRUE)) {
      throw new InvalidArgumentException(sprintf('Invalid configuration key: %s', $key));
    }
  }
}
