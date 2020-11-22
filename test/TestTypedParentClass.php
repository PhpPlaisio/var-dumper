<?php
declare(strict_types=1);

namespace Plaisio\Debug\Test;

/**
 * Just a typed class.
 *
 * @property-read int    $propertyInt
 * @property-read float  $propertyFloat
 * @property-read string $propertyString
 */
class TestTypedParentClass
{
  //--------------------------------------------------------------------------------------------------------------------
  protected bool $false = false;

  protected float $float = 3.14;

  protected int $int = 1;

  protected ?TestTypedClass $null = null;

  protected string $password = 'qwerty';

  protected $resource;

  protected string $string = 'Hello, World!';

  protected bool $true = true;

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
