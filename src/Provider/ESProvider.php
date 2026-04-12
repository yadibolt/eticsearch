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
  }

  public static function create(): self {
    $instance = new self();
    $config = $instance->configFactory->get();

    $instance->host = $config['es:host'] ?? $instance->host;
    $instance->port = $config['es:port'] ?? $instance->port;
    $instance->authMethod = $config['es:auth_method'] ?? $instance->authMethod;
    $instance->username = $config['es:username'] ?? $instance->username;
    $instance->password = $config['es:password'] ?? $instance->password;
    $instance->apiKeyID = $config['es:api_key_id'] ?? $instance->apiKeyID;
    $instance->apiKeySecret = $config['es:api_key_secret'] ?? $instance->apiKeySecret;
    $instance->verifySSL = $config['es:verify_ssl'] ?? $instance->verifySSL;
    $instance->certificateAuthority = $config['es:ca_cert'] ?? $instance->certificateAuthority;

    $clientBuilder = ClientBuilder::create();

    // set host
    $fullHost = rtrim($instance->host, '/') . ':' . $instance->port;
    $clientBuilder->setHosts([$fullHost]);

    // set auth method depending on the params
    $mClientBuilder = match ($instance->authMethod) {
      'basic' => $clientBuilder->setBasicAuthentication($instance->username, $instance->password),
      'api_key' => $clientBuilder->setApiKey($instance->apiKeySecret, $instance->apiKeyID),
      'none' => $clientBuilder,
      default => NULL,
    };

    if ($mClientBuilder === NULL) {
      throw new InvalidArgumentException('Invalid authentication method: ' . $instance->authMethod);
    }

    // set either SSL verification or CA certificate
    if (!$instance->verifySSL) {
      $clientBuilder->setSSLVerification(FALSE);
    } elseif ($instance->certificateAuthority) {
      $clientBuilder->setSSLVerification($instance->certificateAuthority);
    } else {
      $clientBuilder->setSSLVerification(TRUE);
    }

    // try to build a client - we do not throw exceptions
    // instead we set the client to NULL
    try {
      $instance->esClient = $clientBuilder->build();
    } catch (AuthenticationException $e) {
      throw new RuntimeException('Failed to authenticate with Elasticsearch: ' . $e->getMessage());
    }

    return $instance;
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
