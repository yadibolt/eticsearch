<?php

namespace Drupal\eticsearch;

use Drupal;
use Drupal\eticsearch\Factory\ConfigFactory;
use InvalidArgumentException;

class Filter
{
    public const array CONFIGURABLE_TOKEN_FILTER_TYPES = [
        'stop', 'synonym', 'synonym_graph', 'stemmer', 'snowball',
        'ngram', 'edge_ngram', 'shingle', 'word_delimiter', 'word_delimiter_graph',
        'length', 'truncate', 'limit', 'pattern_replace', 'pattern_capture',
        'keyword_marker', 'elision', 'multiplexer', 'condition', 'unique',
        'predicate_token_filter', 'asciifolding', 'lowercase', 'uppercase',
    ];

    public const array SYNONYM_FORMATS = ['solr', 'wordnet'];
    public const array EDGE_NGRAM_SIDES = ['front', 'back'];

    private ConfigFactory $configFactory;
    private string $name = 'token_filter';
    private string $type = 'stop';
    private string|array $stopwords = '_english_';
    private ?string $stopwordsPath = NULL;
    private bool $ignoreCase = FALSE;
    private bool $removeTrailing = TRUE;
    private array $synonyms = [];
    private ?string $synonymsPath = NULL;
    private string $format = 'solr';
    private bool $lenient = FALSE;
    private ?string $analyzer = NULL;
    private bool $expand = TRUE;
    private string $language = 'english';
    private int $minGram = 1;
    private int $maxGram = 2;
    private bool $preserveOriginal = FALSE;
    private string $side = 'front';
    private int $maxShingleSize = 2;
    private int $minShingleSize = 2;
    private bool $outputUnigrams = TRUE;
    private bool $outputUnigramsIfNoShingles = FALSE;
    private string $tokenSeparator = ' ';
    private string $fillerToken = '_';
    private bool $generateWordParts = TRUE;
    private bool $generateNumberParts = TRUE;
    private bool $catenateWords = FALSE;
    private bool $catenateNumbers = FALSE;
    private bool $catenateAll = FALSE;
    private bool $splitOnCaseChange = TRUE;
    private bool $splitOnNumerics = TRUE;
    private bool $stemEnglishPossessive = TRUE;
    private array $protectedWords = [];
    private ?string $protectedWordsPath = NULL;
    private array $typeTable = [];
    private ?string $typeTablePath = NULL;
    private bool $adjustOffsets = TRUE;
    private int $min = 0;
    private int $max = 2147483647;
    private int $truncateLength = 10;
    private int $maxTokenCount = 1;
    private bool $consumeAllTokens = FALSE;
    private ?string $pattern = NULL;
    private string $replacement = '';
    private ?string $flags = NULL;
    private bool $all = TRUE;
    private array $patterns = [];
    private array $keywords = [];
    private ?string $keywordsPath = NULL;
    private ?string $keywordsPattern = NULL;
    private array $articles = [];
    private ?string $articlesPath = NULL;
    private bool $articlesCase = FALSE;
    private array $multiplexerFilters = [];
    private array $conditionFilter = [];
    private mixed $script = NULL;
    private bool $onlyOnSamePosition = FALSE;

    public function __construct()
    {
        $this->configFactory = Drupal::service('eticsearch.factory.config');
    }

    public static function create(string  $name, string $type, string|array $stopwords = '_english_', ?string $stopwordsPath = NULL, bool $ignoreCase = FALSE,
                                  bool    $removeTrailing = TRUE, array $synonyms = [], ?string $synonymsPath = NULL, string $format = 'solr', bool $lenient = FALSE,
                                  ?string $analyzer = NULL, bool $expand = TRUE, string $language = 'english', int $minGram = 1, int $maxGram = 2, bool $preserveOriginal = FALSE,
                                  string  $side = 'front', int $maxShingleSize = 2, int $minShingleSize = 2, bool $outputUnigrams = TRUE, bool $outputUnigramsIfNoShingles = FALSE,
                                  string  $tokenSeparator = ' ', string $fillerToken = '_', bool $generateWordParts = TRUE, bool $generateNumberParts = TRUE, bool $catenateWords = FALSE,
                                  bool    $catenateNumbers = FALSE, bool $catenateAll = FALSE, bool $splitOnCaseChange = TRUE, bool $splitOnNumerics = TRUE, bool $stemEnglishPossessive = TRUE,
                                  array   $protectedWords = [], ?string $protectedWordsPath = NULL, array $typeTable = [], ?string $typeTablePath = NULL, bool $adjustOffsets = TRUE, int $min = 0,
                                  int     $max = 2147483647, int $truncateLength = 10, int $maxTokenCount = 1, bool $consumeAllTokens = FALSE, ?string $pattern = NULL, string $replacement = '',
                                  ?string $flags = NULL, bool $all = TRUE, array $patterns = [], array $keywords = [], ?string $keywordsPath = NULL, ?string $keywordsPattern = NULL,
                                  array   $articles = [], ?string $articlesPath = NULL, bool $articlesCase = FALSE, array $multiplexerFilters = [], array $conditionFilter = [], mixed $script = NULL,
                                  bool    $onlyOnSamePosition = FALSE): self
    {
        if (!in_array($type, self::CONFIGURABLE_TOKEN_FILTER_TYPES, TRUE)) {
            throw new InvalidArgumentException(
                'create only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_TOKEN_FILTER_TYPES)
            );
        }

        $instance = new self();
        $instance->_setName($name);
        $instance->_setType($type);
        $instance->_setStopwords($stopwords);
        $instance->_setStopwordsPath($stopwordsPath);
        $instance->_setIgnoreCase($ignoreCase);
        $instance->_setRemoveTrailing($removeTrailing);
        $instance->_setSynonyms($synonyms);
        $instance->_setSynonymsPath($synonymsPath);
        $instance->_setFormat($format);
        $instance->_setLenient($lenient);
        $instance->_setAnalyzer($analyzer);
        $instance->_setExpand($expand);
        $instance->_setLanguage($language);
        $instance->_setMinGram($minGram);
        $instance->_setMaxGram($maxGram);
        $instance->_setPreserveOriginal($preserveOriginal);
        $instance->_setSide($side);
        $instance->_setMaxShingleSize($maxShingleSize);
        $instance->_setMinShingleSize($minShingleSize);
        $instance->_setOutputUnigrams($outputUnigrams);
        $instance->_setOutputUnigramsIfNoShingles($outputUnigramsIfNoShingles);
        $instance->_setTokenSeparator($tokenSeparator);
        $instance->_setFillerToken($fillerToken);
        $instance->_setGenerateWordParts($generateWordParts);
        $instance->_setGenerateNumberParts($generateNumberParts);
        $instance->_setCatenateWords($catenateWords);
        $instance->_setCatenateNumbers($catenateNumbers);
        $instance->_setCatenateAll($catenateAll);
        $instance->_setSplitOnCaseChange($splitOnCaseChange);
        $instance->_setSplitOnNumerics($splitOnNumerics);
        $instance->_setStemEnglishPossessive($stemEnglishPossessive);
        $instance->_setProtectedWords($protectedWords);
        $instance->_setProtectedWordsPath($protectedWordsPath);
        $instance->_setTypeTable($typeTable);
        $instance->_setTypeTablePath($typeTablePath);
        $instance->_setAdjustOffsets($adjustOffsets);
        $instance->_setMin($min);
        $instance->_setMax($max);
        $instance->_setTruncateLength($truncateLength);
        $instance->_setMaxTokenCount($maxTokenCount);
        $instance->_setConsumeAllTokens($consumeAllTokens);
        $instance->_setPattern($pattern);
        $instance->_setReplacement($replacement);
        $instance->_setFlags($flags);
        $instance->_setAll($all);
        $instance->_setPatterns($patterns);
        $instance->_setKeywords($keywords);
        $instance->_setKeywordsPath($keywordsPath);
        $instance->_setKeywordsPattern($keywordsPattern);
        $instance->_setArticles($articles);
        $instance->_setArticlesPath($articlesPath);
        $instance->_setArticlesCase($articlesCase);
        $instance->_setMultiplexerFilters($multiplexerFilters);
        $instance->_setConditionFilter($conditionFilter);
        $instance->_setScript($script);
        $instance->_setOnlyOnSamePosition($onlyOnSamePosition);

        return $instance;
    }

    public static function load(string $retrieval = 'single', ?string $filterName = NULL): NULL|array|self
    {
        if (!in_array($retrieval, ['single', 'all'], TRUE)) {
            throw new InvalidArgumentException('load only accepts retrieval as one of: single, all');
        }

        if ($retrieval === 'all') {
            /** @var ConfigFactory $configService */
            $configService = Drupal::service('eticsearch.factory.config');
            $filters = $configService->getFilters();

            return array_map(fn($f) => self::fromArray($f), $filters);
        }

        if ($filterName === NULL) {
            throw new InvalidArgumentException('load with retrieval single requires a filter name');
        }

        /** @var ConfigFactory $configService */
        $configService = Drupal::service('eticsearch.factory.config');
        if (($filter = $configService->getFilters()[$filterName] ?? NULL) !== NULL) {
            return self::fromArray($filter);
        }

        return NULL;
    }

    public static function delete(string $filterName): bool
    {
        /** @var ConfigFactory $configService */
        $configService = Drupal::service('eticsearch.factory.config');

        // we cannot delete the filter if some index is using it
        $indices = $configService->getIndices();
        foreach ($indices as $index) {
            if (in_array($filterName, $index['filters'] ?? [], TRUE)) {
                return FALSE;
            }
        }

        return $configService->deleteFilter($filterName);
    }

    public static function fromArray(array $entry): self {
        return self::create(
            $entry['name'] ?? 'token_filter',
            $entry['type'] ?? 'stop',
            $entry['stopwords'] ?? '_english_',
            $entry['stopwords_path'] ?? NULL,
            $entry['ignore_case'] ?? FALSE,
            $entry['remove_trailing'] ?? TRUE,
            $entry['synonyms'] ?? [],
            $entry['synonyms_path'] ?? NULL,
            $entry['format'] ?? 'solr',
            $entry['lenient'] ?? FALSE,
            $entry['analyzer'] ?? NULL,
            $entry['expand'] ?? TRUE,
            $entry['language'] ?? 'english',
            $entry['min_gram'] ?? 1,
            $entry['max_gram'] ?? 2,
            $entry['preserve_original'] ?? FALSE,
            $entry['side'] ?? 'front',
            $entry['max_shingle_size'] ?? 2,
            $entry['min_shingle_size'] ?? 2,
            $entry['output_unigrams'] ?? TRUE,
            $entry['output_unigrams_if_no_shingles'] ?? FALSE,
            $entry['token_separator'] ?? ' ',
            $entry['filler_token'] ?? '_',
            $entry['generate_word_parts'] ?? TRUE,
            $entry['generate_number_parts'] ?? TRUE,
            $entry['catenate_words'] ?? FALSE,
            $entry['catenate_numbers'] ?? FALSE,
            $entry['catenate_all'] ?? FALSE,
            $entry['split_on_case_change'] ?? TRUE,
            $entry['split_on_numerics'] ?? TRUE,
            $entry['stem_english_possessive'] ?? TRUE,
            $entry['protected_words'] ?? [],
            $entry['protected_words_path'] ?? NULL,
            $entry['type_table'] ?? [],
            $entry['type_table_path'] ?? NULL,
            $entry['adjust_offsets'] ?? TRUE,
            $entry['min'] ?? 0,
            $entry['max'] ?? 2147483647,
            $entry['truncate_length'] ?? 10,
            $entry['max_token_count'] ?? 1,
            $entry['consume_all_tokens'] ?? FALSE,
            $entry['pattern'] ?? NULL,
            $entry['replacement'] ?? '',
            $entry['flags'] ?? NULL,
            $entry['all'] ?? TRUE,
            $entry['patterns'] ?? [],
            $entry['keywords'] ?? [],
            $entry['keywords_path'] ?? NULL,
            $entry['keywords_pattern'] ?? NULL,
            $entry['articles'] ?? [],
            $entry['articles_path'] ?? NULL,
            $entry['articles_case'] ?? FALSE,
            $entry['multiplexer_filters'] ?? [],
            $entry['condition_filter'] ?? [],
            $entry['script'] ?? NULL,
            $entry['only_on_same_position'] ?? FALSE,
        );
    }

    /**
     * Formats the token filter configuration as an array for use in ES config.
     * This method will only include properties relevant to the filter type.
     * @return array
     */
    public function toArray(): array
    {
        $props = [
            'name' => $this->name,
            'type' => $this->type,
        ];

        switch ($this->type) {
            case 'stop':
                $props['stopwords'] = $this->stopwords;
                $props['ignore_case'] = $this->ignoreCase;
                $props['remove_trailing'] = $this->removeTrailing;

                if ($this->stopwordsPath !== NULL) $props['stopwords_path'] = $this->stopwordsPath;
                break;
            case 'synonym':
            case 'synonym_graph':
                $props['format'] = $this->format;
                $props['lenient'] = $this->lenient;
                $props['expand'] = $this->expand;

                if (!empty($this->synonyms)) $props['synonyms'] = $this->synonyms;
                if ($this->synonymsPath !== NULL) $props['synonyms_path'] = $this->synonymsPath;
                if ($this->analyzer !== NULL) $props['analyzer'] = $this->analyzer;
                break;
            case 'stemmer':
            case 'snowball':
                $props['language'] = $this->language;
                break;
            case 'ngram':
                $props['min_gram'] = $this->minGram;
                $props['max_gram'] = $this->maxGram;
                $props['preserve_original'] = $this->preserveOriginal;
                break;
            case 'edge_ngram':
                $props['min_gram'] = $this->minGram;
                $props['max_gram'] = $this->maxGram;
                $props['side'] = $this->side;
                $props['preserve_original'] = $this->preserveOriginal;
                break;
            case 'shingle':
                $props['max_shingle_size'] = $this->maxShingleSize;
                $props['min_shingle_size'] = $this->minShingleSize;
                $props['output_unigrams'] = $this->outputUnigrams;
                $props['output_unigrams_if_no_shingles'] = $this->outputUnigramsIfNoShingles;
                $props['token_separator'] = $this->tokenSeparator;
                $props['filler_token'] = $this->fillerToken;
                break;
            case 'word_delimiter':
                $props['generate_word_parts'] = $this->generateWordParts;
                $props['generate_number_parts'] = $this->generateNumberParts;
                $props['catenate_words'] = $this->catenateWords;
                $props['catenate_numbers'] = $this->catenateNumbers;
                $props['catenate_all'] = $this->catenateAll;
                $props['split_on_case_change'] = $this->splitOnCaseChange;
                $props['preserve_original'] = $this->preserveOriginal;
                $props['split_on_numerics'] = $this->splitOnNumerics;
                $props['stem_english_possessive'] = $this->stemEnglishPossessive;

                if (!empty($this->protectedWords)) $props['protected_words'] = $this->protectedWords;
                if ($this->protectedWordsPath !== NULL) $props['protected_words_path'] = $this->protectedWordsPath;
                if (!empty($this->typeTable)) $props['type_table'] = $this->typeTable;
                if ($this->typeTablePath !== NULL) $props['type_table_path'] = $this->typeTablePath;
                break;
            case 'word_delimiter_graph':
                $props['generate_word_parts'] = $this->generateWordParts;
                $props['generate_number_parts'] = $this->generateNumberParts;
                $props['catenate_words'] = $this->catenateWords;
                $props['catenate_numbers'] = $this->catenateNumbers;
                $props['catenate_all'] = $this->catenateAll;
                $props['split_on_case_change'] = $this->splitOnCaseChange;
                $props['preserve_original'] = $this->preserveOriginal;
                $props['split_on_numerics'] = $this->splitOnNumerics;
                $props['stem_english_possessive'] = $this->stemEnglishPossessive;
                $props['adjust_offsets'] = $this->adjustOffsets;

                if (!empty($this->protectedWords)) $props['protected_words'] = $this->protectedWords;
                if ($this->protectedWordsPath !== NULL) $props['protected_words_path'] = $this->protectedWordsPath;
                if (!empty($this->typeTable)) $props['type_table'] = $this->typeTable;
                if ($this->typeTablePath !== NULL) $props['type_table_path'] = $this->typeTablePath;
                break;
            case 'length':
                $props['min'] = $this->min;
                $props['max'] = $this->max;
                break;
            case 'truncate':
                $props['length'] = $this->truncateLength;
                break;
            case 'limit':
                $props['max_token_count'] = $this->maxTokenCount;
                $props['consume_all_tokens'] = $this->consumeAllTokens;
                break;
            case 'pattern_replace':
                $props['replacement'] = $this->replacement;
                $props['all'] = $this->all;

                if ($this->pattern !== NULL) $props['pattern'] = $this->pattern;
                if ($this->flags !== NULL) $props['flags'] = $this->flags;
                break;
            case 'pattern_capture':
                $props['patterns'] = $this->patterns;
                $props['preserve_original'] = $this->preserveOriginal;
                break;
            case 'keyword_marker':
                $props['ignore_case'] = $this->ignoreCase;

                if (!empty($this->keywords)) $props['keywords'] = $this->keywords;
                if ($this->keywordsPath !== NULL) $props['keywords_path'] = $this->keywordsPath;
                if ($this->keywordsPattern !== NULL) $props['keywords_pattern'] = $this->keywordsPattern;
                break;
            case 'elision':
                $props['articles_case'] = $this->articlesCase;

                if (!empty($this->articles)) $props['articles'] = $this->articles;
                if ($this->articlesPath !== NULL) $props['articles_path'] = $this->articlesPath;
                break;
            case 'multiplexer':
                $props['filters'] = $this->multiplexerFilters;
                $props['preserve_original'] = $this->preserveOriginal;
                break;
            case 'condition':
                $props['filter'] = $this->conditionFilter;

                if ($this->script !== NULL) $props['script'] = $this->script;
                break;
            case 'unique':
                $props['only_on_same_position'] = $this->onlyOnSamePosition;
                break;
            case 'predicate_token_filter':
                if ($this->script !== NULL) $props['script'] = $this->script;
                break;
            case 'asciifolding':
            case 'lowercase':
            case 'uppercase':
                break;
            default:
                throw new InvalidArgumentException(
                    'toArray only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_TOKEN_FILTER_TYPES)
                );
        }

        return $props;
    }

    public function save(): void
    {
        $filters = $this->configFactory->getFilters();
        $filters[$this->name] = $this->toArray();

        $this->configFactory->set('etic:filters', $filters);
    }

    private function _setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function _setType(string $type): void
    {
        if (!in_array($type, self::CONFIGURABLE_TOKEN_FILTER_TYPES, TRUE)) {
            throw new InvalidArgumentException(
                '_setType only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_TOKEN_FILTER_TYPES)
            );
        }

        $this->type = $type;
    }

    private function _setStopwords(string|array $stopwords): void
    {
        if (is_string($stopwords) && !preg_match('/^_[a-z]+_$/', $stopwords)) {
            throw new InvalidArgumentException(
                '_setStopwords only accepts stopwords as a language preset string like _english_, or an array of stop word strings'
            );
        }

        $this->stopwords = $stopwords;
    }

    private function _setStopwordsPath(?string $stopwordsPath): void
    {
        $this->stopwordsPath = $stopwordsPath;
    }

    private function _setIgnoreCase(bool $ignoreCase): void
    {
        $this->ignoreCase = $ignoreCase;
    }

    private function _setRemoveTrailing(bool $removeTrailing): void
    {
        $this->removeTrailing = $removeTrailing;
    }

    private function _setSynonyms(array $synonyms): void
    {
        $this->synonyms = $synonyms;
    }

    private function _setSynonymsPath(?string $synonymsPath): void
    {
        $this->synonymsPath = $synonymsPath;
    }

    private function _setFormat(string $format): void
    {
        if (!in_array($format, self::SYNONYM_FORMATS, TRUE)) {
            throw new InvalidArgumentException(
                '_setFormat only accepts format as one of: ' . implode(', ', self::SYNONYM_FORMATS)
            );
        }

        $this->format = $format;
    }

    private function _setLenient(bool $lenient): void
    {
        $this->lenient = $lenient;
    }

    private function _setAnalyzer(?string $analyzer): void
    {
        $this->analyzer = $analyzer;
    }

    private function _setExpand(bool $expand): void
    {
        $this->expand = $expand;
    }

    private function _setLanguage(string $language): void
    {
        $this->language = $language;
    }

    private function _setMinGram(int $minGram): void
    {
        $this->minGram = $minGram;
    }

    private function _setMaxGram(int $maxGram): void
    {
        $this->maxGram = $maxGram;
    }

    private function _setPreserveOriginal(bool $preserveOriginal): void
    {
        $this->preserveOriginal = $preserveOriginal;
    }

    private function _setSide(string $side): void
    {
        if (!in_array($side, self::EDGE_NGRAM_SIDES, TRUE)) {
            throw new InvalidArgumentException(
                '_setSide only accepts side as one of: ' . implode(', ', self::EDGE_NGRAM_SIDES)
            );
        }

        $this->side = $side;
    }

    private function _setMaxShingleSize(int $maxShingleSize): void
    {
        $this->maxShingleSize = $maxShingleSize;
    }

    private function _setMinShingleSize(int $minShingleSize): void
    {
        $this->minShingleSize = $minShingleSize;
    }

    private function _setOutputUnigrams(bool $outputUnigrams): void
    {
        $this->outputUnigrams = $outputUnigrams;
    }

    private function _setOutputUnigramsIfNoShingles(bool $outputUnigramsIfNoShingles): void
    {
        $this->outputUnigramsIfNoShingles = $outputUnigramsIfNoShingles;
    }

    private function _setTokenSeparator(string $tokenSeparator): void
    {
        $this->tokenSeparator = $tokenSeparator;
    }

    private function _setFillerToken(string $fillerToken): void
    {
        $this->fillerToken = $fillerToken;
    }

    private function _setGenerateWordParts(bool $generateWordParts): void
    {
        $this->generateWordParts = $generateWordParts;
    }

    private function _setGenerateNumberParts(bool $generateNumberParts): void
    {
        $this->generateNumberParts = $generateNumberParts;
    }

    private function _setCatenateWords(bool $catenateWords): void
    {
        $this->catenateWords = $catenateWords;
    }

    private function _setCatenateNumbers(bool $catenateNumbers): void
    {
        $this->catenateNumbers = $catenateNumbers;
    }

    private function _setCatenateAll(bool $catenateAll): void
    {
        $this->catenateAll = $catenateAll;
    }

    private function _setSplitOnCaseChange(bool $splitOnCaseChange): void
    {
        $this->splitOnCaseChange = $splitOnCaseChange;
    }

    private function _setSplitOnNumerics(bool $splitOnNumerics): void
    {
        $this->splitOnNumerics = $splitOnNumerics;
    }

    private function _setStemEnglishPossessive(bool $stemEnglishPossessive): void
    {
        $this->stemEnglishPossessive = $stemEnglishPossessive;
    }

    private function _setProtectedWords(array $protectedWords): void
    {
        $this->protectedWords = $protectedWords;
    }

    private function _setProtectedWordsPath(?string $protectedWordsPath): void
    {
        $this->protectedWordsPath = $protectedWordsPath;
    }

    private function _setTypeTable(array $typeTable): void
    {
        $this->typeTable = $typeTable;
    }

    private function _setTypeTablePath(?string $typeTablePath): void
    {
        $this->typeTablePath = $typeTablePath;
    }

    private function _setAdjustOffsets(bool $adjustOffsets): void
    {
        $this->adjustOffsets = $adjustOffsets;
    }

    private function _setMin(int $min): void
    {
        $this->min = $min;
    }

    private function _setMax(int $max): void
    {
        $this->max = $max;
    }

    private function _setTruncateLength(int $truncateLength): void
    {
        $this->truncateLength = $truncateLength;
    }

    private function _setMaxTokenCount(int $maxTokenCount): void
    {
        $this->maxTokenCount = $maxTokenCount;
    }

    private function _setConsumeAllTokens(bool $consumeAllTokens): void
    {
        $this->consumeAllTokens = $consumeAllTokens;
    }

    private function _setPattern(?string $pattern): void
    {
        $this->pattern = $pattern;
    }

    private function _setReplacement(string $replacement): void
    {
        $this->replacement = $replacement;
    }

    private function _setFlags(?string $flags): void
    {
        $this->flags = $flags;
    }

    private function _setAll(bool $all): void
    {
        $this->all = $all;
    }

    private function _setPatterns(array $patterns): void
    {
        $this->patterns = $patterns;
    }

    private function _setKeywords(array $keywords): void
    {
        $this->keywords = $keywords;
    }

    private function _setKeywordsPath(?string $keywordsPath): void
    {
        $this->keywordsPath = $keywordsPath;
    }

    private function _setKeywordsPattern(?string $keywordsPattern): void
    {
        $this->keywordsPattern = $keywordsPattern;
    }

    private function _setArticles(array $articles): void
    {
        $this->articles = $articles;
    }

    private function _setArticlesPath(?string $articlesPath): void
    {
        $this->articlesPath = $articlesPath;
    }

    private function _setArticlesCase(bool $articlesCase): void
    {
        $this->articlesCase = $articlesCase;
    }

    private function _setMultiplexerFilters(array $multiplexerFilters): void
    {
        $this->multiplexerFilters = $multiplexerFilters;
    }

    private function _setConditionFilter(array $conditionFilter): void
    {
        $this->conditionFilter = $conditionFilter;
    }

    private function _setScript(mixed $script): void
    {
        $this->script = $script;
    }

    private function _setOnlyOnSamePosition(bool $onlyOnSamePosition): void
    {
        $this->onlyOnSamePosition = $onlyOnSamePosition;
    }
}
