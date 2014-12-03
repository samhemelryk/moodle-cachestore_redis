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
 * This file belongs to the redis cache store and defines what an interaction class should look like.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Redis interaction interface.
 *
 * This interface defines how Redis interaction should occur.
 * Each Redis cache store instance uses an instance of the driver class.
 * The driver class uses an instance of an interaction to actually interact with the Redis backend.
 *
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface cachestore_redis_interaction {

    /**
     * Constructs an interaction instance.
     *
     * @param Redis $redis
     * @param cache_definition $definition
     */
    public function __construct(Redis $redis, cache_definition $definition);

    /**
     * Sets a key value pair in Redis.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value);

    /**
     * Gets a value from Redis.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * Deletes a key value pair from Redis.
     *
     * @param string $key
     * @return mixed
     */
    public function delete($key);

    /**
     * Sets many values at once in Redis.
     *
     * @param mixed[] $values Key value array.
     * @return mixed
     */
    public function set_many(array $values);

    /**
     * Gets many values at once from Redis.
     *
     * @param string[] $keys
     * @return mixed
     */
    public function get_many(array $keys);

    /**
     * Deletes many values at once from Redis.
     *
     * @param string[] $keys
     * @return mixed
     */
    public function delete_many(array $keys);

    /**
     * Checks if the given value exists in Redis.
     *
     * @param string $key
     * @return mixed
     */
    public function has($key);

    /**
     * Purges the given Redis backend of all data for this instance.
     *
     * @return mixed
     */
    public function purge();
}