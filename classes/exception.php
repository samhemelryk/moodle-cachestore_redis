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
 * This file belongs to the redis cache store and contains the redis cache store exception.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Redis cache store exception.
 *
 * @package    cachestore_redis
 * @copyright  2014 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_redis_exception extends moodle_exception {

    /**
     * Constructs a new Redis cache store exception.
     *
     * @param string $errorcode
     * @param string $operation
     * @param string $link
     * @param mixed $a
     * @param string $debuginfo
     */
    public function __construct($errorcode, $operation = null, $link='', $a = null, $debuginfo = null) {
        if (!$debuginfo && $operation) {
            $debuginfo = 'Exception occured performing '.$operation;
        }
        parent::__construct($errorcode, 'cachestore_redis', $link, $a, $debuginfo);
    }
}