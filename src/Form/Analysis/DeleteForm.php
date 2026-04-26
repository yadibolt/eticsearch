<?php

namespace Drupal\eticsearch\Form\Analysis;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\eticsearch\Index\Analyzer;
use Drupal\eticsearch\Index\CharFilter;
use Drupal\eticsearch\Index\Filter;
use Drupal\eticsearch\Index\Similarity;
use Drupal\eticsearch\Index\Tokenizer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DeleteForm extends ConfirmFormBase
{

  private string $name;
  private string $component;

  public function __construct(
    RouteMatchInterface $routeMatch,
  ) {
    $this->routeMatch = $routeMatch;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('current_route_match'),
    );
  }

  public function getFormId(): string
  {
    return 'eticsearch_analysis_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $name = NULL): array
  {
    $this->name = $name ?? $this->routeMatch->getParameter('name');
    $this->component = $this->routeMatch->getRouteObject()->getDefault('component');
    return parent::buildForm($form, $form_state);
  }

  public function getQuestion(): TranslatableMarkup
  {
    return $this->t('Delete %name?', ['%name' => $this->name]);
  }

  public function getDescription(): TranslatableMarkup
  {
    return $this->t('This action cannot be undone. Items currently used by an index cannot be deleted.');
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $deleted = match ($this->component) {
      'tokenizer' => Tokenizer::delete($this->name),
      'filter' => Filter::delete($this->name),
      'char_filter' => CharFilter::delete($this->name),
      'analyzer' => Analyzer::delete($this->name),
      'similarity' => Similarity::delete($this->name),
      default => FALSE,
    };

    if ($deleted) {
      $this->messenger()->addStatus($this->t('%name has been deleted.', ['%name' => $this->name]));
    } else {
      $this->messenger()->addError($this->t('%name could not be deleted. It may be in use by an index.', ['%name' => $this->name]));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  public function getCancelUrl(): Url
  {
    return Url::fromRoute("eticsearch.{$this->component}.list");
  }
}
