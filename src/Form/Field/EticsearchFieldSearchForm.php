<?php

namespace Drupal\eticsearch\Form\Field;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


class EticsearchFieldSearchForm extends FormBase {

  public function getFormId(): string {
    return 'eticsearch.field_search.form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $default_value = ''): array {
    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#default_value' => $default_value,
      '#maxlength' => 512,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }
}
