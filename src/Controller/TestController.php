<?php

namespace Drupal\eticsearch\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\eticsearch\Analyzer;
use Drupal\eticsearch\CharFilter;
use Drupal\eticsearch\Factory\FieldFactory;
use Drupal\eticsearch\Factory\IndexFactory;
use Drupal\eticsearch\Factory\MappingFactory;
use Drupal\eticsearch\Filter;
use Drupal\eticsearch\Tokenizer;

/**
 * Tests the Elasticsearch connection.
 */
class TestController extends ControllerBase {
  public static function test() {
    self::createSample();

    echo '-----';

    $config = Drupal::service('eticsearch.factory.config')->getIndices()['test_index'];
    var_dump($config);
    die();

    return [];
  }

  public static function createSample() {
    IndexFactory::delete('test_index');

    // Tokenizers
    $tokenizerStandard = Tokenizer::create('standard', 'standard');
    $tokenizerKeyword = Tokenizer::create('keyword', 'keyword');

    // Built-in filters
    $filterAsciiFolding = Filter::create('asciifolding', 'asciifolding');
    $filterLowercase = Filter::create('lowercase', 'lowercase');
    $filterUnique = Filter::create('unique', 'unique');

    // Custom stop words filter
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

    // Edge n-gram filter for autocomplete
    $filterWordNgrams = Filter::create(
      name: 'word_ngrams',
      type: 'edge_ngram',
      minGram: 3,
      maxGram: 60
    );
    $filterWordNgrams->save();

    // Char filter that strips numbers before tokenisation
    $charFilterNumber = CharFilter::create('number_filter', 'pattern_replace', pattern: '\d+', replacement: '');
    $charFilterNumber->save();

    // Analyzers (hunspell stemmer omitted)
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
}
