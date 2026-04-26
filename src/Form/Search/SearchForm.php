<?php

namespace Drupal\eticsearch\Form\Search;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;
use Drupal\eticsearch\Search\Factory\SearchFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchForm extends FormBase {

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
    return 'eticsearch_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $index_name = NULL, string $search_name = NULL): array {
    $indexName  = $index_name  ?? $this->routeMatch->getParameter('index_name');
    $searchName = $search_name ?? $this->routeMatch->getParameter('search_name');

    $existing = SearchFactory::load($indexName, $searchName);
    $data     = $existing ? $existing->toArray() : [];

    $form_state->set('index_name', $indexName);
    $form_state->set('search_name', $searchName);

    $form['#attached']['library'][] = 'eticsearch/admin_forms';

    $form['columns'] = [
      '#type'       => 'container',
      '#attributes' => ['class' => ['eticsearch-form-columns']],
    ];

    // ── LEFT COLUMN ──────────────────────────────────────────────────────────

    $left = &$form['columns']['left'];
    $left = ['#type' => 'container', '#attributes' => ['class' => ['eticsearch-form-left']]];

    $left['info'] = [
      '#type'  => 'item',
      '#title' => $this->t('Index / Search'),
      '#markup' => '<code>' . $indexName . '</code> / <code>' . $searchName . '</code>',
    ];

    // Pagination
    $left['pagination'] = [
      '#type'  => 'details',
      '#title' => $this->t('Pagination'),
      '#open'  => TRUE,
    ];
    $left['pagination']['size'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Size (results per page)'),
      '#default_value' => $data['size'] ?? 10,
      '#min'           => 0,
      '#required'      => TRUE,
    ];
    $left['pagination']['from'] = [
      '#type'          => 'number',
      '#title'         => $this->t('From (offset)'),
      '#default_value' => $data['from'] ?? 0,
      '#min'           => 0,
    ];

    // Query
    $left['query_section'] = [
      '#type'  => 'details',
      '#title' => $this->t('Query'),
      '#open'  => TRUE,
    ];
    $left['query_section']['query'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Query (JSON)'),
      '#default_value' => isset($data['query']) ? json_encode($data['query'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
      '#rows'          => 12,
      '#description'   => $this->t('Elasticsearch query DSL as JSON. Use <code>$e%user_input%e$</code> as placeholder for user-provided search terms.'),
      '#attributes'    => ['class' => ['eticsearch-json-input']],
    ];

    // Source
    $left['source_section'] = [
      '#type'  => 'details',
      '#title' => $this->t('Source fields'),
      '#open'  => FALSE,
    ];
    $sourceRaw = $data['_source'] ?? TRUE;
    $sourceMode = is_array($sourceRaw) ? 'list' : ($sourceRaw === FALSE ? 'none' : 'all');
    $left['source_section']['source_mode'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Source mode'),
      '#options'       => [
        'all'  => $this->t('All fields'),
        'none' => $this->t('No source (disabled)'),
        'list' => $this->t('Specific fields'),
      ],
      '#default_value' => $sourceMode,
    ];
    $left['source_section']['source_fields'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Fields'),
      '#default_value' => is_array($sourceRaw) ? implode(', ', $sourceRaw) : '',
      '#description'   => $this->t('Comma-separated field names. Only used when mode is "Specific fields".'),
      '#states'        => ['visible' => [':input[name="source_mode"]' => ['value' => 'list']]],
    ];

    // Options
    $left['options_section'] = [
      '#type'  => 'details',
      '#title' => $this->t('Options'),
      '#open'  => FALSE,
    ];
    $left['options_section']['min_score'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Minimum score'),
      '#default_value' => $data['min_score'] ?? '',
      '#step'          => 0.01,
      '#min'           => 0,
      '#description'   => $this->t('Exclude results below this relevance score. Leave blank to disable.'),
    ];
    $left['options_section']['timeout'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Timeout'),
      '#default_value' => $data['timeout'] ?? '',
      '#size'          => 10,
      '#description'   => $this->t('Search timeout, e.g. <code>5s</code>, <code>500ms</code>. Leave blank for no limit.'),
    ];
    $trackRaw = $data['track_total_hits'] ?? NULL;
    $left['options_section']['track_total_hits'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Track total hits'),
      '#default_value' => $trackRaw !== NULL ? (string) $trackRaw : '',
      '#size'          => 10,
      '#description'   => $this->t('Set to <code>true</code>, <code>false</code>, or an integer threshold. Leave blank to use default.'),
    ];

    // Collapse
    $left['collapse_section'] = [
      '#type'  => 'details',
      '#title' => $this->t('Result collapsing'),
      '#open'  => FALSE,
    ];
    $left['collapse_section']['collapse_field'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Collapse field'),
      '#default_value' => $data['collapse']['field'] ?? '',
      '#description'   => $this->t('Deduplicate results by this keyword field. Leave blank to disable.'),
    ];
    $innerHits = $data['collapse']['inner_hits'] ?? NULL;
    $left['collapse_section']['collapse_inner_hits'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Inner hits (JSON)'),
      '#default_value' => $innerHits ? json_encode($innerHits, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
      '#rows'          => 4,
      '#description'   => $this->t('Optional inner hits configuration as JSON.'),
    ];

    // Completion suggest
    $left['suggest_section'] = [
      '#type'  => 'details',
      '#title' => $this->t('Completion suggestions'),
      '#open'  => FALSE,
    ];
    $left['suggest_section']['suggest'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Suggest (JSON)'),
      '#default_value' => !empty($data['suggest']) ? json_encode($data['suggest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
      '#rows'          => 6,
      '#description'   => $this->t('Completion suggest configuration as JSON. The prefix <code>$e%user_input%e$</code> is substituted with user input at query time.'),
    ];

    // Highlighting
    $left['highlight_section'] = [
      '#type'  => 'details',
      '#title' => $this->t('Highlighting'),
      '#open'  => FALSE,
    ];
    $hl = $data['highlight'] ?? NULL;
    $left['highlight_section']['highlight_pre_tags'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Pre-tag'),
      '#default_value' => $hl['pre_tags'][0] ?? '<em>',
      '#size'          => 20,
    ];
    $left['highlight_section']['highlight_post_tags'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Post-tag'),
      '#default_value' => $hl['post_tags'][0] ?? '</em>',
      '#size'          => 20,
    ];
    $hlFields = $hl ? $hl : NULL;
    unset($hlFields['pre_tags'], $hlFields['post_tags'], $hlFields['fields']);
    $left['highlight_section']['highlight_fields'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Fields to highlight'),
      '#default_value' => $hl ? implode(', ', array_keys($hl['fields'] ?? [])) : '',
      '#description'   => $this->t('Comma-separated field names. Leave blank to disable highlighting.'),
    ];
    $left['highlight_section']['highlight_options'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Global highlight options (JSON)'),
      '#default_value' => $hlFields ? json_encode($hlFields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
      '#rows'          => 4,
      '#description'   => $this->t('Additional global highlight options as JSON (e.g. <code>{"number_of_fragments": 3}</code>).'),
    ];

    // Actions
    $left['actions'] = ['#type' => 'actions'];
    $left['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Save search'),
    ];
    $left['actions']['cancel'] = [
      '#type'       => 'link',
      '#title'      => $this->t('Cancel'),
      '#url'        => Url::fromRoute('eticsearch.search.list'),
      '#attributes' => ['class' => ['button']],
    ];
    $left['actions']['refresh'] = [
      '#type'                    => 'submit',
      '#value'                   => $this->t('Refresh preview'),
      '#submit'                  => [],
      '#limit_validation_errors' => [],
      '#ajax'                    => [
        'callback' => '::refreshPreview',
        'wrapper'  => 'eticsearch-search-preview',
      ],
    ];

    // ── RIGHT COLUMN ─────────────────────────────────────────────────────────

    $form['columns']['right'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'eticsearch-search-preview', 'class' => ['eticsearch-form-right']],
    ];
    $form['columns']['right']['label'] = [
      '#markup' => '<span class="eticsearch-json-preview-label">' . $this->t('Search body (JSON preview)') . '</span>',
    ];
    $form['columns']['right']['json'] = [
      '#markup' => '<pre class="eticsearch-json-preview">' . $this->buildPreviewJson($data) . '</pre>',
    ];

    return $form;
  }

  private function buildPreviewJson(array $data): string {
    if (empty($data)) {
      $preview = ['size' => 10, 'from' => 0, 'query' => ['match_all' => new \stdClass()]];
    }
    else {
      $preview = $data;
    }
    return htmlspecialchars(json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
  }

  public function refreshPreview(array &$form, FormStateInterface $form_state): array {
    $preview = $this->buildSearchArrayFromValues($form_state->getValues());
    $form['columns']['right']['json']['#markup'] =
      '<pre class="eticsearch-json-preview">' .
      htmlspecialchars(json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) .
      '</pre>';
    return $form['columns']['right'];
  }

  private function buildSearchArrayFromValues(array $v): array {
    $body = [
      'size' => (int) ($v['size'] ?? 10),
      'from' => (int) ($v['from'] ?? 0),
    ];

    $queryJson = trim($v['query'] ?? '');
    if ($queryJson !== '') {
      $decoded = json_decode($queryJson, TRUE);
      if ($decoded !== NULL) $body['query'] = $decoded;
    }

    $mode = $v['source_mode'] ?? 'all';
    if ($mode === 'none') {
      $body['_source'] = FALSE;
    }
    elseif ($mode === 'list' && ($v['source_fields'] ?? '') !== '') {
      $body['_source'] = array_map('trim', explode(',', $v['source_fields']));
    }

    if (($v['min_score'] ?? '') !== '') {
      $body['min_score'] = (float) $v['min_score'];
    }
    if (($v['timeout'] ?? '') !== '') {
      $body['timeout'] = $v['timeout'];
    }
    if (($v['track_total_hits'] ?? '') !== '') {
      $raw = $v['track_total_hits'];
      if ($raw === 'true') $body['track_total_hits'] = TRUE;
      elseif ($raw === 'false') $body['track_total_hits'] = FALSE;
      elseif (is_numeric($raw)) $body['track_total_hits'] = (int) $raw;
    }

    $collapseField = trim($v['collapse_field'] ?? '');
    if ($collapseField !== '') {
      $collapse = ['field' => $collapseField];
      $innerHitsJson = trim($v['collapse_inner_hits'] ?? '');
      if ($innerHitsJson !== '') {
        $decoded = json_decode($innerHitsJson, TRUE);
        if ($decoded !== NULL) $collapse['inner_hits'] = $decoded;
      }
      $body['collapse'] = $collapse;
    }

    $suggestJson = trim($v['suggest'] ?? '');
    if ($suggestJson !== '') {
      $decoded = json_decode($suggestJson, TRUE);
      if ($decoded !== NULL) $body['suggest'] = $decoded;
    }

    $hlFields = array_filter(array_map('trim', explode(',', $v['highlight_fields'] ?? '')));
    if (!empty($hlFields)) {
      $fields = array_combine($hlFields, array_fill(0, count($hlFields), new \stdClass()));
      $hlBody = [
        'pre_tags'  => [$v['highlight_pre_tags'] ?? '<em>'],
        'post_tags' => [$v['highlight_post_tags'] ?? '</em>'],
        'fields'    => $fields,
      ];
      $hlOptions = trim($v['highlight_options'] ?? '');
      if ($hlOptions !== '') {
        $decoded = json_decode($hlOptions, TRUE);
        if ($decoded !== NULL) $hlBody = array_merge($hlBody, $decoded);
      }
      $body['highlight'] = $hlBody;
    }

    return $body;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    foreach (['query', 'collapse_inner_hits', 'suggest', 'highlight_options'] as $field) {
      $raw = trim($form_state->getValue($field) ?? '');
      if ($raw !== '' && json_decode($raw) === NULL) {
        $form_state->setErrorByName($field, $this->t('Invalid JSON in %field.', ['%field' => $field]));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $indexName  = $form_state->get('index_name');
    $searchName = $form_state->get('search_name');
    $values     = $form_state->getValues();

    $search = SearchFactory::create($indexName, $searchName, (int) ($values['size'] ?? 10), (int) ($values['from'] ?? 0));

    $queryJson = trim($values['query'] ?? '');
    if ($queryJson !== '') {
      $decoded = json_decode($queryJson, TRUE);
      if ($decoded !== NULL) $search->setQuery($decoded);
    }

    $mode = $values['source_mode'] ?? 'all';
    if ($mode === 'none') {
      $search->setSource(FALSE);
    }
    elseif ($mode === 'list' && ($values['source_fields'] ?? '') !== '') {
      $search->setSource(array_map('trim', explode(',', $values['source_fields'])));
    }

    if (($values['min_score'] ?? '') !== '') {
      $search->setMinScore((float) $values['min_score']);
    }
    if (($values['timeout'] ?? '') !== '') {
      $search->setTimeout($values['timeout']);
    }
    if (($values['track_total_hits'] ?? '') !== '') {
      $raw = $values['track_total_hits'];
      if ($raw === 'true') $search->setTrackTotalHits(TRUE);
      elseif ($raw === 'false') $search->setTrackTotalHits(FALSE);
      elseif (is_numeric($raw)) $search->setTrackTotalHits((int) $raw);
    }

    $collapseField = trim($values['collapse_field'] ?? '');
    if ($collapseField !== '') {
      $innerHitsJson = trim($values['collapse_inner_hits'] ?? '');
      $innerHits = [];
      if ($innerHitsJson !== '') {
        $decoded = json_decode($innerHitsJson, TRUE);
        if ($decoded !== NULL) $innerHits = $decoded;
      }
      $search->setCollapse($collapseField, $innerHits);
    }

    $suggestJson = trim($values['suggest'] ?? '');
    if ($suggestJson !== '') {
      $decoded = json_decode($suggestJson, TRUE);
      if ($decoded !== NULL) {
        // Manually set suggest since SearchFactory only has addCompletionSuggest
        // We store raw suggest JSON directly via the factory's save() → toArray()
        foreach ($decoded as $suggestName => $suggestConfig) {
          $field = $suggestConfig['completion']['field'] ?? '';
          $size  = $suggestConfig['completion']['size'] ?? 5;
          unset($suggestConfig['completion']['field'], $suggestConfig['completion']['size']);
          $search->addCompletionSuggest($suggestName, $field, $size, $suggestConfig['completion'] ?? []);
        }
      }
    }

    $hlFields = array_filter(array_map('trim', explode(',', $values['highlight_fields'] ?? '')));
    if (!empty($hlFields)) {
      $hlOptionsJson = trim($values['highlight_options'] ?? '');
      $globalOpts = [];
      if ($hlOptionsJson !== '') {
        $decoded = json_decode($hlOptionsJson, TRUE);
        if ($decoded !== NULL) $globalOpts = $decoded;
      }
      $search->setHighlight($hlFields, $values['highlight_pre_tags'] ?? '<em>', $values['highlight_post_tags'] ?? '</em>', $globalOpts);
    }

    $search->save();
    $this->messenger()->addStatus($this->t('Search %name saved.', ['%name' => $searchName]));
    $form_state->setRedirectUrl(Url::fromRoute('eticsearch.search.list'));
  }
}
