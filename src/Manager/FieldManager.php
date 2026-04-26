<?php

namespace Drupal\eticsearch\Manager;

use Drupal\eticsearch\Index\Factory\FieldFactory;

class FieldManager
{
  public const array ALLOWED_BASE_NODE_FIELDS = [
    'nid', 'langcode', 'title', 'status', 'created', 'changed'
  ];

  public static function mapFieldTypeToElasticType(string $fieldType, bool $asFormKeys = FALSE): array
  {
    $types = match ($fieldType) {
      'string', 'string_long', 'text', 'text_long', 'text_with_summary', 'file', 'image', 'list_string', 'link', 'email', 'telephone', 'uri', 'language' => array_merge(FieldFactory::TEXT_TYPES, FieldFactory::KEYWORD_TYPES, FieldFactory::OTHER_TYPES),
      'datetime', 'timestamp', 'created', 'changed' => FieldFactory::DATE_TYPES,
      'boolean' => FieldFactory::BOOLEAN_TYPES,
      'list_integer', 'list_float', 'integer', 'decimal', 'float' => FieldFactory::NUMERIC_TYPES,
      default => NULL,
    };

    if ($asFormKeys) {
      $formKeyValuePairs = [];
      foreach ($types as $type) {
        $formKeyValuePairs[$type] = $type;
      }

      return $formKeyValuePairs;
    }

    return $types;
  }
}
