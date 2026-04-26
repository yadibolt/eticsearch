<?php

namespace Drupal\eticsearch\Form\Search;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchNameForm extends FormBase {

  protected $eticConfig;

  public function __construct(ConfigFactory $eticConfig) {
    $this->eticConfig = $eticConfig;
  }

  public static function create(ContainerInterface $container): static {
    return new static($container->get('eticsearch.factory.config'));
  }

  public function getFormId(): string {
    return 'eticsearch_search_name_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $indices = $this->eticConfig->getIndices();
    $indexOptions = array_combine(array_keys($indices), array_keys($indices));

    if (empty($indexOptions)) {
      $form['notice'] = [
        '#markup' => '<p>' . $this->t('No indices configured yet. <a href="@url">Create an index first.</a>', [
          '@url' => Url::fromRoute('eticsearch.index.add')->toString(),
        ]) . '</p>',
      ];
      return $form;
    }

    $form['index_name'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Index'),
      '#options'     => $indexOptions,
      '#required'    => TRUE,
      '#description' => $this->t('The Elasticsearch index this search configuration belongs to.'),
    ];

    $form['search_name'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Search name'),
      '#description' => $this->t('A unique identifier for this search within the selected index. Lowercase letters, numbers, and underscores only.'),
      '#required'    => TRUE,
      '#pattern'     => '[a-z][a-z0-9_]*',
      '#attributes'  => ['placeholder' => 'e.g. product_search'],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Continue'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $searchName = trim($form_state->getValue('search_name'));
    $indexName  = $form_state->getValue('index_name');

    if (!preg_match('/^[a-z][a-z0-9_]*$/', $searchName)) {
      $form_state->setErrorByName('search_name', $this->t('Search name must start with a lowercase letter and contain only lowercase letters, numbers, and underscores.'));
      return;
    }

    $existing = $this->eticConfig->getSearches()[$indexName][$searchName] ?? NULL;
    if ($existing !== NULL) {
      $form_state->setErrorByName('search_name', $this->t('A search named %name already exists for index %index.', [
        '%name'  => $searchName,
        '%index' => $indexName,
      ]));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirectUrl(Url::fromRoute('eticsearch.search.edit', [
      'index_name'  => $form_state->getValue('index_name'),
      'search_name' => trim($form_state->getValue('search_name')),
    ]));
  }
}
