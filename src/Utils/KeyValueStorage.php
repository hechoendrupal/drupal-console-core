<?php


namespace Drupal\Console\Core\Utils;


class KeyValueStorage
{

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Checks if the container has the given key.
     *
     * @param  string $key
     *   The key to check.
     *
     * @return boolean
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Gets the given key from the container, or returns the default if it does
     * not exist.
     *
     * @param  string $key
     *   The key to get.
     * @param  mixed $default
     *   Default value to return.
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    /**
     * Sets the given key in the container.
     *
     * @param mixed $key
     *   The key to set
     * @param mixed $value
     *   The value.
     */
    public function set($key, $value = null)
    {
        $this->data[$key] = $value;
    }

    /**
     * Removes the given key from the container.
     *
     * @param  string $key The key to forget.
     *
     * @return void
     */
    public function remove($key)
    {
        unset($this->data[$key]);
    }

}
