<?php

namespace Drupal\eticsearch\Factory;

use Drupal;
use Drupal\eticsearch\Logger;
use Drupal\eticsearch\Provider\ElasticsearchProvider;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use InvalidArgumentException;
use Throwable;

class IndexFactory
{
  protected ?Client $client;

  public function __construct(private readonly ElasticsearchProvider $elasticsearchProvider)
  {
    // returns NULL if the client connection fails
    $this->client = $this->elasticsearchProvider->connect();
  }

  /**
   * For mappings and settings, refer to the official Elasticsearch documentation:
   * - https://www.elastic.co/docs/reference/elasticsearch/clients/php/index_management
   */
  public function createIndex(string $indexName, array $settings = [], array $mappings = []): bool
  {
    if ($this->client === NULL) return FALSE;
    if ($this->indexExists($indexName)) return FALSE;

    // build array from the params
    $params = [
      'index' => $indexName,
    ];

    if (!empty($settings)) $params['body']['settings'] = $settings;
    if (!empty($mappings)) $params['body']['mappings'] = $mappings;

    try {
      $response = $this->client->indices()->create($params);
      return $response->asBool();
    } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
      Logger::send($e->getMessage(), [], 'error');
      return FALSE;
    }
  }

  public function indexExists(string $indexName): bool
  {
    if ($this->client === NULL) return FALSE;

    try {
      $response = $this->client->indices()->exists(['index' => $indexName]);
      return $response->asBool();
    } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
      Logger::send($e->getMessage(), [], 'error');
      return FALSE;
    }
  }

  public function deleteIndex(string $indexName): bool
  {
    if ($this->client === NULL) return FALSE;
    if (!$this->indexExists($indexName)) return FALSE;

    try {
      $response = $this->client->indices()->delete(['index' => $indexName]);
      return $response->asBool();
    } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
      Logger::send($e->getMessage(), [], 'error');
      return FALSE;
    }
  }

  /**
   * Refer to the official Elasticsearch documentation for index settings:
   * - https://www.elastic.co/docs/reference/elasticsearch/index-settings/index-modules
   */
  public function createIndexSettings(
    string  $indexName,
    int     $numberOfShards = 1,
    ?int    $numberOfRoutingShards = NULL,
    string  $codec = 'default',
    string  $mode = 'standard',
    int     $routingPartitionSize = 1,
    int     $numberOfReplicas = 1,
    bool    $autoExpandReplicas = FALSE,
    string  $searchIdleAfter = '30s',
    string  $refreshInterval = '1s',
    int     $maxResultWindow = 10000,
    int     $maxInnerResultWindow = 100,
    int     $maxRescoreWindow = 10000,
    int     $maxDocvalueFieldsSearch = 100,
    int     $maxScriptFields = 32,
    int     $maxNgramDiff = 1,
    int     $maxShingleDiff = 3,
    int     $maxRefreshListeners = 3,
    int     $analyzeMaxTokenCount = 10000,
    int     $highlightMaxAnalyzedOffset = 1000000,
    int     $maxTermsCount = 65536,
    int     $maxRegexLength = 1000,
    string  $queryDefaultField = '*',
    string  $routingAllocationEnable = 'all',
    string  $routingRebalanceEnable = 'all',
    string  $gcDeletes = '60s',
    ?string $defaultPipeline = NULL,
    ?string $finalPipeline = NULL,
    bool    $hidden = FALSE
  ): array
  {
    $index = [
      /* static settings */
      'number_of_shards' => $numberOfShards,
      'codec' => $codec,
      'mode' => $mode,
      'routing_partition_size' => $routingPartitionSize,

      /* dynamic settings */
      'number_of_replicas' => $numberOfReplicas,
      'auto_expand_replicas' => $autoExpandReplicas,
      'search' => [
        'idle' => [
          'after' => $searchIdleAfter,
        ]
      ],
      'refresh_interval' => $refreshInterval,
      'max_result_window' => $maxResultWindow,
      'max_inner_result_window' => $maxInnerResultWindow,
      'max_rescore_window' => $maxRescoreWindow,
      'max_docvalue_fields_search' => $maxDocvalueFieldsSearch,
      'max_script_fields' => $maxScriptFields,
      'max_ngram_diff' => $maxNgramDiff,
      'max_shingle_diff' => $maxShingleDiff,
      'max_refresh_listeners' => $maxRefreshListeners,
      'analyze' => [
        'max_token_count' => $analyzeMaxTokenCount,
      ],
      'highlight' => [
        'max_analyzed_offset' => $highlightMaxAnalyzedOffset,
      ],
      'max_terms_count' => $maxTermsCount,
      'max_regex_length' => $maxRegexLength,
      'query' => [
        'default_field' => $queryDefaultField,
      ],
      'routing' => [
        'allocation' => [
          'enable' => $routingAllocationEnable,
        ],
        'rebalance' => [
          'enable' => $routingRebalanceEnable,
        ],
      ],
      'gc_deletes' => $gcDeletes,
      'hidden' => $hidden,
    ];

    // handle provided values that were by default set to NULL
    if ($numberOfRoutingShards !== NULL) $index['number_of_routing_shards'] = $numberOfRoutingShards;
    if ($defaultPipeline !== NULL) $index['default_pipeline'] = $defaultPipeline;
    if ($finalPipeline !== NULL) $index['final_pipeline'] = $finalPipeline;

    // handle analysis setting
    // it has to call a function to build the array, because of the complexity of the setting.
    $functionName = 'eticsearch_build_analysis_settings_' . strtolower($indexName) . '_alter';
    if (function_exists($functionName)) {
      $analysisSettingsDefault = [];
      $analysisSettings = call_user_func($functionName, [$analysisSettingsDefault]);
      if (is_array($analysisSettings)) {
        if (!empty($analysisSettings)) $index['analysis'] = $analysisSettings;
      }
    }

    return $index;
  }

  public function createIndexMappings(string $indexName, string $entityType, string $bundle): array
  {
    // TODO: extend support for more entity types
    return match($entityType) {
      'node' => $this->createNodeMappings($indexName, $bundle),
      default => throw new InvalidArgumentException('Unsupported entity type: ' . $entityType),
    };
  }

  private function createNodeMappings(string $indexName, string $contentType): array {
    $entityFieldManager = Drupal::service('eticsearch.entity_field.manager');
    $fields = $entityFieldManager->getEntityFieldTypeMappings('node', $contentType);

    $functionName = 'eticsearch_build_mappings' . strtolower($indexName) . '_alter';
    $mappings = [
      'properties' => $fields,
    ];
    if (function_exists($functionName)) {
      $mappings = call_user_func($functionName, [$mappings]);
    }

    return $mappings;
  }
}
