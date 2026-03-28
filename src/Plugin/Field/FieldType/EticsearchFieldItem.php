<?php

namespace Drupal\eticsearch\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

#[FieldType(
  id: 'eticsearch_field',
  label: new TranslatableMarkup('Eticsearch Field'),
  description: new TranslatableMarkup('A field for Eticsearch vector search integration.'),
  default_widget: 'eticsearch_field_widget',
  default_formatter: 'eticsearch_field_formatter',
)]
class EticsearchFieldItem extends FieldItemBase {

  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Value'))
      ->setRequired(FALSE);

    return $properties;
  }

  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 512,
        ],
      ],
    ];
  }

  public function isEmpty(): bool {
    return FALSE;
  }
}
