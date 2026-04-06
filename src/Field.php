<?php

namespace Drupal\eticsearch;

use Drupal;
use Drupal\eticsearch\Factory\ConfigurationFactory;

class Field
{
  private string $name;
  private string $indexName;
  private string $parentFieldName;
  private string $type = 'text';
  private ?string $analyzer = NULL;
  private ?string $searchAnalyzer = NULL;

  public function __construct(string $name, string $indexName, string $parentFieldName, string $type, ?string $analyzer = NULL, ?string $searchAnalyzer = NULL)
  {
    $this->name = $name;
    $this->indexName = $indexName;
    $this->parentFieldName = $parentFieldName;
    $this->type = $type;
    $this->analyzer = $analyzer;
    $this->searchAnalyzer = $searchAnalyzer;
  }

  public static function create(
    string $name,
    string $indexName,
    string $parentFieldName,
    string $type = 'text',
    ?string $analyzer = NULL,
    ?string $searchAnalyzer = NULL
  ): ?self
  {
    /** @var ConfigurationFactory $configFactory */
    $configFactory = Drupal::service('eticsearch.configuration.factory');
    $analyzers = $configFactory->getSingle('eticsearch:analyzers') ?? [];

    // if the analyzer or search analyzer does not exist
    // we can't create the field. as it depends on it
    if ($analyzer !== NULL) {
      if (!isset($analyzers[$analyzer])) {
        return NULL;
      }
    }

    if ($searchAnalyzer !== NULL) {
      if (!isset($analyzers[$searchAnalyzer])) {
        return NULL;
      }
    }

    // also if the parent field does not exist in the mapping
    // we cannot create the field for it
    $indices = $configFactory->getSingle('eticsearch:indices') ?? [];
    if (!isset($indices[$indexName])) return NULL;

    $indexMappings = $indices[$indexName]['eticsearch_index:mappings'] ?? [];
    $properties = $indexMappings['properties'] ?? [];
    if (!isset($properties[$parentFieldName])) return NULL;

    return new self(
      $name,
      $indexName,
      $parentFieldName,
      $type,
      $analyzer,
      $searchAnalyzer
    );
  }

  public static function remove(string $name, string $indexName): bool
  {
    /** @var ConfigurationFactory $configFactory */
    $configFactory = Drupal::service('eticsearch.configuration.factory');

    $inUse = $configFactory->getSingle('eticsearch:indices') ?? [];
    foreach ($inUse as $index) {
      $fields = $index['eticsearch_index:fields'] ?? [];
      if (isset($fields[$name])) {
        // if the field is in use, we cannot remove it
        return FALSE;
      }
    }

    $existingFields = $configFactory->getSingle('eticsearch:fields') ?? [];
    if (isset($existingFields[$indexName][$name])) {
      unset($existingFields[$indexName][$name]);
      $configFactory->setValue('eticsearch:fields', $existingFields);

      return TRUE;
    }

    return FALSE;
  }

  public function save(): void
  {
    /** @var ConfigurationFactory $configFactory */
    $configFactory = Drupal::service('eticsearch.configuration.factory');

    $existingFields = $configFactory->getSingle('eticsearch:fields') ?? [];
    $existingFields[$this->indexName][$this->name] = [
      'parent' => $this->parentFieldName,
      'type' => $this->type,
    ];

    if ($this->analyzer !== NULL) $existingFields[$this->indexName][$this->name]['analyzer'] = $this->analyzer;
    if ($this->searchAnalyzer !== NULL) $existingFields[$this->indexName][$this->name]['search_analyzer'] = $this->searchAnalyzer;

    $configFactory->setValue('eticsearch:fields', $existingFields);

    // also save the field to the index configuration, as it depends on it
    $indices = $configFactory->getSingle('eticsearch:indices') ?? [];
    if (isset($indices[$this->indexName])) {
      $indices[$this->indexName]['eticsearch_index:fields'][$this->name] = [
        'parent' => $this->parentFieldName,
        'type' => $this->type,
      ];

      if ($this->analyzer !== NULL) $indices[$this->indexName]['eticsearch_index:fields'][$this->name]['analyzer'] = $this->analyzer;
      if ($this->searchAnalyzer !== NULL) $indices[$this->indexName]['eticsearch_index:fields'][$this->name]['search_analyzer'] = $this->searchAnalyzer;

      $configFactory->setValue('eticsearch:indices', $indices);
    }
  }
}
