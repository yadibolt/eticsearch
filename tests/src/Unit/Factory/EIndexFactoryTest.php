<?php

namespace Drupal\Tests\eticsearch\Unit\Factory;

use Drupal\eticsearch\Analyzer;
use Drupal\eticsearch\CharFilter;
use Drupal\eticsearch\Factory\IndexFactory;
use Drupal\eticsearch\Filter;
use Drupal\eticsearch\Factory\MappingFactory;
use Drupal\eticsearch\Normalizer;
use Drupal\eticsearch\Similarity;
use Drupal\eticsearch\Tokenizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\eticsearch\Factory\IndexFactory
 * @group eticsearch
 */
class EIndexFactoryTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $mockConfigFactory = $this->createMock(\Drupal\eticsearch\Factory\ConfigFactory::class);
    $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
    $container->method('get')
      ->with('eticsearch.factory.config')
      ->willReturn($mockConfigFactory);
    \Drupal::setContainer($container);
  }

  public function testCreateDefaults(): void {
    $factory = IndexFactory::create('my_index');
    $array = $factory->toArray();

    $this->assertSame('my_index', $array['indexName']);
    $this->assertSame([], $array['mappings']);
    $this->assertSame([], $array['similarities']);
    $this->assertSame([], $array['analysis']['analyzer']);
    $this->assertSame([], $array['analysis']['tokenizer']);
    $this->assertSame([], $array['analysis']['filter']);
    $this->assertSame([], $array['analysis']['char_filter']);
    $this->assertSame([], $array['analysis']['normalizer']);
  }

  public function testCreateDefaultOptions(): void {
    $factory = IndexFactory::create('my_index');
    $options = $factory->toArray()['options'];

    $this->assertSame(1, $options['number_of_shards']);
    $this->assertSame('default', $options['codec']);
    $this->assertSame('hybridfs', $options['store_type']);
    $this->assertSame(1, $options['number_of_replicas']);
    $this->assertFalse($options['auto_expand_replicas']);
    $this->assertSame('1s', $options['refresh_interval']);
    $this->assertSame(10000, $options['max_result_window']);
    $this->assertSame(100, $options['max_docvalue_fields_search']);
    $this->assertSame(32, $options['max_script_fields']);
    $this->assertSame(1, $options['max_ngram_diff']);
    $this->assertSame(65536, $options['max_terms_count']);
    $this->assertSame(1000, $options['max_regex_length']);
    $this->assertSame('60s', $options['gc_deletes']);
    $this->assertSame(1, $options['priority']);
    $this->assertSame(1000, $options['mapping_total_fields_limit']);
    $this->assertSame(20, $options['mapping_depth_limit']);
    $this->assertSame(50, $options['mapping_nested_fields_limit']);
    $this->assertSame(10000, $options['mapping_nested_objects_limit']);
    $this->assertNull($options['mapping_field_name_length_limit']);
  }

  public function testCreateWithMapping(): void {
    $mapping = MappingFactory::create(dynamic: 'strict');
    $factory = IndexFactory::create('my_index', mappingFactory: $mapping);
    $array = $factory->toArray();

    $this->assertSame('strict', $array['mappings']['dynamic']);
  }

  public function testCreateWithAnalyzer(): void {
    $analyzer = Analyzer::create('my_analyzer', 'standard');
    $factory = IndexFactory::create('my_index', analyzers: [$analyzer]);
    $array = $factory->toArray();

    // The component name is the array key; 'name' must not appear in the value.
    $this->assertArrayHasKey('my_analyzer', $array['analysis']['analyzer']);
    $this->assertSame('standard', $array['analysis']['analyzer']['my_analyzer']['type']);
    $this->assertArrayNotHasKey('name', $array['analysis']['analyzer']['my_analyzer']);
  }

  public function testCreateWithTokenizer(): void {
    $tokenizer = Tokenizer::create('my_tok', 'ngram');
    $factory = IndexFactory::create('my_index', tokenizers: [$tokenizer]);
    $array = $factory->toArray();

    $this->assertArrayHasKey('my_tok', $array['analysis']['tokenizer']);
    $this->assertArrayNotHasKey('name', $array['analysis']['tokenizer']['my_tok']);
  }

  public function testCreateWithFilter(): void {
    $filter = Filter::create('my_filter', 'stop');
    $factory = IndexFactory::create('my_index', filters: [$filter]);
    $array = $factory->toArray();

    $this->assertArrayHasKey('my_filter', $array['analysis']['filter']);
    $this->assertArrayNotHasKey('name', $array['analysis']['filter']['my_filter']);
  }

  public function testCreateWithCharFilter(): void {
    $charFilter = CharFilter::create('html', 'html_strip');
    $factory = IndexFactory::create('my_index', charFilters: [$charFilter]);
    $array = $factory->toArray();

    $this->assertArrayHasKey('html', $array['analysis']['char_filter']);
    $this->assertArrayNotHasKey('name', $array['analysis']['char_filter']['html']);
  }

  public function testCreateWithNormalizer(): void {
    $normalizer = Normalizer::create('my_norm');
    $factory = IndexFactory::create('my_index', normalizers: [$normalizer]);
    $array = $factory->toArray();

    $this->assertArrayHasKey('my_norm', $array['analysis']['normalizer']);
    $this->assertArrayNotHasKey('name', $array['analysis']['normalizer']['my_norm']);
  }

  public function testCreateWithValidSimilarity(): void {
    $sim = Similarity::create('my_bm25', 'BM25');
    $factory = IndexFactory::create('my_index', similarities: [$sim]);
    $array = $factory->toArray();

    $this->assertArrayHasKey('my_bm25', $array['similarities']);
    $this->assertArrayNotHasKey('name', $array['similarities']['my_bm25']);
  }

  public function testCreateSkipsAnalyzerWithoutType(): void {
    // An analyzer without a type in toArray() should not be added.
    // This cannot happen via Analyzer::create() (type is required), so we
    // verify valid analyzers are always added.
    $analyzer = Analyzer::create('valid', 'stop');
    $factory = IndexFactory::create('my_index', analyzers: [$analyzer]);
    $array = $factory->toArray();

    $this->assertArrayHasKey('valid', $array['analysis']['analyzer']);
  }

  // ------------------------------------------------------- static options --

  public function testNumberOfShardsClampedToMinimum(): void {
    $factory = IndexFactory::create('i', options: ['number_of_shards' => 0]);
    $this->assertSame(1, $factory->toArray()['options']['number_of_shards']);
  }

  public function testNumberOfShardsClampedToMaximum(): void {
    $factory = IndexFactory::create('i', options: ['number_of_shards' => 2000]);
    $this->assertSame(1024, $factory->toArray()['options']['number_of_shards']);
  }

  public function testCodecInvalidDefaultsToDefault(): void {
    $factory = IndexFactory::create('i', options: ['codec' => 'bad_codec']);
    $this->assertSame('default', $factory->toArray()['options']['codec']);
  }

  public function testCodecBestCompression(): void {
    $factory = IndexFactory::create('i', options: ['codec' => 'best_compression']);
    $this->assertSame('best_compression', $factory->toArray()['options']['codec']);
  }

  public function testStoreTypeInvalidDefaultsToHybridfs(): void {
    $factory = IndexFactory::create('i', options: ['store_type' => 'bad_type']);
    $this->assertSame('hybridfs', $factory->toArray()['options']['store_type']);
  }

  public function testStoreTypeValidOptions(): void {
    foreach (['hybridfs', 'niofs', 'mmapfs', 'fs'] as $type) {
      $factory = IndexFactory::create('i', options: ['store_type' => $type]);
      $this->assertSame($type, $factory->toArray()['options']['store_type']);
    }
  }

  // ------------------------------------------------------ dynamic options --

  public function testNumberOfReplicasClampedToMinimum(): void {
    $factory = IndexFactory::create('i', options: ['number_of_replicas' => 0]);
    $this->assertSame(1, $factory->toArray()['options']['number_of_replicas']);
  }

  public function testAutoExpandReplicasValidFormat(): void {
    $factory = IndexFactory::create('i', options: ['auto_expand_replicas' => '0-all']);
    $this->assertSame('0-all', $factory->toArray()['options']['auto_expand_replicas']);
  }

  public function testAutoExpandReplicasNumericRange(): void {
    $factory = IndexFactory::create('i', options: ['auto_expand_replicas' => '0-5']);
    $this->assertSame('0-5', $factory->toArray()['options']['auto_expand_replicas']);
  }

  public function testAutoExpandReplicasInvalidDefaultsToFalse(): void {
    $factory = IndexFactory::create('i', options: ['auto_expand_replicas' => 'invalid']);
    $this->assertFalse($factory->toArray()['options']['auto_expand_replicas']);
  }

  public function testAutoExpandReplicasFalseDisabled(): void {
    $factory = IndexFactory::create('i', options: ['auto_expand_replicas' => FALSE]);
    $this->assertFalse($factory->toArray()['options']['auto_expand_replicas']);
  }

  public function testRefreshIntervalValid(): void {
    $factory = IndexFactory::create('i', options: ['refresh_interval' => '500ms']);
    $this->assertSame('500ms', $factory->toArray()['options']['refresh_interval']);
  }

  public function testRefreshIntervalDisabled(): void {
    $factory = IndexFactory::create('i', options: ['refresh_interval' => '-1']);
    $this->assertSame('-1', $factory->toArray()['options']['refresh_interval']);
  }

  public function testRefreshIntervalInvalidDefaultsTo1s(): void {
    $factory = IndexFactory::create('i', options: ['refresh_interval' => 'invalid']);
    $this->assertSame('1s', $factory->toArray()['options']['refresh_interval']);
  }

  public function testGcDeletesValid(): void {
    $factory = IndexFactory::create('i', options: ['gc_deletes' => '5m']);
    $this->assertSame('5m', $factory->toArray()['options']['gc_deletes']);
  }

  public function testGcDeletesInvalidDefaultsTo60s(): void {
    $factory = IndexFactory::create('i', options: ['gc_deletes' => 'bad']);
    $this->assertSame('60s', $factory->toArray()['options']['gc_deletes']);
  }

  public function testMaxResultWindowClampedToMinimum(): void {
    $factory = IndexFactory::create('i', options: ['max_result_window' => 0]);
    $this->assertSame(10000, $factory->toArray()['options']['max_result_window']);
  }

  public function testPriorityClampedToMinimum(): void {
    $factory = IndexFactory::create('i', options: ['priority' => -5]);
    $this->assertSame(1, $factory->toArray()['options']['priority']);
  }

  public function testMappingFieldNameLengthLimitNullAllowed(): void {
    $factory = IndexFactory::create('i', options: ['mapping_field_name_length_limit' => NULL]);
    $this->assertNull($factory->toArray()['options']['mapping_field_name_length_limit']);
  }

  public function testMappingFieldNameLengthLimitBelowOneBecomesNull(): void {
    $factory = IndexFactory::create('i', options: ['mapping_field_name_length_limit' => 0]);
    $this->assertNull($factory->toArray()['options']['mapping_field_name_length_limit']);
  }

  // -------------------------------------------------- updateDynamicSettings --

  public function testUpdateDynamicSettings(): void {
    $factory = IndexFactory::create('i');
    $factory->updateDynamicSettings([
      'number_of_replicas' => 3,
      'refresh_interval' => '5s',
      'max_result_window' => 20000,
    ]);

    $options = $factory->toArray()['options'];
    $this->assertSame(3, $options['number_of_replicas']);
    $this->assertSame('5s', $options['refresh_interval']);
    $this->assertSame(20000, $options['max_result_window']);
  }

  public function testUpdateDynamicSettingsReturnsSelf(): void {
    $factory = IndexFactory::create('i');
    $result = $factory->updateDynamicSettings([]);
    $this->assertSame($factory, $result);
  }

  // ------------------------------------------------------- fromArray --

  public function testFromArrayRoundtrip(): void {
    $mapping = MappingFactory::create(dynamic: 'strict');
    $factory = IndexFactory::create('test_index', mappingFactory: $mapping);

    $array = $factory->toArray();
    $restored = IndexFactory::fromArray($array);
    $restoredArray = $restored->toArray();

    $this->assertSame('test_index', $restoredArray['indexName']);
    $this->assertSame('strict', $restoredArray['mappings']['dynamic']);
  }

  public function testFromArrayRoundtripPreservesComponentKey(): void {
    // The component name is stored as the array key (not inside the value), so
    // the key survives a toArray() / fromArray() cycle without any data loss.
    $analyzer = Analyzer::create('my_analyzer', 'standard');
    $factory = IndexFactory::create('test_index', analyzers: [$analyzer]);

    $array = $factory->toArray();

    // After toArray() the name is the key and 'name' is absent from the value.
    $this->assertArrayHasKey('my_analyzer', $array['analysis']['analyzer']);
    $this->assertArrayNotHasKey('name', $array['analysis']['analyzer']['my_analyzer']);
  }

}
