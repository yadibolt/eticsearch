<?php

namespace Drupal\eticsearch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eticsearch\Manager\ConfigurationManager;
use Drupal\eticsearch\Manager\FieldManager;

class GeneralSettingsForm extends ConfigFormBase {
  public function getFormId(): string {
    return 'eticsearch.general.settings.form';
  }

  protected function getEditableConfigNames(): array {
    return [ConfigurationManager::CONFIG_NAME];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $content_types = ConfigurationManager::getEnabledContentTypes();
    foreach ($content_types as $content_type) {
      $form['node_type_' . $content_type->id()] = [
        '#type' => 'fieldset',
        '#title' => $this->t('NodeType: @name', ['@name' => $content_type->label()]),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $field_definitions = FieldManager::getFieldDefinitionsContentType($content_type->id(), FALSE);
      foreach ($field_definitions as $field_definition) {
        $form['node_type_' . $content_type->id()][$field_definition->getName()] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Field: @name', ['@name' => $field_definition->getName()]),
          '#description' => $this->t('Field type: @type', ['@type' => $field_definition->getType()]),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ];

        $form['node_type_' . $content_type->id()][$field_definition->getName()]['include_search'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Include in search'),
          '#default_value' => $content_type->getThirdPartySetting('eticsearch', 'field_' . $field_definition->getName() . '_include_search', FALSE),
        ];

        $form['node_type_' . $content_type->id()][$field_definition->getName()]['include_context'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Include in context retrieved from the Elasticsearch'),
          '#default_value' => $content_type->getThirdPartySetting('eticsearch', 'field_' . $field_definition->getName() . '_include_context', FALSE),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(ConfigurationManager::CONFIG_NAME)->save();
    parent::submitForm($form, $form_state);
  }
}
