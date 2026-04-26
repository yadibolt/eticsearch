<?php

namespace Drupal\eticsearch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IndexListController extends ControllerBase {

  protected $eticConfig;

  public function __construct(ConfigFactory $eticConfig) {
    $this->eticConfig = $eticConfig;
  }

  public static function create(ContainerInterface $container): static {
    return new static($container->get('eticsearch.factory.config'));
  }

  public function list(): array {
    $indices = $this->eticConfig->getIndices();

    $rows = [];
    foreach ($indices as $name => $index) {
      $opts = $index['options'] ?? [];
      $rows[] = [
        $name,
        $opts['number_of_shards'] ?? '-',
        $opts['number_of_replicas'] ?? '-',
        $opts['codec'] ?? '-',
        [
          'data' => [
            '#type'  => 'operations',
            '#links' => [
              'edit'   => [
                'title' => $this->t('Edit'),
                'url'   => Url::fromRoute('eticsearch.index.edit', ['name' => $name]),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url'   => Url::fromRoute('eticsearch.index.delete', ['name' => $name]),
              ],
            ],
          ],
        ],
      ];
    }

    return [
      'add_link' => [
        '#type'       => 'link',
        '#title'      => $this->t('Add Index'),
        '#url'        => Url::fromRoute('eticsearch.index.add'),
        '#attributes' => ['class' => ['button', 'button--primary', 'button--small']],
        '#prefix'     => '<p>',
        '#suffix'     => '</p>',
      ],
      'table' => [
        '#type'   => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Shards'),
          $this->t('Replicas'),
          $this->t('Codec'),
          $this->t('Operations'),
        ],
        '#rows'  => $rows,
        '#empty' => $this->t('No indices configured yet.'),
      ],
    ];
  }
}
