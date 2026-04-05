<?php

namespace Drupal\eticsearch\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;

/**
 * Tests the Elasticsearch connection.
 */
class TestController extends ControllerBase {
  public static function test() {

    /** @var Drupal\eticsearch\Factory\IndexFactory $indexFactory */
    $indexFactory = Drupal::service('eticsearch.index.factory');

    $settings = $indexFactory->createIndexSettings('test_index');
    $mappings = $indexFactory->createIndexMappings('test_index', 'node', 'test');

    $r = $indexFactory->createIndex('test_index', $settings, $mappings);
    var_dump($r);

    return [];
  }
}
