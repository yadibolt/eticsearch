<?php

namespace Drupal\eticsearch\Provider;

use Drupal;
use Drupal\eticsearch\Factory\ConfigFactory;
use Drupal\eticsearch\Logger;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use http\Exception\RuntimeException;
use InvalidArgumentException;

class ESProvider
{
  private Client $esClient;
  private ConfigFactory $configFactory;
  private string $host = 'http://eticsearch_elasticsearch';
  private int $port = 9200;
  private string $authMethod = 'none';
  private ?string $username = NULL;
  private ?string $password = NULL;
  private ?string $apiKeyID = NULL;
  private ?string $apiKeySecret = NULL;
  private bool $verifySSL = FALSE;
  private ?string $certificateAuthority = NULL;

  public function __construct() {
    $this->configFactory = Drupal::service('eticsearch.factory.config');
    $config = $this->configFactory->get();

    $this->host = $config['es:host'] ?? $this->host;
    $this->port = $config['es:port'] ?? $this->port;
    $this->authMethod = $config['es:auth_method'] ?? $this->authMethod;
    $this->username = $config['es:username'] ?? $this->username;
    $this->password = $config['es:password'] ?? $this->password;
    $this->apiKeyID = $config['es:api_key_id'] ?? $this->apiKeyID;
    $this->apiKeySecret = $config['es:api_key_secret'] ?? $this->apiKeySecret;
    $this->verifySSL = $config['es:verify_ssl'] ?? $this->verifySSL;
    $this->certificateAuthority = $config['es:ca_cert'] ?? $this->certificateAuthority;

    $clientBuilder = ClientBuilder::create();

    // set host
    $fullHost = rtrim($this->host, '/') . ':' . $this->port;
    $clientBuilder->setHosts([$fullHost]);

    // set auth method depending on the params
    $mClientBuilder = match ($this->authMethod) {
      'basic' => $clientBuilder->setBasicAuthentication($this->username, $this->password),
      'api_key' => $clientBuilder->setApiKey($this->apiKeySecret, $this->apiKeyID),
      'none' => $clientBuilder,
      default => NULL,
    };

    if ($mClientBuilder === NULL) {
      throw new InvalidArgumentException('Invalid authentication method: ' . $this->authMethod);
    }

    // set either SSL verification or CA certificate
    if (!$this->verifySSL) {
      $clientBuilder->setSSLVerification(FALSE);
    } elseif ($this->certificateAuthority) {
      $clientBuilder->setSSLVerification($this->certificateAuthority);
    } else {
      $clientBuilder->setSSLVerification(TRUE);
    }

    // try to build a client - we do not throw exceptions
    // instead we set the client to NULL
    try {
      $this->esClient = $clientBuilder->build();
    } catch (AuthenticationException $e) {
      throw new RuntimeException('Failed to authenticate with Elasticsearch: ' . $e->getMessage());
    }
  }

  public function connect(): bool|Client {
    if (!$this->isAvailable()) return FALSE;
    return $this->esClient;
  }

  public function isAvailable(): bool {
    try {
      if (empty($this->esClient)) return FALSE;
      $this->esClient->ping();
      return TRUE;
    } catch (ClientResponseException|ServerResponseException $e) {
      Logger::send($e->getMessage(), [], 'error');
      return FALSE;
    }
  }
}
