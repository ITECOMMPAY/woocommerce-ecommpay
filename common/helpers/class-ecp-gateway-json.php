<?php

defined('ABSPATH') || exit;

/**
 * <h2>JSON object.</h2>
 *
 * @class    Ecp_Gateway_Json
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 * @noinspection PhpMultipleClassDeclarationsInspection
 */
class Ecp_Gateway_Json extends Ecp_Gateway_Array implements JsonSerializable
{
    const PATH_SEPARATOR = '.';

    const STRING_TYPE = 'string';
    const INTEGER_TYPE = 'integer';
    const FLOAT_TYPE = 'double';
    const BOOL_TYPE = 'boolean';
    const ARRAY_TYPE = 'array';
    const OBJECT_TYPE = 'object';
    const JSON_TYPE = 'json';

    const EMPTY_STRING = '';
    const EMPTY_ARRAY = [];
    const EMPTY_OBJECT = null;

    private $register = [];

    public function __construct(array $array = [])
    {
        parent::__construct();

        $this->deserialize($array);
    }

    // region JsonInterface realisation

    /**
     * <h2>Returns the integer value by path in json-tree.</h2>
     *
     * @param string $path <p>Json-tree path.</p>
     * @since 2.0.0
     * @return int <p>The int value by JSON-path.</p>
     * @throws Ecp_Gateway_Exception <p>If Json-tree path is not available in current object.</p>
     */
    protected function get_int($path)
    {
        return $this->get_value(self::INTEGER_TYPE, $path);
    }

    /**
     * <h2>Returns the result of trying to put an integer value into variable by reference.</h2>
     *
     * @param ?int &$value <p>Container for integer value - variable by reference.</p>
     * @param string $path <p>Json-tree path.</p>
     * @param int $default [optional] <p>Default value if path in json-tree is not found.</p>
     * @since 2.0.0
     * @return bool <p><b>TRUE</b> if success try get string value or <b>FALSE</b> otherwise.</p>
     */
    protected function try_get_int(&$value, $path, $default = 0)
    {
        try {
            $value = $this->get_int($path);
            return true;
        } catch (Ecp_Gateway_Exception $e) {
            $value = $default;
        }

        return false;
    }

    /**
     * <h2>Returns the string value by path in json-tree.</h2>
     *
     * @param string $path <p>Json-tree path.</p>
     * @return string <p>The string value by JSON-path.</p>
     * @since 2.0.0
     * @throws Ecp_Gateway_Exception <p>If Json-tree path is not available in current object.</p>
     */
    protected function get_string($path)
    {
        return $this->get_value(self::STRING_TYPE, $path);
    }

    /**
     * <h2>Returns the result of trying to put a string value into variable by reference.</h2>
     *
     * @param ?string &$value <p>Container for string value - variable by reference.</p>
     * @param string $path <p>Json-tree path.</p>
     * @param string $default [optional] <p>Default value if path in json-tree is not found.</p>
     * @since 2.0.0
     * @return bool <p><b>TRUE</b> if success try get string value or <b>FALSE</b> otherwise.</p>
     */
    protected function try_get_string(&$value, $path, $default = self::EMPTY_STRING)
    {
        try {
            $value = $this->get_string($path);
            return true;
        } catch (Ecp_Gateway_Exception $e) {
            $value = $default;
        }

        return false;
    }

    /**
     * <h2>Returns the float value by path in json-tree.</h2>
     *
     * @param string $path <p>Json-tree path.</p>
     * @return float <p>The float value by JSON-path.</p>
     * @since 2.0.0
     * @throws Ecp_Gateway_Exception <p>If Json-tree path is not available in current object.</p>
     */
    protected function get_float($path)
    {
        return $this->get_value(self::FLOAT_TYPE, $path);
    }

    /**
     * <h2>Returns the result of trying to put a float value into variable by reference.</h2>
     *
     * @param ?float &$value <p>Container for float value - variable by reference.</p>
     * @param string $path <p>Json-tree path.</p>
     * @param float $default [optional] <p>Default value if path in json-tree is not found.</p>
     * @since 2.0.0
     * @return bool <p><b>TRUE</b> if success try get string value or <b>FALSE</b> otherwise.</p>
     */
    protected function try_get_float(&$value, $path, $default = 0.0)
    {
        try {
            $value = $this->get_float($path);
            return true;
        } catch (Ecp_Gateway_Exception $e) {
            $value = $default;
        }

        return false;
    }

    /**
     * <h2>Returns the bool value by path in json-tree.</h2>
     *
     * @param string $path <p>Json-tree path.</p>
     * @since 2.0.0
     * @return bool <p>The bool value by JSON-path.</p>
     * @throws Ecp_Gateway_Exception <p>If Json-tree path is not available in current object.</p>
     */
    protected function get_bool($path)
    {
        return $this->get_value(self::BOOL_TYPE, $path);
    }

    /**
     * <h2>Returns the result of trying to put a bool value into variable by reference.</h2>
     *
     * @param ?bool &$value <p>Container for bool value - variable by reference.</p>
     * @param string $path <p>Json-tree path.</p>
     * @param bool $default [optional] <p>Default value if path in json-tree is not found.</p>
     * @since 2.0.0
     * @return bool <p><b>TRUE</b> if success try get string value or <b>FALSE</b> otherwise.</p>
     */
    protected function try_get_bool(&$value, $path, $default = false)
    {
        try {
            $value = $this->get_bool($path);
            return true;
        } catch (Ecp_Gateway_Exception $e) {
            $value = $default;
        }

        return false;
    }

    /**
     * <h2>Returns the array value by path in json-tree.</h2>
     *
     * @param string $path <p>Json-tree path.</p>
     * @return array <p>The array value by JSON-path.</p>
     * @since 2.0.0
     * @throws Ecp_Gateway_Exception <p>If Json-tree path is not available in current object.</p>
     */
    protected function get_array($path)
    {
        return $this->get_value(self::ARRAY_TYPE, $path);
    }

    /**
     * <h2>Returns the result of trying to put an array value into variable by reference.</h2>
     *
     * @param ?array &$value <p>Container for array value - variable by reference.</p>
     * @param string $path <p>Json-tree path.</p>
     * @param array $default [optional] <p>Default value if path in json-tree is not found.</p>
     * @since 2.0.0
     * @return bool <p><b>TRUE</b> if success try get string value or <b>FALSE</b> otherwise.</p>
     */
    protected function try_get_array(&$value, $path, $default = self::EMPTY_ARRAY)
    {
        try {
            $value = $this->get_array($path);
            return true;
        } catch (Ecp_Gateway_Exception $e) {
            $value = $default;
        }

        return false;
    }

    /**
     * <h2>Returns the object value by path in json-tree.</h2>
     *
     * @param string $path <p>Json-tree path.</p>
     * @since 2.0.0
     * @return object <p>The object value by JSON-path.</p>
     * @throws Ecp_Gateway_Exception <p>If Json-tree path is not available in current object.</p>
     */
    protected function get_object($path)
    {
        return $this->get_value(self::OBJECT_TYPE, $path);
    }

    /**
     * <h2>Returns the result of trying to put an object value into variable by reference.</h2>
     *
     * @param ?object &$value <p>Container for object value - variable by reference.</p>
     * @param string $path <p>Json-tree path.</p>
     * @param ?object $default [optional] <p>Default value if path in json-tree is not found.</p>
     * @since 2.0.0
     * @return bool <p><b>TRUE</b> if success try get string value or <b>FALSE</b> otherwise.</p>
     */
    protected function try_get_object(&$value, $path, $default = self::EMPTY_OBJECT)
    {
        try {
            $value = $this->get_object($path);
            return true;
        } catch (Ecp_Gateway_Exception $e) {
            $value = $default;
        }

        return false;
    }

    /**
     * <h2>Returns the JSON-object value by path in json-tree.</h2>
     *
     * @param string $path <p>Json-tree path.</p>
     * @since 2.0.0
     * @return Ecp_Gateway_Json <p>The JSON-object value by JSON-path.</p>
     * @throws Ecp_Gateway_Exception <p>If Json-tree path is not available in current object.</p>
     */
    protected function get_json($path)
    {
        return $this->get_value(self::JSON_TYPE, $path);
    }

    /**
     * <h2>Returns the result of trying to put an JSON-object value into variable by reference.</h2>
     *
     * @param ?Ecp_Gateway_Json &$value <p>Container for JSON-object value - variable by reference.</p>
     * @param string $path <p>Json-tree path.</p>
     * @param ?Ecp_Gateway_Json $default [optional] <p>Default value if path in json-tree is not found.</p>
     * @since 2.0.0
     * @return bool <p><b>TRUE</b> if success try get string value or <b>FALSE</b> otherwise.</p>
     */
    protected function try_get_json(&$value, $path, $default = self::EMPTY_OBJECT)
    {
        try {
            $value = $this->get_json($path);
            return true;
        } catch (Ecp_Gateway_Exception $e) {
            $value = $default;
        }

        return false;
    }

    // endregion

    // region JsonSerializable interface realisation

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return array <p>Prepared array for {@see json_encode}.</p>
     */
    public function jsonSerialize()
    {
        $result = [];

        foreach (parent::to_array() as $key => $value) {
            $value = $this->serialize($value, $key);

            if ($value === null) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    // endregion

    // region Arrayable interface realisation

    /**
     * <h2>Returns current object as array value.</h2>
     *
     * @since 2.0.0
     * @return array <p>Native array.</p>
     */
    public function to_array()
    {
        return $this->jsonSerialize();
    }

    /**
     * <h2>Returns object as key-value pairs.</h2>
     *
     * @param string $separator [optional] <p>Tree path separator. Default: {@see Ecp_Gateway_Json::PATH_SEPARATOR}.</p>
     * @param string $prefix [optional] <p>Prefix for root. Default: {@see Ecp_Gateway_Json::EMPTY_STRING}.</p>
     * @since 2.0.0
     * @return array <p>List of key-value pairs.</p>
     */
    public function to_pairs($separator = self::PATH_SEPARATOR, $prefix = self::EMPTY_STRING)
    {
        return $this->to_key_value_pairs($this->jsonSerialize(), $prefix, $separator);
    }

    // endregion

    /**
     * @param array $values
     * @param bool $onDuplicateReplace
     * @since 2.0.0
     * @return static
     * @throws Ecp_Gateway_Duplicate_Exception
     */
    public function append($values, $onDuplicateReplace = false)
    {
        $list = $this->to_key_value_pairs($values);

        foreach ($list as $key => $value) {
            $this->parseValue($value);
            $this->add($key, $value, $onDuplicateReplace);
        }

        return $this;
    }

    /**
     * <h2>Add value by tree path.</h2>
     *
     * @param string $path <p>Tree path.</p>
     * @param mixed $value <p>Value for tree path.</p>
     * @param bool $onDuplicateReplace [optional] <p>Replace value on duplicate? Default: no.</p>
     * @since 2.0.0
     * @return static <p>Current JSON object.</p>
     * @throws Ecp_Gateway_Duplicate_Exception <p>If tree path already exists.</p>
     */
    protected function add($path, $value, $onDuplicateReplace = false)
    {
        if ($this->has($path) && !$onDuplicateReplace) {
            throw new Ecp_Gateway_Duplicate_Exception($path);
        }

        $this->set($path, $value);

        return $this;
    }

    /**
     * <h2>Sets value for tree path.</h2>
     *
     * @param string $path <p>Tree path.</p>
     * @param mixed $value <p>Value for tree path.</p>
     * @since 2.0.0
     * @return static <p>Curren JSON object.</p>
     */
    protected function set($path, $value)
    {
        $this->parseValue($value);
        $keys = $this->parse_path($path);
        $key = array_shift($keys);

        if (preg_match('|^(?P<array>[^]\[]+)(?P<indexes>(?:\[\d+])+)$|', $key, $match)) {
            $key = $match['array'];
            $indexesRaw = $match['indexes'];
            preg_match_all('|\[(?P<index>\d+)]|', $indexesRaw, $match);
            $indexes = $match['index'];

            $this->try_get_array($array, $key, []);
            $child = &$array;

            foreach ($indexes as $index) {
                $child = &$child[$index];
            }

            if (count($keys) > 0) {
                if (!$child instanceof Ecp_Gateway_Json) {
                    $child = new Ecp_Gateway_Json();
                }

                $child->set(implode(self::PATH_SEPARATOR, $keys), $value);
            } else {
                $child = $value;
            }

            $this->offsetSet($key, $array);
        } elseif (count($keys) > 0) {
            $result = $this->try_get_object($child, $key, new Ecp_Gateway_Json());

            if (!$result) {
                $this->offsetSet($key, $child);
            }

            $child->set(implode(self::PATH_SEPARATOR, $keys), $value);
        } else {
            $this->offsetSet($key, $value);
        }

        return $this;
    }

    /**
     * <h2>Returns the result of checking if the tree path exists in the object.</h2>
     *
     * @param string $path <p>Tree path.</p>
     * @since 2.0.0
     * @return bool <p><b>TRUE</b> if the tree path exists, or <b>FALSE</b> otherwise.</p>
     */
    protected function has($path)
    {
        $keys = $this->parse_path($path);
        $key = array_shift($keys);
        $child = null;

        if (preg_match('|^(?P<array>[^]\[]+)(?P<indexes>(?:\[\d+])+)$|', $key, $match)) {
            $key = $match['array'];
            $indexesRaw = $match['indexes'];
            preg_match_all('|\[(?P<index>\d+)]|', $indexesRaw, $match);
            $indexes = $match['index'];

            $result = $this->try_get_array($child, $key, null);

            if (!$result) {
                return $result;
            }

            foreach ($indexes as $index) {
                if (!is_array($child)) {
                    return false;
                }

                if (!isset($child[$index])) {
                    return false;
                }

                $child = $child[$index];
            }
        }

        if (count($keys) > 0) {
            if ($child !== null) {
                if (!$child instanceof Ecp_Gateway_Json) {
                    return false;
                }
            } else {
                $result = $this->try_get_object($child, $key, null);

                if (!$result) {
                    return $result;
                }
            }

            return $child->has(implode(self::PATH_SEPARATOR, $keys));
        }

        return $this->offsetExists($key);
    }

    /**
     * <h2>Returns a value by path.</h2>
     * <p>Always throw exception.</p>
     *
     * @param string $path <p>Tree path.</p>
     * @since 2.0.0
     * @return void
     * @throws Ecp_Gateway_Not_Available_Exception <p>Always.</p>
     * @noinspection PhpUnusedParameterInspection
     */
    protected function get($path)
    {
        throw new Ecp_Gateway_Not_Available_Exception();
    }

    /**
     * <h2>Removes a tree from an object along a path.</h2>
     *
     * @param string $path <p>Removing path.</p>
     * @since 2.0.0
     * @return void
     */
    protected function remove($path)
    {
        $keys = $this->parse_path($path);
        $key = array_shift($keys);
        $child = null;

        if (preg_match('|^(?P<array>[^]\[]+)(?P<indexes>(?:\[\d+])+)$|', $key, $match)) {
            $key = $match['array'];
            $indexesRaw = $match['indexes'];
            preg_match_all('|\[(?P<index>\d+)]|', $indexesRaw, $match);
            $indexes = $match['index'];
            $result = $this->try_get_array($array, $key, null);

            if (!$result) {
                return;
            }

            $child = &$array;
            $count = count($indexes) - 1;

            for ($i = 0; $i < $count; ++$i) {
                if (!isset($child[$indexes[$i]])) {
                    return;
                }

                if (!is_array($child[$indexes[$i]])) {
                    return;
                }

                $child = &$child[$indexes[$i]];
            }

            if (count($keys) <= 0) {
                unset($child[$indexes[$count]]);
                $this->set($key, $array);
                return;
            }

            $child = $child[$indexes[$count]];
        }

        if (count($keys) > 0) {
            if ($child !== null) {
                if (!$child instanceof Ecp_Gateway_Json) {
                    return;
                }
            } else {
                $result = $this->try_get_object($child, $key, null);

                if (!$result) {
                    return;
                }
            }

            $child->remove(implode(self::PATH_SEPARATOR, $keys));
        }

        $this->offsetUnset($key);
    }

    // region Protected methods

    /**
     * <h2>Return json pack rules.</h2>
     *
     * @since 2.0.0
     * @return array
     */
    protected function packRules()
    {
        return [];
    }

    /**
     * <h2>Return json unpack rules.</h2>
     *
     * @since 2.0.0
     * @return array
     */
    protected function unpackRules()
    {
        return [];
    }

    /**
     * <h2>Register JSON-class for key for deserialize procedure.</h2>
     *
     * @param string $key <p>Key for JSON-value.</h2>
     * @param string|callable $class <p>Class name for deserialize procedure.</p>
     * @since 2.0.0
     * @return void
     */
    protected function register($key, $class)
    {
        $this->register[$key] = $class;
    }

    // endregion

    // region Private methods

    /**
     * @param mixed &$value
     * @since 2.0.0
     * @return void
     */
    private function parseValue(&$value)
    {
        if (is_array($value)) {
            foreach ($value as $k => &$v) {
                if (!is_numeric($k)) {
                    $value = new Ecp_Gateway_Json($value);
                    return;
                }

                if (is_array($v)) {
                    $this->parseValue($v);
                }
            }
        }
    }

    /**
     * Returns value from json-tree by type.
     *
     * @param string $type Variable type.
     * @param string $path Json-tree path.
     * @since 2.0.0
     * @return array|bool|float|int|string
     * @throws Ecp_Gateway_Invalid_Argument_Exception
     * @throws Ecp_Gateway_Key_Not_Found_Exception
     * @throws Ecp_Gateway_Not_Implemented_Exception
     */
    private function get_value($type, $path)
    {
        $keys = $this->parse_path($path);
        $key = array_shift($keys);

        if (preg_match('|^(?P<array>[^]\[]+)(?P<indexes>(?:\[\d+])+)$|', $key, $match)) {
            $key = $match['array'];
            $indexesRaw = $match['indexes'];
            preg_match_all('|\[(?P<index>\d+)]|', $indexesRaw, $match);
            $indexes = $match['index'];

            $array = $this->get_current_value(self::ARRAY_TYPE, $key);

            foreach ($indexes as $index) {
                $array = $array[$index];
            }

            if (count($keys) > 0) {
                return $array->get_value($type, implode(self::PATH_SEPARATOR, $keys));
            }

            return $array;
        }

        if (count($keys) > 0) {
            return $this->get_current_value(self::OBJECT_TYPE, $key)
                ->get_value($type, implode(self::PATH_SEPARATOR, $keys));
        }

        return $this->get_current_value($type, $key);
    }

    /**
     * @param string $type
     * @param string $path
     * @since 2.0.0
     * @return array|bool|float|int|string|static
     * @throws Ecp_Gateway_Invalid_Argument_Exception
     * @throws Ecp_Gateway_Key_Not_Found_Exception
     * @throws Ecp_Gateway_Not_Implemented_Exception
     */
    private function get_current_value($type, $path)
    {
        if (!$this->offsetExists($path)) {
            throw new Ecp_Gateway_Key_Not_Found_Exception($path, $this->keys());
        }

        $result = $this->offsetGet($path);

        switch ($type) {
            case self::STRING_TYPE:
                if (!is_string($result)) {
                    throw new Ecp_Gateway_Invalid_Argument_Exception($path, $type, gettype($result));
                }
                break;
            case self::BOOL_TYPE:
                if (!is_bool($result)) {
                    throw new Ecp_Gateway_Invalid_Argument_Exception($path, $type, gettype($result));
                }
                break;
            case self::INTEGER_TYPE:
                if (!is_int($result)) {
                    throw new Ecp_Gateway_Invalid_Argument_Exception($path, $type, gettype($result));
                }
                break;
            case self::FLOAT_TYPE:
                if (!is_float($result)) {
                    throw new Ecp_Gateway_Invalid_Argument_Exception($path, $type, gettype($result));
                }
                break;
            case self::ARRAY_TYPE:
                if ($result instanceof Ecp_Gateway_Json) {
                    $result = $result->to_array();
                }
                if (!is_array($result)) {
                    throw new Ecp_Gateway_Invalid_Argument_Exception($path, $type, gettype($result));
                }
                break;
            case self::JSON_TYPE:
                if (!is_object($result)) {
                    throw new Ecp_Gateway_Invalid_Argument_Exception($path, $type, gettype($result));
                }

                if (!$result instanceof Ecp_Gateway_Json) {
                    throw new Ecp_Gateway_Not_Implemented_Exception($result, Ecp_Gateway_Json::class);
                }
                break;
            case self::OBJECT_TYPE:
                if (!is_object($result)) {
                    throw new Ecp_Gateway_Invalid_Argument_Exception($path, $type, gettype($result));
                }

                break;
        }

        return $result;
    }

    /**
     * <h2>Returns json-path as array.</h2>
     *
     * @param string $path <p>Tree path as string.</p>
     * @since 2.0.0
     * @return string[] <p>Tree path as array.</p>
     */
    private function parse_path($path)
    {
        return explode(self::PATH_SEPARATOR, $path);
    }

    /**
     * @param $value
     * @param $key
     * @since 2.0.0
     * @return array|mixed|string|null
     */
    private function serialize($value, $key)
    {
        switch (true) {
            case array_key_exists($key, $this->packRules()):
                $options = $this->packRules()[$key];

                if (is_callable($options)) {
                    return $options($value);
                }

                $className = array_shift($options);
                $transformer = Ecp_Gateway_Registry::get_by_class($className);

                if ($transformer instanceof Ecp_Gateway_Serializer_Interface) {
                    return $transformer->serialize($value, ...$options);
                }
                return $value;
            case $value instanceof Ecp_Gateway_Array && !$value instanceof Ecp_Gateway_Json:
                return $value->to_array();
            case $value instanceof Ecp_Gateway_Json:
                if ($value->count() <= 0) {
                    return null;
                }

                return $value->jsonSerialize();
            case is_array($value):
                $this->serialize_array($value);
                return $value;
            default:
                return $value;
        }
    }

    /**
     * @param array $data
     * @since 2.0.0
     * @return void
     */
    private function deserialize(array $data)
    {
        foreach ($data as $key => $value) {
            switch (true) {
                case array_key_exists($key, $this->register):
                    if ($value instanceof $this->register[$key]) {
                        $this->set($key, $value);
                    } else {
                        $this->set($key, new $this->register[$key]($value));
                    }
                    break;
                case array_key_exists($key, $this->unpackRules()):
                    $options = $this->unpackRules()[$key];

                    if (is_callable($options)) {
                        $this->set($key, $options($value));
                        continue(2);
                    }

                    $className = array_shift($options);
                    $transformer = Ecp_Gateway_Registry::get_by_class($className);

                    if ($transformer instanceof Ecp_Gateway_Serializer_Interface) {
                        $this->set($key, $transformer->deserialize($value, ...$options));
                    }
                    break;
                default:
                    $method = 'set_' . $key;

                    if (method_exists($this, $method)) {
                        $this->$method($value);
                    } else {
                        $this->set($key, $value);
                    }
            }
        }
    }

    /**
     * @param $array
     * @since 2.0.0
     * @return void
     */
    private function serialize_array(&$array)
    {
        foreach ($array as &$item) {
            if ($item instanceof Ecp_Gateway_Json) {
                $item = $item->jsonSerialize();
            }

            if (is_array($item)) {
                $this->serialize_array($item);
            }
        }

        unset($item);
    }

    /**
     * @param array $data
     * @param $prefix
     * @param $separator
     * @since 2.0.0
     * @return array
     */
    private function to_key_value_pairs(array $data, $prefix = self::EMPTY_STRING, $separator = self::PATH_SEPARATOR)
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_int($key) && $prefix !== self::EMPTY_STRING) {
                $key = $prefix . '[' . $key . ']';
            } else {
                $key = ($prefix !== self::EMPTY_STRING ? $prefix . $separator : self::EMPTY_STRING) . $key;
            }

            if (is_array($value)) {
                $result = array_merge(
                    $result,
                    $this->to_key_value_pairs(
                        $value,
                        $key,
                        $separator
                    )
                );
            } else {
                $result[$key] = $value;
            }
        }

        if (count($result) === 0 && $prefix !== self::EMPTY_STRING) {
            $result[$prefix] = [];
        }

        return $result;
    }

    // endregion
}
