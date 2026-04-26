<?php

namespace Drupal\eticsearch\Form\Analysis;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;
use Drupal\eticsearch\Index\Similarity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SimilarityForm extends FormBase
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
    return 'eticsearch_similarity_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $name = NULL): array
  {
    $name = $name ?? $this->routeMatch->getParameter('name');

    $rawSimilarities = $this->eticConfig->getSimilarities();
    $data = $rawSimilarities[$name] ?? [];
    $isNew = empty($data);

    $form_state->set('similarity_name', $name);

    $currentType = $form_state->getValue('type') ?? ($data['type'] ?? 'BM25');

    $form['name_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Machine name'),
      '#markup' => '<code>' . $name . '</code>',
    ];

    $typeOptions = array_combine(
      Similarity::CONFIGURABLE_SIMILARITY_TYPES,
      Similarity::CONFIGURABLE_SIMILARITY_TYPES,
    );

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $typeOptions,
      '#default_value' => $currentType,
      '#disabled' => !$isNew,
      '#ajax' => [
        'callback' => '::rebuildTypeFields',
        'wrapper' => 'similarity-type-fields',
        'event' => 'change',
      ],
    ];

    $form['config_fields'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'similarity-type-fields'],
    ];

    $this->buildTypeFields($form['config_fields'], $currentType, $data);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save similarity'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('eticsearch.similarity.list'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  private function buildTypeFields(array &$container, string $type, array $data): void
  {
    switch ($type) {
      case 'BM25':
        $container['k1'] = [
          '#type' => 'number',
          '#title' => $this->t('k1'),
          '#default_value' => $data['k1'] ?? 1.2,
          '#step' => 0.01,
          '#min' => 0,
          '#description' => $this->t('Controls term frequency saturation. Default: 1.2.'),
          '#required' => TRUE,
        ];
        $container['b'] = [
          '#type' => 'number',
          '#title' => $this->t('b'),
          '#default_value' => $data['b'] ?? 0.75,
          '#step' => 0.01,
          '#min' => 0,
          '#max' => 1,
          '#description' => $this->t('Controls field-length normalization. 0 disables, 1 fully normalizes. Default: 0.75.'),
          '#required' => TRUE,
        ];
        $container['discount_overlaps'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Discount overlaps'),
          '#default_value' => $data['discount_overlaps'] ?? TRUE,
          '#description' => $this->t('Ignore overlap tokens when calculating norms.'),
        ];
        break;

      case 'DFR':
        $container['basic_model'] = [
          '#type' => 'select',
          '#title' => $this->t('Basic model'),
          '#options' => array_combine(Similarity::DFR_BASIC_MODELS, Similarity::DFR_BASIC_MODELS),
          '#default_value' => $data['basic_model'] ?? 'g',
          '#required' => TRUE,
        ];
        $container['after_effect'] = [
          '#type' => 'select',
          '#title' => $this->t('After effect'),
          '#options' => array_combine(Similarity::DFR_AFTER_EFFECTS, Similarity::DFR_AFTER_EFFECTS),
          '#default_value' => $data['after_effect'] ?? 'l',
          '#required' => TRUE,
        ];
        $this->buildNormalizationFields($container, $data);
        break;

      case 'DFI':
        $container['independence_measure'] = [
          '#type' => 'select',
          '#title' => $this->t('Independence measure'),
          '#options' => array_combine(Similarity::DFI_MEASURES, Similarity::DFI_MEASURES),
          '#default_value' => $data['independence_measure'] ?? 'standardized',
          '#required' => TRUE,
        ];
        break;

      case 'IB':
        $container['distribution'] = [
          '#type' => 'select',
          '#title' => $this->t('Distribution'),
          '#options' => array_combine(Similarity::IB_DISTRIBUTIONS, Similarity::IB_DISTRIBUTIONS),
          '#default_value' => $data['distribution'] ?? 'll',
          '#required' => TRUE,
        ];
        $container['ib_lambda'] = [
          '#type' => 'select',
          '#title' => $this->t('Lambda'),
          '#options' => array_combine(Similarity::IB_LAMBDAS, Similarity::IB_LAMBDAS),
          '#default_value' => $data['ib_lambda'] ?? 'df',
          '#required' => TRUE,
        ];
        $this->buildNormalizationFields($container, $data);
        break;

      case 'LMDirichlet':
        $container['mu'] = [
          '#type' => 'number',
          '#title' => $this->t('Mu (μ)'),
          '#default_value' => $data['mu'] ?? 2000,
          '#min' => 0,
          '#description' => $this->t('Smoothing parameter. Default: 2000.'),
          '#required' => TRUE,
        ];
        break;

      case 'LMJelinekMercer':
        $container['lambda'] = [
          '#type' => 'number',
          '#title' => $this->t('Lambda (λ)'),
          '#default_value' => $data['lambda'] ?? 0.1,
          '#step' => 0.01,
          '#min' => 0,
          '#max' => 1,
          '#description' => $this->t('Smoothing parameter. Optimal for title queries: 0.1, body queries: 0.7. Default: 0.1.'),
          '#required' => TRUE,
        ];
        break;

      case 'scripted':
        $container['script_source'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Script source (Painless)'),
          '#default_value' => $data['script']['source'] ?? '',
          '#description' => $this->t('Painless script returning the similarity score. Available: <code>weight</code>, <code>query</code>, <code>field</code>, <code>term</code>, <code>doc</code>.'),
          '#rows' => 6,
          '#required' => TRUE,
        ];
        $container['weight_script_source'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Weight script source (optional)'),
          '#default_value' => $data['weight_script']['source'] ?? '',
          '#description' => $this->t('Optional Painless script computing the weight once per query term. Available: <code>query</code>, <code>field</code>, <code>term</code>.'),
          '#rows' => 4,
        ];
        $paramsDefault = '';
        foreach ($data['params'] ?? [] as $k => $v) {
          $paramsDefault .= "$k: $v\n";
        }
        $container['params'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Parameters'),
          '#default_value' => trim($paramsDefault),
          '#description' => $this->t('One <code>key: value</code> pair per line. These are available in the script as <code>params.key</code>.'),
          '#rows' => 4,
        ];
        break;
    }
  }

  private function buildNormalizationFields(array &$container, array $data): void
  {
    $normOptions = array_combine(Similarity::DFR_IB_NORMALIZATIONS, Similarity::DFR_IB_NORMALIZATIONS);
    $currentNorm = $data['normalization'] ?? 'h2';

    $container['normalization'] = [
      '#type' => 'select',
      '#title' => $this->t('Normalization'),
      '#options' => $normOptions,
      '#default_value' => $currentNorm,
      '#required' => TRUE,
    ];
    $container['normalization_h1_c'] = [
      '#type' => 'number',
      '#title' => $this->t('Normalization H1 constant (c)'),
      '#default_value' => $data['normalization.h1.c'] ?? 1.0,
      '#step' => 0.01,
      '#min' => 0,
      '#states' => ['visible' => [':input[name="normalization"]' => ['value' => 'h1']]],
    ];
    $container['normalization_h2_c'] = [
      '#type' => 'number',
      '#title' => $this->t('Normalization H2 constant (c)'),
      '#default_value' => $data['normalization.h2.c'] ?? 1.0,
      '#step' => 0.01,
      '#min' => 0,
      '#states' => ['visible' => [':input[name="normalization"]' => ['value' => 'h2']]],
    ];
    $container['normalization_h3_c'] = [
      '#type' => 'number',
      '#title' => $this->t('Normalization H3 constant (c)'),
      '#default_value' => $data['normalization.h3.c'] ?? 800.0,
      '#step' => 0.01,
      '#min' => 0,
      '#states' => ['visible' => [':input[name="normalization"]' => ['value' => 'h3']]],
    ];
    $container['normalization_z_z'] = [
      '#type' => 'number',
      '#title' => $this->t('Normalization Z constant (z)'),
      '#default_value' => $data['normalization.z.z'] ?? 0.3,
      '#step' => 0.01,
      '#min' => 0,
      '#states' => ['visible' => [':input[name="normalization"]' => ['value' => 'z']]],
    ];
  }

  public function rebuildTypeFields(array &$form, FormStateInterface $form_state): array
  {
    return $form['config_fields'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $name = $form_state->get('similarity_name');
    $type = $form_state->getValue('type');
    $values = $form_state->getValues();

    $script = ['source' => $values['script_source'] ?? ''];
    $weightSource = trim($values['weight_script_source'] ?? '');
    $weightScript = $weightSource !== '' ? ['source' => $weightSource] : NULL;

    $params = [];
    foreach (array_filter(array_map('trim', explode("\n", $values['params'] ?? ''))) as $line) {
      if (str_contains($line, ':')) {
        [$k, $v] = explode(':', $line, 2);
        $params[trim($k)] = trim($v);
      }
    }

    $similarity = Similarity::create(
      $name,
      $type,
      (float)($values['k1'] ?? 1.2),
      (float)($values['b'] ?? 0.75),
      (bool)($values['discount_overlaps'] ?? TRUE),
      $values['basic_model'] ?? 'g',
      $values['after_effect'] ?? 'l',
      $values['normalization'] ?? 'h2',
      (float)($values['normalization_h1_c'] ?? 1.0),
      (float)($values['normalization_h2_c'] ?? 1.0),
      (float)($values['normalization_h3_c'] ?? 800.0),
      (float)($values['normalization_z_z'] ?? 0.3),
      $values['independence_measure'] ?? 'standardized',
      $values['distribution'] ?? 'll',
      $values['ib_lambda'] ?? 'df',
      (int)($values['mu'] ?? 2000),
      (float)($values['lambda'] ?? 0.1),
      $script,
      $weightScript,
      $params,
    );

    $similarity->save();
    $this->messenger()->addStatus($this->t('Similarity %name saved.', ['%name' => $name]));
    $form_state->setRedirectUrl(Url::fromRoute('eticsearch.similarity.list'));
  }

  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('current_route_match'),
      $container->get('eticsearch.factory.config'),
    );
  }
}
