<?php

namespace Drupal\Tests\eticsearch\Unit;

use Drupal\eticsearch\Analyzer;
use Drupal\eticsearch\CharFilter;
use Drupal\eticsearch\Filter;
use Drupal\eticsearch\Tokenizer;
use Drupal\Tests\UnitTestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\eticsearch\Analyzer
 * @group eticsearch
 */
class AnalyzerTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $this->setUpDrupalContainer();
  }

  private function setUpDrupalContainer(): void {
    $mockConfigFactory = $this->createMock(\Drupal\eticsearch\Factory\ConfigFactory::class);
    $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
    $container->method('get')
      ->with('eticsearch.factory.config')
      ->willReturn($mockConfigFactory);
    \Drupal::setContainer($container);
  }

  public function testCreateInvalidTypeThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Analyzer::create('test', 'invalid_type');
  }

  // ------------------------------------------------------------- standard --

  public function testCreateStandardDefaults(): void {
    $analyzer = Analyzer::create('my_analyzer', 'standard');
    $array = $analyzer->toArray();

    $this->assertSame('my_analyzer', $array['name']);
    $this->assertSame('standard', $array['type']);
    $this->assertSame(255, $array['max_token_length']);
    $this->assertArrayNotHasKey('stopwords', $array);
  }

  public function testCreateStandardWithStopwordsPreset(): void {
    $analyzer = Analyzer::create('my_analyzer', 'standard', stopwords: '_english_');
    $array = $analyzer->toArray();

    $this->assertSame('_english_', $array['stopwords']);
  }

  public function testCreateStandardWithStopwordsArray(): void {
    $analyzer = Analyzer::create('my_analyzer', 'standard', stopwords: ['the', 'a', 'is']);
    $array = $analyzer->toArray();

    $this->assertSame(['the', 'a', 'is'], $array['stopwords']);
  }

  public function testCreateStandardWithInvalidStopwordsStringThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Analyzer::create('my_analyzer', 'standard', stopwords: 'english');
  }

  public function testCreateStandardWithMaxTokenLength(): void {
    $analyzer = Analyzer::create('my_analyzer', 'standard', maxTokenLength: 100);
    $array = $analyzer->toArray();

    $this->assertSame(100, $array['max_token_length']);
  }

  // --------------------------------------------------------------- stop --

  public function testCreateStop(): void {
    $analyzer = Analyzer::create('stop_analyzer', 'stop');
    $array = $analyzer->toArray();

    $this->assertSame('stop', $array['type']);
    $this->assertArrayNotHasKey('max_token_length', $array);
  }

  public function testCreateStopWithStopwords(): void {
    $analyzer = Analyzer::create('stop_analyzer', 'stop', stopwords: '_french_');
    $array = $analyzer->toArray();

    $this->assertSame('_french_', $array['stopwords']);
  }

  // ------------------------------------------------------------- pattern --

  public function testCreatePattern(): void {
    $analyzer = Analyzer::create('pattern_analyzer', 'pattern');
    $array = $analyzer->toArray();

    $this->assertSame('pattern', $array['type']);
    $this->assertTrue($array['lowercase']);
    $this->assertArrayNotHasKey('pattern', $array);
    $this->assertArrayNotHasKey('flags', $array);
  }

  public function testCreatePatternWithOptions(): void {
    $analyzer = Analyzer::create('pattern_analyzer', 'pattern', pattern: '\W+', flags: 'CASE_INSENSITIVE', lowercase: FALSE);
    $array = $analyzer->toArray();

    $this->assertFalse($array['lowercase']);
    $this->assertSame('\W+', $array['pattern']);
    $this->assertSame('CASE_INSENSITIVE', $array['flags']);
  }

  // ---------------------------------------------------------- fingerprint --

  public function testCreateFingerprint(): void {
    $analyzer = Analyzer::create('fp_analyzer', 'fingerprint');
    $array = $analyzer->toArray();

    $this->assertSame('fingerprint', $array['type']);
    $this->assertSame(' ', $array['separator']);
    $this->assertSame(255, $array['max_output_size']);
  }

  public function testCreateFingerprintWithOptions(): void {
    $analyzer = Analyzer::create('fp_analyzer', 'fingerprint', separator: ',', maxOutputSize: 100);
    $array = $analyzer->toArray();

    $this->assertSame(',', $array['separator']);
    $this->assertSame(100, $array['max_output_size']);
  }

  // ------------------------------------------------------------- language --

  public function testCreateLanguageWithValidLanguage(): void {
    $analyzer = Analyzer::create('lang_analyzer', 'language', language: 'english');
    $array = $analyzer->toArray();

    $this->assertSame('language', $array['type']);
    $this->assertSame('english', $array['language']);
  }

  public function testCreateLanguageWithStemExclusion(): void {
    $analyzer = Analyzer::create('lang_analyzer', 'language', stemExclusion: ['running', 'jumping']);
    $array = $analyzer->toArray();

    $this->assertSame(['running', 'jumping'], $array['stem_exclusion']);
  }

  public function testCreateLanguageWithInvalidLanguageThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Analyzer::create('lang_analyzer', 'language', language: 'klingon');
  }

  // --------------------------------------------------------------- custom --

  public function testCreateCustomDefault(): void {
    $analyzer = Analyzer::create('custom_analyzer', 'custom');
    $array = $analyzer->toArray();

    $this->assertSame('custom', $array['type']);
    $this->assertArrayNotHasKey('tokenizer', $array);
    $this->assertArrayNotHasKey('char_filter', $array);
    $this->assertArrayNotHasKey('filter', $array);
  }

  public function testCreateCustomWithTokenizer(): void {
    $tokenizer = Tokenizer::create('my_tokenizer', 'standard');
    $analyzer = Analyzer::create('custom_analyzer', 'custom', tokenizer: $tokenizer);
    $array = $analyzer->toArray();

    $this->assertSame('my_tokenizer', $array['tokenizer']);
  }

  public function testCreateCustomWithCharFiltersAndFilters(): void {
    $charFilter = CharFilter::create('html', 'html_strip');
    $filter = Filter::create('my_stop', 'stop');
    $analyzer = Analyzer::create('custom_analyzer', 'custom', charFilters: [$charFilter], filters: [$filter]);
    $array = $analyzer->toArray();

    $this->assertSame(['html'], $array['char_filter']);
    $this->assertSame(['my_stop'], $array['filter']);
  }

  // ------------------------------------------------------------- getName --

  public function testGetName(): void {
    $analyzer = Analyzer::create('test_name', 'standard');
    $this->assertSame('test_name', $analyzer->getName());
  }

  // ------------------------------------------------------------ fromArray --

  public function testFromArrayStandard(): void {
    $entry = [
      'name' => 'my_std',
      'type' => 'standard',
      'stopwords' => '_english_',
      'max_token_length' => 100,
    ];
    $analyzer = Analyzer::fromArray($entry);
    $array = $analyzer->toArray();

    $this->assertSame('my_std', $array['name']);
    $this->assertSame('standard', $array['type']);
    $this->assertSame('_english_', $array['stopwords']);
    $this->assertSame(100, $array['max_token_length']);
  }

  public function testFromArrayDefaults(): void {
    $analyzer = Analyzer::fromArray([]);
    $this->assertSame('analyzer', $analyzer->getName());
  }

  public function testFromArrayCustomWithFilters(): void {
    $entry = [
      'name' => 'custom_a',
      'type' => 'custom',
      'tokenizer' => ['name' => 'std', 'type' => 'standard'],
      'char_filter' => [['name' => 'html', 'type' => 'html_strip']],
      'filter' => [['name' => 'stop_f', 'type' => 'stop']],
    ];
    $analyzer = Analyzer::fromArray($entry);
    $array = $analyzer->toArray();

    $this->assertSame('std', $array['tokenizer']);
    $this->assertSame(['html'], $array['char_filter']);
    $this->assertSame(['stop_f'], $array['filter']);
  }

}
