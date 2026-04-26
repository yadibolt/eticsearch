<?php

namespace Drupal\eticsearch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FieldListController extends ControllerBase
{

  public function __construct(
    private readonly EntityFieldManagerInterface $entityFieldManager,
  )
  {
  }

  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('entity_field.manager'),
    );
  }

  public function list(): array
  {
    $nodeTypes = $this->entityTypeManager()->getStorage('node_type')->loadMultiple();

    $rows = [];
    foreach ($nodeTypes as $nodeType) {
      if (!$nodeType->getThirdPartySetting('eticsearch', 'enabled', FALSE)) {
        continue;
      }

      $enabledFields = $nodeType->getThirdPartySetting('eticsearch', 'fields', []);
      $fieldDefs = $this->entityFieldManager->getFieldDefinitions('node', $nodeType->id());

      foreach ($enabledFields as $fieldName => $fieldConfig) {
        if (!($fieldConfig['enabled'] ?? FALSE)) {
          continue;
        }

        $fieldDef = $fieldDefs[$fieldName] ?? NULL;
        $drupalType = $fieldDef ? $fieldDef->getType() : 'unknown';
        $mapping = $fieldConfig['mapping'] ?? NULL;
        $esType = $mapping['type'] ?? NULL;

        $rows[] = [
          $nodeType->label(),
          $nodeType->id(),
          $fieldName,
          $drupalType,
          $esType ?? $this->t('Not configured'),
          [
            'data' => [
              '#type' => 'operations',
              '#links' => [
                'edit' => [
                  'title' => $esType ? $this->t('Edit') : $this->t('Configure'),
                  'url' => Url::fromRoute('eticsearch.field.edit', [
                    'content_type' => $nodeType->id(),
                    'field_name' => $fieldName,
                  ]),
                ],
              ],
            ],
          ],
        ];
      }
    }

    $build = [];

    if (empty($rows)) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t(
            'No fields are currently enabled for Eticsearch. Enable content types and their fields in the <a href="@url">content type settings</a>.',
            ['@url' => Url::fromRoute('entity.node_type.collection')->toString()]
          ) . '</p>',
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Content type'),
        $this->t('Bundle'),
        $this->t('Field'),
        $this->t('Drupal type'),
        $this->t('ES type'),
        $this->t('Operations'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No fields enabled.'),
    ];

    return $build;
  }
}
