<?php

namespace Drupal\eticsearch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchListController extends ControllerBase {

  protected $eticConfig;

  public function __construct(ConfigFactory $eticConfig) {
    $this->eticConfig = $eticConfig;
  }

  public static function create(ContainerInterface $container): static {
    return new static($container->get('eticsearch.factory.config'));
  }

  public function list(): array {
    $allSearches = $this->eticConfig->getSearches();

    $rows = [];
    foreach ($allSearches as $indexName => $searches) {
      foreach ($searches as $searchName => $search) {
        $size = $search['size'] ?? 10;
        $from = $search['from'] ?? 0;
        $rows[] = [
          $indexName,
          $searchName,
          $size,
          $from,
          [
            'data' => [
              '#type'  => 'operations',
              '#links' => [
                'edit'   => [
                  'title' => $this->t('Edit'),
                  'url'   => Url::fromRoute('eticsearch.search.edit', [
                    'index_name'  => $indexName,
                    'search_name' => $searchName,
                  ]),
                ],
                'delete' => [
                  'title' => $this->t('Delete'),
                  'url'   => Url::fromRoute('eticsearch.search.delete', [
                    'index_name'  => $indexName,
                    'search_name' => $searchName,
                  ]),
                ],
              ],
            ],
          ],
        ];
      }
    }

    return [
      'add_link' => [
        '#type'       => 'link',
        '#title'      => $this->t('Add Search'),
        '#url'        => Url::fromRoute('eticsearch.search.add'),
        '#attributes' => ['class' => ['button', 'button--primary', 'button--small']],
        '#prefix'     => '<p>',
        '#suffix'     => '</p>',
      ],
      'table' => [
        '#type'   => 'table',
        '#header' => [
          $this->t('Index'),
          $this->t('Search name'),
          $this->t('Size'),
          $this->t('From'),
          $this->t('Operations'),
        ],
        '#rows'  => $rows,
        '#empty' => $this->t('No searches configured yet.'),
      ],
    ];
  }
}
