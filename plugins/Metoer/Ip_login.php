<?php
/**
 * Data class for storing IP addresses of new registrants.
 *
 * PHP version 5
 *
 * @category Data
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 *
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.     See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

/**
 * Data class for storing IP addresses of new registrants.
 *
 * @category Spam
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://status.net/
 */
class Ip_login extends Memcached_DataObject
{
    public $__table = 'ip_login';     // table name
    public $id;
    public $user_id;                         // int(4)  primary_key not_null
    public $ipaddress;                       // varchar(15)
    public $created;                         // timestamp

    /**
     * Get an instance by key
     *
     * @param string $k Key to use to lookup (usually 'user_id' for this class)
     * @param mixed  $v Value to lookup
     *
     * @return User_greeting_count object found, or null for no hits
     *
     */
    function staticGet($k, $v=null)
    {
        return Memcached_DataObject::staticGet('Ip_login', $k, $v);
    }

    /**
     * return table definition for DB_DataObject
     *
     * @return array array of column definitions
     */
    function table()
    {
        return array('id' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
                     'user_id' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
                     'ipaddress' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
                     'created' => DB_DATAOBJECT_MYSQLTIMESTAMP + DB_DATAOBJECT_NOTNULL);
    }

    /**
     * return key definitions for DB_DataObject
     *
     * DB_DataObject needs to know about keys that the table has; this function
     * defines them.
     *
     * @return array key definitions
     */
    function keys()
    {
        return array('id' => 'K',
            'user_id' => 'K');
    }

    /**
     * return key definitions for Memcached_DataObject
     *
     * Our caching system uses the same key definitions, but uses a different
     * method to get them.
     *
     * @return array key definitions
     */
    function keyTypes()
    {
        return $this->keys();
    }

    /**
     * Magic formula for non-autoincrementing integer primary keys
     *
     * If a table has a single integer column as its primary key, DB_DataObject
     * assumes that the column is auto-incrementing and makes a sequence table
     * to do this incrementation. Since we don't need this for our class, we
     * overload this method and return the magic formula that DB_DataObject needs.
     *
     * @return array magic three-false array that stops auto-incrementing.
     */

    function sequenceKey()
    {
        return array(false, false, false);
    }

    /**
     * Get the users who've registered with this ip address.
     *
     * @param Array $ipaddress IP address to check for
     *
     * @return Array IDs of users who registered with this address.
     */

    static function usersByIP($ip)
    {
        // You'd think a str_split would work here. It doesn't, because of those weird
        // multi-IPs with the commas.
        preg_match('@(([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+).*)@', $ip, $ip);
        $ids = array();

        // IP address block support
        $ip = array(
            $ip[2],
            "{$ip[2]}.{$ip[3]}",
            "{$ip[2]}.{$ip[3]}.{$ip[4]}",
            "{$ip[2]}.{$ip[3]}.{$ip[4]}.{$ip[5]}",
            $ip[1],
        );

        $ri            = new Ip_login();
        foreach($ip as $ipaddr) {
            $ri->whereAdd('ipaddress = \'' . mysql_escape_string($ipaddr) . '\'', 'OR');
        }

        if ($ri->find()) {
            while ($ri->fetch()) {
                // Boom. Duplicates prevented. Lazy but it works.
                $ids[$ri->user_id] = $ri->user_id;
            }
        }
        // You forgot that using keyed arrays breaks indexing. Doh.
        $ids = array_values($ids);

        return $ids;
    }
}
