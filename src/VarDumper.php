<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Abc\Debug;

use SetBased\Abc\Helper\Html;
use SetBased\Exception\FallenException;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class VarDumper.
 */
class VarDumper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * File name for output.
   *
   * @var string.
   */
  private $fileName;

  /**
   * Handle to resource file.
   */
  private $handle;

  /**
   * The variables that we have dumped so var.
   *
   * @var array
   */
  private $seen;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main function for dumping.
   *
   * @param string $name  The name of the variable.
   * @param mixed  $value Variable for dumping.
   *
   * @api
   * @since 1.0.0
   */
  public function dump($name, $value)
  {
    $this->handle = fopen($this->fileName, 'wb');

    $this->seen = [];
    $this->recursiveDump($value, $name, gettype($name));

    fclose($this->handle);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for file name.
   *
   * @param string $fileName
   */
  public function setFileName($fileName)
  {
    $this->fileName = $fileName;
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
    $id = $this->dumpReference($value, $name, $keyType);
    if ($id===false)
    {
      fwrite($this->handle, Html::generateTag('array', ['name'     => $name,
                                                        'key_type' => $keyType,
                                                        'id'       => (sizeof($this->seen) - 1)]));
      foreach ($value as $key => &$item)
      {
        $this->recursiveDump($item, $key, gettype($key));
      }
      fwrite($this->handle, '</array>');
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
    $val = ($value) ? 'true' : 'false';
    fwrite($this->handle, Html::generateVoidElement($val, ['name' => $name, 'key_type' => $keyType]));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps null.
   *
   * @param string $name    The name of the variable.
   * @param string $keyType When the name of the variable is key of an array the type of the key (integer or string).
   */
  private function dumpNull($name, $keyType)
  {
    fwrite($this->handle, Html::generateVoidElement('null', ['name' => $name, 'key_type' => $keyType]));
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
    $id = $this->dumpReference($value, $name, $keyType);
    if ($id===false)
    {
      fwrite($this->handle, Html::generateTag('object', ['name'     => $name,
                                                         'key_type' => $keyType,
                                                         'class'    => get_class($value),
                                                         'id'       => (sizeof($this->seen) - 1)]));

      $properties = get_object_vars($value);
      foreach ($properties as $key => $item)
      {
        $this->recursiveDump($item, $key, null);
      }

      fwrite($this->handle, '</object>');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a reference if a value has been dumped before and returns th ID f the variable. Otherwise returns false.
   *
   * @param mixed  $value   The reference or variable.
   * @param string $name    The name of the variable.
   * @param string $keyType When the name of the variable is key of an array the type of the key (integer or string).
   *
   * @return false|int
   */
  private function dumpReference(&$value, $name, $keyType)
  {
    switch (true)
    {
      case is_bool($value):
      case is_null($value):
        $id = false;
        break;

      case is_int($value):
      case is_string($value):
      case is_double($value):
      case is_array($value):
      case is_object($value):
      case is_resource($value):
        $id = $this->testSeen($value);
        if ($id!==false)
        {
          fwrite($this->handle, Html::generateVoidElement('reference', ['name'     => $name,
                                                                        'key_type' => $keyType,
                                                                        'id'       => $id]));
        }
        else
        {
          $this->seen[] = &$value;
        }
        break;

      default:
        throw new FallenException('type', gettype($value));
    }

    return $id;
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
    $id = $this->dumpReference($value, $name, $keyType);
    if ($id===false)
    {
      fwrite($this->handle, Html::generateVoidElement('resource', ['name'     => $name,
                                                                   'key_type' => $keyType,
                                                                   'type'     => get_resource_type($value),
                                                                   'id'       => (sizeof($this->seen) - 1)]));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps an integer, double, or string.
   *
   * @param string|int|double $value   The scalar.
   * @param string            $name    The name of the variable.
   * @param string            $keyType When the name of the variable is key of an array the type of the key (integer or
   *                                   string).
   */
  private function dumpScalarTypes(&$value, $name, $keyType)
  {
    $id = $this->dumpReference($value, $name, $keyType);
    if ($id===false)
    {
      fwrite($this->handle, Html::generateElement(gettype($value),
                                                  ['name'     => $name,
                                                   'key_type' => $keyType,
                                                   'id'       => (sizeof($this->seen) - 1)],
                                                  $value));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps recursively a variable.
   *
   * @param mixed  $value   The variable.
   * @param string $name    Variable The name of the variable.
   * @param string $keyType When the name of the variable is key of an array the type of the key (integer or string).
   */
  private function recursiveDump(&$value, $name, $keyType)
  {
    switch (true)
    {
      case is_null($value):
        $this->dumpNull($name, $keyType);
        break;

      case is_bool($value):
        $this->dumpBool($value, $name, $keyType);
        break;

      case is_int($value):
      case is_string($value):
      case is_double($value):
        $this->dumpScalarTypes($value, $name, $keyType);
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
   * Returns true if and only if two variables are references to the same variable content.
   *
   * @param mixed $first  The first variable.
   * @param mixed $second The second variable.
   *
   * @return bool
   */
  private function testReferences(&$first, &$second)
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
   * If a value has been seen before returns the ID of the variable. Otherwise returns false.
   *
   * @param mixed $value The value.
   *
   * @return int|false
   *
   * @throws FallenException
   */
  private function testSeen(&$value)
  {
    switch (true)
    {
      case is_object($value):
        foreach ($this->seen as $id => $item)
        {
          if ($item===$value)
          {
            return $id;
          }
        }
        break;

      case is_array($value):
      case is_string($value):
      case is_int($value):
      case is_double($value):
      case is_resource($value):
        foreach ($this->seen as $id => &$item)
        {
          $check = $this->testReferences($item, $value);
          if ($check)
          {
            return $id;
          }
        }
        break;

      default :
        throw new FallenException('gettype', gettype($value));
    }

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------