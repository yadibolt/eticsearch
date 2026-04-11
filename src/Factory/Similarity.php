<?php

namespace Drupal\eticsearch\Factory;

use InvalidArgumentException;

class Similarity
{
  public const array CONFIGURABLE_SIMILARITY_TYPES = [
    'BM25', 'DFR', 'DFI', 'IB', 'LMDirichlet', 'LMJelinekMercer', 'scripted',
  ];
  public const array DFR_BASIC_MODELS = ['g', 'if', 'in', 'ine'];
  public const array DFR_AFTER_EFFECTS = ['b', 'l', 'no'];
  public const array DFR_IB_NORMALIZATIONS = ['no', 'h1', 'h2', 'h3', 'z'];
  public const array IB_DISTRIBUTIONS = ['ll', 'spl'];
  public const array IB_LAMBDAS = ['df', 'ttf'];
  public const array DFI_MEASURES = ['standardized', 'saturated', 'chisquared'];

  private string $name = 'similarity';
  private string $type = 'BM25';
  private float $k1 = 1.2;
  private float $b = 0.75;
  private bool $discountOverlaps = TRUE;
  private string $basicModel = 'g';
  private string $afterEffect = 'l';
  private string $normalization = 'h2';
  private float $normalizationH1C = 1.0;
  private float $normalizationH2C = 1.0;
  private float $normalizationH3C = 800.0;
  private float $normalizationZZ = 0.3;
  private string $independenceMeasure = 'standardized';
  private string $distribution = 'll';
  private string $ibLambda = 'df';
  private int $mu = 2000;
  private float $lambda = 0.1;
  private array $script = ['source' => NULL];
  private ?array $weightScript = NULL;
  private array $params = [];

  public function __construct()
  {
  }

  public static function create(string $name, string $type, float $k1 = 1.2, float $b = 0.75, bool $discountOverlaps = TRUE, string $basicModel = 'g',
                                string $afterEffect = 'l', string $normalization = 'h2', float $normalizationH1C = 1.0, float $normalizationH2C = 1.0, float $normalizationH3C = 800.0,
                                float  $normalizationZZ = 0.3, string $independenceMeasure = 'standardized', string $distribution = 'll', string $ibLambda = 'df', int $mu = 2000,
                                float  $lambda = 0.1, array $script = ['source' => NULL], ?array $weightScript = NULL, array $params = []): self
  {
    if (!in_array($type, self::CONFIGURABLE_SIMILARITY_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        'create only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_SIMILARITY_TYPES)
      );
    }

    $instance = new self();
    $instance->_setName($name);
    $instance->_setType($type);
    $instance->_setK1($k1);
    $instance->_setB($b);
    $instance->_setDiscountOverlaps($discountOverlaps);
    $instance->_setBasicModel($basicModel);
    $instance->_setAfterEffect($afterEffect);
    $instance->_setNormalization($normalization);
    $instance->_setNormalizationH1C($normalizationH1C);
    $instance->_setNormalizationH2C($normalizationH2C);
    $instance->_setNormalizationH3C($normalizationH3C);
    $instance->_setNormalizationZZ($normalizationZZ);
    $instance->_setIndependenceMeasure($independenceMeasure);
    $instance->_setDistribution($distribution);
    $instance->_setIbLambda($ibLambda);
    $instance->_setMu($mu);
    $instance->_setLambda($lambda);
    $instance->_setScript($script);
    $instance->_setWeightScript($weightScript);
    $instance->_setParams($params);

    return $instance;
  }

  public static function load(string $indexName, string $similarityName): ?self
  {
    // todo: return instantiated index factory from the config or null if does not exists
  }

  public static function delete(string $indexName, string $similarityName): bool
  {
    // todo: implement config delete
    // todo: implement index deletion in ES
  }

  /**
   * Formats the similarity configuration as an array for use in ES config.
   * This method will only include properties relevant to the similarity type.
   * @return array
   */
  public function toArray(): array
  {
    $props = [
      'type' => $this->type,
    ];

    switch ($this->type) {
      case 'BM25':
        $props['k1'] = $this->k1;
        $props['b'] = $this->b;
        $props['discount_overlaps'] = $this->discountOverlaps;
        break;
      case 'DFR':
        $props['basic_model'] = $this->basicModel;
        $props['after_effect'] = $this->afterEffect;
        $props['normalization'] = $this->normalization;

        if ($this->normalization === 'h1') $props['normalization.h1.c'] = $this->normalizationH1C;
        if ($this->normalization === 'h2') $props['normalization.h2.c'] = $this->normalizationH2C;
        if ($this->normalization === 'h3') $props['normalization.h3.c'] = $this->normalizationH3C;
        if ($this->normalization === 'z') $props['normalization.z.z'] = $this->normalizationZZ;
        break;
      case 'DFI':
        $props['independence_measure'] = $this->independenceMeasure;
        break;
      case 'IB':
        $props['distribution'] = $this->distribution;
        $props['lambda'] = $this->ibLambda;
        $props['normalization'] = $this->normalization;

        if ($this->normalization === 'h1') $props['normalization.h1.c'] = $this->normalizationH1C;
        if ($this->normalization === 'h2') $props['normalization.h2.c'] = $this->normalizationH2C;
        if ($this->normalization === 'h3') $props['normalization.h3.c'] = $this->normalizationH3C;
        if ($this->normalization === 'z') $props['normalization.z.z'] = $this->normalizationZZ;
        break;

      case 'LMDirichlet':
        $props['mu'] = $this->mu;
        break;
      case 'LMJelinekMercer':
        $props['lambda'] = $this->lambda;
        break;
      case 'scripted':
        $props['script'] = $this->script;

        if ($this->weightScript !== NULL) $props['weight_script'] = $this->weightScript;
        if (!empty($this->params)) $props['params'] = $this->params;
        break;
      default:
        throw new InvalidArgumentException(
          'toArray only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_SIMILARITY_TYPES)
        );
    }

    return $props;
  }

  public function save()
  {
    // todo: implement config save
    // todo: implement index creation in ES
  }

  private function _setName(string $name): void
  {
    $this->name = $name;
  }

  public function getName(): string
  {
    return $this->name;
  }

  private function _setType(string $type): void
  {
    if (!in_array($type, self::CONFIGURABLE_SIMILARITY_TYPES, TRUE)) {
      throw new InvalidArgumentException(
        '_setType only accepts type as one of: ' . implode(', ', self::CONFIGURABLE_SIMILARITY_TYPES)
      );
    }

    $this->type = $type;
  }

  private function _setK1(float $k1): void
  {
    $this->k1 = $k1;
  }

  private function _setB(float $b): void
  {
    $this->b = $b;
  }

  private function _setDiscountOverlaps(bool $discountOverlaps): void
  {
    $this->discountOverlaps = $discountOverlaps;
  }

  private function _setBasicModel(string $basicModel): void
  {
    if (!in_array($basicModel, self::DFR_BASIC_MODELS, TRUE)) {
      throw new InvalidArgumentException(
        '_setBasicModel only accepts basic_model as one of: ' . implode(', ', self::DFR_BASIC_MODELS)
      );
    }

    $this->basicModel = $basicModel;
  }

  private function _setAfterEffect(string $afterEffect): void
  {
    if (!in_array($afterEffect, self::DFR_AFTER_EFFECTS, TRUE)) {
      throw new InvalidArgumentException(
        '_setAfterEffect only accepts after_effect as one of: ' . implode(', ', self::DFR_AFTER_EFFECTS)
      );
    }

    $this->afterEffect = $afterEffect;
  }

  private function _setNormalization(string $normalization): void
  {
    if (!in_array($normalization, self::DFR_IB_NORMALIZATIONS, TRUE)) {
      throw new InvalidArgumentException(
        '_setNormalization only accepts normalization as one of: ' . implode(', ', self::DFR_IB_NORMALIZATIONS)
      );
    }

    $this->normalization = $normalization;
  }

  private function _setNormalizationH1C(float $normalizationH1C): void
  {
    $this->normalizationH1C = $normalizationH1C;
  }

  private function _setNormalizationH2C(float $normalizationH2C): void
  {
    $this->normalizationH2C = $normalizationH2C;
  }

  private function _setNormalizationH3C(float $normalizationH3C): void
  {
    $this->normalizationH3C = $normalizationH3C;
  }

  private function _setNormalizationZZ(float $normalizationZZ): void
  {
    $this->normalizationZZ = $normalizationZZ;
  }

  private function _setIndependenceMeasure(string $independenceMeasure): void
  {
    if (!in_array($independenceMeasure, self::DFI_MEASURES, TRUE)) {
      throw new InvalidArgumentException(
        '_setIndependenceMeasure only accepts independence_measure as one of: ' . implode(', ', self::DFI_MEASURES)
      );
    }

    $this->independenceMeasure = $independenceMeasure;
  }

  private function _setDistribution(string $distribution): void
  {
    if (!in_array($distribution, self::IB_DISTRIBUTIONS, TRUE)) {
      throw new InvalidArgumentException(
        '_setDistribution only accepts distribution as one of: ' . implode(', ', self::IB_DISTRIBUTIONS)
      );
    }

    $this->distribution = $distribution;
  }

  private function _setIbLambda(string $ibLambda): void
  {
    if (!in_array($ibLambda, self::IB_LAMBDAS, TRUE)) {
      throw new InvalidArgumentException(
        '_setIbLambda only accepts lambda as one of: ' . implode(', ', self::IB_LAMBDAS)
      );
    }

    $this->ibLambda = $ibLambda;
  }

  private function _setMu(int $mu): void
  {
    $this->mu = $mu;
  }

  private function _setLambda(float $lambda): void
  {
    $this->lambda = $lambda;
  }

  private function _setScript(array $script): void
  {
    $this->script = $script;
  }

  private function _setWeightScript(?array $weightScript): void
  {
    $this->weightScript = $weightScript;
  }

  private function _setParams(array $params): void
  {
    $this->params = $params;
  }
}
