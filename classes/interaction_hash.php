<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file belongs to the redis cache store and contains the hash interaction class.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Redis hash interaction method class.
 *
 * This class uses a hash collection for storage of the key value pairs.
 * The hash is the definition hash.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_redis_interaction_hash implements cachestore_redis_interaction {

    /**
     * The Redis object.
     * @var Redis
     */
    protected $redis;

    /**
     * The collection hash we are using here.
     * @var string
     */
    protected $hash;

    /**
     * Initialises an instance of this interaction method.
     *
     * @param Redis $redis
     * @param cache_definition $definition
     */
    public function __construct(Redis $redis, cache_definition $definition) {
        $this->redis = $redis;
        $this->hash = $definition->generate_definition_hash();
    }

    /**
     * Sets a key value pair in Redis.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value) {
        return ($this->redis->hSet($this->hash, $key, $value) !== false);
    }

    /**
     * Gets a value from Redis.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        return $this->redis->hGet($this->hash, $key);
    }

    /**
     * Checks if the given value exists in Redis.
     *
     * @param string $key
     * @return mixed
     */
    public function has($key) {
        return $this->redis->hExists($this->hash, $key);
    }

    /**
     * Deletes a key value pair from Redis.
     *
     * @param string $key
     * @return mixed
     */
    public function delete($key) {
        return ($this->redis->hDel($this->hash, $key) === 1);
    }

    /**
     * Sets many values at once in Redis.
     *
     * @param mixed[] $values Key value array.
     * @return mixed
     */
    public function set_many(array $values) {
        if ($this->redis->hMSet($this->hash, $values)) {
            return count($values);
        }
        return 0;
    }

    /**
     * Gets many values at once from Redis.
     *
     * @param string[] $keys
     * @return mixed
     */
    public function get_many(array $keys) {
        $values = $this->redis->hMGet($this->hash, $keys);
        return array_combine($keys, $values);
    }

    /**
     * Deletes many values at once from Redis.
     *
     * @param string[] $keys
     * @return mixed
     */
    public function delete_many(array $keys) {
        $count = 0;
        foreach ($keys as $key) {
            $count += $this->redis->hDel($this->hash, $key);
        }
        return $count;
    }

    /**
     * Purges the given Redis backend of all data for this instance.
     *
     * @return mixed
     */
    public function purge() {
        // Get all of the keys in the range.
        return ($this->redis->del($this->hash) !== false);
    }
}
