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

    Drupal\eticsearch\Filter::create(
      'stop_words_filter',
      'stop',
      TRUE,
      ['the', 'is', 'at', 'which', 'on'],
    )->save();

    Drupal\eticsearch\Filter::create(
      'word_ngram_filter',
      'edge_ngram',
      NULL,
      [],
      3,
      60
    )->save();

    Drupal\eticsearch\Analyzer::create(
      'suggester_text_analyzer',
      'custom',
      ['lowercase', 'asciifolding'],
      [],
      'standard',
    )->save();

    $filters = array_merge(Drupal\eticsearch\Filter::get('stop_words_filter'), Drupal\eticsearch\Filter::get('word_ngram_filter'));
    $analyzers = array_merge(Drupal\eticsearch\Analyzer::get('suggester_text_analyzer'));

    $r = $indexFactory->createIndex('test_index', $settings, $mappings, $analyzers, $filters);

    Drupal\eticsearch\Field::create(
      'test_field',
      'test_index',
      'field_plaintext',
      'text',
      'suggester_text_analyzer'
    )->save();

    $indexFactory->recreateIndex('test_index');

    $indexFactory->deleteIndex('test_index');
    $configFactory = Drupal::service('eticsearch.configuration.factory');

    echo '<pre>';
    var_dump($configFactory->get());
    echo '</pre>';

    return [];
  }
}
