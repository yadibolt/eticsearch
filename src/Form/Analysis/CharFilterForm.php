<?php

namespace Drupal\eticsearch\Form\Analysis;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\eticsearch\Index\CharFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CharFilterForm extends FormBase
{

  public function __construct(
    RouteMatchInterface $routeMatch,
  ) {
    $this->routeMatch = $routeMatch;
  }

  public function getFormId(): string
  {
    return 'eticsearch_char_filter_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $name = NULL): array
  {
    $name = $name ?? $this->routeMatch->getParameter('name');
    $existing = CharFilter::load('single', $name);
    $isNew = $existing === NULL;
    $data = $existing ? $existing->toArray() : [];

    $form_state->set('char_filter_name', $name);

    $currentType = $form_state->getValue('type') ?? ($data['type'] ?? 'html_strip');

    $form['name_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Machine name'),
      '#markup' => '<code>' . $name . '</code>',
    ];

    $typeOptions = [];
    foreach (CharFilter::CONFIGURABLE_CHAR_FILTER_TYPES as $t) {
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
        'wrapper' => 'char-filter-type-fields',
        'event' => 'change',
      ],
    ];

    $form['config_fields'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'char-filter-type-fields'],
    ];

    $this->buildTypeFields($form['config_fields'], $currentType, $data);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save character filter'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('eticsearch.char_filter.list'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  private function buildTypeFields(array &$container, string $type, array $data): void
  {
    switch ($type) {
      case 'html_strip':
        $container['escaped_tags'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Escaped tags'),
          '#default_value' => implode("\n", $data['escaped_tags'] ?? []),
          '#description' => $this->t('HTML tags to leave in the output, one per line (e.g. <code>&lt;b&gt;</code>).'),
          '#rows' => 5,
        ];
        break;

      case 'mapping':
        $mappingsDefault = [];
        foreach ($data['mappings'] ?? [] as $mapping) {
          $mappingsDefault[] = $mapping;
        }
        $container['mappings'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Mappings'),
          '#default_value' => implode("\n", $mappingsDefault),
          '#description' => $this->t('One mapping per line in the format <code>source => replacement</code>, e.g. <code>:) => happy</code>.'),
          '#rows' => 8,
        ];
        $container['mappings_path'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Mappings path'),
          '#default_value' => $data['mappings_path'] ?? '',
          '#description' => $this->t('Path to a UTF-8 encoded mappings file (relative to Elasticsearch config directory). Overrides inline mappings.'),
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
          '#description' => $this->t('Replacement string. Use <code>$1</code>, <code>$2</code>, etc. to reference capture groups.'),
        ];
        $container['flags'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Flags'),
          '#default_value' => $data['flags'] ?? '',
          '#description' => $this->t('Pipe-separated Java regex flags, e.g. <code>CASE_INSENSITIVE|COMMENTS</code>.'),
        ];
        break;
    }
  }

  public function rebuildTypeFields(array &$form, FormStateInterface $form_state): array
  {
    return $form['config_fields'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $name = $form_state->get('char_filter_name');
    $type = $form_state->getValue('type');
    $values = $form_state->getValues();

    $escapedTags = array_values(array_filter(array_map('trim', explode("\n", $values['escaped_tags'] ?? ''))));
    $mappings = array_values(array_filter(array_map('trim', explode("\n", $values['mappings'] ?? ''))));
    $mappingsPath = ($values['mappings_path'] ?? '') !== '' ? $values['mappings_path'] : NULL;
    $pattern = ($values['pattern'] ?? '') !== '' ? $values['pattern'] : NULL;
    $flags = ($values['flags'] ?? '') !== '' ? $values['flags'] : NULL;

    $charFilter = CharFilter::create(
      $name,
      $type,
      $escapedTags,
      $mappings,
      $mappingsPath,
      $pattern,
      $values['replacement'] ?? '',
      $flags,
    );

    $charFilter->save();
    $this->messenger()->addStatus($this->t('Character filter %name saved.', ['%name' => $name]));
    $form_state->setRedirectUrl(Url::fromRoute('eticsearch.char_filter.list'));
  }

  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('current_route_match'),
    );
  }
}
