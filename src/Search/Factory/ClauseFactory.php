<?php

namespace Drupal\eticsearch\Search\Factory;

use InvalidArgumentException;

class ClauseFactory
{
  public static function match(
    string $field,
    float $boost = 1.0,
    int|string $fuzziness = 0,
    string $operator = 'or',
    ?string $analyzer = null,
    int $prefixLength = 0,
    string $zeroTermsQuery = 'none'
  ): array {
    $params = ['query' => '$e%user_input%e$'];
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($fuzziness !== 0) $params['fuzziness'] = $fuzziness;
    if ($operator !== 'or') $params['operator'] = $operator;
    if ($analyzer !== null) $params['analyzer'] = $analyzer;
    if ($prefixLength !== 0) $params['prefix_length'] = $prefixLength;
    if ($zeroTermsQuery !== 'none') $params['zero_terms_query'] = $zeroTermsQuery;

    return ['match' => [$field => $params]];
  }

  public static function matchPhrase(
    string $field,
    float $boost = 1.0,
    int $slop = 0,
    ?string $analyzer = null,
    string $zeroTermsQuery = 'none'
  ): array {
    $params = ['query' => '$e%user_input%e$'];
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($slop !== 0) $params['slop'] = $slop;
    if ($analyzer !== null) $params['analyzer'] = $analyzer;
    if ($zeroTermsQuery !== 'none') $params['zero_terms_query'] = $zeroTermsQuery;

    return ['match_phrase' => [$field => $params]];
  }

  public static function matchPhrasePrefix(
    string $field,
    float $boost = 1.0,
    int $slop = 0,
    int $maxExpansions = 50,
    ?string $analyzer = null
  ): array {
    $params = ['query' => '$e%user_input%e$'];
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($slop !== 0) $params['slop'] = $slop;
    if ($maxExpansions !== 50) $params['max_expansions'] = $maxExpansions;
    if ($analyzer !== null) $params['analyzer'] = $analyzer;

    return ['match_phrase_prefix' => [$field => $params]];
  }

  public static function matchBoolPrefix(
    string $field,
    float $boost = 1.0,
    ?string $analyzer = null,
    int|string $fuzziness = 0,
    string $operator = 'or'
  ): array {
    $params = ['query' => '$e%user_input%e$'];
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($analyzer !== null) $params['analyzer'] = $analyzer;
    if ($fuzziness !== 0) $params['fuzziness'] = $fuzziness;
    if ($operator !== 'or') $params['operator'] = $operator;

    return ['match_bool_prefix' => [$field => $params]];
  }

  public static function multiMatch(
    array $fields,
    string $type = 'best_fields',
    float $boost = 1.0,
    int|string $fuzziness = 0,
    string $operator = 'or',
    float $tieBreaker = 0.0
  ): array {
    $params = ['fields' => $fields, 'query' => '$e%user_input%e$'];
    if ($type !== 'best_fields') $params['type'] = $type;
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($fuzziness !== 0) $params['fuzziness'] = $fuzziness;
    if ($operator !== 'or') $params['operator'] = $operator;
    if ($tieBreaker !== 0.0) $params['tie_breaker'] = $tieBreaker;

    return ['multi_match' => $params];
  }

  public static function combinedFields(
    array $fields,
    float $boost = 1.0,
    string $operator = 'or'
  ): array {
    $params = ['fields' => $fields, 'query' => '$e%user_input%e$'];
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($operator !== 'or') $params['operator'] = $operator;

    return ['combined_fields' => $params];
  }

  public static function queryString(
    array $fields = [],
    float $boost = 1.0,
    string $defaultOperator = 'or',
    ?string $analyzer = null,
    int|string $fuzziness = 0
  ): array {
    $params = ['query' => '$e%user_input%e$'];
    if (!empty($fields)) $params['fields'] = $fields;
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($defaultOperator !== 'or') $params['default_operator'] = $defaultOperator;
    if ($analyzer !== null) $params['analyzer'] = $analyzer;
    if ($fuzziness !== 0) $params['fuzziness'] = $fuzziness;

    return ['query_string' => $params];
  }

  public static function simpleQueryString(
    array $fields = [],
    float $boost = 1.0,
    string $defaultOperator = 'or',
    ?string $analyzer = null
  ): array {
    $params = ['query' => '$e%user_input%e$'];
    if (!empty($fields)) $params['fields'] = $fields;
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($defaultOperator !== 'or') $params['default_operator'] = $defaultOperator;
    if ($analyzer !== null) $params['analyzer'] = $analyzer;

    return ['simple_query_string' => $params];
  }

  public static function term(string $field, mixed $value, float $boost = 1.0): array
  {
    $params = ['value' => $value];
    if ($boost !== 1.0) $params['boost'] = $boost;

    return ['term' => [$field => $params]];
  }

  public static function terms(string $field, array $values, float $boost = 1.0): array
  {
    $clause = [$field => $values];
    if ($boost !== 1.0) $clause['boost'] = $boost;

    return ['terms' => $clause];
  }

  public static function range(
    string $field,
    mixed $gt = null,
    mixed $gte = null,
    mixed $lt = null,
    mixed $lte = null,
    float $boost = 1.0,
    ?string $format = null,
    ?string $relation = null
  ): array {
    if ($gt === null && $gte === null && $lt === null && $lte === null) {
      throw new InvalidArgumentException('range() requires at least one bound (gt, gte, lt, lte).');
    }

    $params = [];
    if ($gt !== null) $params['gt'] = $gt;
    if ($gte !== null) $params['gte'] = $gte;
    if ($lt !== null) $params['lt'] = $lt;
    if ($lte !== null) $params['lte'] = $lte;
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($format !== null) $params['format'] = $format;
    if ($relation !== null) $params['relation'] = $relation;

    return ['range' => [$field => $params]];
  }

  public static function ids(array $values): array
  {
    return ['ids' => ['values' => $values]];
  }

  public static function exists(string $field): array
  {
    return ['exists' => ['field' => $field]];
  }

  public static function prefix(
    string $field,
    string $value,
    float $boost = 1.0,
    bool $caseInsensitive = false
  ): array {
    $params = ['value' => $value];
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($caseInsensitive) $params['case_insensitive'] = true;

    return ['prefix' => [$field => $params]];
  }

  public static function fuzzy(
    string $field,
    mixed $value,
    float $boost = 1.0,
    int|string $fuzziness = 'AUTO',
    int $prefixLength = 0,
    int $maxExpansions = 50,
    bool $transpositions = true
  ): array {
    $params = ['value' => $value];
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($fuzziness !== 'AUTO') $params['fuzziness'] = $fuzziness;
    if ($prefixLength !== 0) $params['prefix_length'] = $prefixLength;
    if ($maxExpansions !== 50) $params['max_expansions'] = $maxExpansions;
    if (!$transpositions) $params['transpositions'] = false;

    return ['fuzzy' => [$field => $params]];
  }

  public static function wildcard(
    string $field,
    string $value,
    float $boost = 1.0,
    bool $caseInsensitive = false
  ): array {
    $params = ['value' => $value];
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($caseInsensitive) $params['case_insensitive'] = true;

    return ['wildcard' => [$field => $params]];
  }

  public static function regexp(
    string $field,
    string $value,
    float $boost = 1.0,
    ?string $flags = null,
    bool $caseInsensitive = false,
    int $maxDeterminizedStates = 10000
  ): array {
    $params = ['value' => $value];
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($flags !== null) $params['flags'] = $flags;
    if ($caseInsensitive) $params['case_insensitive'] = true;
    if ($maxDeterminizedStates !== 10000) $params['max_determinized_states'] = $maxDeterminizedStates;

    return ['regexp' => [$field => $params]];
  }

  public static function nested(
    string $path,
    string $scoreMode = 'avg',
    float $boost = 1.0,
    ?array $innerHits = null
  ): array {
    $params = ['path' => $path, 'query' => '$e%user_input%e$'];
    if ($scoreMode !== 'avg') $params['score_mode'] = $scoreMode;
    if ($boost !== 1.0) $params['boost'] = $boost;
    if ($innerHits !== null) $params['inner_hits'] = $innerHits;

    return ['nested' => $params];
  }

  public static function script(
    string $source,
    array $params = [],
    string $lang = 'painless'
  ): array {
    $script = ['source' => $source, 'lang' => $lang];
    if (!empty($params)) $script['params'] = $params;

    return ['script' => ['script' => $script]];
  }
}
