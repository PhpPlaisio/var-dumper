<?php
declare(strict_types=1);

namespace Plaisio\Debug\Test;

/**
 * Just a typed class.
 *
 * @property-read int    $propertyInt
 * @property-read float  $propertyFloat
 * @property-read string $propertyString
 * @property-read bool   $propertyTrue
 * @property-read bool   $propertyFalse
 * @property-read null   $propertyNull
 */
class TestTypedClass
{
  //--------------------------------------------------------------------------------------------------------------------
  use TestTrait;

  //--------------------------------------------------------------------------------------------------------------------
  private bool $false = false;

  private float $float = 3.14;

  private int $int = 1;

  private ?TestTypedClass $null = null;

  private string $password = 'qwerty';

  private \resource $resource;

  private string $string = 'Hello, World!';

  private bool $true = true;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * TestClass constructor.
   */
  public function __construct()
  {
    $this->propertyInt    = 1;
    $this->propertyFloat  = 3.14;
    $this->propertyString = 'Hello, World!';
    $this->propertyTrue   = true;
    $this->propertyFalse  = false;
    $this->propertyNull   = null;

    $this->resource       = fopen('php://stdin', 'r');
  //  self::$staticResource = &$this->resource;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
