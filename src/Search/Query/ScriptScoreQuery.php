<?php

namespace Drupal\eticsearch\Search\Query;

class ScriptScoreQuery
{
  private array $query;
  private string $source;
  private array $params;
  private ?float $minScore;

  private function __construct(array $query, string $source, array $params, ?float $minScore)
  {
    $this->query = $query;
    $this->source = $source;
    $this->params = $params;
    $this->minScore = $minScore;
  }

  public static function create(
    array|BoolQuery $query,
    string $source,
    array $params = [],
    ?float $minScore = null
  ): self {
    return new self(
      $query instanceof BoolQuery ? $query->toArray() : $query,
      $source,
      $params,
      $minScore
    );
  }

  public static function fromArray(array $data): self
  {
    $ss = $data['script_score'] ?? $data;
    return new self(
      $ss['query'],
      $ss['script']['source'],
      $ss['script']['params'] ?? [],
      isset($ss['min_score']) ? (float) $ss['min_score'] : null,
    );
  }

  public function toArray(): array
  {
    $script = ['source' => $this->source];
    if (!empty($this->params)) {
      $script['params'] = $this->params;
    }

    $body = [
      'query' => $this->query,
      'script' => $script,
    ];

    if ($this->minScore !== null) {
      $body['min_score'] = $this->minScore;
    }

    return ['script_score' => $body];
  }
}
