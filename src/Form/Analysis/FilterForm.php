<?php

namespace Drupal\eticsearch\Form\Analysis;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;
use Drupal\eticsearch\Index\Filter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FilterForm extends FormBase
{

  protected $eticConfig;

  public function __construct(
    RouteMatchInterface $routeMatch,
    ConfigFactory       $eticConfig,
  ) {
    $this->routeMatch = $routeMatch;
    $this->eticConfig = $eticConfig;
  }

  public function getFormId(): string
  {
    return 'eticsearch_filter_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $name = NULL): array
  {
    $name = $name ?? $this->routeMatch->getParameter('name');
    $existing = Filter::load('single', $name);
    $isNew = $existing === NULL;
    $data = $existing ? $existing->toArray() : [];

    $form_state->set('filter_name', $name);

    $currentType = $form_state->getValue('type') ?? ($data['type'] ?? 'stop');

    $form['name_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Machine name'),
      '#markup' => '<code>' . $name . '</code>',
    ];

    $typeOptions = [];
    foreach (Filter::CONFIGURABLE_TOKEN_FILTER_TYPES as $t) {
      $typeOptions[$t] = ucwords(str_replace('_', ' ', $t));
    }

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $typeOptions,
      '#default_value' => $currentType,
      '#disabled' => !$isNew,
      '#ajax' => [
        'callback' => '::rebuildTypeFields',
        'wrapper' => 'filter-type-fields',
        'event' => 'change',
      ],
    ];

    $form['config_fields'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'filter-type-fields'],
    ];

    $this->buildTypeFields($form['config_fields'], $currentType, $data);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save filter'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('eticsearch.filter.list'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  private function buildTypeFields(array &$container, string $type, array $data): void
  {
    switch ($type) {
      case 'stop':
        $stopwordsDefault = $data['stopwords'] ?? '_english_';
        $container['stopwords'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Stopwords'),
          '#default_value' => is_array($stopwordsDefault) ? implode("\n", $stopwordsDefault) : $stopwordsDefault,
          '#description' => $this->t('Enter a language preset (e.g. <code>_english_</code>) on a single line, or one stop word per line for a custom list.'),
          '#rows' => 5,
        ];
        $container['stopwords_path'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Stopwords path'),
          '#default_value' => $data['stopwords_path'] ?? '',
          '#description' => $this->t('Path to a file containing stop words (relative to Elasticsearch config dir).'),
        ];
        $container['ignore_case'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Ignore case'),
          '#default_value' => $data['ignore_case'] ?? FALSE,
        ];
        $container['remove_trailing'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Remove trailing stop words'),
          '#default_value' => $data['remove_trailing'] ?? TRUE,
        ];
        break;

      case 'synonym':
      case 'synonym_graph':
        $container['synonyms'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Synonyms'),
          '#default_value' => implode("\n", $data['synonyms'] ?? []),
          '#description' => $this->t('One synonym rule per line. Solr format: <code>i-pod, i pod => ipod</code>. WordNet format: <code>s(100000001,1,\'fast\',v,1,0).</code>'),
          '#rows' => 8,
        ];
        $container['synonyms_path'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Synonyms path'),
          '#default_value' => $data['synonyms_path'] ?? '',
          '#description' => $this->t('Path to a synonyms file (relative to Elasticsearch config dir).'),
        ];
        $container['format'] = [
          '#type' => 'select',
          '#title' => $this->t('Format'),
          '#options' => ['solr' => 'Solr', 'wordnet' => 'WordNet'],
          '#default_value' => $data['format'] ?? 'solr',
        ];
        $container['expand'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Expand'),
          '#default_value' => $data['expand'] ?? TRUE,
          '#description' => $this->t('If enabled, synonym expansions go both ways.'),
        ];
        $container['lenient'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Lenient'),
          '#default_value' => $data['lenient'] ?? FALSE,
          '#description' => $this->t('Ignore exceptions during parsing of synonym rules.'),
        ];
        $container['analyzer'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Analyzer'),
          '#default_value' => $data['analyzer'] ?? '',
          '#description' => $this->t('Analyzer used to tokenize the synonym entries. Leave blank for the default search analyzer.'),
        ];
        break;

      case 'stemmer':
      case 'snowball':
        $container['language'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Language'),
          '#default_value' => $data['language'] ?? 'english',
          '#description' => $type === 'snowball'
            ? $this->t('Snowball language name, e.g. <code>English</code>, <code>German</code>, <code>French</code>.')
            : $this->t('Stemmer language key, e.g. <code>english</code>, <code>german</code>, <code>light_french</code>.'),
          '#required' => TRUE,
        ];
        break;

      case 'ngram':
        $container['min_gram'] = [
          '#type' => 'number',
          '#title' => $this->t('Min gram'),
          '#default_value' => $data['min_gram'] ?? 1,
          '#min' => 1,
          '#required' => TRUE,
        ];
        $container['max_gram'] = [
          '#type' => 'number',
          '#title' => $this->t('Max gram'),
          '#default_value' => $data['max_gram'] ?? 2,
          '#min' => 1,
          '#required' => TRUE,
        ];
        $container['preserve_original'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Preserve original'),
          '#default_value' => $data['preserve_original'] ?? FALSE,
        ];
        break;

      case 'edge_ngram':
        $container['min_gram'] = [
          '#type' => 'number',
          '#title' => $this->t('Min gram'),
          '#default_value' => $data['min_gram'] ?? 1,
          '#min' => 1,
          '#required' => TRUE,
        ];
        $container['max_gram'] = [
          '#type' => 'number',
          '#title' => $this->t('Max gram'),
          '#default_value' => $data['max_gram'] ?? 2,
          '#min' => 1,
          '#required' => TRUE,
        ];
        $container['side'] = [
          '#type' => 'select',
          '#title' => $this->t('Side'),
          '#options' => ['front' => $this->t('Front'), 'back' => $this->t('Back')],
          '#default_value' => $data['side'] ?? 'front',
        ];
        $container['preserve_original'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Preserve original'),
          '#default_value' => $data['preserve_original'] ?? FALSE,
        ];
        break;

      case 'shingle':
        $container['min_shingle_size'] = [
          '#type' => 'number',
          '#title' => $this->t('Min shingle size'),
          '#default_value' => $data['min_shingle_size'] ?? 2,
          '#min' => 2,
          '#required' => TRUE,
        ];
        $container['max_shingle_size'] = [
          '#type' => 'number',
          '#title' => $this->t('Max shingle size'),
          '#default_value' => $data['max_shingle_size'] ?? 2,
          '#min' => 2,
          '#required' => TRUE,
        ];
        $container['output_unigrams'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Output unigrams'),
          '#default_value' => $data['output_unigrams'] ?? TRUE,
        ];
        $container['output_unigrams_if_no_shingles'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Output unigrams if no shingles'),
          '#default_value' => $data['output_unigrams_if_no_shingles'] ?? FALSE,
        ];
        $container['token_separator'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Token separator'),
          '#default_value' => $data['token_separator'] ?? ' ',
          '#size' => 5,
        ];
        $container['filler_token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Filler token'),
          '#default_value' => $data['filler_token'] ?? '_',
          '#size' => 5,
          '#description' => $this->t('Used to replace stop words in shingles.'),
        ];
        break;

      case 'word_delimiter':
      case 'word_delimiter_graph':
        $this->buildWordDelimiterFields($container, $type, $data);
        break;

      case 'length':
        $container['min'] = [
          '#type' => 'number',
          '#title' => $this->t('Minimum length'),
          '#default_value' => $data['min'] ?? 0,
          '#min' => 0,
        ];
        $container['max'] = [
          '#type' => 'number',
          '#title' => $this->t('Maximum length'),
          '#default_value' => $data['max'] ?? 2147483647,
          '#min' => 0,
        ];
        break;

      case 'truncate':
        $container['length'] = [
          '#type' => 'number',
          '#title' => $this->t('Truncate length'),
          '#default_value' => $data['length'] ?? 10,
          '#min' => 1,
          '#required' => TRUE,
        ];
        break;

      case 'limit':
        $container['max_token_count'] = [
          '#type' => 'number',
          '#title' => $this->t('Max token count'),
          '#default_value' => $data['max_token_count'] ?? 1,
          '#min' => 1,
          '#required' => TRUE,
        ];
        $container['consume_all_tokens'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Consume all tokens'),
          '#default_value' => $data['consume_all_tokens'] ?? FALSE,
          '#description' => $this->t('If enabled, all tokens are read even if the limit is reached.'),
        ];
        break;

      case 'pattern_replace':
        $container['pattern'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Pattern (Java regex)'),
          '#default_value' => $data['pattern'] ?? '',
          '#required' => TRUE,
        ];
        $container['replacement'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Replacement'),
          '#default_value' => $data['replacement'] ?? '',
          '#description' => $this->t('Use <code>$1</code>, <code>$2</code>, etc. for capture groups.'),
        ];
        $container['flags'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Flags'),
          '#default_value' => $data['flags'] ?? '',
          '#description' => $this->t('Pipe-separated Java regex flags.'),
        ];
        $container['all'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Replace all occurrences'),
          '#default_value' => $data['all'] ?? TRUE,
        ];
        break;

      case 'pattern_capture':
        $container['patterns'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Patterns'),
          '#default_value' => implode("\n", $data['patterns'] ?? []),
          '#description' => $this->t('One Java regex pattern per line. Each capture group becomes a token.'),
          '#rows' => 5,
          '#required' => TRUE,
        ];
        $container['preserve_original'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Preserve original'),
          '#default_value' => $data['preserve_original'] ?? FALSE,
        ];
        break;

      case 'keyword_marker':
        $container['keywords'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Keywords'),
          '#default_value' => implode("\n", $data['keywords'] ?? []),
          '#description' => $this->t('One keyword per line. These tokens will be marked and skipped by stemmers.'),
          '#rows' => 5,
        ];
        $container['keywords_path'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Keywords path'),
          '#default_value' => $data['keywords_path'] ?? '',
          '#description' => $this->t('Path to a keywords file.'),
        ];
        $container['keywords_pattern'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Keywords pattern (regex)'),
          '#default_value' => $data['keywords_pattern'] ?? '',
          '#description' => $this->t('Java regex. Tokens matching this pattern are marked as keywords.'),
        ];
        $container['ignore_case'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Ignore case'),
          '#default_value' => $data['ignore_case'] ?? FALSE,
        ];
        break;

      case 'elision':
        $container['articles'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Articles'),
          '#default_value' => implode("\n", $data['articles'] ?? []),
          '#description' => $this->t('One elision article per line (e.g. <code>l</code>, <code>d</code>).'),
          '#rows' => 5,
        ];
        $container['articles_path'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Articles path'),
          '#default_value' => $data['articles_path'] ?? '',
        ];
        $container['articles_case'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Case sensitive articles'),
          '#default_value' => $data['articles_case'] ?? FALSE,
        ];
        break;

      case 'multiplexer':
        $availableFilters = array_keys($this->eticConfig->getFilters());
        $filterOptions = array_combine($availableFilters, $availableFilters);
        $container['multiplexer_filters'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Filters'),
          '#options' => $filterOptions ?: ['' => $this->t('No filters available — add filters first.')],
          '#default_value' => $data['filters'] ?? [],
          '#description' => $this->t('Each selected filter produces an additional token copy.'),
        ];
        $container['preserve_original'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Preserve original'),
          '#default_value' => $data['preserve_original'] ?? FALSE,
        ];
        break;

      case 'condition':
        $availableFilters = array_keys($this->eticConfig->getFilters());
        $filterOptions = array_combine($availableFilters, $availableFilters);
        $container['condition_filter'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Filters to apply'),
          '#options' => $filterOptions ?: ['' => $this->t('No filters available — add filters first.')],
          '#default_value' => $data['filter'] ?? [],
        ];
        $container['script'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Condition script (Painless)'),
          '#default_value' => is_array($data['script'] ?? NULL) ? ($data['script']['source'] ?? '') : '',
          '#description' => $this->t('Painless script that must return <code>true</code> for the filter to apply. Available variable: <code>token</code>.'),
          '#rows' => 6,
        ];
        break;

      case 'unique':
        $container['only_on_same_position'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Only on same position'),
          '#default_value' => $data['only_on_same_position'] ?? FALSE,
          '#description' => $this->t('Remove duplicates only at the same position.'),
        ];
        break;

      case 'predicate_token_filter':
        $container['script'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Predicate script (Painless)'),
          '#default_value' => is_array($data['script'] ?? NULL) ? ($data['script']['source'] ?? '') : '',
          '#description' => $this->t('Painless script that must return <code>true</code> for a token to be kept. Available variable: <code>token</code>.'),
          '#rows' => 6,
          '#required' => TRUE,
        ];
        break;

      case 'asciifolding':
        $container['info'] = [
          '#markup' => '<p>' . $this->t('The asciifolding filter converts alphabetic, numeric, and symbolic characters that are not in the Basic Latin Unicode block to their ASCII equivalent. No configuration needed.') . '</p>',
        ];
        break;

      case 'lowercase':
      case 'uppercase':
        $container['info'] = [
          '#markup' => '<p>' . $this->t('The %type filter requires no configuration.', ['%type' => $type]) . '</p>',
        ];
        break;
    }
  }

  private function buildWordDelimiterFields(array &$container, string $type, array $data): void
  {
    $container['generate_word_parts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate word parts'),
      '#default_value' => $data['generate_word_parts'] ?? TRUE,
      '#description' => $this->t('Split on infix hyphens, e.g. <code>Wi-Fi</code> → <code>Wi</code>, <code>Fi</code>.'),
    ];
    $container['generate_number_parts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate number parts'),
      '#default_value' => $data['generate_number_parts'] ?? TRUE,
    ];
    $container['catenate_words'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Catenate words'),
      '#default_value' => $data['catenate_words'] ?? FALSE,
      '#description' => $this->t('Generate <code>WiFi</code> from <code>Wi-Fi</code>.'),
    ];
    $container['catenate_numbers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Catenate numbers'),
      '#default_value' => $data['catenate_numbers'] ?? FALSE,
    ];
    $container['catenate_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Catenate all'),
      '#default_value' => $data['catenate_all'] ?? FALSE,
    ];
    $container['split_on_case_change'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Split on case change'),
      '#default_value' => $data['split_on_case_change'] ?? TRUE,
      '#description' => $this->t('Split on camelCase boundaries.'),
    ];
    $container['preserve_original'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preserve original'),
      '#default_value' => $data['preserve_original'] ?? FALSE,
    ];
    $container['split_on_numerics'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Split on numerics'),
      '#default_value' => $data['split_on_numerics'] ?? TRUE,
    ];
    $container['stem_english_possessive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Strip English possessive (\\'s)"),
      '#default_value' => $data['stem_english_possessive'] ?? TRUE,
    ];
    if ($type === 'word_delimiter_graph') {
      $container['adjust_offsets'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Adjust offsets'),
        '#default_value' => $data['adjust_offsets'] ?? TRUE,
      ];
    }
    $container['protected_words'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Protected words'),
      '#default_value' => implode("\n", $data['protected_words'] ?? []),
      '#description' => $this->t('One word per line. These tokens will not be split.'),
      '#rows' => 4,
    ];
    $container['protected_words_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Protected words path'),
      '#default_value' => $data['protected_words_path'] ?? '',
    ];
    $container['type_table'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Type table'),
      '#default_value' => implode("\n", $data['type_table'] ?? []),
      '#description' => $this->t('Custom type mappings, one per line (e.g. <code>$ => DIGIT</code>).'),
      '#rows' => 4,
    ];
    $container['type_table_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Type table path'),
      '#default_value' => $data['type_table_path'] ?? '',
    ];
  }

  public function rebuildTypeFields(array &$form, FormStateInterface $form_state): array
  {
    return $form['config_fields'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $name = $form_state->get('filter_name');
    $type = $form_state->getValue('type');
    $values = $form_state->getValues();

    $stopwordsRaw = trim($values['stopwords'] ?? '_english_');
    $lines = array_values(array_filter(array_map('trim', explode("\n", $stopwordsRaw))));
    $stopwords = (count($lines) === 1 && preg_match('/^_[a-z]+_$/', $lines[0])) ? $lines[0] : $lines;
    if (empty($lines)) {
      $stopwords = '_english_';
    }

    $synonyms = array_values(array_filter(array_map('trim', explode("\n", $values['synonyms'] ?? ''))));
    $patterns = array_values(array_filter(array_map('trim', explode("\n", $values['patterns'] ?? ''))));
    $keywords = array_values(array_filter(array_map('trim', explode("\n", $values['keywords'] ?? ''))));
    $articles = array_values(array_filter(array_map('trim', explode("\n", $values['articles'] ?? ''))));
    $protectedWords = array_values(array_filter(array_map('trim', explode("\n", $values['protected_words'] ?? ''))));
    $typeTable = array_values(array_filter(array_map('trim', explode("\n", $values['type_table'] ?? ''))));

    $multiplexerFilters = array_values(array_filter($values['multiplexer_filters'] ?? []));
    $conditionFilter = array_values(array_filter($values['condition_filter'] ?? []));

    $scriptSource = trim($values['script'] ?? '');
    $script = $scriptSource !== '' ? ['source' => $scriptSource] : NULL;

    $filter = Filter::create(
      $name,
      $type,
      $stopwords,
      ($values['stopwords_path'] ?? '') !== '' ? $values['stopwords_path'] : NULL,
      (bool)($values['ignore_case'] ?? FALSE),
      (bool)($values['remove_trailing'] ?? TRUE),
      $synonyms,
      ($values['synonyms_path'] ?? '') !== '' ? $values['synonyms_path'] : NULL,
      $values['format'] ?? 'solr',
      (bool)($values['lenient'] ?? FALSE),
      ($values['analyzer'] ?? '') !== '' ? $values['analyzer'] : NULL,
      (bool)($values['expand'] ?? TRUE),
      $values['language'] ?? 'english',
      (int)($values['min_gram'] ?? 1),
      (int)($values['max_gram'] ?? 2),
      (bool)($values['preserve_original'] ?? FALSE),
      $values['side'] ?? 'front',
      (int)($values['max_shingle_size'] ?? 2),
      (int)($values['min_shingle_size'] ?? 2),
      (bool)($values['output_unigrams'] ?? TRUE),
      (bool)($values['output_unigrams_if_no_shingles'] ?? FALSE),
      $values['token_separator'] ?? ' ',
      $values['filler_token'] ?? '_',
      (bool)($values['generate_word_parts'] ?? TRUE),
      (bool)($values['generate_number_parts'] ?? TRUE),
      (bool)($values['catenate_words'] ?? FALSE),
      (bool)($values['catenate_numbers'] ?? FALSE),
      (bool)($values['catenate_all'] ?? FALSE),
      (bool)($values['split_on_case_change'] ?? TRUE),
      (bool)($values['split_on_numerics'] ?? TRUE),
      (bool)($values['stem_english_possessive'] ?? TRUE),
      $protectedWords,
      ($values['protected_words_path'] ?? '') !== '' ? $values['protected_words_path'] : NULL,
      $typeTable,
      ($values['type_table_path'] ?? '') !== '' ? $values['type_table_path'] : NULL,
      (bool)($values['adjust_offsets'] ?? TRUE),
      (int)($values['min'] ?? 0),
      (int)($values['max'] ?? 2147483647),
      (int)($values['length'] ?? 10),
      (int)($values['max_token_count'] ?? 1),
      (bool)($values['consume_all_tokens'] ?? FALSE),
      ($values['pattern'] ?? '') !== '' ? $values['pattern'] : NULL,
      $values['replacement'] ?? '',
      ($values['flags'] ?? '') !== '' ? $values['flags'] : NULL,
      (bool)($values['all'] ?? TRUE),
      $patterns,
      $keywords,
      ($values['keywords_path'] ?? '') !== '' ? $values['keywords_path'] : NULL,
      ($values['keywords_pattern'] ?? '') !== '' ? $values['keywords_pattern'] : NULL,
      $articles,
      ($values['articles_path'] ?? '') !== '' ? $values['articles_path'] : NULL,
      (bool)($values['articles_case'] ?? FALSE),
      $multiplexerFilters,
      $conditionFilter,
      $script,
      (bool)($values['only_on_same_position'] ?? FALSE),
    );

    $filter->save();
    $this->messenger()->addStatus($this->t('Filter %name saved.', ['%name' => $name]));
    $form_state->setRedirectUrl(Url::fromRoute('eticsearch.filter.list'));
  }

  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('current_route_match'),
      $container->get('eticsearch.factory.config'),
    );
  }
}
