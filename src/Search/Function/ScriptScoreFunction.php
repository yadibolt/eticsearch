<?php

namespace Drupal\eticsearch\Search\Function;

class ScriptScoreFunction
{
  private string $source;
  private array $params;

  private function __construct(string $source, array $params)
  {
    $this->source = $source;
    $this->params = $params;
  }

  public static function create(string $source, array $params = []): self
  {
    return new self($source, $params);
  }

  public static function fromArray(array $data): self
  {
    $script = $data['script_score']['script'] ?? $data;
    return new self($script['source'], $script['params'] ?? []);
  }

  public function toArray(): array
  {
    $script = ['source' => $this->source];
    if (!empty($this->params)) {
      $script['params'] = $this->params;
    }

    return ['script_score' => ['script' => $script]];
  }
}
