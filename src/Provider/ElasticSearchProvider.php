<?php

namespace Drupal\eticsearch\Provider;

use Drupal\eticsearch\Factory\ConfigurationFactory;
use Drupal\eticsearch\Logger;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use InvalidArgumentException;

/**
 * Class ElasticSearchProvider
 *
 * Provides methods to interact with the ElasticSearch service.
 *
 * @package Drupal\eticsearch\Provider
 */
class ElasticSearchProvider
{
  private ?Client $elasticsearchClient;

  public function __construct(private readonly ConfigurationFactory $configFactory)
  {
    $config = $this->configFactory->get();

    $host = $config['elasticsearch.host'] ?? 'http://eticsearch_elasticsearch';
    $port = $config['elasticsearch.port'] ?? 9200;
    $authMethod = $config['elasticsearch.auth_method'] ?? 'none';
    $username = $config['elasticsearch.username'] ?? NULL;
    $password = $config['elasticsearch.password'] ?? NULL;
    $apiKeyID = $config['elasticsearch.api_key_id'] ?? NULL;
    $apiKeySecret = $config['elasticsearch.api_key_secret'] ?? NULL;
    $verifySSL = $config['elasticsearch.verify_ssl'] ?? FALSE;
    $certificateAuthority = $config['elasticsearch.ca_cert'] ?? NULL;

    $this->elasticsearchClient = $this->buildClient($host, $port, $authMethod, $username, $password, $apiKeyID, $apiKeySecret, $verifySSL, $certificateAuthority);
  }

  protected function buildClient(
    string $host,
    int $port,
    string $authMethod,
    ?string $username,
    ?string $password,
    ?string $apiKeyID,
    ?string $apiKeySecret,
    bool $verifySSL,
    ?string $certificateAuthority
  ): Client
  {
    $clientBuilder = ClientBuilder::create();

    // set host
    $fullHost = rtrim($host, '/') . ':' . $port;
    $clientBuilder->setHosts([$fullHost]);

    // set auth method depending on the params
    $modifiedClientBuilder = match ($authMethod) {
      'basic' => $clientBuilder->setBasicAuthentication($username, $password),
      'api_key' => $clientBuilder->setApiKey($apiKeySecret, $apiKeyID),
      'none' => $clientBuilder,
      default => NULL,
    };

    if ($modifiedClientBuilder === NULL) {
      throw new InvalidArgumentException('Invalid authentication method: ' . $authMethod);
    }

    // set either SSL verification or CA certificate
    if (!$verifySSL) {
      $clientBuilder->setSSLVerification(FALSE);
    } elseif ($certificateAuthority) {
      $clientBuilder->setSSLVerification($certificateAuthority);
    } else {
      $clientBuilder->setSSLVerification(TRUE);
    }

    // try to build a client - we do not throw exceptions
    // instead we set the client to NULL
    try {
      $this->elasticsearchClient = $clientBuilder->build();
    } catch (AuthenticationException $e) {
      $this->elasticsearchClient = NULL;
      Logger::send($e->getMessage(), [], 'error');
    }

    return $this->elasticsearchClient;
  }

  public function connect(): ?Client {
    if (!$this->isAvailable()) return NULL;
    return $this->elasticsearchClient;
  }

  public function disconnect(): void {
    // ES does not maintain persistent connections,
    // this is solely just for cleanup - if necessary
    $this->elasticsearchClient = NULL;
  }

  public function isAvailable(): bool {
    try {
      if (empty($this->elasticsearchClient)) return FALSE;
      $this->elasticsearchClient->ping();
      return TRUE;
    } catch (ClientResponseException|ServerResponseException $e) {
      Logger::send($e->getMessage(), [], 'error');
      return FALSE;
    }
  }
}
