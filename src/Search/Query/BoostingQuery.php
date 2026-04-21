<?php

namespace Drupal\eticsearch\Search\Query;

class BoostingQuery
{
  private array $positive;
  private array $negative;
  private float $negativeBoost;

  private function __construct(array $positive, array $negative, float $negativeBoost)
  {
    $this->positive = $positive;
    $this->negative = $negative;
    $this->negativeBoost = $negativeBoost;
  }

  public static function create(array|BoolQuery $positive, array|BoolQuery $negative, float $negativeBoost): self
  {
    return new self(
      $positive instanceof BoolQuery ? $positive->toArray() : $positive,
      $negative instanceof BoolQuery ? $negative->toArray() : $negative,
      $negativeBoost
    );
  }

  public static function fromArray(array $data): self
  {
    $b = $data['boosting'] ?? $data;
    return new self($b['positive'], $b['negative'], (float) $b['negative_boost']);
  }

  public function toArray(): array
  {
    return ['boosting' => [
      'positive' => $this->positive,
      'negative' => $this->negative,
      'negative_boost' => $this->negativeBoost,
    ]];
  }
}
