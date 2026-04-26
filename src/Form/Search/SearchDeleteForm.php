<?php

namespace Drupal\eticsearch\Form\Search;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\eticsearch\Factory\ConfigFactory;

class SearchDeleteForm extends ConfirmFormBase {

  private string $indexName;
  private string $searchName;
  protected $eticConfig;

  public function __construct(RouteMatchInterface $routeMatch, ConfigFactory $eticConfig) {
    $this->routeMatch = $routeMatch;
    $this->eticConfig = $eticConfig;
  }

  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container): static {
    return new static(
      $container->get('current_route_match'),
      $container->get('eticsearch.factory.config'),
    );
  }

  public function getFormId(): string {
    return 'eticsearch_search_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $index_name = NULL, string $search_name = NULL): array {
    $this->indexName  = $index_name  ?? $this->routeMatch->getParameter('index_name');
    $this->searchName = $search_name ?? $this->routeMatch->getParameter('search_name');
    return parent::buildForm($form, $form_state);
  }

  public function getQuestion(): TranslatableMarkup {
    return $this->t('Delete search %name from index %index?', [
      '%name'  => $this->searchName,
      '%index' => $this->indexName,
    ]);
  }

  public function getCancelUrl(): Url {
    return Url::fromRoute('eticsearch.search.list');
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $searches = $this->eticConfig->getSearches();
    unset($searches[$this->indexName][$this->searchName]);
    if (empty($searches[$this->indexName])) {
      unset($searches[$this->indexName]);
    }
    $this->eticConfig->set('etic:searches', $searches);
    $this->messenger()->addStatus($this->t('Search %name deleted.', ['%name' => $this->searchName]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
