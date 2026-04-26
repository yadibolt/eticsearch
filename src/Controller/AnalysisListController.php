<?php

namespace Drupal\eticsearch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AnalysisListController extends ControllerBase
{
  public function __construct(
    private readonly ConfigFactory       $eticConfig,
    private readonly RouteMatchInterface $routeMatch,
  )
  {
  }

  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('eticsearch.factory.config'),
      $container->get('current_route_match'),
    );
  }

  public function list(): array
  {
    $component = $this->routeMatch->getRouteObject()->getDefault('component');

    $items = match ($component) {
      'tokenizer' => $this->eticConfig->getTokenizers(),
      'filter' => $this->eticConfig->getFilters(),
      'char_filter' => $this->eticConfig->getCharFilters(),
      'analyzer' => $this->eticConfig->getAnalyzers(),
      'similarity' => $this->eticConfig->getSimilarities(),
      default => [],
    };

    $label = match ($component) {
      'tokenizer' => 'Tokenizer',
      'filter' => 'Filter',
      'char_filter' => 'Character Filter',
      'analyzer' => 'Analyzer',
      'similarity' => 'Similarity',
      default => ucfirst($component),
    };

    $rows = [];
    foreach ($items as $name => $item) {
      $rows[] = [
        $name,
        $item['type'] ?? '-',
        [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute("eticsearch.{$component}.edit", ['name' => $name]),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute("eticsearch.{$component}.delete", ['name' => $name]),
              ],
            ],
          ],
        ],
      ];
    }

    return [
      'add_link' => [
        '#type' => 'link',
        '#title' => $this->t('Add @label', ['@label' => $label]),
        '#url' => Url::fromRoute("eticsearch.{$component}.add"),
        '#attributes' => ['class' => ['button', 'button--primary', 'button--small']],
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [$this->t('Name'), $this->t('Type'), $this->t('Operations')],
        '#rows' => $rows,
        '#empty' => $this->t('No @label items found.', ['@label' => strtolower($label)]),
      ],
    ];
  }
}
