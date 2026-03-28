<?php

namespace Drupal\eticsearch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eticsearch\Provider\ElasticsearchProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Tests the Elasticsearch connection.
 */
class TestController extends ControllerBase {

  public function __construct(
    private readonly ElasticsearchProvider $elasticsearchProvider,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('eticsearch.elasticsearch_provider'),
    );
  }

  /**
   * Tests the Elasticsearch connection and returns the cluster info.
   */
  public function testConnection(): JsonResponse {
    $provider = $this->elasticsearchProvider;
    $types = $provider->getEticsearchEnabledContentTypes();

    var_dump($types);

    /*try {
      $client = $this->elasticsearchProvider->connect();
      $info = $client->info();

      return new JsonResponse([
        'status' => 'ok',
        'cluster_name' => $info['cluster_name'],
        'version' => $info['version']['number'],
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $e->getMessage(),
      ], 500);
    }*/
  }

}
