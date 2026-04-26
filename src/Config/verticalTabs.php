<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\eticsearch\Manager\FieldManager;
use Drupal\node\Entity\NodeType;

function eticsearchContentTypeVerticalTabs(array &$form, FormStateInterface $formState, string $formId): void
{
  /** @var NodeType $entity */
  if ($entity = $formState->getFormObject()?->getEntity() ?? NULL) {
    $entityFieldManager = Drupal::service('entity_field.manager');

    // Containers
    $form['eticsearch'] = [
      '#type' => 'details',
      '#title' => t('Eticsearch'),
      '#group' => 'additional_settings',
      '#description' => t('Configure Eticsearch settings for this content type.'),
      '#weight' => 10,
      '#tree' => TRUE,
    ];

    $form['eticsearch']['settings_container'] = [
      '#type' => 'details',
      '#title' => t('General Settings'),
      '#open' => TRUE,
      '#description' => t('General settings for Eticsearch indexing of this content type.'),
    ];

    $form['eticsearch']['field_container'] = [
      '#type' => 'details',
      '#title' => t('Fields to include in the index'),
      '#open' => TRUE,
      '#description' => t('Select which fields of this content type should be indexed in Eticsearch.'),
    ];

    // Values
    $form['eticsearch']['settings_container']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Eticsearch for this content type'),
      '#default_value' => $entity->getThirdPartySetting('eticsearch', 'enabled', FALSE),
      '#description' => t('When enabled, nodes of this content type will be indexed in Eticsearch.'),
    ];

    if ($fields = $entityFieldManager->getFieldDefinitions('node', $entity->id())) {
      $configuredFields = $entity->getThirdPartySetting('eticsearch', 'fields', []);
      foreach ($fields as $field) {
        if (!in_array($field->getName(), FieldManager::ALLOWED_BASE_NODE_FIELDS)) continue;

        $form['eticsearch']['field_container'][$field->getName()] = [
          '#type' => 'checkbox',
          '#title' => t('Include \'@field\' field in the indexes', ['@field' => $field->getName()]),
          '#default_value' => $configuredFields[$field->getName()]['enabled'] ?? FALSE,
        ];
      }
    }

    $form['#entity_builders'][] = 'eticsearchContentTypeVerticalTabsSubmit';
  }
}

function eticsearchContentTypeVerticalTabsSubmit(string $entityType, NodeType $entity, array &$form, FormStateInterface $formState): void
{
  if ($formState->getValue(['eticsearch', 'settings_container', 'enabled'], FALSE)) {
    $entity->setThirdPartySetting('eticsearch', 'enabled', TRUE);

    $fields = [];
    $formFields = $formState->getValue(['eticsearch', 'field_container'], []);
    foreach ($formFields as $fieldName => $checked) {
      if ($checked) $fields[$fieldName] = ['enabled' => TRUE];
    }

    $entity->setThirdPartySetting('eticsearch', 'fields', $fields);
  } else {
    $entity->setThirdPartySetting('eticsearch', 'enabled', FALSE);
  }
}
