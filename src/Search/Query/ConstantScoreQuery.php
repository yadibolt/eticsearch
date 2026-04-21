<?php

namespace Drupal\eticsearch\Search\Query;

class ConstantScoreQuery
{
  private array $filter;
  private float $boost;

  private function __construct(array $filter, float $boost)
  {
    $this->filter = $filter;
    $this->boost = $boost;
  }

  public static function create(array|BoolQuery $filter, float $boost = 1.0): self
  {
    return new self(
      $filter instanceof BoolQuery ? $filter->toArray() : $filter,
      $boost
    );
  }

  public static function fromArray(array $data): self
  {
    $cs = $data['constant_score'] ?? $data;
    return new self($cs['filter'], (float) ($cs['boost'] ?? 1.0));
  }

  public function toArray(): array
  {
    return ['constant_score' => [
      'filter' => $this->filter,
      'boost' => $this->boost,
    ]];
  }
}
