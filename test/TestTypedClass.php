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
class TestTypedClass extends TestTypedParentClass
{
  //--------------------------------------------------------------------------------------------------------------------
  use TestTrait;

  //--------------------------------------------------------------------------------------------------------------------

  private bool $refFalse;

  private float $refFloat;

  private int $refInt;

  private ?TestTypedClass $refNull;

  private $refResource;

  private string $refString;

  private bool $refTrue;

  private string $void;

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

    $this->refInt      = &$this->int;
    $this->refFloat    = &$this->float;
    $this->refString   = &$this->string;
    $this->refTrue     = &$this->true;
    $this->refFalse    = &$this->false;
    $this->refNull     = &$this->null;
    $this->refResource = &$this->resource;

    $this->resource       = fopen('php://stdin', 'r');
    self::$staticResource = &$this->resource;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
