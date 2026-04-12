<?php

namespace Drupal\Tests\eticsearch\Unit\Factory;

use Drupal\eticsearch\Factory\MappingFactory;
use Drupal\Tests\UnitTestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\eticsearch\Factory\MappingFactory
 * @group eticsearch
 */
class MappingFactoryTest extends UnitTestCase {

  public function testCreateDefaults(): void {
    $mapping = MappingFactory::create();
    $array = $mapping->toArray();

    $this->assertSame(TRUE, $array['mappings']['dynamic']);
    $this->assertTrue($array['mappings']['date_detection']);
    $this->assertFalse($array['mappings']['numeric_detection']);
    $this->assertSame([], $array['mappings']['properties']);
  }

  public function testCreateWithBooleanDynamic(): void {
    $mapping = MappingFactory::create(dynamic: FALSE);
    $array = $mapping->toArray();

    $this->assertFalse($array['mappings']['dynamic']);
  }

  public function testCreateWithStringDynamicStrict(): void {
    $mapping = MappingFactory::create(dynamic: 'strict');
    $array = $mapping->toArray();

    $this->assertSame('strict', $array['mappings']['dynamic']);
  }

  public function testCreateWithStringDynamicRuntime(): void {
    $mapping = MappingFactory::create(dynamic: 'runtime');
    $array = $mapping->toArray();

    $this->assertSame('runtime', $array['mappings']['dynamic']);
  }

  public function testCreateWithInvalidDynamicStringThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    MappingFactory::create(dynamic: 'invalid');
  }

  public function testCreateWithDateDetectionDisabled(): void {
    $mapping = MappingFactory::create(date_detection: FALSE);
    $array = $mapping->toArray();

    $this->assertFalse($array['mappings']['date_detection']);
  }

  public function testCreateWithNumericDetectionEnabled(): void {
    $mapping = MappingFactory::create(numericDetection: TRUE);
    $array = $mapping->toArray();

    $this->assertTrue($array['mappings']['numeric_detection']);
  }

  public function testCreateWithProperties(): void {
    $fields = ['title' => ['type' => 'text'], 'status' => ['type' => 'boolean']];
    $mapping = MappingFactory::create(fields: $fields);
    $array = $mapping->toArray();

    $this->assertSame($fields, $array['mappings']['properties']);
  }

  public function testToArrayStructure(): void {
    $mapping = MappingFactory::create();
    $array = $mapping->toArray();

    $this->assertArrayHasKey('mappings', $array);
    $this->assertArrayHasKey('dynamic', $array['mappings']);
    $this->assertArrayHasKey('date_detection', $array['mappings']);
    $this->assertArrayHasKey('numeric_detection', $array['mappings']);
    $this->assertArrayHasKey('properties', $array['mappings']);
  }

  public function testFromArrayWithAllKeys(): void {
    $input = [
      'dynamic' => 'strict',
      'date_detection' => FALSE,
      'numeric_detection' => TRUE,
      'properties' => ['body' => ['type' => 'text']],
    ];

    $mapping = MappingFactory::fromArray($input);
    $array = $mapping->toArray();

    $this->assertSame('strict', $array['mappings']['dynamic']);
    $this->assertFalse($array['mappings']['date_detection']);
    $this->assertTrue($array['mappings']['numeric_detection']);
    $this->assertSame(['body' => ['type' => 'text']], $array['mappings']['properties']);
  }

  public function testFromArrayWithDefaults(): void {
    $mapping = MappingFactory::fromArray([]);
    $array = $mapping->toArray();

    $this->assertSame(TRUE, $array['mappings']['dynamic']);
    $this->assertTrue($array['mappings']['date_detection']);
    $this->assertFalse($array['mappings']['numeric_detection']);
    $this->assertSame([], $array['mappings']['properties']);
  }

  public function testFromArrayRoundtrip(): void {
    $input = [
      'dynamic' => 'runtime',
      'date_detection' => TRUE,
      'numeric_detection' => TRUE,
      'properties' => ['nid' => ['type' => 'long']],
    ];

    $array = MappingFactory::fromArray($input)->toArray()['mappings'];

    $this->assertSame('runtime', $array['dynamic']);
    $this->assertTrue($array['date_detection']);
    $this->assertTrue($array['numeric_detection']);
    $this->assertSame(['nid' => ['type' => 'long']], $array['properties']);
  }

}
