<?php

namespace Drupal\eticsearch\Search\Query;

use InvalidArgumentException;

class PinnedQuery
{
  private array $organic;
  private array $ids;
  private array $docs;

  private function __construct(array $organic, array $ids, array $docs)
  {
    $this->organic = $organic;
    $this->ids = $ids;
    $this->docs = $docs;
  }

  public static function create(array|BoolQuery $organic, array $ids = [], array $docs = []): self
  {
    if (!empty($ids) && !empty($docs)) {
      throw new InvalidArgumentException('PinnedQuery accepts either ids or docs, not both.');
    }

    if (empty($ids) && empty($docs)) {
      throw new InvalidArgumentException('PinnedQuery requires either ids or docs.');
    }

    return new self(
      $organic instanceof BoolQuery ? $organic->toArray() : $organic,
      $ids,
      $docs
    );
  }

  public static function fromArray(array $data): self
  {
    $p = $data['pinned'] ?? $data;
    return new self($p['organic'], $p['ids'] ?? [], $p['docs'] ?? []);
  }

  public function toArray(): array
  {
    $body = ['organic' => $this->organic];

    if (!empty($this->ids)) {
      $body['ids'] = $this->ids;
    } else {
      $body['docs'] = $this->docs;
    }

    return ['pinned' => $body];
  }
}
