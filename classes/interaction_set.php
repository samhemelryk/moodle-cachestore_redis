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
 * This file belongs to the redis cache store and contains the standard set interaction class.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The Redis standard set interaction method class.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_redis_interaction_set implements cachestore_redis_interaction {

    /**
     * The Redis object.
     * @var Redis
     */
    protected $redis;

    /**
     * The collection to use for storage.
     * @var string
     */
    protected $collection;

    /**
     * The TTL for entries in this instance.
     * @var int|null
     */
    protected $ttl = null;

    /**
     * The timestamp, taken during construction.
     * @var int
     */
    protected $timestamp;

    /**
     * Whether to update the TTL when reading.
     * @var bool
     */
    protected $updatettl = false;

    /**
     * How often to run the garbage collection routine.
     * @var int
     */
    protected $gcfreq = 500;

    /**
     * Constructs an interaction instance.
     *
     * @param Redis $redis
     * @param cache_definition $definition
     */
    public function __construct(Redis $redis, cache_definition $definition) {
        $this->redis = $redis;
        $this->collection = $definition->get_id();
        $this->ttl = $definition->get_ttl();
        $this->timestamp = cache::now();
        if (defined('CACHESTORE_REDIS_UPDATE_TTL')) {
            $this->updatettl = (bool)CACHESTORE_REDIS_UPDATE_TTL;
        }
        if (defined('CACHESTORE_REDIS_GC_FREQ')) {
            $this->gcfreq = (int)CACHESTORE_REDIS_GC_FREQ;
        }
        if ($this->gcfreq > 0 && rand(0, 500) === 1) {
            // This happens on roughly 1 in 500 initialisations.
            $this->gc();
        }
    }

    /**
     * Sets a key value pair in Redis.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value) {
        if ($this->redis->set($key, $value, $this->ttl)) {
            $this->redis->zAdd($this->collection, $this->timestamp, $key);
        }
        return true;
    }

    /**
     * Gets a value from Redis.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        if ($this->updatettl && $this->ttl) {
            $this->redis->setTimeout($key, $this->ttl);
        }
        return $this->redis->get($key);
    }

    /**
     * Checks if the given value exists in Redis.
     *
     * @param string $key
     * @return mixed
     */
    public function has($key) {
        return $this->redis->exists($key);
    }

    /**
     * Deletes a key value pair from Redis.
     *
     * @param string $key
     * @return mixed
     */
    public function delete($key) {
        $this->redis->zDelete($this->collection, $key);
        return (bool)$this->redis->delete($key);
    }

    /**
     * Sets many values at once in Redis.
     *
     * @param mixed[] $values Key value array.
     * @return mixed
     */
    public function set_many(array $values) {
        if ($this->redis->mset($values)) {
            if ($this->updatettl && $this->ttl) {
                foreach ($values as $key => $value) {
                    $this->redis->setTimeout($key, $this->ttl);
                }
            }
            $this->redis->zAdd($this->collection, $this->timestamp, array_keys($values));
        }
        return count($values);
    }

    /**
     * Gets many values at once from Redis.
     *
     * @param string[] $keys
     * @return mixed
     */
    public function get_many(array $keys) {
        if ($this->updatettl && $this->ttl) {
            foreach ($keys as $key) {
                $this->redis->setTimeout($key, $this->ttl);
            }
        }
        $values = $this->redis->mGet($keys);
        return array_combine($keys, $values);
    }

    /**
     * Deletes many values at once from Redis.
     *
     * @param string[] $keys
     * @return mixed
     */
    public function delete_many(array $keys) {
        foreach ($keys as $key) {
            $this->redis->zDelete($this->collection, $key);
        }
        return $this->redis->delete($keys);
    }

    /**
     * Purges the given Redis backend of all data for this instance.
     *
     * @param int $timestamp
     * @return mixed
     */
    public function purge($timestamp = -1) {
        // Get all of the keys in the range.
        $keys = $this->redis->zRange($this->collection, 0, $timestamp);
        if (count($keys)) {
            // Delete all of the stored key=>value pairs.
            call_user_func_array(array($this->redis, 'delete'), $keys);
            // Remove everything from the range.
            call_user_func(array($this->redis, 'zDelete'), $this->collection, $keys);
        }
        return true;
    }

    /**
     * Garbage clean the Redis backend.
     */
    protected function gc() {
        $this->purge($this->redis, $this->timestamp - 1);
    }
}
