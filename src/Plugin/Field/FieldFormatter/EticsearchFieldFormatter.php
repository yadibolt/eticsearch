<?php

namespace Drupal\eticsearch\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eticsearch\Form\Field\EticsearchFieldSearchForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[FieldFormatter(
  id: 'eticsearch_field_formatter',
  label: new TranslatableMarkup('Eticsearch Field'),
  field_types: ['eticsearch_field'],
)]
class EticsearchFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface
{

  public function __construct(
    string                         $plugin_id,
    mixed                          $plugin_definition,
    FieldDefinitionInterface       $field_definition,
    array                          $settings,
    string                         $label,
    string                         $view_mode,
    array                          $third_party_settings,
    protected FormBuilderInterface $formBuilder,
  )
  {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static
  {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('form_builder'),
    );
  }

  public function viewElements(FieldItemListInterface $items, $langcode): array
  {
    $value = '';

    foreach ($items as $item) {
      $value = $item->value ?? '';
      break;
    }

    return [
      $this->formBuilder->getForm(EticsearchFieldSearchForm::class, $value)
    ];
  }
}
