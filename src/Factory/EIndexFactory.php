<?php

namespace Drupal\eticsearch\Factory;

/**
 * Factory for the indices.
 */
class EIndexFactory
{
  private string $indexName = 'eticsearch_index';
  private int $numberOfShards = 1;
  private string $codec = 'default';
  private string $storeType = 'hybridfs';
  private int $numberOfReplicas = 1;
  private string|false $autoExpandReplicas = false;
  private string $refreshInterval = '1s';
  private int $maxResultWindow = 10000;
  private int $maxDocvalueFieldsSearch = 100;
  private int $maxScriptFields = 32;
  private int $maxNgramDiff = 1;
  private int $maxTermsCount = 65536;
  private int $maxRegexLength = 1000;
  private string $gcDeletes = '60s';
  private int $priority = 1;
  private int $mappingTotalFieldsLimit = 1000;
  private int $mappingDepthLimit = 20;
  private int $mappingNestedFieldsLimit = 50;
  private int $mappingNestedObjectsLimit = 10000;
  private ?int $mappingFieldNameLengthLimit = null;
  private array $similarity = [];
  private array $analysis = [
    'analyzer' => [],
    'tokenizer' => [],
    'filter' => [],
    'char_filter' => [],
    'normalizer' => [],
  ];
  private array $mappings = [];

  public function __construct()
  {

  }

  /**
   * Creates a new index factory instance with the specified configuration.
   * @param string $indexName - Name of the index to create.
   * @param MappingFactory|null $mappingFactory
   * @param array<Analyzer> $analyzers - Custom analyzers definitions to register with the index.
   * @param array<Tokenizer> $tokenizers - Custom tokenizers definitions to register with the index.
   * @param array<Filter> $filters - Custom token filters definitions to register with the index.
   * @param array<CharFilter> $charFilters - Custom character filters definitions to register with the index.
   * @param array<Normalizer> $normalizers - Custom normalizers definitions to register with the index.
   * @param array<Similarity> $similarities - Custom similarity definitions to register with the index.
   * @param array<string, mixed> $options - Additional index settings options (@example number_of_shards, codec, store_type, number_of_replicas, auto_expand_replicas, refresh_interval, max_result_window, max_docvalue_fields_search, max_script_fields, max_ngram_diff, max_terms_count, max_regex_length, gc_deletes, priority, mapping_total_fields_limit, mapping_depth_limit, mapping_nested_fields_limit, mapping_nested_objects_limit, mapping_field_name_length_limit).
   * @return self
   */
  public static function create(string $indexName, ?MappingFactory $mappingFactory = NULL, array $analyzers = [], array $tokenizers = [],
                                array  $filters = [], array $charFilters = [], array $normalizers = [], array $similarities = [],
                                array  $options = []): self
  {
    $instance = new self();
    $instance->_setIndexName($indexName);
    $instance->_setMappings($mappingFactory);

    if (isset($options['number_of_shards'])) {
      $instance->_setNumberOfShards($options['number_of_shards']);
    }

    if (isset($options['codec'])) {
      $instance->_setCodec($options['codec']);
    }

    if (isset($options['store_type'])) {
      $instance->_setStoreType($options['store_type']);
    }

    foreach ($similarities as $name => $config) {
      $instance->_addSimilarity($name, $config);
    }

    foreach ($analyzers as $name => $config) {
      $instance->_addAnalyzer($name, $config);
    }

    foreach ($tokenizers as $name => $config) {
      $instance->_addTokenizer($name, $config);
    }

    foreach ($filters as $name => $config) {
      $instance->_addFilter($name, $config);
    }

    foreach ($charFilters as $name => $config) {
      $instance->_addCharFilter($name, $config);
    }

    foreach ($normalizers as $name => $config) {
      $instance->_addNormalizer($name, $config);
    }

    if (isset($options['number_of_replicas'])) {
      $instance->_setNumberOfReplicas($options['number_of_replicas']);
    }

    if (isset($options['auto_expand_replicas'])) {
      $instance->_setAutoExpandReplicas($options['auto_expand_replicas']);
    }

    if (isset($options['refresh_interval'])) {
      $instance->_setRefreshInterval($options['refresh_interval']);
    }

    if (isset($options['max_result_window'])) {
      $instance->_setMaxResultWindow($options['max_result_window']);
    }

    if (isset($options['max_docvalue_fields_search'])) {
      $instance->_setMaxDocvalueFieldsSearch($options['max_docvalue_fields_search']);
    }

    if (isset($options['max_script_fields'])) {
      $instance->_setMaxScriptFields($options['max_script_fields']);
    }

    if (isset($options['max_ngram_diff'])) {
      $instance->_setMaxNgramDiff($options['max_ngram_diff']);
    }

    if (isset($options['max_terms_count'])) {
      $instance->_setMaxTermsCount($options['max_terms_count']);
    }

    if (isset($options['max_regex_length'])) {
      $instance->_setMaxRegexLength($options['max_regex_length']);
    }

    if (isset($options['gc_deletes'])) {
      $instance->_setGcDeletes($options['gc_deletes']);
    }

    if (isset($options['priority'])) {
      $instance->_setPriority($options['priority']);
    }

    if (isset($options['mapping_total_fields_limit'])) {
      $instance->_setMappingTotalFieldsLimit($options['mapping_total_fields_limit']);
    }

    if (isset($options['mapping_depth_limit'])) {
      $instance->_setMappingDepthLimit($options['mapping_depth_limit']);
    }

    if (isset($options['mapping_nested_fields_limit'])) {
      $instance->_setMappingNestedFieldsLimit($options['mapping_nested_fields_limit']);
    }

    if (isset($options['mapping_nested_objects_limit'])) {
      $instance->_setMappingNestedObjectsLimit($options['mapping_nested_objects_limit']);
    }

    if (isset($options['mapping_field_name_length_limit'])) {
      $instance->_setMappingFieldNameLengthLimit($options['mapping_field_name_length_limit']);
    }

    return $instance;
  }

  /**
   * [STATIC]
   * Sets the index name for the index factory.
   * @param string $indexName
   * @return void
   */
  private function _setIndexName(string $indexName): void
  {
    $this->indexName = $indexName;
  }

  /**
   * [STATIC]
   * Sets the mappings for the index factory.
   * @param array $mappings
   * @return void
   */
  private function _setMappings(?MappingFactory $mappingFactory): void
  {
    $this->mappings = $mappingFactory ? $mappingFactory->toArray() : [];
  }

  public function toArray(): array
  {
    // todo: format the index to array
  }

  /**
   * [STATIC PROPERTY]
   * Number of primary shards. Max 1024.
   * @param int $numberOfShards
   * @return void
   */
  private function _setNumberOfShards(int $numberOfShards): void
  {
    if ($numberOfShards <= 0) $numberOfShards = 1;
    if ($numberOfShards > 1024) $numberOfShards = 1024;

    $this->numberOfShards = $numberOfShards;
  }

  /**
   * [STATIC]
   * Compression type for stored fields.
   * 'default' = LZ4 (fast). 'best_compression' = ZSTD (~28% smaller, slower reads).
   * @param string $codec
   * @return void
   */
  private function _setCodec(string $codec): void
  {
    if (!in_array($codec, ['default', 'best_compression'], TRUE)) {
      $codec = 'default';
    }

    $this->codec = $codec;
  }

  /**
   * [STATIC]
   * Filesystem implementation used for shard storage.
   * 'hybridfs' (default) picks the optimal type per file automatically.
   * Other options: 'niofs', 'mmapfs', 'fs'.
   * @param string $storeType
   * @return void
   */
  private function _setStoreType(string $storeType): void
  {
    if (!in_array($storeType, ['hybridfs', 'niofs', 'mmapfs', 'fs'], TRUE)) {
      $storeType = 'hybridfs';
    }

    $this->storeType = $storeType;
  }

  /**
   * [STATIC]
   * Registers a custom named similarity configuration assignable to fields in mappings.
   * Supported types: BM25, boolean, DFR, IB, LMDirichlet, LMJelinekMercer.
   * @param string $name
   * @param Similarity $similarity
   * @return void
   * @example $this->_addSimilarity('my_bm25', ['type' => 'BM25', 'k1' => 1.5, 'b' => 0.75])
   */
  private function _addSimilarity(string $name, Similarity $similarity): void
  {
    $config = $similarity->toArray();
    if (empty($name) || !isset($config['type']) || !in_array($config['type'],
        ['BM25', 'boolean', 'DFR', 'IB', 'LMDirichlet', 'LMJelinekMercer'], TRUE)) {
      return;
    }

    $this->similarity[$name] = $config;
  }

  /**
   * [STATIC]
   * Registers a custom named analyzer combining a tokenizer with optional
   * char_filters and token filters.
   * @param string $name
   * @param Analyzer $analyzer
   * @return void
   * @example ['type' => 'custom', 'tokenizer' => 'standard', 'filter' => ['lowercase']]
   */
  private function _addAnalyzer(string $name, Analyzer $analyzer): void
  {
    $config = $analyzer->toArray();
    if (empty($name) || !isset($config['type'])) return;

    $this->analysis['analyzer'][$name] = $config;
  }

  /**
   * [STATIC]
   * Registers a custom named tokenizer definition.
   * @param string $name
   * @param Tokenizer $tokenizer
   * @return void
   * @example ['type' => 'ngram', 'min_gram' => 2, 'max_gram' => 3]
   */
  private function _addTokenizer(string $name, Tokenizer $tokenizer): void
  {
    $config = $tokenizer->toArray();
    if (empty($name) || !isset($config['type'])) return;

    $this->analysis['tokenizer'][$name] = $config;
  }

  /**
   * [STATIC]
   * Registers a custom named token filter definition applied after tokenization.
   * @param string $name
   * @param Filter $filter
   * @return void
   * @example ['type' => 'stop', 'stopwords' => ['the', 'a']]
   */
  private function _addFilter(string $name, Filter $filter): void
  {
    $config = $filter->toArray();
    if (empty($name) || !isset($config['type'])) return;

    $this->analysis['filter'][$name] = $config;
  }

  /**
   * [STATIC]
   * Registers a custom named character filter definition applied before tokenization.
   * @param string $name
   * @param CharFilter $charFilter
   * @return void
   * @example ['type' => 'html_strip'] or ['type' => 'mapping', 'mappings' => ['ph => f']]
   */
  private function _addCharFilter(string $name, CharFilter $charFilter): void
  {
    $config = $charFilter->toArray();
    if (empty($name) || !isset($config['type'])) return;

    $this->analysis['char_filter'][$name] = $config;
  }

  /**
   * [STATIC]
   * Registers a custom named normalizer for keyword fields (no tokenizer — filters only).
   * @param string $name
   * @param Normalizer $normalizer
   * @return void
   * @example ['type' => 'custom', 'filter' => ['lowercase', 'asciifolding']]
   */
  private function _addNormalizer(string $name, Normalizer $normalizer): void
  {
    $config = $normalizer->toArray();
    if (empty($name) || !isset($config['type'])) return;

    $this->analysis['normalizer'][$name] = $config;
  }

  /**
   * [DYNAMIC PROPERTY]
   * Number of replica shards per primary.
   * More replicas = better read throughput and fault tolerance.
   * @param int $numberOfReplicas
   * @return void
   */
  private function _setNumberOfReplicas(int $numberOfReplicas): void
  {
    if ($numberOfReplicas < 1) $numberOfReplicas = 1;

    $this->numberOfReplicas = $numberOfReplicas;
  }

  /**
   * [DYNAMIC]
   * Automatically scale replicas based on cluster node count.
   * Format: '0-5', '0-all', or false to disable.
   * @param string|false $autoExpandReplicas
   * @return void
   */
  private function _setAutoExpandReplicas(string|false $autoExpandReplicas): void
  {
    if ($autoExpandReplicas !== false) {
      if (!preg_match('/^\d+-(all|\d+)$/', $autoExpandReplicas)) {
        $autoExpandReplicas = false;
      }
    }

    $this->autoExpandReplicas = $autoExpandReplicas;
  }

  /**
   * [DYNAMIC]
   * How often the index is refreshed to make newly indexed documents visible to search.
   * Use '-1' to disable refresh entirely.
   * Format: time value string @param string $refreshInterval
   * @return void
   * @example '1s', '500ms', or '-1'.
   */
  private function _setRefreshInterval(string $refreshInterval): void
  {
    if ($refreshInterval !== '-1' && !preg_match('/^\d+(ms|s|m|h|d)$/', $refreshInterval)) {
      $refreshInterval = '1s';
    }

    $this->refreshInterval = $refreshInterval;
  }

  /**
   * [DYNAMIC]
   * Maximum value for search requests.
   * Raising this is memory-expensive.
   * @param int $maxResultWindow
   * @return void
   */
  private function _setMaxResultWindow(int $maxResultWindow): void
  {
    if ($maxResultWindow < 1) $maxResultWindow = 10000;

    $this->maxResultWindow = $maxResultWindow;
  }

  /**
   * [DYNAMIC]
   * Maximum number of docvalue_fields that can be requested per search query.
   * @param int $maxDocvalueFieldsSearch
   * @return void
   */
  private function _setMaxDocvalueFieldsSearch(int $maxDocvalueFieldsSearch): void
  {
    if ($maxDocvalueFieldsSearch < 1) $maxDocvalueFieldsSearch = 100;

    $this->maxDocvalueFieldsSearch = $maxDocvalueFieldsSearch;
  }

  /**
   * [DYNAMIC]
   * Maximum number of script_fields allowed per search request.
   * @param int $maxScriptFields
   * @return void
   */
  private function _setMaxScriptFields(int $maxScriptFields): void
  {
    if ($maxScriptFields < 1) $maxScriptFields = 32;

    $this->maxScriptFields = $maxScriptFields;
  }

  /**
   * [DYNAMIC]
   * Maximum allowed difference between min_gram and max_gram for the NGram tokenizer.
   * @param int $maxNgramDiff
   * @return void
   */
  private function _setMaxNgramDiff(int $maxNgramDiff): void
  {
    if ($maxNgramDiff < 0) $maxNgramDiff = 1;

    $this->maxNgramDiff = $maxNgramDiff;
  }

  /**
   * [DYNAMIC]
   * Maximum number of terms that can be passed in a single Terms query.
   * @param int $maxTermsCount
   * @return void
   */
  private function _setMaxTermsCount(int $maxTermsCount): void
  {
    if ($maxTermsCount < 1) $maxTermsCount = 65536;

    $this->maxTermsCount = $maxTermsCount;
  }

  /**
   * [DYNAMIC]
   * Maximum character length of a regex pattern used in Regexp or Wildcard queries.
   * @param int $maxRegexLength
   * @return void
   */
  private function _setMaxRegexLength(int $maxRegexLength): void
  {
    if ($maxRegexLength < 1) $maxRegexLength = 1000;

    $this->maxRegexLength = $maxRegexLength;
  }

  /**
   * [DYNAMIC]
   * How long a deleted document's version number is retained for optimistic concurrency control.
   * After this window the version is discarded and cannot be referenced.
   * Format: time value string @param string $gcDeletes
   * @return void
   * @example '60s', '5m'.
   */
  private function _setGcDeletes(string $gcDeletes): void
  {
    if (!preg_match('/^\d+(ms|s|m|h|d)$/', $gcDeletes)) {
      $gcDeletes = '60s';
    }

    $this->gcDeletes = $gcDeletes;
  }

  /**
   * [DYNAMIC]
   * Recovery priority after a cluster restart.
   * Higher integer = this index is recovered before lower-priority indices.
   * @param int $priority
   * @return void
   */
  private function _setPriority(int $priority): void
  {
    if ($priority < 0) $priority = 1;

    $this->priority = $priority;
  }

  /**
   * [DYNAMIC]
   * Maximum total number of fields in the index mapping.
   * Guards against mapping explosion from dynamic field creation.
   * @param int $mappingTotalFieldsLimit
   * @return void
   */
  private function _setMappingTotalFieldsLimit(int $mappingTotalFieldsLimit): void
  {
    if ($mappingTotalFieldsLimit < 1) $mappingTotalFieldsLimit = 1000;

    $this->mappingTotalFieldsLimit = $mappingTotalFieldsLimit;
  }

  /**
   * [DYNAMIC]
   * Maximum nesting depth for object fields.
   * Each level of object/nested nesting counts toward this limit.
   * @param int $mappingDepthLimit
   * @return void
   */
  private function _setMappingDepthLimit(int $mappingDepthLimit): void
  {
    if ($mappingDepthLimit < 1) $mappingDepthLimit = 20;

    $this->mappingDepthLimit = $mappingDepthLimit;
  }

  /**
   * [DYNAMIC]
   * Maximum number of distinct nested field type definitions across the entire mapping.
   * @param int $mappingNestedFieldsLimit
   * @return void
   */
  private function _setMappingNestedFieldsLimit(int $mappingNestedFieldsLimit): void
  {
    if ($mappingNestedFieldsLimit < 1) $mappingNestedFieldsLimit = 50;

    $this->mappingNestedFieldsLimit = $mappingNestedFieldsLimit;
  }

  /**
   * [DYNAMIC]
   * Maximum number of nested JSON objects a single document can contain in total
   * across all nested fields.
   * @param int $mappingNestedObjectsLimit
   * @return void
   */
  private function _setMappingNestedObjectsLimit(int $mappingNestedObjectsLimit): void
  {
    if ($mappingNestedObjectsLimit < 1) $mappingNestedObjectsLimit = 10000;

    $this->mappingNestedObjectsLimit = $mappingNestedObjectsLimit;
  }

  /**
   * [DYNAMIC]
   * Maximum character length allowed for field names.
   * null = no enforced limit.
   * @param int|null $mappingFieldNameLengthLimit
   * @return void
   */
  private function _setMappingFieldNameLengthLimit(?int $mappingFieldNameLengthLimit): void
  {
    if ($mappingFieldNameLengthLimit !== null && $mappingFieldNameLengthLimit < 1) {
      $mappingFieldNameLengthLimit = null;
    }

    $this->mappingFieldNameLengthLimit = $mappingFieldNameLengthLimit;
  }

  /**
   * Loads an existing index factory instance by index name.
   * @param string $indexName
   * @return self|null
   */
  public static function load(string $indexName): ?self
  {
    // todo: return instantiated index factory from the config or null if does not exists
  }

  /**
   * Deletes the index configuration and removes the index from Elasticsearch.
   * @param string $indexName
   * @return bool
   */
  public static function delete(string $indexName): bool
  {
    // todo: implement config delete
    // todo: implement index deletion in ES
  }

  /**
   * Updates dynamic settings of the index.
   * @param array<string, mixed> $options - Additional index settings options (@example number_of_shards, codec, store_type, number_of_replicas, auto_expand_replicas, refresh_interval, max_result_window, max_docvalue_fields_search, max_script_fields, max_ngram_diff, max_terms_count, max_regex_length, gc_deletes, priority, mapping_total_fields_limit, mapping_depth_limit, mapping_nested_fields_limit, mapping_nested_objects_limit, mapping_field_name_length_limit).
   * @return $this
   */
  public function updateDynamicSettings(array $options): self
  {
    if (isset($options['number_of_replicas'])) {
      $this->_setNumberOfReplicas($options['number_of_replicas']);
    }

    if (isset($options['auto_expand_replicas'])) {
      $this->_setAutoExpandReplicas($options['auto_expand_replicas']);
    }

    if (isset($options['refresh_interval'])) {
      $this->_setRefreshInterval($options['refresh_interval']);
    }

    if (isset($options['max_result_window'])) {
      $this->_setMaxResultWindow($options['max_result_window']);
    }

    if (isset($options['max_docvalue_fields_search'])) {
      $this->_setMaxDocvalueFieldsSearch($options['max_docvalue_fields_search']);
    }

    if (isset($options['max_script_fields'])) {
      $this->_setMaxScriptFields($options['max_script_fields']);
    }

    if (isset($options['max_ngram_diff'])) {
      $this->_setMaxNgramDiff($options['max_ngram_diff']);
    }

    if (isset($options['max_terms_count'])) {
      $this->_setMaxTermsCount($options['max_terms_count']);
    }

    if (isset($options['max_regex_length'])) {
      $this->_setMaxRegexLength($options['max_regex_length']);
    }

    if (isset($options['gc_deletes'])) {
      $this->_setGcDeletes($options['gc_deletes']);
    }

    if (isset($options['priority'])) {
      $this->_setPriority($options['priority']);
    }

    if (isset($options['mapping_total_fields_limit'])) {
      $this->_setMappingTotalFieldsLimit($options['mapping_total_fields_limit']);
    }

    if (isset($options['mapping_depth_limit'])) {
      $this->_setMappingDepthLimit($options['mapping_depth_limit']);
    }

    if (isset($options['mapping_nested_fields_limit'])) {
      $this->_setMappingNestedFieldsLimit($options['mapping_nested_fields_limit']);
    }

    if (isset($options['mapping_nested_objects_limit'])) {
      $this->_setMappingNestedObjectsLimit($options['mapping_nested_objects_limit']);
    }

    if (isset($options['mapping_field_name_length_limit'])) {
      $this->_setMappingFieldNameLengthLimit($options['mapping_field_name_length_limit']);
    }

    return $this;
  }

  /**
   * Saves the index configuration and creates the index in Elasticsearch.
   * @return void
   */
  public function save()
  {
    // todo: implement config save
    // todo: implement index creation in ES
  }
}
