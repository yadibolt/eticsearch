<?php

namespace Drupal\Tests\eticsearch\Unit;

use Drupal\eticsearch\Filter;
use Drupal\Tests\UnitTestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\eticsearch\Filter
 * @group eticsearch
 */
class FilterTest extends UnitTestCase {

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
    Filter::create('f', 'invalid_type');
  }

  // --------------------------------------------------------------- stop --

  public function testCreateStopDefaults(): void {
    $filter = Filter::create('my_stop', 'stop');
    $array = $filter->toArray();

    $this->assertSame('stop', $array['type']);
    $this->assertSame('my_stop', $array['name']);
    $this->assertSame('_english_', $array['stopwords']);
    $this->assertFalse($array['ignore_case']);
    $this->assertTrue($array['remove_trailing']);
    $this->assertArrayNotHasKey('stopwords_path', $array);
  }

  public function testCreateStopWithStopwordsArray(): void {
    $filter = Filter::create('my_stop', 'stop', stopwords: ['the', 'a']);
    $array = $filter->toArray();

    $this->assertSame(['the', 'a'], $array['stopwords']);
  }

  public function testCreateStopWithInvalidStopwordsStringThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Filter::create('my_stop', 'stop', stopwords: 'english');
  }

  public function testCreateStopWithPath(): void {
    $filter = Filter::create('my_stop', 'stop', stopwordsPath: '/path/to/stopwords.txt');
    $array = $filter->toArray();

    $this->assertSame('/path/to/stopwords.txt', $array['stopwords_path']);
  }

  // ------------------------------------------------------------ synonym --

  public function testCreateSynonymDefaults(): void {
    $filter = Filter::create('my_syn', 'synonym');
    $array = $filter->toArray();

    $this->assertSame('synonym', $array['type']);
    $this->assertSame('solr', $array['format']);
    $this->assertFalse($array['lenient']);
    $this->assertTrue($array['expand']);
    $this->assertArrayNotHasKey('synonyms', $array);
    $this->assertArrayNotHasKey('synonyms_path', $array);
    $this->assertArrayNotHasKey('analyzer', $array);
  }

  public function testCreateSynonymWithSynonyms(): void {
    $filter = Filter::create('my_syn', 'synonym', synonyms: ['car, auto, automobile']);
    $array = $filter->toArray();

    $this->assertSame(['car, auto, automobile'], $array['synonyms']);
  }

  public function testCreateSynonymGraphType(): void {
    $filter = Filter::create('my_syn_g', 'synonym_graph');
    $this->assertSame('synonym_graph', $filter->toArray()['type']);
  }

  public function testCreateSynonymWithInvalidFormatThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Filter::create('my_syn', 'synonym', format: 'invalid');
  }

  // ------------------------------------------------------------ stemmer --

  public function testCreateStemmer(): void {
    $filter = Filter::create('my_stemmer', 'stemmer', language: 'english');
    $array = $filter->toArray();

    $this->assertSame('stemmer', $array['type']);
    $this->assertSame('english', $array['language']);
  }

  public function testCreateSnowball(): void {
    $filter = Filter::create('my_snow', 'snowball', language: 'French');
    $array = $filter->toArray();

    $this->assertSame('snowball', $array['type']);
    $this->assertSame('French', $array['language']);
  }

  // -------------------------------------------------------------- ngram --

  public function testCreateNgram(): void {
    $filter = Filter::create('my_ngram', 'ngram', minGram: 2, maxGram: 4);
    $array = $filter->toArray();

    $this->assertSame('ngram', $array['type']);
    $this->assertSame(2, $array['min_gram']);
    $this->assertSame(4, $array['max_gram']);
    $this->assertFalse($array['preserve_original']);
  }

  public function testCreateEdgeNgram(): void {
    $filter = Filter::create('my_edge', 'edge_ngram', minGram: 1, maxGram: 3, side: 'back');
    $array = $filter->toArray();

    $this->assertSame('edge_ngram', $array['type']);
    $this->assertSame('back', $array['side']);
  }

  public function testCreateEdgeNgramInvalidSideThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Filter::create('my_edge', 'edge_ngram', side: 'middle');
  }

  // ------------------------------------------------------------ shingle --

  public function testCreateShingle(): void {
    $filter = Filter::create('my_shingle', 'shingle', maxShingleSize: 3, minShingleSize: 2);
    $array = $filter->toArray();

    $this->assertSame('shingle', $array['type']);
    $this->assertSame(3, $array['max_shingle_size']);
    $this->assertSame(2, $array['min_shingle_size']);
    $this->assertTrue($array['output_unigrams']);
    $this->assertFalse($array['output_unigrams_if_no_shingles']);
    $this->assertSame(' ', $array['token_separator']);
    $this->assertSame('_', $array['filler_token']);
  }

  // ------------------------------------------------------- word_delimiter --

  public function testCreateWordDelimiter(): void {
    $filter = Filter::create('my_wd', 'word_delimiter');
    $array = $filter->toArray();

    $this->assertSame('word_delimiter', $array['type']);
    $this->assertTrue($array['generate_word_parts']);
    $this->assertTrue($array['generate_number_parts']);
    $this->assertFalse($array['catenate_words']);
    $this->assertFalse($array['catenate_numbers']);
    $this->assertFalse($array['catenate_all']);
    $this->assertTrue($array['split_on_case_change']);
    $this->assertTrue($array['split_on_numerics']);
    $this->assertTrue($array['stem_english_possessive']);
    $this->assertArrayNotHasKey('protected_words', $array);
    $this->assertArrayNotHasKey('type_table', $array);
  }

  public function testCreateWordDelimiterGraph(): void {
    $filter = Filter::create('my_wdg', 'word_delimiter_graph', adjustOffsets: FALSE);
    $array = $filter->toArray();

    $this->assertSame('word_delimiter_graph', $array['type']);
    $this->assertFalse($array['adjust_offsets']);
  }

  // ------------------------------------------------------------- length --

  public function testCreateLength(): void {
    $filter = Filter::create('my_len', 'length', min: 2, max: 100);
    $array = $filter->toArray();

    $this->assertSame('length', $array['type']);
    $this->assertSame(2, $array['min']);
    $this->assertSame(100, $array['max']);
  }

  // ----------------------------------------------------------- truncate --

  public function testCreateTruncate(): void {
    $filter = Filter::create('my_trunc', 'truncate', truncateLength: 20);
    $array = $filter->toArray();

    $this->assertSame('truncate', $array['type']);
    $this->assertSame(20, $array['length']);
  }

  // --------------------------------------------------------------- limit --

  public function testCreateLimit(): void {
    $filter = Filter::create('my_limit', 'limit', maxTokenCount: 5, consumeAllTokens: TRUE);
    $array = $filter->toArray();

    $this->assertSame('limit', $array['type']);
    $this->assertSame(5, $array['max_token_count']);
    $this->assertTrue($array['consume_all_tokens']);
  }

  // ---------------------------------------------------- pattern_replace --

  public function testCreatePatternReplace(): void {
    $filter = Filter::create('my_pr', 'pattern_replace', pattern: '\d+', replacement: 'NUM');
    $array = $filter->toArray();

    $this->assertSame('pattern_replace', $array['type']);
    $this->assertSame('\d+', $array['pattern']);
    $this->assertSame('NUM', $array['replacement']);
    $this->assertTrue($array['all']);
  }

  // ---------------------------------------------------- pattern_capture --

  public function testCreatePatternCapture(): void {
    $filter = Filter::create('my_pc', 'pattern_capture', patterns: ['\d+', '[a-z]+'], preserveOriginal: TRUE);
    $array = $filter->toArray();

    $this->assertSame('pattern_capture', $array['type']);
    $this->assertSame(['\d+', '[a-z]+'], $array['patterns']);
    $this->assertTrue($array['preserve_original']);
  }

  // ------------------------------------------------- keyword_marker --

  public function testCreateKeywordMarker(): void {
    $filter = Filter::create('my_kw', 'keyword_marker', keywords: ['Elasticsearch', 'Lucene']);
    $array = $filter->toArray();

    $this->assertSame('keyword_marker', $array['type']);
    $this->assertSame(['Elasticsearch', 'Lucene'], $array['keywords']);
  }

  // ----------------------------------------------------------- elision --

  public function testCreateElision(): void {
    $filter = Filter::create('my_elision', 'elision', articles: ["l'", "d'"]);
    $array = $filter->toArray();

    $this->assertSame('elision', $array['type']);
    $this->assertSame(["l'", "d'"], $array['articles']);
    $this->assertFalse($array['articles_case']);
  }

  // -------------------------------------------------------- multiplexer --

  public function testCreateMultiplexer(): void {
    $filter = Filter::create('my_mx', 'multiplexer', multiplexerFilters: ['lowercase', 'uppercase'], preserveOriginal: TRUE);
    $array = $filter->toArray();

    $this->assertSame('multiplexer', $array['type']);
    $this->assertSame(['lowercase', 'uppercase'], $array['filters']);
    $this->assertTrue($array['preserve_original']);
  }

  // --------------------------------------------------------- condition --

  public function testCreateCondition(): void {
    $filter = Filter::create('my_cond', 'condition', conditionFilter: ['lowercase'], script: ['source' => 'true']);
    $array = $filter->toArray();

    $this->assertSame('condition', $array['type']);
    $this->assertSame(['lowercase'], $array['filter']);
    $this->assertSame(['source' => 'true'], $array['script']);
  }

  // ------------------------------------------------------------ unique --

  public function testCreateUnique(): void {
    $filter = Filter::create('my_uniq', 'unique', onlyOnSamePosition: TRUE);
    $array = $filter->toArray();

    $this->assertSame('unique', $array['type']);
    $this->assertTrue($array['only_on_same_position']);
  }

  // ------------------------------------------------------ predicate_token_filter --

  public function testCreatePredicateTokenFilter(): void {
    $filter = Filter::create('my_pred', 'predicate_token_filter', script: ['source' => "token.getTerm().length() > 2"]);
    $array = $filter->toArray();

    $this->assertSame('predicate_token_filter', $array['type']);
    $this->assertSame(['source' => "token.getTerm().length() > 2"], $array['script']);
  }

  // ----------------------------------------------------------- getName --

  public function testGetName(): void {
    $filter = Filter::create('test_filter', 'stop');
    $this->assertSame('test_filter', $filter->getName());
  }

  // --------------------------------------------------------- fromArray --

  public function testFromArrayStop(): void {
    $entry = [
      'name' => 'custom_stop',
      'type' => 'stop',
      'stopwords' => ['the', 'a'],
      'ignore_case' => TRUE,
    ];
    $filter = Filter::fromArray($entry);
    $array = $filter->toArray();

    $this->assertSame('custom_stop', $array['name']);
    $this->assertSame(['the', 'a'], $array['stopwords']);
    $this->assertTrue($array['ignore_case']);
  }

  public function testFromArrayDefaults(): void {
    $filter = Filter::fromArray([]);
    $this->assertSame('token_filter', $filter->getName());
  }

}
