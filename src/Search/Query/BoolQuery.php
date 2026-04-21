<?php

namespace Drupal\eticsearch\Search\Query;

class BoolQuery
{
  private array $must = [];
  private array $should = [];
  private array $filter = [];
  private array $mustNot = [];
  private int|string|null $minimumShouldMatch = null;
  private ?float $boost = null;

  public static function create(): self
  {
    return new self();
  }

  public static function fromArray(array $data): self
  {
    $bool = $data['bool'] ?? $data;
    $instance = new self();

    if (isset($bool['must'])) {
      $instance->must = array_is_list($bool['must']) ? $bool['must'] : [$bool['must']];
    }
    if (isset($bool['should'])) {
      $instance->should = $bool['should'];
    }
    if (isset($bool['filter'])) {
      $instance->filter = array_is_list($bool['filter']) ? $bool['filter'] : [$bool['filter']];
    }
    if (isset($bool['must_not'])) {
      $instance->mustNot = array_is_list($bool['must_not']) ? $bool['must_not'] : [$bool['must_not']];
    }
    if (isset($bool['minimum_should_match'])) {
      $instance->minimumShouldMatch = $bool['minimum_should_match'];
    }
    if (isset($bool['boost'])) {
      $instance->boost = (float) $bool['boost'];
    }

    return $instance;
  }

  public function addMust(array|BoolQuery $clause): self
  {
    $this->must[] = $clause instanceof BoolQuery ? $clause->toArray() : $clause;
    return $this;
  }

  public function addShould(array|BoolQuery $clause): self
  {
    $this->should[] = $clause instanceof BoolQuery ? $clause->toArray() : $clause;
    return $this;
  }

  public function addFilter(array|BoolQuery $clause): self
  {
    $this->filter[] = $clause instanceof BoolQuery ? $clause->toArray() : $clause;
    return $this;
  }

  public function addMustNot(array|BoolQuery $clause): self
  {
    $this->mustNot[] = $clause instanceof BoolQuery ? $clause->toArray() : $clause;
    return $this;
  }

  public function setMinimumShouldMatch(int|string $value): self
  {
    $this->minimumShouldMatch = $value;
    return $this;
  }

  public function setBoost(float $boost): self
  {
    $this->boost = $boost;
    return $this;
  }

  public function toArray(): array
  {
    $bool = [];

    if (!empty($this->must)) {
      $bool['must'] = count($this->must) === 1 ? $this->must[0] : $this->must;
    }

    if (!empty($this->should)) {
      $bool['should'] = $this->should;
    }

    if (!empty($this->filter)) {
      $bool['filter'] = $this->filter;
    }

    if (!empty($this->mustNot)) {
      $bool['must_not'] = count($this->mustNot) === 1 ? $this->mustNot[0] : $this->mustNot;
    }

    if ($this->minimumShouldMatch !== null) {
      $bool['minimum_should_match'] = $this->minimumShouldMatch;
    }

    if ($this->boost !== null) {
      $bool['boost'] = $this->boost;
    }

    return ['bool' => $bool];
  }
}
