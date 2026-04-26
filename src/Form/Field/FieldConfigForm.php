<?php

namespace Drupal\eticsearch\Form\Field;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;
use Drupal\eticsearch\Index\Factory\FieldFactory;
use Drupal\eticsearch\Manager\FieldManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FieldConfigForm extends FormBase {

  // Non-readonly, untyped properties — required for Drupal's DependencySerializationTrait.
  protected $eticEntityTypeManager;
  protected $eticFieldManager;
  protected $eticConfig;

  public function __construct(
    EntityTypeManagerInterface  $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager,
    ConfigFactory               $eticConfig,
    RouteMatchInterface         $routeMatch,
  ) {
    $this->eticEntityTypeManager = $entityTypeManager;
    $this->eticFieldManager      = $entityFieldManager;
    $this->eticConfig            = $eticConfig;
    $this->routeMatch            = $routeMatch; // inherited from FormBase
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('eticsearch.factory.config'),
      $container->get('current_route_match'),
    );
  }

  public function getFormId(): string {
    return 'eticsearch_field_config_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $content_type = NULL, string $field_name = NULL): array {
    $content_type = $content_type ?? $this->routeMatch->getParameter('content_type');
    $field_name   = $field_name   ?? $this->routeMatch->getParameter('field_name');

    /** @var \Drupal\node\Entity\NodeType $nodeType */
    $nodeType    = $this->eticEntityTypeManager->getStorage('node_type')->load($content_type);
    $fieldDefs   = $this->eticFieldManager->getFieldDefinitions('node', $content_type);
    $fieldDef    = $fieldDefs[$field_name] ?? NULL;
    $drupalType  = $fieldDef ? $fieldDef->getType() : 'string';

    $fields          = $nodeType->getThirdPartySetting('eticsearch', 'fields', []);
    $existingMapping = $fields[$field_name]['mapping'] ?? [];

    $form_state->set('content_type', $content_type);
    $form_state->set('field_name', $field_name);

    $allowedEsTypes = FieldManager::mapFieldTypeToElasticType($drupalType, TRUE);
    if (empty($allowedEsTypes)) {
      $allowedEsTypes = ['text' => 'text'];
    }

    $currentEsType = $form_state->getValue('es_type')
      ?? ($existingMapping['type'] ?? array_key_first($allowedEsTypes));

    // ── Info ──────────────────────────────────────────────────────────────────

    $form['info'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Field information'),
    ];
    $form['info']['ct'] = [
      '#type'   => 'item',
      '#title'  => $this->t('Content type'),
      '#markup' => '<code>' . ($nodeType?->label() ?? $content_type) . '</code> (' . $content_type . ')',
    ];
    $form['info']['fn'] = [
      '#type'   => 'item',
      '#title'  => $this->t('Field name'),
      '#markup' => '<code>' . $field_name . '</code>',
    ];
    $form['info']['dt'] = [
      '#type'        => 'item',
      '#title'       => $this->t('Drupal field type'),
      '#markup'      => '<code>' . $drupalType . '</code>',
    ];

    // ── ES type selector ──────────────────────────────────────────────────────

    $form['es_type'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Elasticsearch mapping type'),
      '#options'       => $allowedEsTypes,
      '#default_value' => $currentEsType,
      '#required'      => TRUE,
      '#ajax'          => [
        'callback' => '::rebuildTypeFields',
        'wrapper'  => 'field-es-type-fields',
        'event'    => 'change',
      ],
    ];

    // ── Type-specific configuration ───────────────────────────────────────────

    $form['config_fields'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'field-es-type-fields'],
    ];

    $this->buildTypeFields($form['config_fields'], $currentEsType, $existingMapping);

    // ── Actions ───────────────────────────────────────────────────────────────

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Save field mapping'),
    ];
    $form['actions']['cancel'] = [
      '#type'       => 'link',
      '#title'      => $this->t('Cancel'),
      '#url'        => Url::fromRoute('eticsearch.field.list'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  // ── Type-specific field builders ──────────────────────────────────────────

  private function buildTypeFields(array &$container, string $esType, array $existing): void {
    if (in_array($esType, FieldFactory::TEXT_TYPES)) {
      $this->buildTextFields($container, $esType, $existing);
    }
    elseif (in_array($esType, FieldFactory::KEYWORD_TYPES)) {
      $this->buildKeywordFields($container, $esType, $existing);
    }
    elseif (in_array($esType, FieldFactory::NUMERIC_TYPES)) {
      $this->buildNumericFields($container, $esType, $existing);
    }
    elseif (in_array($esType, FieldFactory::DATE_TYPES)) {
      $this->buildDateFields($container, $existing);
    }
    elseif (in_array($esType, FieldFactory::BOOLEAN_TYPES)) {
      $this->buildBooleanFields($container, $existing);
    }
    elseif (in_array($esType, FieldFactory::BINARY_TYPES)) {
      $container['info'] = [
        '#markup' => '<p>' . $this->t('Binary fields have no additional configuration.') . '</p>',
      ];
    }
    elseif (in_array($esType, FieldFactory::RANGE_TYPES)) {
      $this->buildRangeFields($container, $existing);
    }
    elseif (in_array($esType, FieldFactory::GEO_TYPES)) {
      $this->buildGeoFields($container, $esType, $existing);
    }
    elseif (in_array($esType, FieldFactory::OTHER_TYPES)) {
      $this->buildCompletionFields($container, $existing);
    }
  }

  private function analyzerOptions(bool $required = FALSE): array {
    $options = $required ? [] : ['' => $this->t('— default —')];
    foreach (array_keys($this->eticConfig->getAnalyzers()) as $name) {
      $options[$name] = $name;
    }
    return $options;
  }

  private function similarityOptions(): array {
    $options = ['' => $this->t('— default (BM25) —')];
    foreach (array_keys($this->eticConfig->getSimilarities()) as $name) {
      $options[$name] = $name;
    }
    return $options;
  }

  private function normalizerOptions(): array {
    $options = ['' => $this->t('— none —')];
    foreach (array_keys($this->eticConfig->getNormalizers()) as $name) {
      $options[$name] = $name;
    }
    return $options;
  }

  private function buildTextFields(array &$c, string $esType, array $d): void {
    $isMatchOnly = $esType === 'match_only_text';

    $c['analyzer'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Analyzer'),
      '#options'       => $this->analyzerOptions(),
      '#default_value' => $d['analyzer'] ?? '',
      '#description'   => $this->t('Used for both indexing and searching. Add analyzers on the <a href="@url">Analyzers</a> page.', [
        '@url' => Url::fromRoute('eticsearch.analyzer.list')->toString(),
      ]),
    ];
    $c['search_analyzer'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Search analyzer'),
      '#options'       => $this->analyzerOptions(),
      '#default_value' => $d['search_analyzer'] ?? '',
      '#description'   => $this->t('Overrides the analyzer for search queries only.'),
    ];
    $c['search_quote_analyzer'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Search quote analyzer'),
      '#options'       => $this->analyzerOptions(),
      '#default_value' => $d['search_quote_analyzer'] ?? '',
      '#description'   => $this->t('Overrides the analyzer for phrase queries.'),
    ];

    if (!$isMatchOnly) {
      $c['similarity'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Similarity'),
        '#options'       => $this->similarityOptions(),
        '#default_value' => $d['similarity'] ?? '',
        '#description'   => $this->t('Scoring algorithm. Add similarities on the <a href="@url">Similarities</a> page.', [
          '@url' => Url::fromRoute('eticsearch.similarity.list')->toString(),
        ]),
      ];
      $c['index'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Index'),
        '#default_value' => $d['index'] ?? TRUE,
        '#description'   => $this->t('Whether the field value is indexed for full-text search.'),
      ];
      $c['norms'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Norms'),
        '#default_value' => $d['norms'] ?? FALSE,
        '#description'   => $this->t('Store field-length normalization factors. Disable to save disk space if scoring by field length is not needed.'),
      ];
      $c['index_phrases'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Index phrases'),
        '#default_value' => $d['index_phrases'] ?? FALSE,
        '#description'   => $this->t('Index 2-shingles to speed up phrase queries. Doubles disk usage.'),
      ];
    }
  }

  private function buildKeywordFields(array &$c, string $esType, array $d): void {
    if ($esType !== 'wildcard') {
      $c['normalizer'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Normalizer'),
        '#options'       => $this->normalizerOptions(),
        '#default_value' => $d['normalizer'] ?? '',
        '#description'   => $this->t('Applied before indexing (e.g. lowercase). Add normalizers via configuration.'),
      ];
      $c['similarity'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Similarity'),
        '#options'       => $this->similarityOptions(),
        '#default_value' => $d['similarity'] ?? '',
      ];
      $c['norms'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Norms'),
        '#default_value' => $d['norms'] ?? FALSE,
      ];
      $c['split_queries_on_whitespace'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Split queries on whitespace'),
        '#default_value' => $d['split_queries_on_whitespace'] ?? FALSE,
        '#description'   => $this->t('Splits query strings on whitespace before searching.'),
      ];
      $c['null_value'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Null value'),
        '#default_value' => $d['null_value'] ?? '',
        '#description'   => $this->t('String to substitute for NULL values. Leave blank to disable.'),
      ];
    }
    else {
      $c['info'] = [
        '#markup' => '<p>' . $this->t('The <code>wildcard</code> type stores the original value and has no additional configuration options.') . '</p>',
      ];
    }
  }

  private function buildNumericFields(array &$c, string $esType, array $d): void {
    $c['index'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Index'),
      '#default_value' => $d['index'] ?? TRUE,
    ];
    if ($esType !== 'unsigned_long') {
      $c['coerce'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Coerce'),
        '#default_value' => $d['coerce'] ?? TRUE,
        '#description'   => $this->t('Convert strings to numbers and truncate fractions for integers.'),
      ];
    }
    $c['null_value'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Null value'),
      '#default_value' => $d['null_value'] ?? '',
      '#description'   => $this->t('Numeric value to substitute for NULL. Leave blank to disable.'),
    ];
  }

  private function buildDateFields(array &$c, array $d): void {
    $c['index'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Index'),
      '#default_value' => $d['index'] ?? TRUE,
    ];
    $c['null_value'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Null value'),
      '#default_value' => $d['null_value'] ?? '',
      '#description'   => $this->t('Date string in <code>strict_date_optional_time</code> format to use for NULL values. Leave blank to disable.'),
    ];
  }

  private function buildBooleanFields(array &$c, array $d): void {
    $c['index'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Index'),
      '#default_value' => $d['index'] ?? TRUE,
    ];
    $nullValue = $d['null_value'] ?? '';
    $c['null_value'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Null value'),
      '#options'       => ['' => $this->t('Disabled'), 'true' => 'true', 'false' => 'false'],
      '#default_value' => $nullValue === TRUE ? 'true' : ($nullValue === FALSE ? 'false' : ''),
      '#description'   => $this->t('Value to substitute for NULL.'),
    ];
  }

  private function buildRangeFields(array &$c, array $d): void {
    $c['index'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Index'),
      '#default_value' => $d['index'] ?? TRUE,
    ];
  }

  private function buildGeoFields(array &$c, string $esType, array $d): void {
    $c['index'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Index'),
      '#default_value' => $d['index'] ?? TRUE,
    ];
    if ($esType === 'geo_shape') {
      $c['coerce'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Coerce'),
        '#default_value' => $d['coerce'] ?? TRUE,
        '#description'   => $this->t('Automatically close unclosed linear rings in polygons.'),
      ];
    }
    if ($esType === 'geo_point') {
      $c['null_value'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Null value'),
        '#default_value' => $d['null_value'] ?? '',
        '#description'   => $this->t('Geo point to substitute for NULL (e.g. <code>0,0</code>). Leave blank to disable.'),
      ];
    }
  }

  private function buildCompletionFields(array &$c, array $d): void {
    $c['analyzer'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Analyzer'),
      '#options'       => $this->analyzerOptions(),
      '#default_value' => $d['analyzer'] ?? '',
    ];
    $c['search_analyzer'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Search analyzer'),
      '#options'       => $this->analyzerOptions(),
      '#default_value' => $d['search_analyzer'] ?? '',
    ];
    $c['preserve_separators'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Preserve separators'),
      '#default_value' => $d['preserve_separators'] ?? TRUE,
    ];
    $c['preserve_position_increments'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Preserve position increments'),
      '#default_value' => $d['preserve_position_increments'] ?? TRUE,
    ];
    $c['max_input_length'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Max input length'),
      '#default_value' => $d['max_input_length'] ?? 50,
      '#min'           => 1,
    ];
  }

  // ── AJAX callback ─────────────────────────────────────────────────────────

  public function rebuildTypeFields(array &$form, FormStateInterface $form_state): array {
    return $form['config_fields'];
  }

  // ── Submit ────────────────────────────────────────────────────────────────

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $contentType = $form_state->get('content_type');
    $fieldName   = $form_state->get('field_name');
    $esType      = $form_state->getValue('es_type');
    $values      = $form_state->getValues();

    $mapping = $this->buildMappingFromValues($esType, $values);

    /** @var \Drupal\node\Entity\NodeType $nodeType */
    $nodeType = $this->eticEntityTypeManager->getStorage('node_type')->load($contentType);
    $fields   = $nodeType->getThirdPartySetting('eticsearch', 'fields', []);

    $fields[$fieldName]['mapping'] = $mapping;
    $nodeType->setThirdPartySetting('eticsearch', 'fields', $fields);
    $nodeType->save();

    $this->messenger()->addStatus($this->t(
      'Field mapping for %field on %type saved.',
      ['%field' => $fieldName, '%type' => $contentType]
    ));
    $form_state->setRedirectUrl(Url::fromRoute('eticsearch.field.list'));
  }

  private function buildMappingFromValues(string $esType, array $v): array {
    $m = ['type' => $esType];

    if (in_array($esType, FieldFactory::TEXT_TYPES)) {
      if (($v['analyzer'] ?? '') !== '')             $m['analyzer']              = $v['analyzer'];
      if (($v['search_analyzer'] ?? '') !== '')      $m['search_analyzer']       = $v['search_analyzer'];
      if (($v['search_quote_analyzer'] ?? '') !== '') $m['search_quote_analyzer'] = $v['search_quote_analyzer'];

      if ($esType !== 'match_only_text') {
        if (($v['similarity'] ?? '') !== '') $m['similarity']     = $v['similarity'];
        $m['index']         = (bool) ($v['index'] ?? TRUE);
        $m['norms']         = (bool) ($v['norms'] ?? FALSE);
        $m['index_phrases'] = (bool) ($v['index_phrases'] ?? FALSE);
      }
    }
    elseif (in_array($esType, FieldFactory::KEYWORD_TYPES) && $esType !== 'wildcard') {
      if (($v['normalizer'] ?? '') !== '') $m['normalizer'] = $v['normalizer'];
      if (($v['similarity'] ?? '') !== '') $m['similarity'] = $v['similarity'];
      $m['norms']                        = (bool) ($v['norms'] ?? FALSE);
      $m['split_queries_on_whitespace']  = (bool) ($v['split_queries_on_whitespace'] ?? FALSE);
      if (($v['null_value'] ?? '') !== '') $m['null_value'] = $v['null_value'];
    }
    elseif (in_array($esType, FieldFactory::NUMERIC_TYPES)) {
      $m['index'] = (bool) ($v['index'] ?? TRUE);
      if ($esType !== 'unsigned_long') {
        $m['coerce'] = (bool) ($v['coerce'] ?? TRUE);
      }
      if (($v['null_value'] ?? '') !== '') $m['null_value'] = (float) $v['null_value'];
    }
    elseif (in_array($esType, FieldFactory::DATE_TYPES)) {
      $m['index']  = (bool) ($v['index'] ?? TRUE);
      if (($v['null_value'] ?? '') !== '') $m['null_value'] = $v['null_value'];
    }
    elseif (in_array($esType, FieldFactory::BOOLEAN_TYPES)) {
      $m['index'] = (bool) ($v['index'] ?? TRUE);
      $raw = $v['null_value'] ?? '';
      if ($raw === 'true')  $m['null_value'] = TRUE;
      if ($raw === 'false') $m['null_value'] = FALSE;
    }
    elseif (in_array($esType, FieldFactory::RANGE_TYPES)) {
      $m['index'] = (bool) ($v['index'] ?? TRUE);
    }
    elseif (in_array($esType, FieldFactory::GEO_TYPES)) {
      $m['index'] = (bool) ($v['index'] ?? TRUE);
      if ($esType === 'geo_shape') {
        $m['coerce'] = (bool) ($v['coerce'] ?? TRUE);
      }
      if ($esType === 'geo_point' && ($v['null_value'] ?? '') !== '') {
        $m['null_value'] = $v['null_value'];
      }
    }
    elseif (in_array($esType, FieldFactory::OTHER_TYPES)) {
      if (($v['analyzer'] ?? '') !== '')        $m['analyzer']        = $v['analyzer'];
      if (($v['search_analyzer'] ?? '') !== '') $m['search_analyzer'] = $v['search_analyzer'];
      $m['preserve_separators']          = (bool) ($v['preserve_separators'] ?? TRUE);
      $m['preserve_position_increments'] = (bool) ($v['preserve_position_increments'] ?? TRUE);
      $m['max_input_length']             = (int)  ($v['max_input_length'] ?? 50);
    }

    return $m;
  }
}
