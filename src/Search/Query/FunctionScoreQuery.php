<?php

namespace Drupal\eticsearch\Search\Query;

use Drupal\eticsearch\Search\Function\ScriptScoreFunction;
use InvalidArgumentException;

class FunctionScoreQuery
{
  private const array VALID_SCORE_MODES = ['multiply', 'sum', 'avg', 'first', 'max', 'min'];

  private array $query;
  private array $functions;
  private string $scoreMode;
  private string $boostMode;
  private ?float $maxBoost;
  private ?float $minScore;

  private function __construct(
    array $query,
    array $functions,
    string $scoreMode,
    string $boostMode,
    ?float $maxBoost,
    ?float $minScore
  ) {
    $this->query = $query;
    $this->functions = $functions;
    $this->scoreMode = $scoreMode;
    $this->boostMode = $boostMode;
    $this->maxBoost = $maxBoost;
    $this->minScore = $minScore;
  }

  /**
   * @param ScriptScoreFunction[] $functions
   */
  public static function create(
    BoolQuery|array $query,
    array $functions = [],
    string $scoreMode = 'sum',
    string $boostMode = 'multiply',
    ?float $maxBoost = null,
    ?float $minScore = null
  ): self {
    if (!in_array($scoreMode, self::VALID_SCORE_MODES, true)) {
      throw new InvalidArgumentException("Invalid score_mode '$scoreMode'.");
    }

    $queryArray = $query instanceof BoolQuery ? $query->toArray() : $query;

    return new self($queryArray, $functions, $scoreMode, $boostMode, $maxBoost, $minScore);
  }

  public static function fromArray(array $data): self
  {
    $fs = $data['function_score'] ?? $data;

    return new self(
      $fs['query'],
      $fs['functions'] ?? [],
      $fs['score_mode'] ?? 'multiply',
      $fs['boost_mode'] ?? 'multiply',
      isset($fs['max_boost']) ? (float) $fs['max_boost'] : null,
      isset($fs['min_score']) ? (float) $fs['min_score'] : null,
    );
  }

  public function toArray(): array
  {
    $body = ['query' => $this->query];

    if (!empty($this->functions)) {
      $body['functions'] = array_map(fn($f) => $f instanceof ScriptScoreFunction ? $f->toArray() : $f, $this->functions);
    }

    if ($this->scoreMode !== 'multiply') {
      $body['score_mode'] = $this->scoreMode;
    }

    if ($this->boostMode !== 'multiply') {
      $body['boost_mode'] = $this->boostMode;
    }

    if ($this->maxBoost !== null) {
      $body['max_boost'] = $this->maxBoost;
    }

    if ($this->minScore !== null) {
      $body['min_score'] = $this->minScore;
    }

    return ['function_score' => $body];
  }
}
