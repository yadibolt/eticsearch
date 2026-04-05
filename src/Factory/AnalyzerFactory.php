<?php

namespace Drupal\eticsearch\Factory;

class AnalyzerFactory
{
  public function __construct(private readonly ConfigurationFactory $configFactory)
  {
  }
}
