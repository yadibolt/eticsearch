<?php

namespace Drupal\eticsearch\Manager;

use Drupal;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Field\FieldDefinitionInterface;

class FieldManager
{
  public const array BASE_FIELD_NAMES = [
    'nid',
    'uuid',
    'vid',
    'langcode',
    'type',
    'status',
    'revision_translation_affected',
    'revision_timestamp',
    'revision_uid',
    'revision_log',
    'uid',
    'created',
    'changed',
    'promote',
    'sticky',
    'default_langcode',
    'revision_default',
  ];

  public static function getFieldDefinitionsContentType(string $content_type, bool $include_base_fields): array
  {
    /** @var EntityFieldManager $field_manager */
    $field_manager = Drupal::service('entity_field.manager');

    /** @var FieldDefinitionInterface[] $field_definitions */
    $field_definitions = $field_manager->getFieldDefinitions('node', $content_type);

    if ($include_base_fields) return $field_definitions;

    return array_filter($field_definitions, function ($field_definition) {
      return !in_array($field_definition->getName(), FieldManager::BASE_FIELD_NAMES);
    });
  }
}
