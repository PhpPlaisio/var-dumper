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
   * Returns a reference to a non static property of an object.
   *
   * @param object $object   The object.
   * @param string $property The name of the property.
   *
   * @return mixed
   */
  private static function &getProperty($object, $property)
  {
    $value = &\Closure::bind(function & () use ($property)
    {
      return $this->$property;
    }, $object, $object)->__invoke();

    return $value;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a reference to a static property of an object.
   *
   * @param object $object   The object.
   * @param string $property The name of the property.
   *
   * @return mixed
   */
  private static function &getStaticProperty($object, $property)
  {
    $value = &\Closure::bind(function & () use ($property)
    {
      return self::$$property;
    }, $object, $object)->__invoke();

    return $value;
  }

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
   * @param array  $value The array.
   * @param string $name  Variable name.
   */
  private function dumpArray(&$value, $name)
  {
    list($id, $ref) = $this->isReference($value);

    if ($ref===null)
    {
      $this->writer->writeArrayOpen($id, $name);

      foreach ($value as $key => &$item)
      {
        $this->recursiveDump($item, $key);
      }

      $this->writer->writeArrayClose($id, $name);
    }
    else
    {
      $this->writer->writeArrayReference($ref, $name);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a boolean.
   *
   * @param bool   $value The boolean.
   * @param string $name  The name of the variable.
   */
  private function dumpBool(&$value, $name)
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

    $this->writer->writeBool($id, $ref, $value, $name);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a float.
   *
   * @param float  $value The float.
   * @param string $name  The name of the variable.
   */
  private function dumpFloat(&$value, $name)
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

    $this->writer->writeFloat($id, $ref, $value, $name);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps an integer.
   *
   * @param int    $value The integer.
   * @param string $name  The name of the variable.
   */
  private function dumpInt(&$value, $name)
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

    $this->writer->writeInt($id, $ref, $value, $name);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps null.
   *
   * @param null   $value The null.
   * @param string $name  The name of the variable.
   */
  private function dumpNull(&$value, $name)
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

    $this->writer->writeNull($id, $ref, $name);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps an object.
   *
   * @param object $value The object.
   * @param string $name  The name of the variable.
   */
  private function dumpObject($value, $name)
  {
    list($id, $ref) = $this->isReference($value);

    if ($ref===null)
    {
      $this->writer->writeObjectOpen($id, $name, get_class($value));

      // Dump all fields of the object, unless the object is me.
      if ($this!==$value)
      {
        $reflect    = new \ReflectionClass($value);
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC |
                                              \ReflectionProperty::IS_PROTECTED |
                                              \ReflectionProperty::IS_PRIVATE);

        if (strpos(get_class($value), '\\')!==false)
        {
          foreach ($properties as $property)
          {
            $propertyName = $property->getName();
            if ($property->isStatic())
            {
              $propertyValue = &self::getStaticProperty($value, $propertyName);
            }
            else
            {
              $propertyValue = &self::getProperty($value, $propertyName);
            }

            $this->recursiveDump($propertyValue, $propertyName);
          }
        }
      }

      $this->writer->writeObjectClose($id, $name, get_class($value));
    }
    else
    {
      $this->writer->writeObjectReference($ref, $name, get_class($value));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a resource.
   *
   * @param resource $value The resource.
   * @param string   $name  The name of the variable.
   */
  private function dumpResource($value, $name)
  {
    list($id, $ref) = $this->isReference($value);

    $this->writer->writeResource($id, $ref, $name, get_resource_type($value));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a string.
   *
   * @param string $value The string.
   * @param string $name  The name of the variable.
   */
  private function dumpString(&$value, $name)
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

    $this->writer->writeString($id, $ref, $value, $name);
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
      case is_resource($value):
        $ref = $this->testSeen($value);
        break;

      case is_object($value):
        $ref = $this->testSeenObject($value);
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
   * @param mixed  $value The variable.
   * @param string $name  Variable The name of the variable.
   */
  private function recursiveDump(&$value, $name)
  {
    switch (true)
    {
      case is_null($value):
        $this->dumpNull($value, $name);
        break;

      case is_bool($value):
        $this->dumpBool($value, $name);
        break;

      case is_float($value):
        $this->dumpFloat($value, $name);
        break;

      case is_int($value):
        $this->dumpInt($value, $name);
        break;

      case is_string($value):
        if (stripos($name, 'password')===false)
        {
          $this->dumpString($value, $name);
        }
        else
        {
          // Do not dump the value of a variable/key with a name tha contains 'password'.
          $this->dumpString(str_repeat('*', 12), $name);
        }
        break;

      case is_object($value):
        $this->dumpObject($value, $name);
        break;

      case is_array($value):
        $this->dumpArray($value, $name);
        break;

      case is_resource($value):
        $this->dumpResource($value, $name);
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
      case is_bool($value):
      case is_double($value):
      case is_int($value):
      case is_null($value):
      case is_resource($value):
      case is_string($value):
        foreach ($this->seen as $ref => &$item)
        {
          if (!is_array($item) && !is_object($item))
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
  /**
   * If an object has been seen before returns the ID of the variable. Otherwise returns null.
   *
   * @param object $value The value.
   *
   * @return int|null
   */
  private function testSeenObject($value)
  {
    foreach ($this->seen as $ref => $item)
    {
      if (is_object($item) && $item===$value)
      {
        return $ref;
      }
    }

    $this->seen[] = $value;

    return null;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
