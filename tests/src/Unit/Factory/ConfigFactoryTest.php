<?php

namespace Drupal\Tests\eticsearch\Unit\Factory;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\Config;
use Drupal\eticsearch\Factory\ConfigFactory;
use Drupal\Tests\UnitTestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\eticsearch\Factory\ConfigFactory
 * @group eticsearch
 */
class ConfigFactoryTest extends UnitTestCase {

  private ConfigFactoryInterface $mockConfigFactoryInterface;
  private ImmutableConfig $mockImmutableConfig;
  private Config $mockEditableConfig;
  private ConfigFactory $configFactory;

  protected function setUp(): void {
    parent::setUp();

    $this->mockImmutableConfig = $this->createMock(ImmutableConfig::class);
    $this->mockEditableConfig = $this->createMock(Config::class);

    $this->mockConfigFactoryInterface = $this->createMock(ConfigFactoryInterface::class);
    $this->mockConfigFactoryInterface
      ->method('get')
      ->with(ConfigFactory::CONFIG_KEY)
      ->willReturn($this->mockImmutableConfig);
    $this->mockConfigFactoryInterface
      ->method('getEditable')
      ->with(ConfigFactory::CONFIG_KEY)
      ->willReturn($this->mockEditableConfig);

    $this->configFactory = new ConfigFactory($this->mockConfigFactoryInterface);
  }

  // ------------------------------------------------------------- constants --

  public function testConfigKeyConstant(): void {
    $this->assertSame('eticsearch.configuration', ConfigFactory::CONFIG_KEY);
  }

  public function testThirdPartyKeyConstant(): void {
    $this->assertSame('eticsearch_settings', ConfigFactory::THIRD_PARTY_KEY);
  }

  // --------------------------------------------------------------------- get --

  public function testGetReturnsAllKeys(): void {
    $this->mockImmutableConfig->method('get')->willReturn(NULL);

    $result = $this->configFactory->get();

    $expectedKeys = [
      'etic:analyzers', 'etic:char_filters', 'etic:filters',
      'etic:normalizers', 'etic:tokenizers', 'etic:similarities', 'etic:indices',
      'es:host', 'es:port', 'es:auth_method', 'es:username', 'es:password',
      'es:api_key_id', 'es:api_key_secret', 'es:verify_ssl', 'es:ca_cert',
    ];
    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, $result);
    }
  }

  // ------------------------------------------------------------- set / _checkVal --

  public function testSetValidKey(): void {
    $this->mockEditableConfig->expects($this->once())
      ->method('set')
      ->with('etic:analyzers', [])
      ->willReturnSelf();
    $this->mockEditableConfig->expects($this->once())->method('save');

    $this->configFactory->set('etic:analyzers', []);
  }

  public function testSetInvalidKeyThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->configFactory->set('invalid:key', 'value');
  }

  public function testSetWrongTypeThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    // 'etic:analyzers' expects array, string is wrong type
    $this->configFactory->set('etic:analyzers', 'not-an-array');
  }

  public function testSetStringKeyWithStringValue(): void {
    $this->mockEditableConfig->expects($this->once())
      ->method('set')
      ->with('es:host', 'localhost')
      ->willReturnSelf();
    $this->mockEditableConfig->expects($this->once())->method('save');

    $this->configFactory->set('es:host', 'localhost');
  }

  // ----------------------------------------------------------------- delete --

  public function testDeleteValidKey(): void {
    $this->mockEditableConfig->expects($this->once())
      ->method('clear')
      ->with('es:host')
      ->willReturnSelf();
    $this->mockEditableConfig->expects($this->once())->method('save');

    $this->configFactory->delete('es:host');
  }

  public function testDeleteInvalidKeyThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->configFactory->delete('bad:key');
  }

  // --------------------------------------------------------------- getIndices --

  public function testGetIndicesReturnsEmptyArrayWhenNull(): void {
    $this->mockImmutableConfig->method('get')->with('etic:indices')->willReturn(NULL);
    $this->assertSame([], $this->configFactory->getIndices());
  }

  public function testGetIndicesReturnsData(): void {
    $indices = ['my_index' => ['indexName' => 'my_index']];
    $this->mockImmutableConfig->method('get')->with('etic:indices')->willReturn($indices);
    $this->assertSame($indices, $this->configFactory->getIndices());
  }

  // ---------------------------------------------------------- getAnalyzers --

  public function testGetAnalyzersReturnsEmptyArrayWhenNull(): void {
    $this->mockImmutableConfig->method('get')->with('etic:analyzers')->willReturn(NULL);
    $this->assertSame([], $this->configFactory->getAnalyzers());
  }

  public function testGetAnalyzersReturnsData(): void {
    $analyzers = ['my_analyzer' => ['name' => 'my_analyzer', 'type' => 'standard']];
    $this->mockImmutableConfig->method('get')->with('etic:analyzers')->willReturn($analyzers);
    $this->assertSame($analyzers, $this->configFactory->getAnalyzers());
  }

  // -------------------------------------------------------- deleteAnalyzer --

  public function testDeleteAnalyzerReturnsFalseWhenNotFound(): void {
    $this->mockEditableConfig->method('get')->with('etic:analyzers')->willReturn([]);
    $result = $this->configFactory->deleteAnalyzer('nonexistent');
    $this->assertFalse($result);
  }

  public function testDeleteAnalyzerReturnsTrueAndSaves(): void {
    $analyzers = ['my_analyzer' => ['name' => 'my_analyzer']];

    $this->mockEditableConfig->method('get')->with('etic:analyzers')->willReturn($analyzers);
    $this->mockEditableConfig->expects($this->once())
      ->method('set')
      ->with('etic:analyzers', [])
      ->willReturnSelf();
    $this->mockEditableConfig->expects($this->once())->method('save');

    $result = $this->configFactory->deleteAnalyzer('my_analyzer');
    $this->assertTrue($result);
  }

  // -------------------------------------------------------- getFilters --

  public function testGetFiltersReturnsEmptyArrayWhenNull(): void {
    $this->mockImmutableConfig->method('get')->with('etic:filters')->willReturn(NULL);
    $this->assertSame([], $this->configFactory->getFilters());
  }

  // ------------------------------------------------------ deleteFilter --

  public function testDeleteFilterReturnsFalseWhenNotFound(): void {
    $this->mockEditableConfig->method('get')->with('etic:filters')->willReturn([]);
    $this->assertFalse($this->configFactory->deleteFilter('nonexistent'));
  }

  // -------------------------------------------------------- getCharFilters --

  public function testGetCharFiltersReturnsEmptyArrayWhenNull(): void {
    $this->mockImmutableConfig->method('get')->with('etic:char_filters')->willReturn(NULL);
    $this->assertSame([], $this->configFactory->getCharFilters());
  }

  // ---------------------------------------------------- deleteCharFilter --

  public function testDeleteCharFilterReturnsFalseWhenNotFound(): void {
    $this->mockEditableConfig->method('get')->with('etic:char_filters')->willReturn([]);
    $this->assertFalse($this->configFactory->deleteCharFilter('nonexistent'));
  }

  // ------------------------------------------------------- getNormalizers --

  public function testGetNormalizersReturnsEmptyArrayWhenNull(): void {
    $this->mockImmutableConfig->method('get')->with('etic:normalizers')->willReturn(NULL);
    $this->assertSame([], $this->configFactory->getNormalizers());
  }

  // ----------------------------------------------------- deleteNormalizer --

  public function testDeleteNormalizerReturnsFalseWhenNotFound(): void {
    $this->mockEditableConfig->method('get')->with('etic:normalizers')->willReturn([]);
    $this->assertFalse($this->configFactory->deleteNormalizer('nonexistent'));
  }

  // ------------------------------------------------------- getTokenizers --

  public function testGetTokenizersReturnsEmptyArrayWhenNull(): void {
    $this->mockImmutableConfig->method('get')->with('etic:tokenizers')->willReturn(NULL);
    $this->assertSame([], $this->configFactory->getTokenizers());
  }

  // ----------------------------------------------------- deleteTokenizer --

  public function testDeleteTokenizerReturnsFalseWhenNotFound(): void {
    $this->mockEditableConfig->method('get')->with('etic:tokenizers')->willReturn([]);
    $this->assertFalse($this->configFactory->deleteTokenizer('nonexistent'));
  }

  // ----------------------------------------------------- getSimilarities --

  public function testGetSimilaritiesReturnsEmptyArrayWhenNull(): void {
    $this->mockImmutableConfig->method('get')->with('etic:similarities')->willReturn(NULL);
    $this->assertSame([], $this->configFactory->getSimilarities());
  }

  // --------------------------------------------------- deleteSimilarity --

  public function testDeleteSimilarityReturnsFalseWhenNotFound(): void {
    $this->mockEditableConfig->method('get')->with('etic:similarities')->willReturn([]);
    $this->assertFalse($this->configFactory->deleteSimilarity('nonexistent'));
  }

  public function testDeleteSimilarityReturnsTrueAndSaves(): void {
    $sims = ['my_sim' => ['name' => 'my_sim']];

    $this->mockEditableConfig->method('get')->with('etic:similarities')->willReturn($sims);
    $this->mockEditableConfig->expects($this->once())
      ->method('set')
      ->with('etic:similarities', [])
      ->willReturnSelf();
    $this->mockEditableConfig->expects($this->once())->method('save');

    $this->assertTrue($this->configFactory->deleteSimilarity('my_sim'));
  }

}
