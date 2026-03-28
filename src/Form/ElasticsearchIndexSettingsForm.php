<?php

namespace Drupal\eticsearch\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eticsearch\Manager\ConfigurationManager;
use Drupal\eticsearch\Provider\ElasticsearchProvider;

class ElasticsearchIndexSettingsForm extends ConfigFormBase
{

  public function getFormId(): string
  {
    return 'eticsearch.elasticsearch_index.settings.form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->config('eticsearch.settings');

    $form['index'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Elasticsearch Index'),
    ];

    $form['index']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#disabled' => TRUE,
      '#default_value' => $config->get('index_name') ?? '',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Regenerate Index'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $config = $this->config(ConfigurationManager::CONFIG_NAME);

    /** @var ElasticsearchProvider $provider */
    $provider = Drupal::service('eticsearch.elasticsearch_provider');

    if (!$provider->deleteIndexIfExists(ConfigurationManager::ELASTICSEARCH_INDEX_NAME)) {
      Drupal::messenger()->addError($this->t('Failed to delete existing index. Please check the logs for more details.'));
      return;
    }

    if (!$provider->createIndexIfNotExists(ConfigurationManager::ELASTICSEARCH_INDEX_NAME)) {
      Drupal::messenger()->addError($this->t('Failed to create new index. Please check the logs for more details.'));
      return;
    }

    $config->set('index_name', ConfigurationManager::ELASTICSEARCH_INDEX_NAME);
    $config->save();

    Drupal::messenger()->addStatus($this->t('Elasticsearch index has been regenerated successfully.'));
  }

  protected function getEditableConfigNames(): array
  {
    return [ConfigurationManager::CONFIG_NAME];
  }
}
