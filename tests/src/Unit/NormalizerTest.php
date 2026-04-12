<?php

namespace Drupal\Tests\eticsearch\Unit;

use Drupal\eticsearch\CharFilter;
use Drupal\eticsearch\Filter;
use Drupal\eticsearch\Normalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\eticsearch\Normalizer
 * @group eticsearch
 */
class NormalizerTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $mockConfigFactory = $this->createMock(\Drupal\eticsearch\Factory\ConfigFactory::class);
    $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
    $container->method('get')
      ->with('eticsearch.factory.config')
      ->willReturn($mockConfigFactory);
    \Drupal::setContainer($container);
  }

  public function testCreateDefaults(): void {
    $normalizer = Normalizer::create('my_normalizer');
    $array = $normalizer->toArray();

    $this->assertSame('my_normalizer', $array['name']);
    $this->assertSame('custom', $array['type']);
    $this->assertArrayNotHasKey('char_filter', $array);
    $this->assertArrayNotHasKey('filter', $array);
  }

  public function testCreateWithCharFilters(): void {
    $cf1 = CharFilter::create('html', 'html_strip');
    $cf2 = CharFilter::create('map', 'mapping');
    $normalizer = Normalizer::create('my_normalizer', charFilters: [$cf1, $cf2]);
    $array = $normalizer->toArray();

    $this->assertSame(['html', 'map'], $array['char_filter']);
  }

  public function testCreateWithFilters(): void {
    $f1 = Filter::create('lowercase', 'stop');
    $f2 = Filter::create('ascii', 'stop');
    $normalizer = Normalizer::create('my_normalizer', filters: [$f1, $f2]);
    $array = $normalizer->toArray();

    $this->assertSame(['lowercase', 'ascii'], $array['filter']);
  }

  public function testCreateWithBothCharFiltersAndFilters(): void {
    $cf = CharFilter::create('html', 'html_strip');
    $f = Filter::create('lowercase', 'stop');
    $normalizer = Normalizer::create('my_normalizer', charFilters: [$cf], filters: [$f]);
    $array = $normalizer->toArray();

    $this->assertSame(['html'], $array['char_filter']);
    $this->assertSame(['lowercase'], $array['filter']);
  }

  public function testAlwaysHasTypeCustom(): void {
    $normalizer = Normalizer::create('test');
    $this->assertSame('custom', $normalizer->toArray()['type']);
  }

  public function testGetName(): void {
    $normalizer = Normalizer::create('my_name');
    $this->assertSame('my_name', $normalizer->getName());
  }

  public function testFromArrayDefaults(): void {
    $normalizer = Normalizer::fromArray([]);
    $this->assertSame('normalizer', $normalizer->getName());
  }

  public function testFromArrayWithCharFiltersAndFilters(): void {
    $entry = [
      'name' => 'custom_norm',
      'char_filters' => [
        ['name' => 'html', 'type' => 'html_strip'],
      ],
      'filters' => [
        ['name' => 'my_stop', 'type' => 'stop'],
      ],
    ];

    $normalizer = Normalizer::fromArray($entry);
    $array = $normalizer->toArray();

    $this->assertSame('custom_norm', $array['name']);
    $this->assertSame(['html'], $array['char_filter']);
    $this->assertSame(['my_stop'], $array['filter']);
  }

  public function testFromArrayRoundtrip(): void {
    $cf = CharFilter::create('html', 'html_strip');
    $f = Filter::create('stop_f', 'stop');
    $normalizer = Normalizer::create('round_trip', charFilters: [$cf], filters: [$f]);

    $array = $normalizer->toArray();
    $this->assertSame('round_trip', $array['name']);
    $this->assertContains('html', $array['char_filter']);
    $this->assertContains('stop_f', $array['filter']);
  }

}
