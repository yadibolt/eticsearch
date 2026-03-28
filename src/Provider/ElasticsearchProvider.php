<?php

namespace Drupal\eticsearch\Provider;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\eticsearch\Manager\ConfigurationManager;
use Drupal\node\Entity\NodeType;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

class ElasticsearchProvider
{
  private ?Client $client = NULL;

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  )
  {
  }

  public function disconnect(): void
  {
    $this->client = NULL;
  }

  public function createIndexIfNotExists(string $index): bool
  {
    $client = $this->connect();

    try {
      $exists = $client->indices()->exists(['index' => $index]);
    } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
      Drupal::logger('eticsearch_module')->error($e->getMessage());
      return FALSE;
    }

    if (!$exists->asBool()) {
      try {
        $client->indices()->create(['index' => $index]);
      } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
        Drupal::logger('eticsearch_module')->error($e->getMessage());
        return FALSE;
      }
    }

    return TRUE;
  }

  public function connect(): Client
  {
    if ($this->client !== NULL) {
      return $this->client;
    }

    $config = $this->configFactory->get(ConfigurationManager::CONFIG_NAME);
    $host = rtrim($config->get('host'), '/') . ':' . $config->get('port');

    $builder = ClientBuilder::create()->setHosts([$host]);

    match ($config->get('auth_method')) {
      'basic' => $builder->setBasicAuthentication(
        $config->get('username'),
        $config->get('password'),
      ),
      'api_key' => $builder->setApiKey($config->get('api_key')),
      default => NULL,
    };

    if (!$config->get('verify_ssl')) {
      $builder->setSSLVerification(FALSE);
    } elseif ($config->get('ca_cert')) {
      $builder->setCABundle($config->get('ca_cert'));
    }

    try {
      $this->client = $builder->build();
    } catch (AuthenticationException $e) {
      Drupal::logger('eticsearch_module')->error($e->getMessage());
    }

    return $this->client;
  }

  public function deleteIndexIfExists(string $index): bool
  {
    $client = $this->connect();

    try {
      $exists = $client->indices()->exists(['index' => $index]);
    } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
      Drupal::logger('eticsearch_module')->error($e->getMessage());
      return FALSE;
    }

    if ($exists->asBool()) {
      try {
        $client->indices()->delete(['index' => $index]);
      } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
        Drupal::logger('eticsearch_module')->error($e->getMessage());
        return FALSE;
      }
    }

    return TRUE;
  }

}
