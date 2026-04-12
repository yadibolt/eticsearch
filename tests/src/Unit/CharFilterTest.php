<?php

namespace Drupal\Tests\eticsearch\Unit;

use Drupal\eticsearch\CharFilter;
use Drupal\Tests\UnitTestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\eticsearch\CharFilter
 * @group eticsearch
 */
class CharFilterTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $mockConfigFactory = $this->createMock(\Drupal\eticsearch\Factory\ConfigFactory::class);
    $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
    $container->method('get')
      ->with('eticsearch.factory.config')
      ->willReturn($mockConfigFactory);
    \Drupal::setContainer($container);
  }

  public function testCreateInvalidTypeThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    CharFilter::create('cf', 'invalid_type');
  }

  // --------------------------------------------------------- html_strip --

  public function testCreateHtmlStripDefaults(): void {
    $cf = CharFilter::create('html', 'html_strip');
    $array = $cf->toArray();

    $this->assertSame('html_strip', $array['type']);
    $this->assertSame('html', $array['name']);
    $this->assertArrayNotHasKey('escaped_tags', $array);
  }

  public function testCreateHtmlStripWithEscapedTags(): void {
    $cf = CharFilter::create('html', 'html_strip', escapedTags: ['<b>', '<i>']);
    $array = $cf->toArray();

    $this->assertSame(['<b>', '<i>'], $array['escaped_tags']);
  }

  // ----------------------------------------------------------- mapping --

  public function testCreateMappingDefaults(): void {
    $cf = CharFilter::create('map', 'mapping');
    $array = $cf->toArray();

    $this->assertSame('mapping', $array['type']);
    $this->assertArrayNotHasKey('mappings', $array);
    $this->assertArrayNotHasKey('mappings_path', $array);
  }

  public function testCreateMappingWithMappings(): void {
    $cf = CharFilter::create('map', 'mapping', mappings: ['ph => f', 'qu => kw']);
    $array = $cf->toArray();

    $this->assertSame(['ph => f', 'qu => kw'], $array['mappings']);
  }

  public function testCreateMappingWithMappingsPath(): void {
    $cf = CharFilter::create('map', 'mapping', mappingsPath: '/path/to/mappings.txt');
    $array = $cf->toArray();

    $this->assertSame('/path/to/mappings.txt', $array['mappings_path']);
  }

  // ----------------------------------------------------- pattern_replace --

  public function testCreatePatternReplaceDefaults(): void {
    $cf = CharFilter::create('pr', 'pattern_replace');
    $array = $cf->toArray();

    $this->assertSame('pattern_replace', $array['type']);
    $this->assertSame('', $array['replacement']);
    $this->assertArrayNotHasKey('pattern', $array);
    $this->assertArrayNotHasKey('flags', $array);
  }

  public function testCreatePatternReplaceWithPattern(): void {
    $cf = CharFilter::create('pr', 'pattern_replace', pattern: '\d+', replacement: 'NUM', flags: 'CASE_INSENSITIVE');
    $array = $cf->toArray();

    $this->assertSame('\d+', $array['pattern']);
    $this->assertSame('NUM', $array['replacement']);
    $this->assertSame('CASE_INSENSITIVE', $array['flags']);
  }

  // ----------------------------------------------------------- getName --

  public function testGetName(): void {
    $cf = CharFilter::create('my_html', 'html_strip');
    $this->assertSame('my_html', $cf->getName());
  }

  // --------------------------------------------------------- fromArray --

  public function testFromArrayHtmlStrip(): void {
    $entry = ['name' => 'html_cf', 'type' => 'html_strip', 'escaped_tags' => ['<b>']];
    $cf = CharFilter::fromArray($entry);
    $array = $cf->toArray();

    $this->assertSame('html_cf', $array['name']);
    $this->assertSame(['<b>'], $array['escaped_tags']);
  }

  public function testFromArrayMapping(): void {
    $entry = ['name' => 'map_cf', 'type' => 'mapping', 'mappings' => ['ph => f']];
    $cf = CharFilter::fromArray($entry);
    $array = $cf->toArray();

    $this->assertSame(['ph => f'], $array['mappings']);
  }

  public function testFromArrayDefaults(): void {
    $cf = CharFilter::fromArray([]);
    $this->assertSame('char_filter', $cf->getName());
  }

}
