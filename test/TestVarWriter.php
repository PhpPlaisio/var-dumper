<?php
declare(strict_types=1);

namespace Plaisio\Debug\Test;

use Plaisio\Debug\VarWriter;

/**
 * Writes a var dump in plain text.
 */
class TestVarWriter implements VarWriter
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The recursion level.
   *
   * @var int
   */
  private $level = 0;

  //--------------------------------------------------------------------------------------------------------------------

  /**
   * Does nothing.
   */
  public function start(): void
  {
    // Nothing to do.
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Does nothing.
   */
  public function stop(): void
  {
    // Nothing to do.
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeArrayClose(int $id, $name): void
  {
    if ($name!==null)
    {
      $this->level--;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeArrayOpen(int $id, $name): void
  {
    if ($name!==null)
    {
      $this->writeName($name, 'array', $id);
      $this->level++;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeArrayReference(int $ref, $name): void
  {
    $this->writeName($name, 'array', null, $ref);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeBool(?int $id, ?int $ref, bool &$value, $name): void
  {
    $this->writeScalar($id, $ref, $name, ($value) ? 'true' : 'false', 'bool');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeFloat(?int $id, ?int $ref, float &$value, $name): void
  {
    $this->writeScalar($id, $ref, $name, (string)$value, 'float');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeInt(?int $id, ?int $ref, int &$value, $name): void
  {
    $this->writeScalar($id, $ref, $name, (string)$value, 'int');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeNull(?int $id, ?int $ref, $name): void
  {
    $this->writeScalar($id, $ref, $name, 'null', 'null');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeObjectClose(int $id, $name, string $class): void
  {
    if ($name!==null)
    {
      $this->level--;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeObjectOpen(int $id, $name, string $class): void
  {
    if ($name!==null)
    {
      $this->writeName($name, 'class', $id, null, $class);
      $this->level++;
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeObjectReference(int $ref, $name, string $class): void
  {
    $this->writeName($name, 'object', null, $ref, $class);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeResource(?int $id, ?int $ref, $name, string $type): void
  {
    $this->writeScalar($id, $ref, $name, $type, 'keyword');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeString(?int $id, ?int $ref, string &$value, $name): void
  {
    $text = mb_strimwidth($value, 0, 80, '...');

    $this->writeScalar($id, $ref, $name, $text, 'string');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function writeUninitialized($name): void
  {
    $this->writeScalar(null, null, $name, 'uninitialized', null);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Writes the name of a variable.
   *
   * @param string|int|null $name  The name of the variable.
   * @param string|null     $type  The type of the value.
   * @param int|null        $id    The ID of the value.
   * @param int|null        $ref   The ID of the value if the variable is a reference to a value that has been dumped
   *                               already.
   * @param string|null     $value The text for displaying the value.
   */
  private function writeName($name, ?string $type, ?int $id = null, ?int $ref = null, ?string $value = null): void
  {
    $text = '';
    if ($name!==null)
    {
      $text .= $name;
    }
    if ($type!==null)
    {
      $text .= '[';

      $text .= sprintf('type=%s ', $type);

      if ($id!==null)
      {
        $text .= sprintf('id=%d ', $id);
      }
      if ($ref!==null)
      {
        $text .= sprintf('ref=%d ', $ref);
      }
      $text = rtrim($text);
      $text .= ']';
    }
    $text = sprintf('%-40s', $text);

    if ($value!==null)
    {
      $text .= ' => ';
      $text .= str_replace(PHP_EOL, '\n', $value);
    }

    echo str_repeat(' ', 2 * $this->level), $text, PHP_EOL;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Dumps a scalar value.
   *
   * @param int|null        $id    The ID of the value.
   * @param int|null        $ref   The ID of the value if the variable is a reference to a value that has been dumped
   *                               already.
   * @param string|int|null $name  The name of the variable.
   * @param string          $value The text for displaying the value.
   * @param string|null     $type  The type of the value.
   */
  private function writeScalar(?int $id, ?int $ref, $name, string $value, ?string $type)
  {
    $this->writeName($name, $type, $id, $ref, $value);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
