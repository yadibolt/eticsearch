<?php

namespace Drupal\eticsearch\Form\Analysis;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;
use Drupal\eticsearch\Index\Analyzer;
use Drupal\eticsearch\Index\CharFilter;
use Drupal\eticsearch\Index\Filter;
use Drupal\eticsearch\Index\Tokenizer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AnalyzerForm extends FormBase
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
    return 'eticsearch_analyzer_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $name = NULL): array
  {
    $name = $name ?? $this->routeMatch->getParameter('name');

    $rawAnalyzers = $this->eticConfig->getAnalyzers();
    $data = $rawAnalyzers[$name] ?? [];
    $isNew = empty($data);

    $form_state->set('analyzer_name', $name);

    $currentType = $form_state->getValue('type') ?? ($data['type'] ?? 'standard');

    $form['name_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Machine name'),
      '#markup' => '<code>' . $name . '</code>',
    ];

    $typeOptions = [];
    foreach (Analyzer::CONFIGURABLE_ANALYZER_TYPES as $t) {
      $typeOptions[$t] = ucfirst($t);
    }

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $typeOptions,
      '#default_value' => $currentType,
      '#disabled' => !$isNew,
      '#ajax' => [
        'callback' => '::rebuildTypeFields',
        'wrapper' => 'analyzer-type-fields',
        'event' => 'change',
      ],
    ];

    $form['config_fields'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'analyzer-type-fields'],
    ];

    $this->buildTypeFields($form['config_fields'], $currentType, $data);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save analyzer'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('eticsearch.analyzer.list'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  private function buildTypeFields(array &$container, string $type, array $data): void
  {
    switch ($type) {
      case 'standard':
        $container['max_token_length'] = [
          '#type' => 'number',
          '#title' => $this->t('Max token length'),
          '#default_value' => $data['max_token_length'] ?? 255,
          '#min' => 1,
        ];
        $container['stopwords'] = $this->buildStopwordsField($data['stopwords'] ?? NULL);
        break;

      case 'stop':
        $container['stopwords'] = $this->buildStopwordsField($data['stopwords'] ?? NULL);
        break;

      case 'pattern':
        $container['pattern'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Pattern (Java regex)'),
          '#default_value' => $data['pattern'] ?? '\W+',
          '#required' => TRUE,
        ];
        $container['flags'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Flags'),
          '#default_value' => $data['flags'] ?? '',
          '#description' => $this->t('Pipe-separated Java regex flags.'),
        ];
        $container['lowercase'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Lowercase tokens'),
          '#default_value' => $data['lowercase'] ?? TRUE,
        ];
        $container['stopwords'] = $this->buildStopwordsField($data['stopwords'] ?? NULL);
        break;

      case 'fingerprint':
        $container['separator'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Separator'),
          '#default_value' => $data['separator'] ?? ' ',
          '#size' => 5,
          '#description' => $this->t('Character used to concatenate token terms.'),
        ];
        $container['max_output_size'] = [
          '#type' => 'number',
          '#title' => $this->t('Max output size'),
          '#default_value' => $data['max_output_size'] ?? 255,
          '#min' => 1,
        ];
        $container['stopwords'] = $this->buildStopwordsField($data['stopwords'] ?? NULL);
        break;

      case 'language':
        $langOptions = [];
        foreach (Analyzer::LANGUAGE_TYPES as $lang) {
          $langOptions[$lang] = ucfirst($lang);
        }
        $container['language'] = [
          '#type' => 'select',
          '#title' => $this->t('Language'),
          '#options' => $langOptions,
          '#default_value' => $data['language'] ?? 'english',
          '#required' => TRUE,
        ];
        $container['stopwords'] = $this->buildStopwordsField($data['stopwords'] ?? NULL);
        $container['stem_exclusion'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Stem exclusion'),
          '#default_value' => implode("\n", $data['stem_exclusion'] ?? []),
          '#description' => $this->t('Words that should not be stemmed, one per line.'),
          '#rows' => 4,
        ];
        break;

      case 'custom':
        $this->buildCustomAnalyzerFields($container, $data);
        break;
    }
  }

  private function buildStopwordsField(mixed $stopwords): array
  {
    $default = '';
    if ($stopwords !== NULL) {
      $default = is_array($stopwords) ? implode("\n", $stopwords) : $stopwords;
    }
    return [
      '#type' => 'textarea',
      '#title' => $this->t('Stopwords'),
      '#default_value' => $default,
      '#description' => $this->t('Optional. Enter a language preset (e.g. <code>_english_</code>) on a single line, or one stop word per line for a custom list. Leave empty to disable.'),
      '#rows' => 4,
    ];
  }

  private function buildCustomAnalyzerFields(array &$container, array $data): void
  {
    $tokenizers = $this->eticConfig->getTokenizers();
    $tokenizerOptions = ['' => $this->t('— none (defaults to standard) —')];
    foreach (array_keys($tokenizers) as $t) {
      $tokenizerOptions[$t] = $t;
    }

    $container['tokenizer'] = [
      '#type' => 'select',
      '#title' => $this->t('Tokenizer'),
      '#options' => $tokenizerOptions,
      '#default_value' => $data['tokenizer'] ?? '',
      '#description' => $this->t('The tokenizer to use. Add tokenizers on the <a href="@url">Tokenizers</a> page.', [
        '@url' => Url::fromRoute('eticsearch.tokenizer.list')->toString(),
      ]),
    ];

    $charFilters = $this->eticConfig->getCharFilters();
    if (!empty($charFilters)) {
      $cfOptions = array_combine(array_keys($charFilters), array_keys($charFilters));
      $container['char_filter'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Character filters'),
        '#options' => $cfOptions,
        '#default_value' => $data['char_filter'] ?? [],
        '#description' => $this->t('Applied before tokenization, in the order shown.'),
      ];
    } else {
      $container['char_filter_empty'] = [
        '#markup' => '<p>' . $this->t('No character filters available. <a href="@url">Add one</a>.', [
            '@url' => Url::fromRoute('eticsearch.char_filter.add')->toString(),
          ]) . '</p>',
      ];
    }

    $filters = $this->eticConfig->getFilters();
    if (!empty($filters)) {
      $fOptions = array_combine(array_keys($filters), array_keys($filters));
      $container['filter'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Token filters'),
        '#options' => $fOptions,
        '#default_value' => $data['filter'] ?? [],
        '#description' => $this->t('Applied after tokenization. <strong>Order matters:</strong> filters are applied top-to-bottom as listed.'),
      ];
    } else {
      $container['filter_empty'] = [
        '#markup' => '<p>' . $this->t('No token filters available. <a href="@url">Add one</a>.', [
            '@url' => Url::fromRoute('eticsearch.filter.add')->toString(),
          ]) . '</p>',
      ];
    }
  }

  public function rebuildTypeFields(array &$form, FormStateInterface $form_state): array
  {
    return $form['config_fields'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $name = $form_state->get('analyzer_name');
    $type = $form_state->getValue('type');
    $values = $form_state->getValues();

    if ($type === 'custom') {
      $this->saveCustomAnalyzer($name, $values);
    } else {
      $this->saveBuiltinAnalyzer($name, $type, $values);
    }

    $this->messenger()->addStatus($this->t('Analyzer %name saved.', ['%name' => $name]));
    $form_state->setRedirectUrl(Url::fromRoute('eticsearch.analyzer.list'));
  }

  private function saveCustomAnalyzer(string $name, array $values): void
  {
    $tokenizerName = ($values['tokenizer'] ?? '') !== '' ? $values['tokenizer'] : NULL;
    $charFilterNames = array_values(array_filter($values['char_filter'] ?? []));
    $filterNames = array_values(array_filter($values['filter'] ?? []));

    $tokenizer = $tokenizerName ? Tokenizer::load('single', $tokenizerName) : NULL;
    $charFilters = array_values(array_filter(array_map(fn($n) => CharFilter::load('single', $n), $charFilterNames)));
    $filters = array_values(array_filter(array_map(fn($n) => Filter::load('single', $n), $filterNames)));

    $analyzer = Analyzer::create($name, 'custom', NULL, 255, NULL, NULL, TRUE, ' ', 255, NULL, [], $tokenizer, $charFilters, $filters);
    $analyzer->save();
  }

  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('current_route_match'),
      $container->get('eticsearch.factory.config'),
    );
  }

  private function saveBuiltinAnalyzer(string $name, string $type, array $values): void
  {
    $stopwords = $this->parseStopwords($values['stopwords'] ?? '');
    $stemExclusion = array_values(array_filter(array_map('trim', explode("\n", $values['stem_exclusion'] ?? ''))));

    $analyzer = Analyzer::create(
      $name,
      $type,
      $stopwords,
      (int)($values['max_token_length'] ?? 255),
      ($values['pattern'] ?? '') !== '' ? $values['pattern'] : NULL,
      ($values['flags'] ?? '') !== '' ? $values['flags'] : NULL,
      (bool)($values['lowercase'] ?? TRUE),
      $values['separator'] ?? ' ',
      (int)($values['max_output_size'] ?? 255),
      ($values['language'] ?? '') !== '' ? $values['language'] : NULL,
      $stemExclusion,
    );

    $analyzer->save();
  }

  private function parseStopwords(string $raw): null|string|array
  {
    $raw = trim($raw);
    $lines = array_values(array_filter(array_map('trim', explode("\n", $raw))));

    if (empty($lines)) {
      return NULL;
    }
    if (count($lines) === 1 && preg_match('/^_[a-z]+_$/', $lines[0])) {
      return $lines[0];
    }
    return $lines;
  }
}
