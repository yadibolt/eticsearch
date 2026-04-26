<?php

namespace Drupal\eticsearch\Form\Index;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\eticsearch\Index\Factory\IndexFactory;

class IndexDeleteForm extends ConfirmFormBase {

  private string $name;

  public function __construct(RouteMatchInterface $routeMatch) {
    $this->routeMatch = $routeMatch;
  }

  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container): static {
    return new static($container->get('current_route_match'));
  }

  public function getFormId(): string {
    return 'eticsearch_index_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $name = NULL): array {
    $this->name = $name ?? $this->routeMatch->getParameter('name');
    return parent::buildForm($form, $form_state);
  }

  public function getQuestion(): TranslatableMarkup {
    return $this->t('Delete index %name?', ['%name' => $this->name]);
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('This will delete the index from both Elasticsearch and the Drupal configuration. This action cannot be undone.');
  }

  public function getCancelUrl(): Url {
    return Url::fromRoute('eticsearch.index.list');
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $deleted = IndexFactory::delete($this->name);
    if ($deleted) {
      $this->messenger()->addStatus($this->t('Index %name deleted.', ['%name' => $this->name]));
    }
    else {
      $this->messenger()->addError($this->t('Failed to delete index %name. Check the Elasticsearch connection.', ['%name' => $this->name]));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
