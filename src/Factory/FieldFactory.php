<?php

namespace Drupal\eticsearch\Factory;

use InvalidArgumentException;

class FieldFactory
{
  public const array TEXT_TYPES = ['text', 'match_only_text'];
  public const array KEYWORD_TYPES = ['keyword', 'wildcard'];
  public const array NUMERIC_TYPES = ['long', 'integer', 'short', 'byte', 'double',
    'float', 'half_float', 'unsigned_long'];
  public const array DATE_TYPES = ['date', 'date_nanos'];
  public const array BOOLEAN_TYPES = ['boolean'];
  public const array BINARY_TYPES = ['binary'];
  public const array RANGE_TYPES = ['integer_range', 'long_range', 'float_range', 'double_range',
    'date_range', 'ip_range'];
  public const array GEO_TYPES = ['geo_point', 'geo_shape'];
  public const array OTHER_TYPES = ['completion'];

  public static function createTextField(string $type, ?Analyzer $analyzer = NULL, ?Analyzer $searchAnalyzer = NULL, ?Analyzer $searchQuoteAnalyzer = NULL,
                                         ?Similarity $similarity = NULL, bool $index = TRUE, bool $norms = FALSE, array $indexPrefixes = [], bool $indexPhrases = FALSE,
                                         array $fields = []): array
  {
    if (!in_array($type, self::TEXT_TYPES, TRUE)) {
      throw new InvalidArgumentException('createTextField only accepts the following types: ' . implode(', ', self::TEXT_TYPES));
    }

    $props = [
      'type' => $type,
      'index' => $index,
      'norms' => $norms,
      'index_phrases' => $indexPhrases,
    ];

    if ($analyzer) $props['analyzer'] = $analyzer->getName();
    if ($searchAnalyzer) $props['search_analyzer'] = $searchAnalyzer->getName();
    if ($searchQuoteAnalyzer) $props['search_quote_analyzer'] = $searchQuoteAnalyzer->getName();
    if ($similarity) $props['similarity'] = $similarity->getName();

    if (!empty($indexPrefixes)) {
      foreach ($indexPrefixes as $prefix) {
        if (!in_array($prefix, ['min_chars', 'max_chars'], TRUE)) {
          throw new InvalidArgumentException('indexPrefixes only accepts the following values: ' . implode(', ', ['min_chars', 'max_chars']));
        }

        if (!is_int($indexPrefixes[$prefix])) {
          throw new InvalidArgumentException($prefix . ' values must be integers');
        }

        $props['index_prefixes'][$prefix] = $indexPrefixes[$prefix];
      }
    }

    // we just add fields without validation
    if (!empty($fields)) $props['fields'] = $fields;

    // unset the fields for 'match_only_text' type as they are not allowed
    if ($type === 'match_only_text') {
      unset($props['norms'], $props['index_prefixes'], $props['index_phrases'], $props['similarity']);
    }

    return $props;
  }

  public static function createKeywordField(string $type, ?Normalizer $normalizer = NULL, ?Similarity $similarity = NULL, bool $norms = FALSE,
                                            bool   $splitQueriesOnWhitespace = FALSE, mixed $nullValue = NULL, array $fields = []): array
  {
    if (!in_array($type, self::KEYWORD_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'createKeywordField only accepts: ' . implode(', ', self::KEYWORD_TYPES)
      );
    }

    $props = [
      'type' => $type,
      'norms' => $norms,
      'split_queries_on_whitespace' => $splitQueriesOnWhitespace,
    ];

    if ($normalizer) $props['normalizer'] = $normalizer->getName();
    if ($similarity) $props['similarity'] = $similarity->getName();
    if ($nullValue !== NULL) $props['null_value'] = $nullValue;

    // we just add fields without validation
    if (!empty($fields)) $props['fields'] = $fields;

    if ($type === 'wildcard') {
      unset($props['norms'], $props['normalizer'], $props['split_queries_on_whitespace'], $props['similarity']);
    }

    return $props;
  }

  public static function createNumericField(string $type, bool $index = TRUE, bool $coerce = TRUE, mixed $nullValue = NULL, array $fields = []): array
  {
    if (!in_array($type, self::NUMERIC_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'createNumericField only accepts: ' . implode(', ', self::NUMERIC_TYPES)
      );
    }

    $props = [
      'type' => $type,
      'index' => $index,
      'coerce' => $coerce,
    ];

    if ($nullValue !== NULL) $props['null_value'] = $nullValue;

    // we just add fields without validation
    if (!empty($fields)) $props['fields'] = $fields;

    if ($type === 'unsigned_long') {
      unset($props['coerce']);
    }

    return $props;
  }

  public static function createDateField(string $type, bool $index = TRUE, mixed $nullValue = NULL, array $fields = []): array
  {
    if (!in_array($type, self::DATE_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'createDateField only accepts: ' . implode(', ', self::DATE_TYPES)
      );
    }

    $props = [
      'type' => $type,
      'index' => $index,
      'format' => 'strict_date_optional_time||epoch_millis'
    ];

    if ($nullValue !== NULL) $props['null_value'] = $nullValue;

    // we just add fields without validation
    if (!empty($fields)) $props['fields'] = $fields;

    return $props;
  }

  public static function createBooleanField(string $type, bool $index = TRUE, mixed $nullValue = NULL, array $fields = []): array
  {
    if (!in_array($type, self::BOOLEAN_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'createBooleanField only accepts: ' . implode(', ', self::BOOLEAN_TYPES)
      );
    }

    $props = [
      'type' => $type,
      'index' => $index,
    ];

    if ($nullValue !== NULL) $props['null_value'] = $nullValue;

    // we just add fields without validation
    if (!empty($fields)) $props['fields'] = $fields;

    return $props;
  }

  public static function createBinaryField(string $type): array
  {
    if (!in_array($type, self::BINARY_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'createBinaryField only accepts: ' . implode(', ', self::BINARY_TYPES)
      );
    }

    return [
      'type' => $type,
    ];
  }

  public static function createRangeField(string $type, bool $index = TRUE): array
  {
    if (!in_array($type, self::RANGE_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'createRangeField only accepts: ' . implode(', ', self::RANGE_TYPES)
      );
    }

    $props = [
      'type' => $type,
      'index' => $index,
    ];

    if ($type === 'date_range') $props['format'] = 'strict_date_optional_time||epoch_millis';

    return $props;
  }

  public static function createGeoField(string $type, bool $index = TRUE, bool $coerce = TRUE, mixed $nullValue = NULL): array
  {
    if (!in_array($type, self::GEO_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'createGeoField only accepts: ' . implode(', ', self::GEO_TYPES)
      );
    }

    $props = [
      'type' => $type,
      'index' => $index,
    ];

    if ($type === 'geo_point' && $nullValue !== NULL) $props['null_value'] = $nullValue;
    if ($type === 'geo_shape') $props['coerce'] = $coerce;

    return $props;
  }

  public static function createOtherField(string $type, ?Analyzer $analyzer = NULL, ?Analyzer $searchAnalyzer = NULL, bool $preserveSeparators = TRUE, bool $preservePositionIncrements = TRUE, int $maxInputLength = 50): array
  {
    if (!in_array($type, self::OTHER_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'createOtherField only accepts: ' . implode(', ', self::OTHER_TYPES)
      );
    }

    $props = [
      'type' => $type,
    ];

    if ($type === 'completion') {
      if ($analyzer) $props['analyzer'] = $analyzer->getName();
      if ($searchAnalyzer) $props['search_analyzer'] = $searchAnalyzer->getName();
      $props['preserve_separators'] = $preserveSeparators;
      $props['preserve_position_increments'] = $preservePositionIncrements;
      $props['max_input_length'] = $maxInputLength;
    }

    return $props;
  }
}
