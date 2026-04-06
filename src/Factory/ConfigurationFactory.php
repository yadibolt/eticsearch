<?php

namespace Drupal\eticsearch\Factory;

use Drupal\Core\Config\ConfigFactoryInterface;
use InvalidArgumentException;

class ConfigurationFactory
{
  private const string CONFIGURATION_NAME = 'eticsearch.configuration';
  private const string THIRD_PARTY_SETTINGS_KEY = 'eticsearch_settings';

  private string $configuration_key;
  private string $third_party_settings_key;

  public function __construct(private readonly ConfigFactoryInterface $configFactory)
  {
    $this->configuration_key = self::CONFIGURATION_NAME;
    $this->third_party_settings_key = self::THIRD_PARTY_SETTINGS_KEY;
  }

  public function getConfigurationKey(): string
  {
    return $this->configuration_key;
  }

  public function getThirdPartySettings(): string
  {
    return $this->third_party_settings_key;
  }

  public function getThirdPartySettingsKey(): string
  {
    return self::THIRD_PARTY_SETTINGS_KEY;
  }

  public function get(): array
  {
    $config = $this->configFactory->get($this->configuration_key);
    $result = [];
    foreach ($this->getTypedKeys() as $key => $type) {
      $result[$key] = $config->get($key);
    }
    return $result;
  }

  public function getSingle(string $key): mixed
  {
    $this->checkKey($key);
    $config = $this->configFactory->get($this->configuration_key);
    return $config->get($key);
  }

  private function getTypedKeys(): array
  {
    return [
      /* ElasticSearch specific */
      'elasticsearch:host' => 'string',
      'elasticsearch:port' => 'integer',
      'elasticsearch:auth_method' => 'string',
      'elasticsearch:username' => 'string',
      'elasticsearch:password' => 'string',
      'elasticsearch:api_key_id' => 'string',
      'elasticsearch:api_key_secret' => 'string',
      'elasticsearch:verify_ssl' => 'boolean',
      'elasticsearch:ca_cert' => 'string',
      /* Eticsearch specific */
      'eticsearch:indices' => 'array',
      'eticsearch:analyzers' => 'array',
      'eticsearch:filters' => 'array',
      'eticsearch:fields' => 'array',
    ];
  }

  public function setValue(string $key, mixed $value): void
  {
    $this->checkValue($key, $value);
    $config = $this->configFactory->getEditable($this->configuration_key);
    $config->set($key, $value)->save();
  }

  private function checkValue(string $key, mixed $value): void
  {
    $this->checkKey($key);

    $type = $this->getTypedKeys()[$key];
    if (gettype($value) !== $type) {
      throw new InvalidArgumentException(sprintf('Invalid value type for key %s: expected %s, got %s', $key, $type, gettype($value)));
    }
  }

  private function checkKey(string $key): void
  {
    $keys = array_keys($this->getTypedKeys());
    if (!in_array($key, $keys, TRUE)) {
      throw new InvalidArgumentException(sprintf('Invalid configuration key: %s', $key));
    }
  }

  public function deleteValue(string $key): void
  {
    $this->checkKey($key);
    $config = $this->configFactory->getEditable($this->configuration_key);
    $config->clear($key)->save();
  }
}
