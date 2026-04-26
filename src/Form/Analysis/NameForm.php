<?php

namespace Drupal\eticsearch\Form\Analysis;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NameForm extends FormBase {

  protected $eticConfig;

  public function __construct(
    ConfigFactory       $eticConfig,
    RouteMatchInterface $routeMatch,
  ) {
    $this->eticConfig = $eticConfig;
    $this->routeMatch = $routeMatch;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('eticsearch.factory.config'),
      $container->get('current_route_match'),
    );
  }

  public function getFormId(): string
  {
    return 'eticsearch_analysis_name_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $component = $this->getComponent();

    $label = match ($component) {
      'tokenizer' => 'tokenizer',
      'filter' => 'filter',
      'char_filter' => 'character filter',
      'analyzer' => 'analyzer',
      'similarity' => 'similarity',
      default => $component,
    };

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine name'),
      '#description' => $this->t('A unique identifier for this @label. Lowercase letters, numbers, and underscores only.', ['@label' => $label]),
      '#required' => TRUE,
      '#pattern' => '[a-z][a-z0-9_]*',
      '#attributes' => ['placeholder' => 'e.g. my_' . str_replace(' ', '_', $label)],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
    ];

    return $form;
  }

  private function getComponent(): string
  {
    return $this->routeMatch->getRouteObject()->getDefault('component');
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    $name = trim($form_state->getValue('name'));

    if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
      $form_state->setErrorByName('name', $this->t('Machine name must start with a lowercase letter and contain only lowercase letters, numbers, and underscores.'));
      return;
    }

    $component = $this->getComponent();
    $existing = match ($component) {
      'tokenizer' => $this->eticConfig->getTokenizers(),
      'filter'    => $this->eticConfig->getFilters(),
      'char_filter' => $this->eticConfig->getCharFilters(),
      'analyzer'  => $this->eticConfig->getAnalyzers(),
      'similarity' => $this->eticConfig->getSimilarities(),
      'index'     => $this->eticConfig->getIndices(),
      default     => [],
    };

    if (isset($existing[$name])) {
      $form_state->setErrorByName('name', $this->t('The name %name is already in use.', ['%name' => $name]));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $name = trim($form_state->getValue('name'));
    $component = $this->getComponent();
    $form_state->setRedirectUrl(Url::fromRoute("eticsearch.{$component}.edit", ['name' => $name]));
  }
}
