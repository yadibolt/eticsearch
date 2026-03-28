<?php

namespace Drupal\eticsearch\Manager;

use Drupal;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\NodeType;

class ConfigurationManager
{
  public const string CONFIG_NAME = 'eticsearch.settings';
  public const string THIRD_PARTY_CONFIG_NAME = 'eticsearch';
  public const string ELASTICSEARCH_INDEX_NAME = 'eticsearch_index';

  /**
   * @return NodeType[]
   */
  public static function getEnabledContentTypes(): array
  {
    /** @var EntityTypeManager $entity_type_manager */
    $entity_type_manager = Drupal::service('entity_type.manager');

    try {
      $content_types = $entity_type_manager
        ->getStorage('node_type')
        ->loadMultiple();
    } catch (Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException|Drupal\Component\Plugin\Exception\PluginNotFoundException $e) {
      Drupal::logger('eticsearch_module')->error($e->getMessage());
      return [];
    }

    $include_content_types = [];
    foreach ($content_types as $content_type) {
      if ($content_type instanceof NodeType) {
        $eticsearch_settings = $content_type->getThirdPartySettings(ConfigurationManager::THIRD_PARTY_CONFIG_NAME);
        if (!empty($eticsearch_settings['index'])) {
          $include_content_types[] = $content_type->id();
        }
      }
    }

    if (empty($include_content_types)) return [];

    return NodeType::loadMultiple($include_content_types);
  }
}
