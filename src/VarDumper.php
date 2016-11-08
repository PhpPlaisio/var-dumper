<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Abc\Debug;

use SetBased\Abc\Helper\Html;
use SetBased\Exception\FallenException;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class VarDumper
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

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main function for dumping.
   *
   * @param string $name The name of the variable.
   * @param *      $value Variable for dumping.
   */
  public function dump($name, $value)
  {
    if (isset($this->fileName))
    {
      $this->handle = fopen($this->fileName, 'w');
    }

    $seen = [];
    $this->recursiveDump($value, $name, $seen);

    if (is_resource($this->handle))
    {
      fclose($this->handle);
    }
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
   * Function for check two variables. Return true if refs is same.
   *
   * @param $first
   * @param $second
   *
   * @return bool
   */
  private function EqualReferences(&$first, &$second)
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
   * Dumping objects.
   *
   * @param *      $value      Variable for dumping.
   * @param string $name       Variable name.
   * @param array  $seenValues Already seen values.
   *
   * @return string
   * @throws FallenException
   */
  private function recursiveDump($value, $name, &$seenValues)
  {
    switch (true)
    {
      case is_null($value):
        $this->dumpNull($name);
        break;

      case is_bool($value):
        $this->dumpBool($value, $name); // xxx rename others too
        break;

      case is_int($value):
      case is_string($value):
      case is_double($value):
        $this->dumpScalarTypes($value, $name);
        break;

      case is_object($value):
        $this->dumpObject($value, $name, $seenValues);
        break;

      case is_array($value):
        $this->dumpArray($value, $name, $seenValues);
        break;

      default:
        throw new FallenException('type', gettype($value));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns true if we have seen a value before. Otherwise returns false.
   *
   * @param array|object $value The value.
   * @param array        $seen  Already seen values.
   *
   * @return int|bool
   *
   * @throws FallenException
   */
  private function testValueSeen($value, &$seen)
  {
    switch (true)
    {
      case is_object($value):
        foreach ($seen as $id => $item)
        {
          if ($item===$value)
          {
            return $id;
          }
        }
        break;
      case is_array($value):
        foreach ($seen as $id => &$item)
        {
//          $check = $this->EqualReferences($item, $value);
          if ($item===$value)
          {
            return $id;
          }
        }
        break;

      default :
        throw new FallenException('type of value', gettype($value));
    }

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dump array variable.
   *
   * @param array  $array       Variable for dumping.
   * @param string $name        Variable name.
   * @param array  $seen_values Already seen values.
   */
  private function dumpArray($array, $name, &$seen_values)
  {
    $has_seen_item = $this->testValueSeen($array, $seen_values);
    if ($has_seen_item===false)
    {
      $seen_values[] = $array;
      fwrite($this->handle, Html::generateTag('array', ['name' => $name,
                                                        'id'   => (sizeof($seen_values) - 1)]));
      foreach ($array as $key => $item)
      {
        $this->recursiveDump($item, $key, $seen_values);
      }
      fwrite($this->handle, '</array>');
    }
    else
    {
      fwrite($this->handle, Html::generateVoidElement('array', ['name'      => $name,
                                                                'id'        => $has_seen_item,
                                                                'recursion' => 'true']));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dump bool variable.
   *
   * @param bool   $value Variable for dumping.
   * @param string $name  Variable name.
   */
  private function dumpBool($value, $name)
  {
    $val = ($value) ? 'true' : 'false';
    fwrite($this->handle, Html::generateVoidElement($val, ['name' => $name]));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dump null variable.
   *
   * @param string $name Variable name.
   */
  private function dumpNull($name)
  {
    fwrite($this->handle, Html::generateVoidElement('null', ['name' => $name]));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dump object variable.
   *
   * @param object $value       Variable for dumping.
   * @param        $name
   * @param array  $seen_values Already seen values.
   */
  private function dumpObject($value, $name, &$seen_values)
  {
    $type          = get_class($value);
    $properties    = get_object_vars($value);
    $has_seen_item = $this->testValueSeen($value, $seen_values);
    if ($has_seen_item===false)
    {
      $seen_values[] = $value;
      fwrite($this->handle, Html::generateTag('object', ['name'  => $name,
                                                         'class' => $type,
                                                         'id'    => (sizeof($seen_values) - 1)]));
      foreach ($properties as $key => $item)
      {
        $this->recursiveDump($item, $key, $seen_values);
      }
      fwrite($this->handle, '</object>');
    }
    else
    {
      fwrite($this->handle, Html::generateVoidElement('object', ['name'  => $name,
                                                                 'id'    => $has_seen_item,
                                                                 'class' => 'recursion']));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dump scalar variables.
   *
   * @param string|int|double $value Variable for dumping.
   * @param string            $name  Variable name.
   */
  private function dumpScalarTypes($value, $name)
  {
    fwrite($this->handle, Html::generateElement(gettype($value), ['name' => $name], $value));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------