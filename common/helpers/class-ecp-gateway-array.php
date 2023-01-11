<?php

defined('ABSPATH') || exit;

/**
 * <h2>Array object.</h2>
 *
 * @class    Ecp_Gateway_Array
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class Ecp_Gateway_Array implements ArrayAccess, Countable
{
    /**
     * Internal container.
     *
     * @var array
     * @since 2.0.0
     */
    private $array;

    /**
     * Array object constructor.
     *
     * @param array $array
     * @since 2.0.0
     */
    public function __construct(array $array = [])
    {
        $this->array = $array;
    }

    // region ArrayAccess interface realisation

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return bool <p><b>TRUE</b> if offset exists or <b>FALSE</b> otherwise.</p>
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->array);
    }

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return mixed <p>Array offset value.</p>
     */
    public function offsetGet($offset)
    {
        return $this->array[$offset];
    }

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->array[] = $value;
        } else {
            $this->array[$offset] = $value;
        }
    }

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->array[$offset]);
    }

    // endregion

    // region Countable interface realisation

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return int <p>The number of elements in the array.</p>
     */
    public function count()
    {
        return count($this->array);
    }

    // endregion

    /**
     * <h2>Returns current object as a native array.</h2>
     *
     * @since 2.0.0
     * @return array <p>Native array.</p>
     */
    public function to_array()
    {
        return $this->array;
    }

    /**
     * <h2>Returns all array keys as array.</h2>
     *
     * @since 2.0.0
     * @return int[]|string[] <p>All array keys.</p>
     */
    public function keys()
    {
        return array_keys($this->array);
    }

    /**
     * <h2>Returns all array values as array.</h2>
     *
     * @since 2.0.0
     * @return array <p>All array values.</p>
     */
    public function values()
    {
        return array_values($this->array);
    }

    /**
     * <h2>Returns the first value from array.</h2>
     *
     * @since 2.0.0
     * @return mixed|null <p>First value from array.</p>
     */
    public function first()
    {
        return $this->first_key()
            ? $this->array[$this->first_key()]
            : null;
    }

    /**
     * <h2>Returns the first key from array.</h2>
     *
     * @since 2.0.0
     * @return ?int|?string <p>First key from array.</p>
     */
    public function first_key()
    {
        return $this->count() > 0
            ? $this->keys()[0]
            : null;
    }

    /**
     * <h2>Returns the last value from array.</h2>
     *
     * @since 2.0.0
     * @return mixed|null <p>Last value from array.</p>
     */
    public function last()
    {
        return $this->last_key()
            ? $this->array[$this->last_key()]
            : null;
    }

    /**
     * <h2>Returns the last key from array.</h2>
     *
     * @since 2.0.0
     * @return ?int|?string <p>Last key from array.</p>
     */
    public function last_key()
    {
        return $this->count() > 0
            ? $this->keys()[$this->count() - 1]
            : null;
    }
}
