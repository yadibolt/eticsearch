<?php

namespace Drupal\Tests\eticsearch\Unit;

use Drupal\eticsearch\Similarity;
use Drupal\Tests\UnitTestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\eticsearch\Similarity
 * @group eticsearch
 */
class SimilarityTest extends UnitTestCase {

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
    Similarity::create('sim', 'TF-IDF');
  }

  // -------------------------------------------------------------- BM25 --

  public function testCreateBM25Defaults(): void {
    $sim = Similarity::create('my_sim', 'BM25');
    $array = $sim->toArray();

    $this->assertSame('BM25', $array['type']);
    $this->assertSame(1.2, $array['k1']);
    $this->assertSame(0.75, $array['b']);
    $this->assertTrue($array['discount_overlaps']);
  }

  public function testCreateBM25Custom(): void {
    $sim = Similarity::create('my_sim', 'BM25', k1: 2.0, b: 0.5, discountOverlaps: FALSE);
    $array = $sim->toArray();

    $this->assertSame(2.0, $array['k1']);
    $this->assertSame(0.5, $array['b']);
    $this->assertFalse($array['discount_overlaps']);
  }

  // --------------------------------------------------------------- DFR --

  public function testCreateDFRDefaults(): void {
    $sim = Similarity::create('my_sim', 'DFR');
    $array = $sim->toArray();

    $this->assertSame('DFR', $array['type']);
    $this->assertSame('g', $array['basic_model']);
    $this->assertSame('l', $array['after_effect']);
    $this->assertSame('h2', $array['normalization']);
    $this->assertSame(1.0, $array['normalization.h2.c']);
  }

  public function testCreateDFRWithH1Normalization(): void {
    $sim = Similarity::create('my_sim', 'DFR', normalization: 'h1', normalizationH1C: 2.5);
    $array = $sim->toArray();

    $this->assertSame('h1', $array['normalization']);
    $this->assertSame(2.5, $array['normalization.h1.c']);
    $this->assertArrayNotHasKey('normalization.h2.c', $array);
  }

  public function testCreateDFRWithH3Normalization(): void {
    $sim = Similarity::create('my_sim', 'DFR', normalization: 'h3', normalizationH3C: 900.0);
    $array = $sim->toArray();

    $this->assertSame(900.0, $array['normalization.h3.c']);
  }

  public function testCreateDFRWithZNormalization(): void {
    $sim = Similarity::create('my_sim', 'DFR', normalization: 'z', normalizationZZ: 0.5);
    $array = $sim->toArray();

    $this->assertSame(0.5, $array['normalization.z.z']);
  }

  public function testCreateDFRWithNoNormalization(): void {
    $sim = Similarity::create('my_sim', 'DFR', normalization: 'no');
    $array = $sim->toArray();

    $this->assertSame('no', $array['normalization']);
    $this->assertArrayNotHasKey('normalization.h1.c', $array);
    $this->assertArrayNotHasKey('normalization.h2.c', $array);
  }

  public function testCreateDFRInvalidBasicModelThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Similarity::create('my_sim', 'DFR', basicModel: 'x');
  }

  public function testCreateDFRInvalidAfterEffectThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Similarity::create('my_sim', 'DFR', afterEffect: 'x');
  }

  public function testCreateDFRInvalidNormalizationThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Similarity::create('my_sim', 'DFR', normalization: 'h4');
  }

  // --------------------------------------------------------------- DFI --

  public function testCreateDFI(): void {
    $sim = Similarity::create('my_sim', 'DFI');
    $array = $sim->toArray();

    $this->assertSame('DFI', $array['type']);
    $this->assertSame('standardized', $array['independence_measure']);
  }

  public function testCreateDFIWithMeasure(): void {
    $sim = Similarity::create('my_sim', 'DFI', independenceMeasure: 'saturated');
    $array = $sim->toArray();

    $this->assertSame('saturated', $array['independence_measure']);
  }

  public function testCreateDFIInvalidMeasureThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Similarity::create('my_sim', 'DFI', independenceMeasure: 'bad_measure');
  }

  // ---------------------------------------------------------------- IB --

  public function testCreateIB(): void {
    $sim = Similarity::create('my_sim', 'IB');
    $array = $sim->toArray();

    $this->assertSame('IB', $array['type']);
    $this->assertSame('ll', $array['distribution']);
    $this->assertSame('df', $array['lambda']);
    $this->assertSame('h2', $array['normalization']);
  }

  public function testCreateIBWithSplDistribution(): void {
    $sim = Similarity::create('my_sim', 'IB', distribution: 'spl');
    $array = $sim->toArray();

    $this->assertSame('spl', $array['distribution']);
  }

  public function testCreateIBInvalidDistributionThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Similarity::create('my_sim', 'IB', distribution: 'bad');
  }

  public function testCreateIBInvalidLambdaThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    Similarity::create('my_sim', 'IB', ibLambda: 'bad');
  }

  // -------------------------------------------------------- LMDirichlet --

  public function testCreateLMDirichlet(): void {
    $sim = Similarity::create('my_sim', 'LMDirichlet', mu: 3000);
    $array = $sim->toArray();

    $this->assertSame('LMDirichlet', $array['type']);
    $this->assertSame(3000, $array['mu']);
  }

  // ------------------------------------------------------ LMJelinekMercer --

  public function testCreateLMJelinekMercer(): void {
    $sim = Similarity::create('my_sim', 'LMJelinekMercer', lambda: 0.7);
    $array = $sim->toArray();

    $this->assertSame('LMJelinekMercer', $array['type']);
    $this->assertSame(0.7, $array['lambda']);
  }

  // ----------------------------------------------------------- scripted --

  public function testCreateScripted(): void {
    $script = ['source' => 'double tf = Math.sqrt(doc.freq); return tf * boost;'];
    $sim = Similarity::create('my_sim', 'scripted', script: $script);
    $array = $sim->toArray();

    $this->assertSame('scripted', $array['type']);
    $this->assertSame($script, $array['script']);
    $this->assertArrayNotHasKey('weight_script', $array);
    $this->assertArrayNotHasKey('params', $array);
  }

  public function testCreateScriptedWithWeightScript(): void {
    $script = ['source' => 'return query.boost;'];
    $weightScript = ['source' => 'double idf = 1.0; return idf;'];
    $sim = Similarity::create('my_sim', 'scripted', script: $script, weightScript: $weightScript);
    $array = $sim->toArray();

    $this->assertSame($weightScript, $array['weight_script']);
  }

  public function testCreateScriptedWithParams(): void {
    $script = ['source' => 'return params.factor * query.boost;'];
    $params = ['factor' => 1.5];
    $sim = Similarity::create('my_sim', 'scripted', script: $script, params: $params);
    $array = $sim->toArray();

    $this->assertSame($params, $array['params']);
  }

  // ----------------------------------------------------------- getName --

  public function testGetName(): void {
    $sim = Similarity::create('my_bm25', 'BM25');
    $this->assertSame('my_bm25', $sim->getName());
  }

  // --------------------------------------------------------- fromArray --

  public function testFromArrayBM25(): void {
    $entry = ['name' => 'custom_bm25', 'type' => 'BM25', 'k1' => 1.5, 'b' => 0.8, 'discount_overlaps' => FALSE];
    $sim = Similarity::fromArray($entry);
    $array = $sim->toArray();

    $this->assertSame('custom_bm25', $array['name']);
    $this->assertSame(1.5, $array['k1']);
    $this->assertSame(0.8, $array['b']);
    $this->assertFalse($array['discount_overlaps']);
  }

  public function testFromArrayDefaults(): void {
    $sim = Similarity::fromArray([]);
    $this->assertSame('similarity', $sim->getName());
  }

}
