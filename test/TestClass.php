<?php
declare(strict_types=1);

namespace Plaisio\Debug\Test;

/**
 * Just a class.
 *
 * @property-read bool   $propertyTrue
 * @property-read bool   $propertyFalse
 * @property-read null   $propertyNull
 */
class TestClass extends TestParentClass implements \Countable
{
  //--------------------------------------------------------------------------------------------------------------------
  use TestTrait;

  //--------------------------------------------------------------------------------------------------------------------
  private bool $false = false;

  private mixed $null = null;

  private bool $true = true;

  private $resource;

  private string $password = 'qwerty';

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

    $this->resource = fopen('php://stdin', 'r');
    self::$staticResource = &$this->resource;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns 0.
   *
   * @return int
   */
  public function count(): int
  {
    return 0;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
