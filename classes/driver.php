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
 * This file belongs to the redis cache store and contains the redis driver class.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The Redis cache store driver class.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_redis_driver {

    /**
     * The actual Redis connection object.
     * @var Redis
     */
    protected $connection;

    /**
     * The current connection state, true if it is connected and ready for use, false otherwise.
     * @var bool
     */
    protected $connectionresult;

    /**
     * The interaction method for this redis connection.
     * @var cachestore_redis_interaction_set
     */
    protected $interaction;

    /**
     * The host to connect to. This could be an IP address, a hostname or a socket path.
     * @var string
     */
    protected $host;

    /**
     * The port to connect to. The default is 6379.
     * @var int
     */
    protected $port = 6379;

    /**
     * The connection timeout in seconds.
     * @var float
     */
    protected $timeout;

    /**
     * The string to use to identify this connection if it is persistent.
     * @var null|string
     */
    protected $persistentid = null;

    /**
     * The retry interval in milliseconds (optional)
     * @var int
     */
    protected $retryinterval;

    /**
     * Get a Redis connection instance.
     *
     * This method essential acts as a connection pool for the lifetime of the request.
     *
     * @param string $host The host to connect to. This could be an IP address, a hostname or a socket path.
     * @param int $port The port to connect to.
     * @param int $database The database to connect to as an integer.
     * @param float $timeout The connection timeout in seconds
     * @param string $persistentid The string to use to identify this connection if it is to be persistent.
     * @param int $retryinterval The retry interval in milliseconds.
     * @return cachestore_redis_driver
     */
    public static function instance($host, $port = 6379, $database = 0, $timeout = null, $persistentid = null,
                                    $retryinterval = null) {
        static $instances = array();
        $hash = crc32($host.' '.$port.' '.$database);
        if (!isset($instances[$hash])) {
            $instances[$hash] = new cachestore_redis_driver($host, $port, $timeout, $persistentid, $retryinterval);
            $instances[$hash]->connect($database);
        }
        return $instances[$hash];
    }

    /**
     * Creates a connection to a Redis server. Please use instance instead.
     *
     * @param string $host The host to connect to. This could be an IP address, a hostname or a socket path.
     * @param int $port The port to connect to.
     * @param float $timeout The connection timeout in seconds
     * @param string $persistentid The string to use to identify this connection if it is to be persistent.
     * @param int $retryinterval The retry interval in milliseconds.
     */
    protected function __construct($host, $port = 6379, $timeout = null, $persistentid = null, $retryinterval = null) {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->persistentid = $persistentid;
        $this->retryinterval = $retryinterval;
    }

    /**
     * Connects to a Redis server and selects the given database for use.
     *
     * @param int $database
     * @throws cachestore_redis_exception
     */
    public function connect($database) {
        $this->connection = new Redis();
        if ($this->persistentid !== null) {
            $method = 'pconnect';
        } else {
            $method = 'connect';
        }
        $this->connectionresult = $this->connection->$method(
            $this->host,
            $this->port,
            $this->timeout,
            $this->persistentid,
            $this->retryinterval
        );
        if ($this->connectionresult) {
            $this->connectionresult = $this->connection->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        }
        if ($this->connectionresult) {
            $this->connection->select($database);
        }
    }

    /**
     * Returns true if this instance is connected successfully to a backend redis server.
     * @return bool
     */
    public function is_connected() {
        return $this->connectionresult;
    }

    /**
     * Authenticates with the Redis server.
     *
     * Should only be called if authentication is required.
     *
     * @param string $password
     * @throws cachestore_redis_exception
     */
    public function authenticate($password) {
        if (!$this->is_connected()) {
            throw new cachestore_redis_exception('exception_operationnotconnected', __METHOD__);
        }
        if (!$this->connection->auth($password)) {
            $this->close();
        }
    }

    /**
     * Closes the connection to the Redis backend.
     */
    public function close() {
        $this->connection->close();
        $this->connectionresult = false;
    }

    /**
     * Pings the Redis server to ensure the connection is properly established and usable.
     * @return bool
     */
    public function ping() {
        try {
            $this->connection->ping();
        } catch (RedisException $exception) {
            return false;
        }
        return true;
    }

    /**
     * Sets the interaction method.
     *
     * @param string $method
     * @param cache_definition $definition
     */
    public function set_interation_instance($method, cache_definition $definition) {
        $class = 'cachestore_redis_interaction_'.$method;
        $this->interaction = new $class($this->connection, $definition);
    }

    /**
     * Sets a key value pair.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value) {
        return $this->interaction->set($key, $value);
    }

    /**
     * Gets a value.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        return $this->interaction->get($key);
    }

    /**
     * Deletes a key value pair.
     *
     * @param string $key
     * @return mixed
     */
    public function delete($key) {
        return $this->interaction->delete($key);
    }

    /**
     * Checks if the given value exists.
     *
     * @param string $key
     * @return mixed
     */
    public function has($key) {
        return $this->interaction->has($key);
    }

    /**
     * Purges all data for this instance.
     *
     * @return mixed
     */
    public function purge() {
        return $this->interaction->purge();
    }

    /**
     * Sets many values at once.
     *
     * @param mixed[] $values Key value array.
     * @return mixed
     */
    public function set_many(array $values) {
        return $this->interaction->set_many($values);
    }

    /**
     * Gets many values at once.
     *
     * @param string[] $keys
     * @return mixed
     */
    public function get_many(array $keys) {
        return $this->interaction->get_many($keys);
    }

    /**
     * Deletes many values at once.
     *
     * @param string[] $keys
     * @return mixed
     */
    public function delete_many(array $keys) {
        return $this->interaction->delete_many($keys);
    }

    /**
     * Destroy this object closing its connection.
     */
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }
}