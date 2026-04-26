<?php

namespace Drupal\eticsearch\Form\Analysis;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\eticsearch\Index\Tokenizer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TokenizerForm extends FormBase
{

  public function __construct(
    RouteMatchInterface $routeMatch,
  ) {
    $this->routeMatch = $routeMatch;
  }

  public function getFormId(): string
  {
    return 'eticsearch_tokenizer_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $name = NULL): array
  {
    $name = $name ?? $this->routeMatch->getParameter('name');
    $existing = Tokenizer::load('single', $name);
    $isNew = $existing === NULL;
    $data = $existing ? $existing->toArray() : [];

    $form_state->set('tokenizer_name', $name);

    $currentType = $form_state->getValue('type') ?? ($data['type'] ?? 'standard');

    $form['name_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Machine name'),
      '#markup' => '<code>' . $name . '</code>',
    ];

    $typeOptions = [];
    foreach (Tokenizer::CONFIGURABLE_TOKENIZER_TYPES as $t) {
      $typeOptions[$t] = ucwords(str_replace('_', ' ', $t));
    }

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $typeOptions,
      '#default_value' => $currentType,
      '#disabled' => !$isNew,
      '#ajax' => [
        'callback' => '::rebuildTypeFields',
        'wrapper' => 'tokenizer-type-fields',
        'event' => 'change',
      ],
    ];

    $form['config_fields'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'tokenizer-type-fields'],
    ];

    $this->buildTypeFields($form['config_fields'], $currentType, $data);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save tokenizer'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('eticsearch.tokenizer.list'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  private function buildTypeFields(array &$container, string $type, array $data): void
  {
    switch ($type) {
      case 'standard':
        $container['max_token_length'] = [
          '#type' => 'number',
          '#title' => $this->t('Max token length'),
          '#default_value' => $data['max_token_length'] ?? 255,
          '#min' => 1,
          '#max' => 10000,
        ];
        break;

      case 'ngram':
      case 'edge_ngram':
        $container['min_gram'] = [
          '#type' => 'number',
          '#title' => $this->t('Min gram'),
          '#default_value' => $data['min_gram'] ?? 1,
          '#min' => 1,
          '#required' => TRUE,
        ];
        $container['max_gram'] = [
          '#type' => 'number',
          '#title' => $this->t('Max gram'),
          '#default_value' => $data['max_gram'] ?? 2,
          '#min' => 1,
          '#required' => TRUE,
        ];
        $container['token_chars'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Token characters'),
          '#description' => $this->t('Character classes that should be included in a token. Leaving empty means all characters are accepted.'),
          '#options' => [
            'letter' => $this->t('Letter'),
            'digit' => $this->t('Digit'),
            'whitespace' => $this->t('Whitespace'),
            'punctuation' => $this->t('Punctuation'),
            'symbol' => $this->t('Symbol'),
            'custom' => $this->t('Custom'),
          ],
          '#default_value' => $data['token_chars'] ?? [],
        ];
        $container['custom_token_chars'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Custom token characters'),
          '#default_value' => $data['custom_token_chars'] ?? '',
          '#description' => $this->t('Characters to treat as part of a token when "Custom" is checked above.'),
          '#states' => [
            'visible' => [':input[name="token_chars[custom]"]' => ['checked' => TRUE]],
          ],
        ];
        break;

      case 'pattern':
        $container['pattern'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Pattern (Java regex)'),
          '#default_value' => $data['pattern'] ?? '\W+',
          '#required' => TRUE,
        ];
        $container['flags'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Flags'),
          '#default_value' => $data['flags'] ?? '',
          '#description' => $this->t('Pipe-separated Java regex flags, e.g. <code>CASE_INSENSITIVE|COMMENTS</code>.'),
        ];
        $container['group'] = [
          '#type' => 'number',
          '#title' => $this->t('Group'),
          '#default_value' => $data['group'] ?? -1,
          '#description' => $this->t('Capture group to extract as the token. Use -1 to split on the pattern.'),
        ];
        break;

      case 'simple_pattern':
      case 'simple_pattern_split':
        $container['pattern'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Pattern (Lucene regular expression)'),
          '#default_value' => $data['pattern'] ?? '',
          '#required' => TRUE,
        ];
        break;

      case 'char_group':
        $container['tokenize_on_chars'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Tokenize on characters'),
          '#default_value' => implode("\n", $data['tokenize_on_chars'] ?? []),
          '#description' => $this->t('One character class or literal character per line. Accepted values: <code>whitespace</code>, <code>letter</code>, <code>digit</code>, <code>punctuation</code>, <code>symbol</code>, or any literal character.'),
          '#rows' => 5,
          '#required' => TRUE,
        ];
        break;

      case 'path_hierarchy':
        $container['delimiter'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Delimiter'),
          '#default_value' => $data['delimiter'] ?? '/',
          '#size' => 5,
          '#maxlength' => 1,
          '#required' => TRUE,
        ];
        $container['replacement'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Replacement'),
          '#default_value' => $data['replacement'] ?? '/',
          '#size' => 5,
          '#maxlength' => 1,
          '#description' => $this->t('Character to use instead of the delimiter in the token.'),
        ];
        $container['skip'] = [
          '#type' => 'number',
          '#title' => $this->t('Skip'),
          '#default_value' => $data['skip'] ?? 0,
          '#min' => 0,
          '#description' => $this->t('Number of initial tokens to skip.'),
        ];
        $container['reverse'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Reverse'),
          '#default_value' => $data['reverse'] ?? FALSE,
          '#description' => $this->t('If checked, uses the reverse path hierarchy.'),
        ];
        break;

      case 'keyword':
        $container['info'] = [
          '#markup' => '<p>' . $this->t('The keyword tokenizer emits the entire input as a single token. No additional configuration is required.') . '</p>',
        ];
        break;
    }
  }

  public function rebuildTypeFields(array &$form, FormStateInterface $form_state): array
  {
    return $form['config_fields'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $name = $form_state->get('tokenizer_name');
    $type = $form_state->getValue('type');
    $values = $form_state->getValues();

    $tokenCharsRaw = $values['token_chars'] ?? [];
    $tokenChars = array_values(array_filter($tokenCharsRaw));

    $tokenizeOnCharsRaw = $values['tokenize_on_chars'] ?? '';
    $tokenizeOnChars = array_values(array_filter(array_map('trim', explode("\n", $tokenizeOnCharsRaw))));

    $tokenizer = Tokenizer::create(
      $name,
      $type,
      (int)($values['max_token_length'] ?? 255),
      (int)($values['min_gram'] ?? 1),
      (int)($values['max_gram'] ?? 2),
      $tokenChars,
      ($values['custom_token_chars'] ?? '') !== '' ? $values['custom_token_chars'] : NULL,
      $values['pattern'] ?? '\W+',
      ($values['flags'] ?? '') !== '' ? $values['flags'] : NULL,
      (int)($values['group'] ?? -1),
      $tokenizeOnChars,
      $values['delimiter'] ?? '/',
      $values['replacement'] ?? '/',
      (int)($values['skip'] ?? 0),
      (bool)($values['reverse'] ?? FALSE),
    );

    $tokenizer->save();
    $this->messenger()->addStatus($this->t('Tokenizer %name saved.', ['%name' => $name]));
    $form_state->setRedirectUrl(Url::fromRoute('eticsearch.tokenizer.list'));
  }

  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('current_route_match'),
    );
  }
}
