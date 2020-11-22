<?php
declare(strict_types=1);

namespace Plaisio\Debug\Test;

use PHPUnit\Framework\TestCase;
use Plaisio\Debug\VarDumper;

/**
 * Test cases for DevelopmentErrorLogger.
 */
class VarDumperTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test an array with scalars.
   */
  public function testArray1(): void
  {
    $value = ['int'    => 1,
              'float'  => 3.14,
              'string' => 'Hello, World!',
              'true'   => true,
              'false'  => false,
              'null'   => null];

    $dumper = new VarDumper(new TestVarWriter());
    $dumper->dump('array', $value);

    $output = $this->getActualOutput();

    $expected = <<< EOL
array[type=array id=0]        
  int[type=int]       => 1
  float[type=float]   => 3.14
  string[type=string] => Hello, World!
  true[type=bool]     => true
  false[type=bool]    => false
  null[type=null]     => null
EOL;

    $expected = trim(preg_replace('/ +/', ' ', $expected));
    $output   = trim(preg_replace('/ +/', ' ', $output));
    self::assertSame($expected, $output);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test an array with scalars and references.
   */
  public function testArray2(): void
  {
    $value            = ['int'    => 1,
                         'float'  => 3.14,
                         'string' => 'Hello, World!',
                         'true'   => true,
                         'false'  => false,
                         'null'   => null];
    $value['array']   = &$value;
    $value['int&']    = &$value['int'];
    $value['float&']  = &$value['float'];
    $value['string&'] = &$value['string'];
    $value['true&']   = &$value['true'];
    $value['false&']  = &$value['false'];
    $value['null&']   = &$value['null'];

    $dumper = new VarDumper(new TestVarWriter());
    $dumper->dump('array', $value, true);

    $output = $this->getActualOutput();

    $expected = <<< EOL
array[type=array id=0]        
  int[type=int id=1]             => 1
  float[type=float id=2]         => 3.14
  string[type=string id=3]       => Hello, World!
  true[type=bool id=4]           => true
  false[type=bool id=5]          => false
  null[type=null id=6]           => null
  array[type=array ref=0]       
  int&[type=int ref=1]           => 1
  float&[type=float ref=2]       => 3.14
  string&[type=string ref=3]     => Hello, World!
  true&[type=bool ref=4]         => true
  false&[type=bool ref=5]        => false
  null&[type=null ref=6]         => null
EOL;

    $expected = trim(preg_replace('/ +/', ' ', $expected));
    $output   = trim(preg_replace('/ +/', ' ', $output));
    self::assertSame($expected, $output);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test an array with scalars.
   */
  public function testClass(): void
  {
    $value = new TestClass();

    $dumper = new VarDumper(new TestVarWriter());
    $dumper->dump('class', $value);

    $output = $this->getActualOutput();

    $expected = <<< EOL
class[type=class id=0]             => Plaisio\Debug\Test\TestClass
  false[type=bool]                   => false
  null[type=null]                    => null
  true[type=bool]                    => true
  resource[type=keyword id=1]        => stream
  password[type=string]              => ************
  float[type=float]                  => 3.14
  int[type=int]                      => 1
  string[type=string]                => Hello, World!
  staticFalse[type=bool]             => false
  staticFloat[type=float]            => 3.14
  staticInt[type=int]                => 1
  staticNull[type=null]              => null
  staticString[type=string]          => Hello, World!
  staticTrue[type=bool]              => true
  staticResource[type=keyword id=2]  => stream
  propertyFalse[type=bool]           => false
  propertyFloat[type=float]          => 3.14
  propertyInt[type=int]              => 1
  propertyString[type=string]        => Hello, World!
  propertyTrue[type=bool]            => true
EOL;

    $expected = trim(preg_replace('/ +/', ' ', $expected));
    $output   = trim(preg_replace('/ +/', ' ', $output));
    self::assertSame($expected, $output);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test an array with scalars.
   */
  public function testTypedClass(): void
  {
    if (PHP_VERSION_ID<=70400)
    {
      static::markTestSkipped('No typed properties.');
    }
    else
    {
      $value = new TestTypedClass();

      $dumper = new VarDumper(new TestVarWriter());
      $dumper->dump('class', $value, true);

      $output = $this->getActualOutput();

      $expected = <<< EOL
class[type=class id=0]               => Plaisio\Debug\Test\TestTypedClass
  refFalse[type=bool id=1]             => false
  refFloat[type=float id=2]            => 3.14
  refInt[type=int id=3]                => 1
  refNull[type=null id=4]              => null
  refResource[type=keyword id=5]       => stream
  refString[type=string id=6]          => Hello, World!
  refTrue[type=bool id=7]              => true
  void                                 => uninitialized
  false[type=bool ref=1]               => false
  float[type=float ref=2]              => 3.14
  int[type=int ref=3]                  => 1
  null[type=null id=7]                 => null
  password[type=string id=8]           => ************
  resource[type=keyword id=9]          => stream
  string[type=string ref=6]            => Hello, World!
  true[type=bool ref=7]                => true
  staticFalse[type=bool id=10]         => false
  staticFloat[type=float id=11]        => 3.14
  staticInt[type=int id=12]            => 1
  staticNull[type=null id=12]          => null
  staticString[type=string id=13]      => Hello, World!
  staticTrue[type=bool id=14]          => true
  staticResource[type=keyword id=15]   => stream
  propertyFalse[type=bool id=16]       => false
  propertyFloat[type=float id=17]      => 3.14
  propertyInt[type=int id=18]          => 1
  propertyString[type=string id=19]    => Hello, World!
  propertyTrue[type=bool id=20]        => true
EOL;

      $expected = trim(preg_replace('/ +/', ' ', $expected));
      $output   = trim(preg_replace('/ +/', ' ', $output));
      self::assertSame($expected, $output);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test an array with scalars.
   */
  public function testStdClass(): void
  {
    $value = new \StdClass();

    $value->int       = 1;
    $value->float     = 3.14;
    $value->string    = 'Hello, World!';
    $value->true      = true;
    $value->false     = false;
    $value->null      = null;
    $value->refClass  = &$value;
    $value->refInt    = &$value->int;
    $value->refFloat  = &$value->float;
    $value->refString = &$value->string;
    $value->refTrue   = &$value->true;
    $value->refFalse  = &$value->false;
    $value->refNull   = &$value->null;

    $dumper = new VarDumper(new TestVarWriter());
    $dumper->dump('class', $value, true);

    $output = $this->getActualOutput();

    $expected = <<< EOL
class[type=class id=0]         => stdClass
  int[type=int id=1]             => 1
  float[type=float ref=1]        => 3.14
  string[type=string ref=1]      => Hello, World!
  true[type=bool ref=1]          => true
  false[type=bool ref=1]         => false
  null[type=null ref=1]          => null
  refClass[type=object ref=0]    => stdClass
  refInt[type=int ref=1]         => 1
  refFloat[type=float ref=1]     => 3.14
  refString[type=string ref=1]   => Hello, World!
  refTrue[type=bool ref=1]       => true
  refFalse[type=bool ref=1]      => false
  refNull[type=null ref=1]       => null
EOL;

    $expected = trim(preg_replace('/ +/', ' ', $expected));
    $output   = trim(preg_replace('/ +/', ' ', $output));
    self::assertSame($expected, $output);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
