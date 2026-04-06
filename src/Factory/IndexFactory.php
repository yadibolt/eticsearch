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

  public function __construct(private readonly ElasticsearchProvider $elasticsearchProvider, private readonly ConfigurationFactory $configFactory)
  {
    // returns NULL if the client connection fails
    $this->client = $this->elasticsearchProvider->connect();
  }

  /**
   * For mappings and settings, refer to the official Elasticsearch documentation:
   * - https://www.elastic.co/docs/reference/elasticsearch/clients/php/index_management
   */
  public function createIndex(string $indexName, array $settings = [], array $mappings = [], array $analyzers = [], array $filters = [], array $fields = []): bool
  {
    if ($this->client === NULL) return FALSE;
    if ($this->indexExists($indexName)) return FALSE;

    // build array from the params
    $params = [
      'index' => $indexName,
    ];

    if (!empty($settings)) {
      if (!empty($analyzers)) {
        $settings['analysis']['analyzer'] = $analyzers;
      }

      if (!empty($filters)) {
        $settings['analysis']['filter'] = $filters;
      }

      $params['body']['settings'] = $settings;
    }
    if (!empty($mappings)) $params['body']['mappings'] = $mappings;

    // if the index was created successfully, we write to the config
    // - fields are mapping specific, so they have to be created
    // beforehand and then added to the mapping settings of the index
    // user can do that by creating a field through UI, assigning it to the specific field
    // and then recreating the index. (maybe not the best user experience, but it is done only once)
    if (!empty($fields)) {
      foreach ($fields as $fieldName => $field) {
        if (isset($mappings['properties'][$field['parent']])) {
          $mappings['properties'][$field['parent']]['fields'][$fieldName] = $field;
          if (isset($mappings['properties'][$field['parent']]['fields'][$fieldName]['parent'])) {
            unset($mappings['properties'][$field['parent']]['fields'][$fieldName]['parent']);
          }
        }
      }
    }

    try {
      $response = $this->client->indices()->create($params);
      $response = $response->asBool();

      $index = [
        $indexName => [
          'eticsearch_index:analyzers' => $analyzers,
          'eticsearch_index:filters' => $filters,
          'eticsearch_index:mappings' => $mappings,
          'eticsearch_index:settings' => $settings,
          'eticsearch_index:fields' => $fields,
        ]
      ];

      $existingIndices = $this->configFactory->getSingle('eticsearch:indices') ?? [];
      $indices = array_merge($existingIndices, $index);
      $this->configFactory->setValue('eticsearch:indices', $indices);

      return $response;
    } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
      Logger::send($e->getMessage(), [], 'error');
      return FALSE;
    }
  }

  public function recreateIndex(string $indexName): bool {
    if ($this->client === NULL) return FALSE;
    if (!$this->indexExists($indexName)) return FALSE;

    $existingIndices = $this->configFactory->getSingle('eticsearch:indices') ?? [];
    $currentIndex = $existingIndices[$indexName] ?? NULL;

    if ($currentIndex === NULL) return FALSE;

    $fields = $currentIndex['eticsearch_index:fields'] ?? [];
    if (empty($fields)) return FALSE;

    $settings = $currentIndex['eticsearch_index:settings'] ?? [];
    $mappings = $currentIndex['eticsearch_index:mappings'] ?? [];
    $analyzers = $currentIndex['eticsearch_index:analyzers'] ?? [];
    $filters = $currentIndex['eticsearch_index:filters'] ?? [];

    // delete the index and recreate it with the new fields
    $response = $this->deleteIndex($indexName);
    if (!$response) return FALSE;

    return $this->createIndex($indexName, $settings, $mappings, $analyzers, $filters, $fields);
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
      $response = $response->asBool();

      if ($response) {
        // if the index was deleted successfully, perform cleanup in the config
        // for index itself and fields. Analyzers and fields are not tied to the index.
        $existingIndices = $this->configFactory->getSingle('eticsearch:indices') ?? [];
        if (isset($existingIndices[$indexName])) {
          unset($existingIndices[$indexName]);
          $this->configFactory->setValue('eticsearch:indices', $existingIndices);
        }

        $existingFields = $this->configFactory->getSingle('eticsearch:fields') ?? [];
        if (isset($existingFields[$indexName])) {
          unset($existingFields[$indexName]);
          $this->configFactory->setValue('eticsearch:fields', $existingFields);
        }
      }

      return $response;
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
    /** @var Drupal\eticsearch\Manager\EntityFieldManager $entityFieldManager */
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
