<?php

namespace Drupal\eticsearch\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\eticsearch\Index\Analyzer;
use Drupal\eticsearch\Index\CharFilter;
use Drupal\eticsearch\Index\Factory\FieldFactory;
use Drupal\eticsearch\Index\Factory\IndexFactory;
use Drupal\eticsearch\Index\Factory\MappingFactory;
use Drupal\eticsearch\Index\Filter;
use Drupal\eticsearch\Index\Tokenizer;
use Drupal\eticsearch\Search\Factory\ClauseFactory;
use Drupal\eticsearch\Search\Function\ScriptScoreFunction;
use Drupal\eticsearch\Search\Query\BoolQuery;
use Drupal\eticsearch\Search\Query\FunctionScoreQuery;
use Drupal\eticsearch\Search\Factory\SearchFactory;

/**
 * Tests the Elasticsearch connection.
 */
class TestController extends ControllerBase {
  public static function test() {
    $q = SearchFactory::use('test_index', 'test_search', 'alibaba');
    var_dump(json_encode($q, JSON_PRETTY_PRINT));

    return [];
  }

  public static function createSample() {
    IndexFactory::delete('test_index');

    $tokenizerStandard = Tokenizer::create('standard', 'standard');
    $tokenizerKeyword = Tokenizer::create('keyword', 'keyword');

    $filterAsciiFolding = Filter::create('asciifolding', 'asciifolding');
    $filterLowercase = Filter::create('lowercase', 'lowercase');
    $filterUnique = Filter::create('unique', 'unique');

    $filterStopWords = Filter::create(
      name: 'my_custom_stop_words_filter',
      type: 'stop',
      ignoreCase: true,
      stopwords: [
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m",
        "n", "p", "q", "r", "s", "t", "u", "v", "x", "y", "w",
        "cize", "takze", "ale", "ako", "aby", "alebo",
        "ktory", "ktora", "ktore", "ktori",
        "preco", "lebo", "len", "iba",
        "clovek", "cloveku",
        "bez", "blizko", "cez", "coby", "do", "dla", "hned", "hoci", "in",
        "mimo", "mimochodom", "na", "nad", "namiesto", "naproti", "napriek",
        "ob", "od", "okolo", "okrem", "oproti", "pocas", "podla", "pod",
        "popod", "popri", "ponad", "po", "pred", "pre", "pri", "proti",
        "prostrednictvom", "skrz", "so", "spomedzi", "spod", "spopod",
        "uprosted", "vdaka", "vnutri", "vzhladom", "za",
      ]
    );
    $filterStopWords->save();

    $filterWordNgrams = Filter::create(
      name: 'word_ngrams',
      type: 'edge_ngram',
      minGram: 3,
      maxGram: 60
    );
    $filterWordNgrams->save();

    $charFilterNumber = CharFilter::create('number_filter', 'pattern_replace', pattern: '\d+', replacement: '');
    $charFilterNumber->save();

    $analyzerSuggesterText = Analyzer::create(
      name: 'suggester_text',
      type: 'custom',
      tokenizer: $tokenizerStandard,
      filters: [$filterAsciiFolding, $filterLowercase]
    );
    $analyzerSuggesterText->save();

    $analyzerSuggesterTextNonDict = Analyzer::create(
      name: 'suggester_text_non_dict',
      type: 'custom',
      tokenizer: $tokenizerStandard,
      filters: [$filterAsciiFolding, $filterLowercase]
    );
    $analyzerSuggesterTextNonDict->save();

    $analyzerBasicText = Analyzer::create(
      name: 'basic_text',
      type: 'custom',
      tokenizer: $tokenizerStandard,
      filters: [$filterAsciiFolding, $filterLowercase, $filterStopWords]
    );
    $analyzerBasicText->save();

    $analyzerBasicTextNonDict = Analyzer::create(
      name: 'basic_text_non_dict',
      type: 'custom',
      tokenizer: $tokenizerStandard,
      filters: [$filterAsciiFolding, $filterLowercase, $filterStopWords]
    );
    $analyzerBasicTextNonDict->save();

    $analyzerExactPhrase = Analyzer::create(
      name: 'exact_phrase',
      type: 'custom',
      tokenizer: $tokenizerKeyword,
      filters: [$filterAsciiFolding, $filterLowercase]
    );
    $analyzerExactPhrase->save();

    $analyzerExactPhraseNonDict = Analyzer::create(
      name: 'exact_phrase_non_dict',
      type: 'custom',
      tokenizer: $tokenizerKeyword,
      filters: [$filterAsciiFolding, $filterLowercase]
    );
    $analyzerExactPhraseNonDict->save();

    $analyzerBasicTextWithNgrams = Analyzer::create(
      name: 'basic_text_with_ngrams',
      type: 'custom',
      tokenizer: $tokenizerStandard,
      charFilters: [$charFilterNumber],
      filters: [$filterAsciiFolding, $filterLowercase, $filterStopWords, $filterUnique, $filterWordNgrams]
    );
    $analyzerBasicTextWithNgrams->save();

    $analyzerBasicTextWithNgramsNonDict = Analyzer::create(
      name: 'basic_text_with_ngrams_non_dict',
      type: 'custom',
      tokenizer: $tokenizerStandard,
      charFilters: [$charFilterNumber],
      filters: [$filterAsciiFolding, $filterLowercase, $filterStopWords, $filterUnique, $filterWordNgrams]
    );
    $analyzerBasicTextWithNgramsNonDict->save();

    $mappings = MappingFactory::create(fields: [
      FieldFactory::createKeywordField('model', 'keyword'),

      FieldFactory::createTextField(
        'title',
        'text',
        analyzer: $analyzerBasicText,
        fields: [
          FieldFactory::createTextField(
            'autocomplete',
            'text',
            analyzer: $analyzerBasicTextWithNgramsNonDict,
            searchAnalyzer: $analyzerBasicTextNonDict
          ),
          FieldFactory::createTextField(
            'exact',
            'text',
            analyzer: $analyzerExactPhraseNonDict
          ),
          FieldFactory::createKeywordField('dedupe', 'keyword'),
        ]
      ),

      FieldFactory::createOtherField(
        'title_suggest',
        'completion',
        analyzer: $analyzerSuggesterText,
        fields: [
          FieldFactory::createOtherField(
            'nondict',
            'completion',
            analyzer: $analyzerSuggesterTextNonDict
          ),
        ]
      ),

      FieldFactory::createTextField(
        'title_extended',
        'text',
        analyzer: $analyzerBasicText,
        fields: [
          FieldFactory::createTextField(
            'autocomplete',
            'text',
            analyzer: $analyzerBasicTextWithNgramsNonDict,
            searchAnalyzer: $analyzerBasicTextNonDict
          ),
          FieldFactory::createTextField(
            'exact',
            'text',
            analyzer: $analyzerExactPhraseNonDict
          ),
        ]
      ),

      FieldFactory::createTextField(
        'manufacturer',
        'text',
        fields: [
          FieldFactory::createTextField(
            'autocomplete',
            'text',
            analyzer: $analyzerBasicTextWithNgramsNonDict,
            searchAnalyzer: $analyzerBasicTextNonDict
          ),
          FieldFactory::createTextField(
            'exact',
            'text',
            analyzer: $analyzerExactPhraseNonDict
          ),
        ]
      ),

      FieldFactory::createTextField(
        'attributes',
        'text',
        fields: [
          FieldFactory::createTextField(
            'autocomplete',
            'text',
            analyzer: $analyzerBasicTextWithNgramsNonDict,
            searchAnalyzer: $analyzerBasicTextNonDict
          ),
          FieldFactory::createTextField(
            'exact',
            'text',
            analyzer: $analyzerExactPhraseNonDict
          ),
        ]
      ),

      FieldFactory::createTextField(
        'material_description',
        'text',
        analyzer: $analyzerBasicText,
        fields: [
          FieldFactory::createTextField(
            'autocomplete',
            'text',
            analyzer: $analyzerBasicTextWithNgramsNonDict,
            searchAnalyzer: $analyzerBasicTextNonDict
          ),
          FieldFactory::createTextField(
            'exact',
            'text',
            analyzer: $analyzerExactPhraseNonDict
          ),
        ]
      ),

      FieldFactory::createTextField(
        'protection_level',
        'text',
        fields: [
          FieldFactory::createTextField(
            'exact',
            'text',
            analyzer: $analyzerExactPhraseNonDict
          ),
        ]
      ),

      FieldFactory::createTextField('norms', 'text', analyzer: $analyzerBasicTextNonDict),

      FieldFactory::createTextField(
        'description',
        'text',
        analyzer: $analyzerBasicText,
        fields: [
          FieldFactory::createTextField(
            'autocomplete',
            'text',
            analyzer: $analyzerBasicTextWithNgramsNonDict,
            searchAnalyzer: $analyzerBasicTextNonDict
          ),
          FieldFactory::createTextField(
            'nondict',
            'text',
            analyzer: $analyzerBasicTextNonDict
          ),
        ]
      ),
    ]);

    $index = IndexFactory::create(
      'test_index',
      mappingFactory: $mappings,
      analyzers: [
        $analyzerSuggesterText,
        $analyzerSuggesterTextNonDict,
        $analyzerBasicText,
        $analyzerBasicTextNonDict,
        $analyzerExactPhrase,
        $analyzerExactPhraseNonDict,
        $analyzerBasicTextWithNgrams,
        $analyzerBasicTextWithNgramsNonDict,
      ],
      tokenizers: [
        $tokenizerStandard,
        $tokenizerKeyword,
      ],
      filters: [
        $filterAsciiFolding,
        $filterLowercase,
        $filterUnique,
        $filterStopWords,
        $filterWordNgrams,
      ],
      charFilters: [
        $charFilterNumber,
      ]
    );
    $index->save();
  }

  public static function createSampleSearch(int $limit = 20): array
  {
    $scriptSource = '_score * (doc["promotion_weight"].value > 0 ? doc["promotion_weight"].value : 1) * (doc["items_sold"].value > 0 ? doc["items_sold"].value * (doc["items_sold"].value * params.soldFactor) : 1) * (doc["stock_level"].value > 0 ? doc["stock_level"].value * params.stockFactor : 0)';

    $innerBool = BoolQuery::create()
      ->addShould(ClauseFactory::match('model', boost: 10000.0))
      ->addShould(ClauseFactory::matchPhrasePrefix('title.exact', boost: 100.0))
      ->addShould(ClauseFactory::matchPhrasePrefix('title_extended.exact', boost: 100.0))
      ->addShould(ClauseFactory::matchPhrasePrefix('manufacturer.exact', boost: 95.0))
      ->addShould(ClauseFactory::matchPhrasePrefix('attributes.exact', boost: 90.0))
      ->addShould(ClauseFactory::matchPhrasePrefix('material_description.exact', boost: 85.0))
      ->addShould(ClauseFactory::matchPhrasePrefix('protection_level.exact', boost: 80.0))
      ->addShould(ClauseFactory::matchPhrasePrefix('material_description', boost: 75.0))
      ->addShould(ClauseFactory::matchPhrase('title', boost: 70.0))
      ->addShould(ClauseFactory::match('title.autocomplete', boost: 65.0))
      ->addShould(ClauseFactory::match('title', boost: 60.0, fuzziness: 1))
      ->addShould(ClauseFactory::matchPhrase('title_extended', boost: 70.0))
      ->addShould(ClauseFactory::match('title_extended.autocomplete', boost: 65.0))
      ->addShould(ClauseFactory::match('title_extended', boost: 60.0, fuzziness: 1))
      ->addShould(ClauseFactory::match('manufacturer.autocomplete', boost: 55.0))
      ->addShould(ClauseFactory::match('attributes.autocomplete', boost: 40.0))
      ->addShould(ClauseFactory::match('material_description.autocomplete', boost: 35.0))
      ->addShould(ClauseFactory::match('material_description', boost: 30.0))
      ->addShould(ClauseFactory::matchPhrasePrefix('norms', boost: 25.0));

    $outerBool = BoolQuery::create()
      ->addMust($innerBool)
      ->addFilter(ClauseFactory::range('price', gt: 0))
      ->addFilter(ClauseFactory::range('stock_level', gt: 0))
      ->addFilter(ClauseFactory::term('published', true))
      ->addShould(ClauseFactory::matchPhrasePrefix('description', boost: 20.0))
      ->addShould(ClauseFactory::matchPhrasePrefix('description.nondict', boost: 15.0))
      ->addShould(ClauseFactory::matchPhrasePrefix('categories.title', boost: 10.0))
      ->addShould(ClauseFactory::matchPhrasePrefix('categories.title.exact', boost: 5.0))
      ->addShould(ClauseFactory::match('categories.title.autocomplete', boost: 2.0, fuzziness: 1))
      ->addShould(ClauseFactory::match('categories.title', boost: 2.0))
      ->addShould(ClauseFactory::match('description.autocomplete', fuzziness: 1))
      ->addShould(ClauseFactory::match('description'))
      ->addShould(ClauseFactory::match('description.nondict'));

    $sf = SearchFactory::create('test_index', 'test_search', size: $limit)
      ->setQuery(
        FunctionScoreQuery::create(
          $outerBool,
          functions: [
            ScriptScoreFunction::create($scriptSource, ['soldFactor' => 0.00001, 'stockFactor' => 0.0001]),
          ],
          scoreMode: 'multiply'
        )
      )
      ->setCollapse('title.dedupe', innerHits: ['name' => 'parent', 'size' => 1])
      ->addCompletionSuggest('completions', 'title_suggest', size: 5, options: ['skip_duplicates' => true, 'fuzzy' => ['fuzziness' => 1]])
      ->addCompletionSuggest('completions_nondict', 'title_suggest.nondict', size: 5, options: ['skip_duplicates' => true, 'fuzzy' => ['fuzziness' => 1]])
      ->setSource(['title', 'title_suggest', 'url', 'price', 'img_url', 'img_alt', 'model', 'categories', 'manufacturer', 'attributes']);

    $sf->save();

    return $sf->toArray();
  }
}
