<?php

namespace Drupal\eticsearch;

use Drupal;
use Drupal\eticsearch\Factory\ConfigurationFactory;

class Filter
{
  private string $name;
  private string $type = 'edge_ngram';
  private ?bool $ignoreCase = NULL;
  private array $stopWords = [];
  private ?int $minGram = NULL;
  private ?int $maxGram = NULL;
  private ?string $locale = NULL;

  public function __construct(string $name, string $type = 'edge_ngram', ?bool $ignoreCase = NULL, array $stopWords = [], ?int $minGram = NULL, ?int $maxGram = NULL, ?string $locale = NULL)
  {
    $this->name = $name;
    $this->type = $type;
    $this->ignoreCase = $ignoreCase;
    $this->stopWords = $stopWords;
    $this->minGram = $minGram;
    $this->maxGram = $maxGram;
    $this->locale = $locale;
  }

  public static function create(
    string  $name,
    string  $type = 'edge_ngram',
    ?bool   $ignoreCase = NULL,
    array   $stopWords = [],
    ?int    $minGram = NULL,
    ?int    $maxGram = NULL,
    ?string $locale = NULL
  ): self
  {
    return new self(
      $name,
      $type,
      $ignoreCase,
      $stopWords,
      $minGram,
      $maxGram,
      $locale
    );
  }

  public static function remove(string $name): bool
  {
    /** @var ConfigurationFactory $configFactory */
    $configFactory = Drupal::service('eticsearch.configuration.factory');

    $inUse = $configFactory->getSingle('eticsearch:indices') ?? [];
    foreach ($inUse as $index) {
      $filters = $index['eticsearch_index:filters'] ?? [];
      if (isset($filters[$name])) {
        // if the filter is in use, we cannot remove it
        return FALSE;
      }
    }

    $existingFilters = $configFactory->getSingle('eticsearch:filters') ?? [];
    if (isset($existingFilters[$name])) {
      unset($existingFilters[$name]);
      $configFactory->setValue('eticsearch:filters', $existingFilters);

      return TRUE;
    }

    return FALSE;
  }

  public static function get(string $name): ?array
  {
    /** @var ConfigurationFactory $configFactory */
    $configFactory = Drupal::service('eticsearch.configuration.factory');
    $filters = $configFactory->getSingle('eticsearch:filters') ?? [];

    return $filters[$name] ? [
      $name => $filters[$name],
    ] : NULL;
  }

  public function save(): void
  {
    /** @var ConfigurationFactory $configFactory */
    $configFactory = Drupal::service('eticsearch.configuration.factory');

    $existingFilters = $configFactory->getSingle('eticsearch:filters') ?? [];
    $existingFilters[$this->name] = [
      'type' => $this->type,
    ];

    if ($this->ignoreCase !== NULL) {
      $existingFilters[$this->name]['ignore_case'] = $this->ignoreCase;
    }

    if (!empty($this->stopWords)) {
      $existingFilters[$this->name]['stop_words'] = $this->stopWords;
    }

    if ($this->minGram !== NULL) {
      $existingFilters[$this->name]['min_gram'] = $this->minGram;
    }

    if ($this->maxGram !== NULL) {
      $existingFilters[$this->name]['max_gram'] = $this->maxGram;
    }

    if ($this->locale !== NULL) {
      $existingFilters[$this->name]['locale'] = $this->locale;
    }

    $configFactory->setValue('eticsearch:filters', $existingFilters);
  }
}
