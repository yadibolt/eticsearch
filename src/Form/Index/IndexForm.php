<?php

namespace Drupal\eticsearch\Form\Index;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;
use Drupal\eticsearch\Index\Analyzer;
use Drupal\eticsearch\Index\CharFilter;
use Drupal\eticsearch\Index\Factory\IndexFactory;
use Drupal\eticsearch\Index\Filter;
use Drupal\eticsearch\Index\Normalizer;
use Drupal\eticsearch\Index\Similarity;
use Drupal\eticsearch\Index\Tokenizer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IndexForm extends FormBase {

  protected $eticConfig;

  public function __construct(ConfigFactory $eticConfig, RouteMatchInterface $routeMatch) {
    $this->eticConfig = $eticConfig;
    $this->routeMatch = $routeMatch;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('eticsearch.factory.config'),
      $container->get('current_route_match'),
    );
  }

  public function getFormId(): string {
    return 'eticsearch_index_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $name = NULL): array {
    $name     = $name ?? $this->routeMatch->getParameter('name');
    $existing = $this->eticConfig->getIndices()[$name] ?? [];
    $isNew    = empty($existing);
    $opts     = $existing['options'] ?? [];

    $form_state->set('index_name', $name);

    $form['#attached']['library'][] = 'eticsearch/admin_forms';

    $form['columns'] = [
      '#type'       => 'container',
      '#attributes' => ['class' => ['eticsearch-form-columns']],
    ];

    // ── LEFT COLUMN ──────────────────────────────────────────────────────────

    $left = &$form['columns']['left'];
    $left = ['#type' => 'container', '#attributes' => ['class' => ['eticsearch-form-left']]];

    $left['name_display'] = [
      '#type'   => 'item',
      '#title'  => $this->t('Index name'),
      '#markup' => '<code>' . $name . '</code>',
    ];

    // Static settings (locked after creation in ES, but editable in config)
    $left['static'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Static settings'),
      '#description' => $this->t('These settings require index recreation in Elasticsearch when changed.'),
      '#open'        => TRUE,
    ];
    $left['static']['number_of_shards'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Number of shards'),
      '#default_value' => $opts['number_of_shards'] ?? 1,
      '#min'           => 1,
      '#max'           => 1024,
      '#required'      => TRUE,
      '#description'   => $this->t('Primary shards for this index. Cannot be changed without reindexing.'),
    ];
    $left['static']['codec'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Codec'),
      '#options'       => ['default' => 'default (LZ4, fast)', 'best_compression' => 'best_compression (ZSTD, ~28% smaller)'],
      '#default_value' => $opts['codec'] ?? 'default',
    ];
    $left['static']['store_type'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Store type'),
      '#options'       => [
        'hybridfs' => 'hybridfs (default)',
        'niofs'    => 'niofs',
        'mmapfs'   => 'mmapfs',
        'fs'       => 'fs',
      ],
      '#default_value' => $opts['store_type'] ?? 'hybridfs',
    ];

    // Dynamic settings
    $left['dynamic'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Dynamic settings'),
      '#description' => $this->t('Can be updated on a live index without recreation.'),
      '#open'        => TRUE,
    ];
    $left['dynamic']['number_of_replicas'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Number of replicas'),
      '#default_value' => $opts['number_of_replicas'] ?? 1,
      '#min'           => 0,
    ];
    $left['dynamic']['auto_expand_replicas'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Auto-expand replicas'),
      '#default_value' => ($opts['auto_expand_replicas'] ?? FALSE) === FALSE ? '' : $opts['auto_expand_replicas'],
      '#description'   => $this->t('Format: <code>0-5</code> or <code>0-all</code>. Leave blank to disable.'),
      '#size'          => 15,
    ];
    $left['dynamic']['refresh_interval'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Refresh interval'),
      '#default_value' => $opts['refresh_interval'] ?? '1s',
      '#size'          => 10,
      '#description'   => $this->t('How often to refresh for new documents. Use <code>-1</code> to disable.'),
    ];
    $left['dynamic']['max_result_window'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Max result window'),
      '#default_value' => $opts['max_result_window'] ?? 10000,
      '#min'           => 1,
    ];
    $left['dynamic']['gc_deletes'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('GC deletes'),
      '#default_value' => $opts['gc_deletes'] ?? '60s',
      '#size'          => 10,
    ];
    $left['dynamic']['priority'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Recovery priority'),
      '#default_value' => $opts['priority'] ?? 1,
      '#min'           => 0,
    ];

    // Advanced limits (collapsed by default)
    $left['limits'] = [
      '#type'  => 'details',
      '#title' => $this->t('Advanced limits'),
      '#open'  => FALSE,
    ];
    foreach ([
      'max_docvalue_fields_search' => ['Max docvalue fields search', 100],
      'max_script_fields'          => ['Max script fields', 32],
      'max_ngram_diff'             => ['Max n-gram diff', 1],
      'max_terms_count'            => ['Max terms count', 65536],
      'max_regex_length'           => ['Max regex length', 1000],
      'mapping_total_fields_limit' => ['Mapping total fields limit', 1000],
      'mapping_depth_limit'        => ['Mapping depth limit', 20],
      'mapping_nested_fields_limit' => ['Mapping nested fields limit', 50],
      'mapping_nested_objects_limit' => ['Mapping nested objects limit', 10000],
    ] as $key => [$label, $default]) {
      $left['limits'][$key] = [
        '#type'          => 'number',
        '#title'         => $this->t($label),
        '#default_value' => $opts[$key] ?? $default,
        '#min'           => 0,
      ];
    }
    $left['limits']['mapping_field_name_length_limit'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Mapping field name length limit'),
      '#default_value' => $opts['mapping_field_name_length_limit'] ?? '',
      '#description'   => $this->t('Leave blank for no limit.'),
      '#min'           => 1,
    ];

    // Analysis component selection
    $existingAnalysis = $existing['analysis'] ?? [];
    $existingSimilarities = array_keys($existing['similarities'] ?? []);

    $left['analysis'] = [
      '#type'  => 'details',
      '#title' => $this->t('Analysis components'),
      '#open'  => TRUE,
    ];
    $this->buildComponentCheckboxes($left['analysis'], 'analyzers',    $this->eticConfig->getAnalyzers(),   array_keys($existingAnalysis['analyzer'] ?? []));
    $this->buildComponentCheckboxes($left['analysis'], 'tokenizers',   $this->eticConfig->getTokenizers(),  array_keys($existingAnalysis['tokenizer'] ?? []));
    $this->buildComponentCheckboxes($left['analysis'], 'filters',      $this->eticConfig->getFilters(),     array_keys($existingAnalysis['filter'] ?? []));
    $this->buildComponentCheckboxes($left['analysis'], 'char_filters', $this->eticConfig->getCharFilters(), array_keys($existingAnalysis['char_filter'] ?? []));
    $this->buildComponentCheckboxes($left['analysis'], 'normalizers',  $this->eticConfig->getNormalizers(), array_keys($existingAnalysis['normalizer'] ?? []));
    $this->buildComponentCheckboxes($left['analysis'], 'similarities', $this->eticConfig->getSimilarities(), $existingSimilarities);

    // Submit inside left column + refresh button
    $left['actions'] = ['#type' => 'actions'];
    $left['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $isNew ? $this->t('Create index') : $this->t('Save index'),
    ];
    $left['actions']['cancel'] = [
      '#type'       => 'link',
      '#title'      => $this->t('Cancel'),
      '#url'        => Url::fromRoute('eticsearch.index.list'),
      '#attributes' => ['class' => ['button']],
    ];
    $left['actions']['refresh'] = [
      '#type'                    => 'submit',
      '#value'                   => $this->t('Refresh preview'),
      '#submit'                  => [],
      '#limit_validation_errors' => [],
      '#ajax'                    => [
        'callback' => '::refreshPreview',
        'wrapper'  => 'eticsearch-index-preview',
      ],
    ];

    // ── RIGHT COLUMN ─────────────────────────────────────────────────────────

    $form['columns']['right'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'eticsearch-index-preview', 'class' => ['eticsearch-form-right']],
    ];
    $form['columns']['right']['label'] = [
      '#markup' => '<span class="eticsearch-json-preview-label">' . $this->t('Index settings (JSON preview)') . '</span>',
    ];
    $form['columns']['right']['json'] = [
      '#markup' => '<pre class="eticsearch-json-preview">' . $this->buildPreviewJson($existing) . '</pre>',
    ];

    return $form;
  }

  private function buildComponentCheckboxes(array &$container, string $key, array $available, array $selected): void {
    if (empty($available)) return;
    $options = array_combine(array_keys($available), array_keys($available));
    $container[$key] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t(ucwords(str_replace('_', ' ', $key))),
      '#options'       => $options,
      '#default_value' => $selected,
    ];
  }

  private function buildPreviewJson(array $existing): string {
    if (empty($existing)) {
      $preview = [
        'settings' => [
          'number_of_shards'  => 1,
          'codec'             => 'default',
          'number_of_replicas' => 1,
          'refresh_interval'  => '1s',
        ],
        'analysis' => new \stdClass(),
        'mappings' => new \stdClass(),
      ];
    }
    else {
      $opts = $existing['options'] ?? [];
      $preview = [
        'settings' => array_filter([
          'number_of_shards'   => $opts['number_of_shards'] ?? 1,
          'codec'              => $opts['codec'] ?? 'default',
          'store_type'         => $opts['store_type'] ?? 'hybridfs',
          'number_of_replicas' => $opts['number_of_replicas'] ?? 1,
          'refresh_interval'   => $opts['refresh_interval'] ?? '1s',
          'max_result_window'  => $opts['max_result_window'] ?? 10000,
          'gc_deletes'         => $opts['gc_deletes'] ?? '60s',
        ]),
        'analysis'     => $existing['analysis'] ?? new \stdClass(),
        'similarities' => $existing['similarities'] ?? new \stdClass(),
        'mappings'     => $existing['mappings'] ?? new \stdClass(),
      ];
    }
    return htmlspecialchars(json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
  }

  public function refreshPreview(array &$form, FormStateInterface $form_state): array {
    $values = $form_state->getValues();
    $name   = $form_state->get('index_name');

    // Build a preview from current form values
    $preview = [
      'settings' => [
        'number_of_shards'   => (int) ($values['number_of_shards'] ?? 1),
        'codec'              => $values['codec'] ?? 'default',
        'store_type'         => $values['store_type'] ?? 'hybridfs',
        'number_of_replicas' => (int) ($values['number_of_replicas'] ?? 1),
        'refresh_interval'   => $values['refresh_interval'] ?? '1s',
        'max_result_window'  => (int) ($values['max_result_window'] ?? 10000),
        'gc_deletes'         => $values['gc_deletes'] ?? '60s',
      ],
      'analysis' => [
        'analyzer'    => array_values(array_filter($values['analyzers'] ?? [])),
        'tokenizer'   => array_values(array_filter($values['tokenizers'] ?? [])),
        'filter'      => array_values(array_filter($values['filters'] ?? [])),
        'char_filter' => array_values(array_filter($values['char_filters'] ?? [])),
        'normalizer'  => array_values(array_filter($values['normalizers'] ?? [])),
      ],
      'similarities' => array_values(array_filter($values['similarities'] ?? [])),
    ];

    $form['columns']['right']['json']['#markup'] =
      '<pre class="eticsearch-json-preview">' .
      htmlspecialchars(json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) .
      '</pre>';

    return $form['columns']['right'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $name   = $form_state->get('index_name');
    $values = $form_state->getValues();

    $opts = [
      'number_of_shards'             => (int) ($values['number_of_shards'] ?? 1),
      'codec'                        => $values['codec'] ?? 'default',
      'store_type'                   => $values['store_type'] ?? 'hybridfs',
      'number_of_replicas'           => (int) ($values['number_of_replicas'] ?? 1),
      'auto_expand_replicas'         => ($values['auto_expand_replicas'] ?? '') !== '' ? $values['auto_expand_replicas'] : FALSE,
      'refresh_interval'             => $values['refresh_interval'] ?? '1s',
      'max_result_window'            => (int) ($values['max_result_window'] ?? 10000),
      'max_docvalue_fields_search'   => (int) ($values['max_docvalue_fields_search'] ?? 100),
      'max_script_fields'            => (int) ($values['max_script_fields'] ?? 32),
      'max_ngram_diff'               => (int) ($values['max_ngram_diff'] ?? 1),
      'max_terms_count'              => (int) ($values['max_terms_count'] ?? 65536),
      'max_regex_length'             => (int) ($values['max_regex_length'] ?? 1000),
      'gc_deletes'                   => $values['gc_deletes'] ?? '60s',
      'priority'                     => (int) ($values['priority'] ?? 1),
      'mapping_total_fields_limit'   => (int) ($values['mapping_total_fields_limit'] ?? 1000),
      'mapping_depth_limit'          => (int) ($values['mapping_depth_limit'] ?? 20),
      'mapping_nested_fields_limit'  => (int) ($values['mapping_nested_fields_limit'] ?? 50),
      'mapping_nested_objects_limit' => (int) ($values['mapping_nested_objects_limit'] ?? 10000),
      'mapping_field_name_length_limit' => ($values['mapping_field_name_length_limit'] ?? '') !== '' ? (int) $values['mapping_field_name_length_limit'] : NULL,
    ];

    $analyzers   = $this->loadComponents('analyzers',    $values, fn($n) => $this->loadAnalyzer($n));
    $tokenizers  = $this->loadComponents('tokenizers',   $values, fn($n) => Tokenizer::load('single', $n));
    $filters     = $this->loadComponents('filters',      $values, fn($n) => Filter::load('single', $n));
    $charFilters = $this->loadComponents('char_filters', $values, fn($n) => CharFilter::load('single', $n));
    $normalizers = $this->loadComponents('normalizers',  $values, fn($n) => Normalizer::load('single', $n));
    $similarities = $this->loadComponents('similarities', $values, fn($n) => Similarity::load('single', $n));

    $index = IndexFactory::create($name, NULL, $analyzers, $tokenizers, $filters, $charFilters, $normalizers, $similarities, $opts);
    $saved = $index->save();

    if ($saved) {
      $this->messenger()->addStatus($this->t('Index %name saved and created in Elasticsearch.', ['%name' => $name]));
    }
    else {
      $this->messenger()->addWarning($this->t('Index %name configuration saved, but could not be created in Elasticsearch. Check the connection settings.', ['%name' => $name]));
    }

    $form_state->setRedirectUrl(Url::fromRoute('eticsearch.index.list'));
  }

  private function loadComponents(string $key, array $values, callable $loader): array {
    $selected = array_values(array_filter($values[$key] ?? []));
    return array_values(array_filter(array_map($loader, $selected)));
  }

  private function loadAnalyzer(string $name): ?Analyzer {
    $raw = $this->eticConfig->getAnalyzers()[$name] ?? NULL;
    if (!$raw) return NULL;

    if ($raw['type'] === 'custom') {
      $tokenizerName = $raw['tokenizer'] ?? NULL;
      $tokenizer     = $tokenizerName ? Tokenizer::load('single', $tokenizerName) : NULL;
      $charFilters   = array_filter(array_map(fn($n) => CharFilter::load('single', $n), $raw['char_filter'] ?? []));
      $filters       = array_filter(array_map(fn($n) => Filter::load('single', $n), $raw['filter'] ?? []));
      return Analyzer::create($name, 'custom', NULL, 255, NULL, NULL, TRUE, ' ', 255, NULL, [], $tokenizer, array_values($charFilters), array_values($filters));
    }

    return Analyzer::fromArray($raw);
  }
}
