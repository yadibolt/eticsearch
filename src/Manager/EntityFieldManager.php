<?php

namespace Drupal\eticsearch\Manager;

use Drupal;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\eticsearch\Factory\ConfigurationFactory;
use InvalidArgumentException;

class EntityFieldManager
{
  public const array BASE_FIELD_NAMES = [
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

  public function __construct(private readonly ConfigurationFactory $configFactory)
  {
  }

  public function getEntityFieldTypeMappings(string $entityType, string $bundle): array {
    return match ($entityType) {
      'node' => $this->getNodeFieldTypeMappings($bundle),
      default => throw new InvalidArgumentException('Unsupported entity type: ' . $entityType),
    };
  }

  private function getNodeFieldTypeMappings(string $bundle): array {
    /** @var EntityFieldManager $fieldDefinitions */
    $fieldDefinitions = Drupal::service('entity_field.manager')
      ->getFieldDefinitions('node', $bundle);

    $fieldMappings = [];

    /** @var FieldDefinitionInterface $fieldDefinition */
    foreach ($fieldDefinitions as $fieldDefinition) {
      // if the field is a multivalue field, it does not matter
      // it still needs to be indexed as the same type, so we can ignore the cardinality
      $fieldType = $fieldDefinition->getType();

      // we ignore the base fields, as they are not relevant
      // (ids on the other hand are preserved)
      if (in_array($fieldDefinition->getName(), self::BASE_FIELD_NAMES)) {
        continue;
      }

      // analyzers needs to be omitted for non-text fields
      $esType = $this->getMapping($fieldType);
      $mapping = ['type' => $esType];

      // TODO: maybe for future support of dynamic analyzers
      /*if ($esType === 'text') {
        $mapping['analyzer'] = $this->getFieldAnalyzer($fieldDefinition);
      }*/
      $fieldMappings[$fieldDefinition->getName()] = $mapping;
    }

    return $fieldMappings;
  }

  private function getMapping(string $originalFieldType): string {
    // TODO: add support for other fields
    return match ($originalFieldType) {
      'string', 'string_long', 'email', 'text_with_summary' => 'text',
      'integer', 'timestamp' => 'integer',
      'boolean' => 'boolean',
      default => throw new InvalidArgumentException('Unsupported field type: ' . $originalFieldType),
    };
  }

  /**
   * @param FieldDefinitionInterface $fieldDefinition
   * @return string - the analyzer for this field, or 'basic_text' if not set.
   * It is set through the third party settings of the field.
   */
  private function getFieldAnalyzer(FieldDefinitionInterface $fieldDefinition): string {
    // TODO: maybe for future support of dynamic analyzers
    if (!$fieldDefinition instanceof ThirdPartySettingsInterface) {
      return 'basic_text';
    }

    $thirdPartySettings = $fieldDefinition->getThirdPartySettings(
      $this->configFactory->getThirdPartySettingsKey()
    );

    if (empty($thirdPartySettings['analyzer'])) {
      return 'basic_text';
    }

    return $thirdPartySettings['analyzer'];
  }
}
