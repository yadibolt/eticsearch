<?php

namespace Drupal\Tests\eticsearch\Unit\Factory;

use Drupal\eticsearch\Analyzer;
use Drupal\eticsearch\Factory\FieldFactory;
use Drupal\eticsearch\Normalizer;
use Drupal\eticsearch\Similarity;
use Drupal\Tests\UnitTestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\eticsearch\Factory\FieldFactory
 * @group eticsearch
 */
class FieldFactoryTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $mockConfigFactory = $this->createMock(\Drupal\eticsearch\Factory\ConfigFactory::class);
    $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
    $container->method('get')
      ->with('eticsearch.factory.config')
      ->willReturn($mockConfigFactory);
    \Drupal::setContainer($container);
  }

  // ------------------------------------------------------------------ text --

  public function testCreateTextFieldDefaults(): void {
    $field = FieldFactory::createTextField('text');

    $this->assertSame('text', $field['type']);
    $this->assertTrue($field['index']);
    $this->assertFalse($field['norms']);
    $this->assertFalse($field['index_phrases']);
    $this->assertArrayNotHasKey('analyzer', $field);
    $this->assertArrayNotHasKey('similarity', $field);
  }

  public function testCreateTextFieldWithAnalyzer(): void {
    $analyzer = Analyzer::create('my_analyzer', 'standard');
    $field = FieldFactory::createTextField('text', analyzer: $analyzer);

    $this->assertSame('my_analyzer', $field['analyzer']);
  }

  public function testCreateTextFieldWithSearchAnalyzers(): void {
    $analyzer = Analyzer::create('idx', 'standard');
    $search = Analyzer::create('search', 'stop');
    $quote = Analyzer::create('quote', 'pattern');
    $field = FieldFactory::createTextField('text', $analyzer, $search, $quote);

    $this->assertSame('idx', $field['analyzer']);
    $this->assertSame('search', $field['search_analyzer']);
    $this->assertSame('quote', $field['search_quote_analyzer']);
  }

  public function testCreateTextFieldWithSimilarity(): void {
    $sim = Similarity::create('my_sim', 'BM25');
    $field = FieldFactory::createTextField('text', similarity: $sim);

    $this->assertSame('my_sim', $field['similarity']);
  }

  public function testCreateTextFieldIndexPrefixes(): void {
    $field = FieldFactory::createTextField('text', indexPrefixes: ['min_chars' => 2, 'max_chars' => 10]);

    $this->assertSame(2, $field['index_prefixes']['min_chars']);
    $this->assertSame(10, $field['index_prefixes']['max_chars']);
  }

  public function testCreateTextFieldInvalidIndexPrefixKey(): void {
    $this->expectException(InvalidArgumentException::class);
    FieldFactory::createTextField('text', indexPrefixes: ['bad_key' => 5]);
  }

  public function testCreateTextFieldInvalidIndexPrefixValue(): void {
    $this->expectException(InvalidArgumentException::class);
    FieldFactory::createTextField('text', indexPrefixes: ['min_chars' => 'not-an-int']);
  }

  public function testCreateTextFieldWithFields(): void {
    $field = FieldFactory::createTextField('text', fields: ['keyword' => ['type' => 'keyword']]);

    $this->assertSame(['type' => 'keyword'], $field['fields']['keyword']);
  }

  public function testCreateTextFieldMatchOnlyTextStripsUnsupportedProps(): void {
    $field = FieldFactory::createTextField('match_only_text');

    $this->assertArrayNotHasKey('norms', $field);
    $this->assertArrayNotHasKey('index_prefixes', $field);
    $this->assertArrayNotHasKey('index_phrases', $field);
    $this->assertArrayNotHasKey('similarity', $field);
  }

  public function testCreateTextFieldInvalidType(): void {
    $this->expectException(InvalidArgumentException::class);
    FieldFactory::createTextField('keyword');
  }

  // --------------------------------------------------------------- keyword --

  public function testCreateKeywordFieldDefaults(): void {
    $field = FieldFactory::createKeywordField('keyword');

    $this->assertSame('keyword', $field['type']);
    $this->assertFalse($field['norms']);
    $this->assertFalse($field['split_queries_on_whitespace']);
  }

  public function testCreateKeywordFieldWithNormalizer(): void {
    $normalizer = Normalizer::create('my_normalizer');
    $field = FieldFactory::createKeywordField('keyword', normalizer: $normalizer);

    $this->assertSame('my_normalizer', $field['normalizer']);
  }

  public function testCreateKeywordFieldWithNullValue(): void {
    $field = FieldFactory::createKeywordField('keyword', nullValue: 'N/A');

    $this->assertSame('N/A', $field['null_value']);
  }

  public function testCreateKeywordFieldWildcardStripsUnsupportedProps(): void {
    $field = FieldFactory::createKeywordField('wildcard');

    $this->assertArrayNotHasKey('norms', $field);
    $this->assertArrayNotHasKey('normalizer', $field);
    $this->assertArrayNotHasKey('split_queries_on_whitespace', $field);
    $this->assertArrayNotHasKey('similarity', $field);
  }

  public function testCreateKeywordFieldInvalidType(): void {
    $this->expectException(InvalidArgumentException::class);
    FieldFactory::createKeywordField('text');
  }

  // --------------------------------------------------------------- numeric --

  public function testCreateNumericFieldDefaults(): void {
    $field = FieldFactory::createNumericField('long');

    $this->assertSame('long', $field['type']);
    $this->assertTrue($field['index']);
    $this->assertTrue($field['coerce']);
  }

  public function testCreateNumericFieldWithNullValue(): void {
    $field = FieldFactory::createNumericField('integer', nullValue: 0);

    $this->assertSame(0, $field['null_value']);
  }

  public function testCreateNumericFieldUnsignedLongStripsCoerce(): void {
    $field = FieldFactory::createNumericField('unsigned_long');

    $this->assertArrayNotHasKey('coerce', $field);
  }

  public function testCreateNumericFieldInvalidType(): void {
    $this->expectException(InvalidArgumentException::class);
    FieldFactory::createNumericField('text');
  }

  // ------------------------------------------------------------------ date --

  public function testCreateDateFieldDefaults(): void {
    $field = FieldFactory::createDateField('date');

    $this->assertSame('date', $field['type']);
    $this->assertTrue($field['index']);
    $this->assertSame('strict_date_optional_time||epoch_millis', $field['format']);
  }

  public function testCreateDateFieldDateNanos(): void {
    $field = FieldFactory::createDateField('date_nanos');

    $this->assertSame('date_nanos', $field['type']);
  }

  public function testCreateDateFieldInvalidType(): void {
    $this->expectException(InvalidArgumentException::class);
    FieldFactory::createDateField('datetime');
  }

  // --------------------------------------------------------------- boolean --

  public function testCreateBooleanFieldDefaults(): void {
    $field = FieldFactory::createBooleanField('boolean');

    $this->assertSame('boolean', $field['type']);
    $this->assertTrue($field['index']);
  }

  public function testCreateBooleanFieldInvalidType(): void {
    $this->expectException(InvalidArgumentException::class);
    FieldFactory::createBooleanField('bool');
  }

  // ---------------------------------------------------------------- binary --

  public function testCreateBinaryField(): void {
    $field = FieldFactory::createBinaryField('binary');

    $this->assertSame(['type' => 'binary'], $field);
  }

  public function testCreateBinaryFieldInvalidType(): void {
    $this->expectException(InvalidArgumentException::class);
    FieldFactory::createBinaryField('blob');
  }

  // ----------------------------------------------------------------- range --

  public function testCreateRangeFieldDefaults(): void {
    $field = FieldFactory::createRangeField('integer_range');

    $this->assertSame('integer_range', $field['type']);
    $this->assertTrue($field['index']);
  }

  public function testCreateRangeFieldDateRangeAddsFormat(): void {
    $field = FieldFactory::createRangeField('date_range');

    $this->assertSame('strict_date_optional_time||epoch_millis', $field['format']);
  }

  public function testCreateRangeFieldInvalidType(): void {
    $this->expectException(InvalidArgumentException::class);
    FieldFactory::createRangeField('range');
  }

  // ------------------------------------------------------------------- geo --

  public function testCreateGeoPointFieldDefaults(): void {
    $field = FieldFactory::createGeoField('geo_point');

    $this->assertSame('geo_point', $field['type']);
    $this->assertTrue($field['index']);
    $this->assertArrayNotHasKey('coerce', $field);
  }

  public function testCreateGeoShapeFieldAddsCoerce(): void {
    $field = FieldFactory::createGeoField('geo_shape', coerce: FALSE);

    $this->assertFalse($field['coerce']);
  }

  public function testCreateGeoPointFieldWithNullValue(): void {
    $field = FieldFactory::createGeoField('geo_point', nullValue: '0,0');

    $this->assertSame('0,0', $field['null_value']);
  }

  public function testCreateGeoShapeFieldIgnoresNullValue(): void {
    $field = FieldFactory::createGeoField('geo_shape', nullValue: '0,0');

    $this->assertArrayNotHasKey('null_value', $field);
  }

  public function testCreateGeoFieldInvalidType(): void {
    $this->expectException(InvalidArgumentException::class);
    FieldFactory::createGeoField('location');
  }

  // ----------------------------------------------------------------- other --

  public function testCreateCompletionFieldDefaults(): void {
    $field = FieldFactory::createOtherField('completion');

    $this->assertSame('completion', $field['type']);
    $this->assertTrue($field['preserve_separators']);
    $this->assertTrue($field['preserve_position_increments']);
    $this->assertSame(50, $field['max_input_length']);
  }

  public function testCreateCompletionFieldWithAnalyzer(): void {
    $analyzer = Analyzer::create('my_analyzer', 'standard');
    $field = FieldFactory::createOtherField('completion', analyzer: $analyzer);

    $this->assertSame('my_analyzer', $field['analyzer']);
  }

  public function testCreateOtherFieldInvalidType(): void {
    $this->expectException(InvalidArgumentException::class);
    FieldFactory::createOtherField('nested');
  }

}
