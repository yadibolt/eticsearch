<?php

namespace Drupal\Tests\eticsearch\Unit;

use Drupal\eticsearch\Tokenizer;
use Drupal\Tests\UnitTestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\eticsearch\Tokenizer
 * @group eticsearch
 */
class TokenizerTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $mockConfigFactory = $this->createMock(\Drupal\eticsearch\Factory\ConfigFactory::class);
    $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
    $container->method('get')
      ->with('eticsearch.factory.config')
      ->willReturn($mockConfigFactory);
    \Drupal::setContainer($container);
  }

  public function testCreateInvalidTypeThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Tokenizer::create('t', 'invalid_type');
  }

  // ------------------------------------------------------------- standard --

  public function testCreateStandardDefaults(): void {
    $tokenizer = Tokenizer::create('my_tokenizer', 'standard');
    $array = $tokenizer->toArray();

    $this->assertSame('standard', $array['type']);
    $this->assertSame('my_tokenizer', $array['name']);
    $this->assertSame(255, $array['max_token_length']);
  }

  public function testCreateStandardWithMaxTokenLength(): void {
    $tokenizer = Tokenizer::create('t', 'standard', maxTokenLength: 100);
    $array = $tokenizer->toArray();

    $this->assertSame(100, $array['max_token_length']);
  }

  // --------------------------------------------------------------- ngram --

  public function testCreateNgram(): void {
    $tokenizer = Tokenizer::create('t', 'ngram', minGram: 2, maxGram: 4);
    $array = $tokenizer->toArray();

    $this->assertSame('ngram', $array['type']);
    $this->assertSame(2, $array['min_gram']);
    $this->assertSame(4, $array['max_gram']);
    $this->assertSame([], $array['token_chars']);
    $this->assertArrayNotHasKey('custom_token_chars', $array);
  }

  public function testCreateNgramWithTokenChars(): void {
    $tokenizer = Tokenizer::create('t', 'ngram', tokenChars: ['letter', 'digit']);
    $array = $tokenizer->toArray();

    $this->assertSame(['letter', 'digit'], $array['token_chars']);
  }

  public function testCreateNgramWithCustomTokenChars(): void {
    $tokenizer = Tokenizer::create('t', 'ngram', customTokenChars: '-_');
    $array = $tokenizer->toArray();

    $this->assertSame('-_', $array['custom_token_chars']);
  }

  // ----------------------------------------------------------- edge_ngram --

  public function testCreateEdgeNgram(): void {
    $tokenizer = Tokenizer::create('t', 'edge_ngram', minGram: 1, maxGram: 3);
    $array = $tokenizer->toArray();

    $this->assertSame('edge_ngram', $array['type']);
    $this->assertSame(1, $array['min_gram']);
    $this->assertSame(3, $array['max_gram']);
  }

  // ------------------------------------------------------------- pattern --

  public function testCreatePattern(): void {
    $tokenizer = Tokenizer::create('t', 'pattern', pattern: '\W+', flags: 'CASE_INSENSITIVE', group: 0);
    $array = $tokenizer->toArray();

    $this->assertSame('pattern', $array['type']);
    $this->assertSame('\W+', $array['pattern']);
    $this->assertSame('CASE_INSENSITIVE', $array['flags']);
    $this->assertSame(0, $array['group']);
  }

  public function testCreatePatternNoFlagsByDefault(): void {
    $tokenizer = Tokenizer::create('t', 'pattern');
    $array = $tokenizer->toArray();

    $this->assertArrayNotHasKey('flags', $array);
  }

  // --------------------------------------------------- simple_pattern --

  public function testCreateSimplePattern(): void {
    $tokenizer = Tokenizer::create('t', 'simple_pattern', pattern: '[0-9]+');
    $array = $tokenizer->toArray();

    $this->assertSame('simple_pattern', $array['type']);
    $this->assertSame('[0-9]+', $array['pattern']);
  }

  public function testCreateSimplePatternSplit(): void {
    $tokenizer = Tokenizer::create('t', 'simple_pattern_split', pattern: ',');
    $array = $tokenizer->toArray();

    $this->assertSame('simple_pattern_split', $array['type']);
    $this->assertSame(',', $array['pattern']);
  }

  // --------------------------------------------------------- char_group --

  public function testCreateCharGroup(): void {
    $tokenizer = Tokenizer::create('t', 'char_group', tokenizeOnChars: ['-', '_', 'whitespace']);
    $array = $tokenizer->toArray();

    $this->assertSame('char_group', $array['type']);
    $this->assertSame(['-', '_', 'whitespace'], $array['tokenize_on_chars']);
  }

  // ------------------------------------------------------ path_hierarchy --

  public function testCreatePathHierarchyDefaults(): void {
    $tokenizer = Tokenizer::create('t', 'path_hierarchy');
    $array = $tokenizer->toArray();

    $this->assertSame('path_hierarchy', $array['type']);
    $this->assertSame('/', $array['delimiter']);
    $this->assertSame('/', $array['replacement']);
    $this->assertSame(0, $array['skip']);
    $this->assertFalse($array['reverse']);
  }

  public function testCreatePathHierarchyCustom(): void {
    $tokenizer = Tokenizer::create('t', 'path_hierarchy', delimiter: '\\', replacement: '/', skip: 2, reverse: TRUE);
    $array = $tokenizer->toArray();

    $this->assertSame('\\', $array['delimiter']);
    $this->assertSame('/', $array['replacement']);
    $this->assertSame(2, $array['skip']);
    $this->assertTrue($array['reverse']);
  }

  // ----------------------------------------------------------- getName --

  public function testGetName(): void {
    $tokenizer = Tokenizer::create('my_tok', 'standard');
    $this->assertSame('my_tok', $tokenizer->getName());
  }

  // --------------------------------------------------------- fromArray --

  public function testFromArrayStandard(): void {
    $entry = ['name' => 'std_tok', 'type' => 'standard', 'max_token_length' => 128];
    $tokenizer = Tokenizer::fromArray($entry);
    $array = $tokenizer->toArray();

    $this->assertSame('std_tok', $array['name']);
    $this->assertSame(128, $array['max_token_length']);
  }

  public function testFromArrayNgram(): void {
    $entry = [
      'name' => 'my_ngram',
      'type' => 'ngram',
      'min_gram' => 2,
      'max_gram' => 5,
      'token_chars' => ['letter'],
    ];
    $tokenizer = Tokenizer::fromArray($entry);
    $array = $tokenizer->toArray();

    $this->assertSame(2, $array['min_gram']);
    $this->assertSame(5, $array['max_gram']);
    $this->assertSame(['letter'], $array['token_chars']);
  }

  public function testFromArrayDefaults(): void {
    $tokenizer = Tokenizer::fromArray([]);
    $this->assertSame('tokenizer', $tokenizer->getName());
  }

}
