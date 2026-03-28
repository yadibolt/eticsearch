<?php

namespace Drupal\eticsearch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eticsearch\Manager\ConfigurationManager;

class ElasticsearchSettingsForm extends ConfigFormBase {

  public function getFormId(): string {
    return 'eticsearch.elasticsearch.settings.form';
  }

  protected function getEditableConfigNames(): array {
    return [ConfigurationManager::CONFIG_NAME];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(ConfigurationManager::CONFIG_NAME);

    $form['connection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Connection'),
    ];

    $form['connection']['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#default_value' => $config->get('host'),
      '#required' => TRUE,
    ];

    $form['connection']['port'] = [
      '#type' => 'number',
      '#title' => $this->t('Port'),
      '#default_value' => $config->get('port') ?? 9200,
      '#min' => 1,
      '#max' => 65535,
      '#required' => TRUE,
    ];

    $form['authentication'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Authentication'),
    ];

    $form['authentication']['auth_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Auth method'),
      '#options' => [
        'none' => $this->t('None'),
        'basic' => $this->t('Username + Password'),
        'api_key' => $this->t('API Key'),
      ],
      '#default_value' => $config->get('auth_method') ?? 'none',
    ];

    $form['authentication']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('username'),
      '#states' => [
        'visible' => [':input[name="auth_method"]' => ['value' => 'basic']],
      ],
    ];

    $form['authentication']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#states' => [
        'visible' => [':input[name="auth_method"]' => ['value' => 'basic']],
      ],
    ];

    $form['authentication']['api_key_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key ID'),
      '#default_value' => $config->get('api_key_id'),
      '#states' => [
        'visible' => [':input[name="auth_method"]' => ['value' => 'api_key']],
      ],
    ];

    $form['authentication']['api_key'] = [
      '#type' => 'password',
      '#title' => $this->t('API Key'),
      '#states' => [
        'visible' => [':input[name="auth_method"]' => ['value' => 'api_key']],
      ],
    ];

    $form['ssl'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SSL'),
    ];

    $form['ssl']['verify_ssl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verify SSL certificate'),
      '#default_value' => $config->get('verify_ssl') ?? TRUE,
    ];

    $form['ssl']['ca_cert'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CA certificate path'),
      '#description' => $this->t('ABS path'),
      '#default_value' => $config->get('ca_cert'),
      '#states' => [
        'visible' => [':input[name="verify_ssl"]' => ['checked' => TRUE]],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(ConfigurationManager::CONFIG_NAME);

    $config
      ->set('host', $form_state->getValue('host'))
      ->set('port', (int) $form_state->getValue('port'))
      ->set('auth_method', $form_state->getValue('auth_method'))
      ->set('username', $form_state->getValue('username'))
      ->set('verify_ssl', (bool) $form_state->getValue('verify_ssl'))
      ->set('ca_cert', $form_state->getValue('ca_cert'))
      ->set('api_key_id', $form_state->getValue('api_key_id'));

    if ($form_state->getValue('password')) {
      $config->set('password', $form_state->getValue('password'));
    }
    if ($form_state->getValue('api_key')) {
      $config->set('api_key', $form_state->getValue('api_key'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }
}
