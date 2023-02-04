<?php
declare(strict_types=1);

namespace Plaisio\Debug;

/**
 * Stub for uninitialized type properties.
 */
class Uninitialized
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The singleton of this class.
   *
   * @var Uninitialized|null
   */
  private static ?Uninitialized $instance = null;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   */
  private function __construct()
  {
    // Nothing to do.
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the singleton of this class.
   *
   * @return static
   */
  public static function get(): self
  {
    if (self::$instance===null)
    {
      self::$instance = new Uninitialized();
    }

    return self::$instance;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
