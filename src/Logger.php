<?php

namespace Drupal\eticsearch;

use Drupal;

class Logger {
  public static function send(string $message, array $context = [], $type = 'notice'): void {
    match ($type) {
      'warning' => Drupal::logger('eticsearch')->warning($message, $context),
      'error' => Drupal::logger('eticsearch')->error($message, $context),
      default => Drupal::logger('eticsearch')->notice($message, $context),
    };
  }
}
