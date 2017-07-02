<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Abc\Debug;

use SetBased\Exception\FallenException;

/**
 * A VarDumper with minimal memory foot print that detects references and recursion and writes data directly to a
 * stream.
 */
class VarDumper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The object for rendering the var dump is the desired output format.
   *
   * @var VarWriter
   */
  public $writer;

  /**
   * A unique string that is not key in any array.
   *
   * @var string
   */
  private $gid;

  /**
   * If true scalar references to values must be traced.
   *
   * @var bool
   */
  private $scalarReferences;

  /**
   * The variables that we have dumped so var.
   *
   * @var \mixed[]
   */
  private $seen;

  //--------------------------------------------------------------------------------------------------------------------

  /**
   * Returns true if and only if two variables are references to the same variable content.
   *
   * @param mixed $first  The first variable.
   * @param mixed $second The second variable.
   *
   * @return bool
   */
  private static function testReferences(&$first, &$second)
  {
    if ($first!==$second)
    {
      return false;
    }

    $value_of_first = $first;
    $first          = ($first===true) ? false : true;
    $is_ref         = ($first===$second);
    $first          = $value_of_first;

    return $is_ref;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main function for dumping.
   *
   * @param string $name             The name of the variable.
   * @param mixed  $value            Variable for dumping.
   * @param bool   $scalarReferences If true scalar references to values must be traced.
   *
   * @api
   * @since 1.0.0
   */
  public function dump($name, &$value, $scalarReferences = false)
  {
    $this->seen             = [];
    $this->scalarReferences = $scalarReferences;
    $this->gid              = uniqid(mt_rand(), true);

    $this->writer->start();

    $this->recursiveDump($value, $name);

    $this->writer->stop();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps an array.
   *
   * @param array  $value   The array.
   * @param string $name    Variable name.
   * @param string $keyType When the name of the variable is key of an array the type of the key (integer or string).
   */
  private function dumpArray(&$value, $name, $keyType)
  {
    list($id, $ref) = $this->isReference($value);

    if ($ref===null)
    {
      $this->writer->writeArrayOpen($id, $name, $keyType);

      foreach ($value as $key => &$item)
      {
        $this->recursiveDump($item, $key, gettype($key));
      }

      $this->writer->writeArrayClose();
    }
    else
    {
      $this->writer->writeArrayReference($ref, $name, $keyType);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a boolean.
   *
   * @param bool   $value   The boolean.
   * @param string $name    The name of the variable.
   * @param string $keyType When the name of the variable is key of an array the type of the key (integer or string).
   */
  private function dumpBool(&$value, $name, $keyType)
  {
    if ($this->scalarReferences)
    {
      list($id, $ref) = $this->isReference($value);
    }
    else
    {
      $id  = null;
      $ref = null;
    }

    $this->writer->writeBool($id, $ref, $value, $name, $keyType);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a float.
   *
   * @param float  $value   The float.
   * @param string $name    The name of the variable.
   * @param string $keyType When the name of the variable is key of an array the type of the key (integer or string).
   */
  private function dumpFloat(&$value, $name, $keyType)
  {
    if ($this->scalarReferences)
    {
      list($id, $ref) = $this->isReference($value);
    }
    else
    {
      $id  = null;
      $ref = null;
    }

    $this->writer->writeFloat($id, $ref, $value, $name, $keyType);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps an integer.
   *
   * @param int    $value   The integer.
   * @param string $name    The name of the variable.
   * @param string $keyType When the name of the variable is key of an array the type of the key (integer or string).
   */
  private function dumpInt(&$value, $name, $keyType)
  {
    if ($this->scalarReferences)
    {
      list($id, $ref) = $this->isReference($value);
    }
    else
    {
      $id  = null;
      $ref = null;
    }

    $this->writer->writeInt($id, $ref, $value, $name, $keyType);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps null.
   *
   * @param object $value   The null.
   * @param string $name    The name of the variable.
   * @param string $keyType When the name of the variable is key of an array the type of the key (integer or string).
   */
  private function dumpNull(&$value, $name, $keyType)
  {
    if ($this->scalarReferences)
    {
      list($id, $ref) = $this->isReference($value);
    }
    else
    {
      $id  = null;
      $ref = null;
    }

    $this->writer->writeNull($id, $ref, $name, $keyType);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps an object.
   *
   * @param object $value   The object.
   * @param string $name    The name of the variable.
   * @param string $keyType When the name of the variable is key of an array the type of the key (integer or string).
   */
  private function dumpObject($value, $name, $keyType)
  {
    list($id, $ref) = $this->isReference($value);

    if ($ref===null)
    {
      $this->writer->writeObjectOpen($id, $name, $keyType, get_class($value));

      // Dump all fields of the object, unless the object is me.
      if ($this!==$value)
      {
        $properties = get_object_vars($value);
        foreach ($properties as $key => &$item)
        {
          $this->recursiveDump($item, $key);
        }
      }

      $this->writer->writeObjectClose();
    }
    else
    {
      $this->writer->writeObjectReference($ref, $name, $keyType, get_class($value));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a resource.
   *
   * @param resource $value   The resource.
   * @param string   $name    The name of the variable.
   * @param string   $keyType When the name of the variable is key of an array the type of the key (integer or string).
   */
  private function dumpResource($value, $name, $keyType)
  {
    list($id, $ref) = $this->isReference($value);

    if ($ref===null)
    {
      $this->writer->writeResource($id, $name, $keyType, get_resource_type($value));
    }
    else
    {
      $this->writer->writeResourceReference($ref, $name, $keyType, get_resource_type($value));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a string.
   *
   * @param string $value   The string.
   * @param string $name    The name of the variable.
   * @param string $keyType When the name of the variable is key of an array the type of the key (integer or string).
   */
  private function dumpString(&$value, $name, $keyType)
  {
    if ($this->scalarReferences)
    {
      list($id, $ref) = $this->isReference($value);
    }
    else
    {
      $id  = null;
      $ref = null;
    }

    $this->writer->writeString($id, $ref, $value, $name, $keyType);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * If a value has been dumped before returns the ID of the variable. Otherwise returns null.
   *
   * @param mixed $value The variable.
   *
   * @return array<int|null>
   */
  private function isReference(&$value)
  {
    switch (true)
    {
      case is_bool($value):
      case is_null($value):
      case is_int($value):
      case is_string($value):
      case is_double($value):
      case is_object($value):
      case is_resource($value):
        $ref = $this->testSeen($value);
        break;

      case is_array($value):
        $ref = $this->testSeenArray($value);
        break;

      default:
        throw new FallenException('type', gettype($value));
    }

    $id = ($ref===null) ? sizeof($this->seen) - 1 : null;

    return [$id, $ref];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps recursively a variable.
   *
   * @param mixed       $value   The variable.
   * @param string      $name    Variable The name of the variable.
   * @param string|null $keyType When the name of the variable is key of an array the type of the key (integer or
   *                             string).
   */
  private function recursiveDump(&$value, $name, $keyType=null)
  {
    switch (true)
    {
      case is_null($value):
        $this->dumpNull($value, $name, $keyType);
        break;

      case is_bool($value):
        $this->dumpBool($value, $name, $keyType);
        break;

      case is_float($value):
        $this->dumpFloat($value, $name, $keyType);
        break;

      case is_int($value):
        $this->dumpInt($value, $name, $keyType);
        break;

      case is_string($value):
        $this->dumpString($value, $name, $keyType);
        break;

      case is_object($value):
        $this->dumpObject($value, $name, $keyType);
        break;

      case is_array($value):
        $this->dumpArray($value, $name, $keyType);
        break;

      case is_resource($value):
        $this->dumpResource($value, $name, $keyType);
        break;

      default:
        throw new FallenException('type', gettype($value));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * If a value (not an array) has been seen before returns the ID of the variable. Otherwise returns null.
   *
   * @param mixed $value The value.
   *
   * @return int|null
   */
  private function testSeen(&$value)
  {
    switch (true)
    {
      case is_object($value):
        foreach ($this->seen as $ref => $item)
        {
          if ($item===$value)
          {
            return $ref;
          }
        }
        break;

      case is_bool($value):
      case is_double($value):
      case is_int($value):
      case is_null($value):
      case is_resource($value):
      case is_string($value):
        foreach ($this->seen as $ref => &$item)
        {
          if (!is_array($item))
          {
            $check = self::testReferences($item, $value);
            if ($check)
            {
              return $ref;
            }
          }
        }
        break;

      default:
        throw new FallenException('gettype', gettype($value));
    }

    $this->seen[] = &$value;

    return null;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * If an array has been seen before returns the ID of the array. Otherwise returns null.
   *
   * @param array $value The value.
   *
   * @return int|null
   */
  private function testSeenArray(&$value)
  {
    $value[$this->gid] = true;

    foreach ($this->seen as $ref => &$item)
    {
      if (is_array($item))
      {
        if (isset($item[$this->gid]))
        {
          unset($value[$this->gid]);

          return $ref;
        }
      }
    }

    unset($value[$this->gid]);

    $this->seen[] = &$value;

    return null;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
