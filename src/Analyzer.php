<?php

namespace Drupal\eticsearch;

use Drupal;
use Drupal\eticsearch\Factory\ConfigurationFactory;

class Analyzer
{
  private string $name;
  private string $type = 'custom';
  private array $filters = [];
  private array $charFilters = [];
  private string $tokenizer = 'standard';

  public function __construct($name, $type, $filters, $charFilters, $tokenizer)
  {
    $this->name = $name;
    $this->type = $type;
    $this->filters = $filters;
    $this->charFilters = $charFilters;
    $this->tokenizer = $tokenizer;
  }

  public static function create(string $name, string $type = 'custom', array $filters = [],
                                array  $charFilters = [], string $tokenizer = 'standard'): self
  {
    return new self(
      $name,
      $type,
      $filters,
      $charFilters,
      $tokenizer
    );
  }

  public static function remove(string $name): bool
  {
    /** @var ConfigurationFactory $configFactory */
    $configFactory = Drupal::service('eticsearch.configuration.factory');

    $inUse = $configFactory->getSingle('eticsearch:indices') ?? [];
    foreach ($inUse as $index) {
      $analyzers = $index['eticsearch_index:analyzers'] ?? [];
      if (isset($analyzers[$name])) {
        // if the analyzer is in use, we cannot remove it
        return FALSE;
      }

      // we do the same for the fields, as they also depend on the analyzers
      $fields = $index['eticsearch_index:fields'] ?? [];
      foreach ($fields as $field) {
        if (isset($field['analyzer']) && $field['analyzer'] === $name) {
          return FALSE;
        }

        if (isset($field['search_analyzer']) && $field['search_analyzer'] === $name) {
          return FALSE;
        }
      }
    }

    $existingAnalyzers = $configFactory->getSingle('eticsearch:analyzers') ?? [];
    if (isset($existingAnalyzers[$name])) {
      unset($existingAnalyzers[$name]);
      $configFactory->setValue('eticsearch:analyzers', $existingAnalyzers);

      return TRUE;
    }

    return FALSE;
  }

  public static function get(string $name): ?array
  {
    /** @var ConfigurationFactory $configFactory */
    $configFactory = Drupal::service('eticsearch.configuration.factory');
    $analyzers = $configFactory->getSingle('eticsearch:analyzers') ?? [];

    return $analyzers[$name] ? [
      $name => $analyzers[$name],
    ] : NULL;
  }

  public function save(): void
  {
    /** @var ConfigurationFactory $configFactory */
    $configFactory = Drupal::service('eticsearch.configuration.factory');

    $existingAnalyzers = $configFactory->getSingle('eticsearch:analyzers') ?? [];
    $existingAnalyzers[$this->name] = [
      'type' => $this->type,
    ];

    if (!empty($this->filters)) {
      $existingAnalyzers[$this->name]['filter'] = $this->filters;
    }

    if (!empty($this->charFilters)) {
      $existingAnalyzers[$this->name]['char_filter'] = $this->charFilters;
    }

    if (!empty($this->tokenizer)) {
      $existingAnalyzers[$this->name]['tokenizer'] = $this->tokenizer;
    }


    $configFactory->setValue('eticsearch:analyzers', $existingAnalyzers);
  }
}
