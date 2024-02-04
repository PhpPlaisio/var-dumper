<?php
declare(strict_types=1);

namespace Plaisio\Debug;

use SetBased\Exception\FallenException;

/**
 * A VarDumper with minimal memory footprint that detects references and recursion and writes data directly to a
 * stream.
 */
class VarDumper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * A unique string that is not key in any array.
   *
   * @var string
   */
  private string $gid;

  /**
   * If true scalar references to values must be traced.
   *
   * @var bool
   */
  private bool $scalarReferences;

  /**
   * The variables that we have dumped so var.
   *
   * @var array
   */
  private array $seen;

  /**
   * The object for rendering the var dump in the desired output format.
   *
   * @var VarWriter
   */
  private VarWriter $writer;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param VarWriter $writer The object for rendering the var dump in the desired output format.
   */
  public function __construct(VarWriter $writer)
  {
    $this->writer = $writer;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a reference to a non-static property of an object.
   *
   * @param object $object   The object.
   * @param string $property The name of the property.
   *
   * @return mixed
   */
  private static function &getProperty(object $object, string $property): mixed
  {
    try
    {
      $value = &\Closure::bind(function & () use ($property) {
        return $this->$property;
      }, $object, $object)->__invoke();
    }
    catch (\Error)
    {
      // Property in an uninitialized typed property.
      $value = Uninitialized::get();
    }

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
  private static function &getStaticProperty(object $object, string $property): mixed
  {
    try
    {
      $value = &\Closure::bind(function & () use ($property) {
        return self::$$property;
      }, $object, $object)->__invoke();
    }
    catch (\Error)
    {
      // Property in an uninitialized typed property.
      $value = Uninitialized::get();
    }

    return $value;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns true if and only if two variables are references to the same variable content.
   *
   * @param mixed $variable1 The first variable.
   * @param mixed $variable2 The second variable.
   * @param mixed $value1    The first alternative value.
   * @param mixed $value2    The second alternative value.
   *
   * @return bool
   */
  private static function testReferences(mixed &$variable1, mixed &$variable2, mixed $value1, mixed $value2): bool
  {
    if ($variable1!==$variable2)
    {
      return false;
    }

    $first     = $variable1;
    $variable1 = ($variable1===$value1) ? $value2 : $value1;
    $isRef     = ($variable1===$variable2);
    $variable1 = $first;

    return $isRef;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main function for dumping.
   *
   * @param string|int|null $name             The name of the variable.
   * @param mixed           $value            Value to be dumped.
   * @param bool            $scalarReferences If true scalar references to values must be traced.
   *
   * @api
   * @since 1.0.0
   */
  public function dump(mixed $name, mixed &$value, bool $scalarReferences = false): void
  {
    $this->seen             = [];
    $this->scalarReferences = $scalarReferences;
    $this->gid              = uniqid((string)mt_rand(), true);

    $this->writer->start();

    $this->recursiveDump($value, $name);

    $this->writer->stop();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps an array.
   *
   * @param array           $value The array.
   * @param string|int|null $name  Variable name.
   */
  private function dumpArray(array &$value, mixed $name): void
  {
    [$id, $ref] = $this->isReference($value);

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
   * @param bool            $value The boolean.
   * @param string|int|null $name  The name of the variable.
   */
  private function dumpBool(bool &$value, mixed $name): void
  {
    if ($this->scalarReferences)
    {
      [$id, $ref] = $this->isReference($value);
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
   * @param float           $value The float.
   * @param string|int|null $name  The name of the variable.
   */
  private function dumpFloat(float &$value, mixed $name): void
  {
    if ($this->scalarReferences)
    {
      [$id, $ref] = $this->isReference($value);
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
   * Dumps a integer.
   *
   * @param int             $value The integer.
   * @param string|int|null $name  The name of the variable.
   */
  private function dumpInt(int &$value, mixed $name): void
  {
    if ($this->scalarReferences)
    {
      [$id, $ref] = $this->isReference($value);
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
   * @param null            $value The null.
   * @param string|int|null $name  The name of the variable.
   */
  private function dumpNull(null &$value, mixed $name): void
  {
    if ($this->scalarReferences)
    {
      [$id, $ref] = $this->isReference($value);
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
   * @param object          $value The object.
   * @param string|int|null $name  The name of the variable.
   */
  private function dumpObject(object $value, mixed $name): void
  {
    [$id, $ref] = $this->isReference($value);

    if ($ref===null)
    {
      $this->writer->writeObjectOpen($id, $name, get_class($value));

      // Dump all fields of the object, unless the object is me.
      if ($this!==$value)
      {
        if (str_contains(get_class($value), '\\'))
        {
          $this->dumpObjectUserDefinedClass($value);
        }
        elseif (get_class($value)==='stdClass')
        {
          $this->dumpObjectStdClass($value);
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
   * Dumps properties set with magic getter and property annotations.
   *
   * @param object $value The object.
   */
  private function dumpObjectMagicProperties(object $value): void
  {
    $reflection  = new \ReflectionClass($value);
    $reflections = $this->extractClassesOfClass($reflection);
    $properties  = $this->extractProperties($reflections);
    sort($properties, SORT_STRING | SORT_FLAG_CASE);

    foreach ($properties as $property)
    {
      if (isset($value->$property))
      {
        $this->recursiveDump($value->$property, $property);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps the properties of a stdClass.
   *
   * @param object $value The object.
   */
  private function dumpObjectStdClass(object $value): void
  {
    foreach ($value as $propertyName => $propertyValue)
    {
      $this->recursiveDump($propertyValue, $propertyName);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps the properties of a user defined class.
   *
   * @param object $value The object.
   */
  private function dumpObjectUserDefinedClass(object $value): void
  {
    $reflect    = new \ReflectionClass($value);
    $properties = $reflect->getProperties();
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

    $this->dumpObjectMagicProperties($value);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a resource.
   *
   * @param resource        $value The resource.
   * @param string|int|null $name  The name of the variable.
   */
  private function dumpResource($value, mixed $name): void
  {
    [$id, $ref] = $this->isReference($value);

    $this->writer->writeResource($id, $ref, $name, get_resource_type($value));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a string.
   *
   * @param string          $value The string.
   * @param string|int|null $name  The name of the variable.
   */
  private function dumpString(string &$value, mixed $name): void
  {
    if ($this->scalarReferences)
    {
      [$id, $ref] = $this->isReference($value);
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
   * Dumps an uninitialized property.
   *
   * @param string|int|null $name The name of the property.
   */
  private function dumpUninitialized(mixed $name): void
  {
    $this->writer->writeUninitialized($name);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the reflections of a class, trait or interface, its parent classes, and implemented interfaces.
   *
   * @param \ReflectionClass $reflection The reflection of the class, trait or interface.
   *
   * @return \ReflectionClass[]
   */
  private function extractClassesOfClass(\ReflectionClass $reflection): array
  {
    $reflections   = [];
    $reflections[] = $reflection;

    $parent = $reflection->getParentClass();
    if ($parent!==false)
    {
      $reflections = array_merge($reflections, $this->extractClassesOfClass($parent));
    }

    $traits = $reflection->getTraits();
    foreach ($traits as $trait)
    {
      $reflections = array_merge($reflections, $this->extractClassesOfClass($trait));
    }

    $interfaces = $reflection->getInterfaces();
    foreach ($interfaces as $interface)
    {
      $reflections = array_merge($reflections, $this->extractClassesOfClass($interface));
    }

    return $reflections;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns all property names properties set with magic getter and property annotations.
   *
   * @param \ReflectionClass[] $reflections The reflections.
   *
   * @return string[]
   */
  private function extractProperties(array $reflections): array
  {
    $properties = [];

    foreach ($reflections as $reflection)
    {
      $comment = $reflection->getDocComment();
      if ($comment!==false)
      {
        $pattern = '/@property(-read|-write) .* \$(?<name>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
        preg_match_all($pattern, $comment, $matches, PREG_SET_ORDER);
        foreach ($matches as $match)
        {
          $properties[] = $match['name'];
        }
      }
    }

    return array_unique($properties);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * If a value has been dumped before returns the ID of the variable. Otherwise, returns null.
   *
   * @param mixed $value The variable.
   *
   * @return array<int|null>
   */
  private function isReference(mixed &$value): array
  {
    switch (true)
    {
      case is_bool($value):
        $ref = $this->testSeen($value, false, true);
        break;

      case is_null($value):
        $ref = $this->testSeenNull($value);
        break;

      case is_int($value):
        $ref = $this->testSeen($value, 1, 2);
        break;

      case is_string($value):
        $ref = $this->testSeen($value, 'a', 'z');
        break;

      case is_double($value):
        $ref = $this->testSeen($value, 1.0, 2.0);
        break;

      case is_resource($value):
        $alt1 = fopen('php://stdin', 'r');
        $alt2 = fopen('php://stdout', 'w');
        $ref  = $this->testSeen($value, $alt1, $alt2);
        fclose($alt2);
        fclose($alt1);
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
   * @param mixed           $value The value.
   * @param string|int|null $name  Variable The name of the variable.
   */
  private function recursiveDump(mixed &$value, mixed $name): void
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
        if (is_string($name) && stripos($name, 'password')!==false)
        {
          // Do not dump the value of a variable/key with a name tha contains 'password'.
          $tmp = str_repeat('*', 12);
          $this->dumpString($tmp, $name);
        }
        else
        {
          $this->dumpString($value, $name);
        }
        break;

      case is_object($value):
        if (is_a($value, Uninitialized::class))
        {
          $this->dumpUninitialized($name);
        }
        else
        {
          $this->dumpObject($value, $name);
        }
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
   * If a value (not an array or object) has been seen before returns the ID of the variable. Otherwise, returns null.
   *
   * @param mixed $value  The value.
   * @param mixed $value1 The first alternative value.
   * @param mixed $value2 The second alternative value.
   *
   * @return int|null
   */
  private function testSeen(mixed &$value, mixed $value1, mixed $value2): ?int
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
            $check = self::testReferences($item, $value, $value1, $value2);
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
   * If an array has been seen before returns the ID of the array. Otherwise, returns null.
   *
   * @param array $value The value.
   *
   * @return int|null
   */
  private function testSeenArray(array &$value): ?int
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
   * If a value (not an array or object) has been seen before returns the ID of the variable. Otherwise, returns null.
   *
   * @param mixed $value The value.
   *
   * @return int|null
   */
  private function testSeenNull(mixed &$value): ?int
  {
    try
    {
      return $this->testSeen($value, false, true);
    }
    catch (\Throwable)
    {
      // $value is typed and not a boolean. We don't know which type and how to construct two different instances.
      return null;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * If an object has been seen before returns the ID of the variable. Otherwise, returns null.
   *
   * @param object $value The value.
   *
   * @return int|null
   */
  private function testSeenObject(object $value): ?int
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
