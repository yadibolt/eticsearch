<?php

namespace Drupal\eticsearch\Search\Factory;

use Drupal;
use Drupal\eticsearch\Factory\ConfigFactory;

class SearchFactory
{
  private string $indexName;
  private string $searchName;
  private ?array $query = null;
  private int $size;
  private int $from;
  private array|bool|null $source = null;
  private ?float $minScore = null;
  private string|bool|null $timeout = null;
  private int|bool|null $trackTotalHits = null;
  private ?array $collapse = null;
  private array $suggest = [];
  private ?array $highlight = null;
  private ConfigFactory $configFactory;

  private function __construct(string $indexName, string $searchName, int $size, int $from)
  {
    $this->configFactory = Drupal::service('eticsearch.factory.config');

    $this->indexName = $indexName;
    $this->searchName = $searchName;
    $this->size = $size;
    $this->from = $from;
  }

  public static function load(string $indexName, string $searchName): ?self
  {
    $configService = Drupal::service('eticsearch.factory.config');
    $search = $configService->getSearches()[$indexName][$searchName] ?? null;
    if ($search !== null) {
      return self::fromArray(self::_decodeKeys($search), $indexName, $searchName);
    }

    return null;
  }

  public function save(): bool
  {
    $searches = $this->configFactory->getSearches();
    $searches[$this->indexName][$this->searchName] = self::_encodeKeys($this->toArray());
    $this->configFactory->set('etic:searches', $searches);
    return true;
  }

  private static function _encodeKeys(array $data): array
  {
    $result = [];
    foreach ($data as $key => $value) {
      $result[str_replace('.', ':', $key)] = is_array($value) ? self::_encodeKeys($value) : $value;
    }
    return $result;
  }

  private static function _decodeKeys(array $data): array
  {
    $result = [];
    foreach ($data as $key => $value) {
      $result[str_replace(':', '.', $key)] = is_array($value) ? self::_decodeKeys($value) : $value;
    }
    return $result;
  }

  public static function create(string $indexName, string $searchName, int $size = 10, int $from = 0): self
  {
    return new self($indexName, $searchName, $size, $from);
  }

  public function setQuery(mixed $query): self
  {
    $this->query = is_array($query) ? $query : $query->toArray();
    return $this;
  }

  public function setSource(array|bool $source): self
  {
    $this->source = $source;
    return $this;
  }

  public function setSize(int $size): self
  {
    $this->size = $size;
    return $this;
  }

  public function setFrom(int $from): self
  {
    $this->from = $from;
    return $this;
  }

  public function setMinScore(float $minScore): self
  {
    $this->minScore = $minScore;
    return $this;
  }

  public function setTimeout(string $timeout): self
  {
    $this->timeout = $timeout;
    return $this;
  }

  public function setTrackTotalHits(int|bool $value): self
  {
    $this->trackTotalHits = $value;
    return $this;
  }

  public function setCollapse(string $field, array $innerHits = []): self
  {
    $collapse = ['field' => $field];
    if (!empty($innerHits)) {
      $collapse['inner_hits'] = $innerHits;
    }

    $this->collapse = $collapse;
    return $this;
  }

  public function addCompletionSuggest(
    string $name,
    string $field,
    int $size = 5,
    array $options = []
  ): self {
    $completion = array_merge(['field' => $field, 'size' => $size], $options);

    $this->suggest[$name] = ['prefix' => '$e%user_input%e$', 'completion' => $completion];
    return $this;
  }

  public function setHighlight(
    array $fields,
    string $preTags = '<em>',
    string $postTags = '</em>',
    array $globalOptions = []
  ): self {
    $fieldMap = [];
    foreach ($fields as $key => $value) {
      if (is_int($key)) {
        $fieldMap[$value] = (object) [];
      } else {
        $fieldMap[$key] = $value;
      }
    }

    $this->highlight = array_merge(
      ['pre_tags' => [$preTags], 'post_tags' => [$postTags], 'fields' => $fieldMap],
      $globalOptions
    );
    return $this;
  }

  public static function fromArray(array $data, string $indexName, string $searchName): self
  {
    $instance = new self($indexName, $searchName, (int) ($data['size'] ?? 10), (int) ($data['from'] ?? 0));

    if (isset($data['query'])) $instance->query = $data['query'];
    if (isset($data['_source'])) $instance->source = $data['_source'];
    if (isset($data['min_score'])) $instance->minScore = (float) $data['min_score'];
    if (isset($data['timeout'])) $instance->timeout = $data['timeout'];
    if (isset($data['track_total_hits'])) $instance->trackTotalHits = $data['track_total_hits'];
    if (isset($data['collapse'])) $instance->collapse = $data['collapse'];
    if (isset($data['suggest'])) $instance->suggest = $data['suggest'];
    if (isset($data['highlight'])) $instance->highlight = $data['highlight'];

    return $instance;
  }

  public static function use(string $indexName, string $searchName, string $userQuery): array {
    $search = self::load($indexName, $searchName);
    if ($search === null) {
      throw new \InvalidArgumentException("Search '$searchName' not found for index '$indexName'.");
    }

    $searchBody = $search->toArray();
    array_walk_recursive($searchBody, function (&$value) use ($userQuery) {
      if (is_string($value) && str_contains($value, '$e%user_input%e$')) {
        $value = str_replace('$e%user_input%e$', $userQuery, $value);
      }
    });

    return $searchBody;
  }

  public function toArray(): array
  {
    $body = ['size' => $this->size, 'from' => $this->from];

    if ($this->query !== null) {
      $body['query'] = $this->query;
    }

    if ($this->source !== null) {
      $body['_source'] = $this->source;
    }

    if ($this->minScore !== null) {
      $body['min_score'] = $this->minScore;
    }

    if ($this->timeout !== null) {
      $body['timeout'] = $this->timeout;
    }

    if ($this->trackTotalHits !== null) {
      $body['track_total_hits'] = $this->trackTotalHits;
    }

    if ($this->collapse !== null) {
      $body['collapse'] = $this->collapse;
    }

    if (!empty($this->suggest)) {
      $body['suggest'] = $this->suggest;
    }

    if ($this->highlight !== null) {
      $body['highlight'] = $this->highlight;
    }

    return $body;
  }
}
