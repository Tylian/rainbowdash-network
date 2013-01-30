<?php

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

class Videosync extends Memcached_DataObject
{
    public $__table = 'videosync'; // table name

    public $id;    //internal id. Used for sorting purposes
    public $yt_id; // YouTube ID of the video
    public $duration; // Length of the video in seconds
    public $started; // Time the video was started

    function staticGet($k, $v=null)
    {
        return Memcached_DataObject::staticGet('Videosync', $k, $v);
    }

    /**
     * return table definition for DB_DataObject
     *
     * DB_DataObject needs to know something about the table to manipulate
     * instances. This method provides all the DB_DataObject needs to know.
     *
     * @return array array of column definitions
     */
    function table()
    {
        return array('id' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
            'yt_id' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'duration' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
            'started' => DB_DATAOBJECT_STR + DB_DATAOBJECT_DATE + DB_DATAOBJECT_TIME,
        );
    }

    /**
     * return key definitions for DB_DataObject
     *
     * DB_DataObject needs to know about keys that the table has, since it
     * won't appear in StatusNet's own keys list. In most cases, this will
     * simply reference your keyTypes() function.
     *
     * @return array list of key field names
     */
    function keys()
    {
        return array_keys($this->keyTypes());
    }

    /**
     * return key definitions for Memcached_DataObject
     *
     * Our caching system uses the same key definitions, but uses a different
     * method to get them. This key information is used to store and clear
     * cached data, so be sure to list any key that will be used for static
     * lookups.
     *
     * @return array associative array of key definitions, field name to type:
     *         'K' for primary key: for compound keys, add an entry for each component;
     *         'U' for unique keys: compound keys are not well supported here.
     */
    function keyTypes()
    {
        return array('id' => 'K');
    }

    function isCurrent() {
        if(strtotime($v->started) + $v->duration <= time()) {
            return false;
        }
        return true;
    }

    static function getCurrent() {
        $v = new Videosync();
        $v->orderBy("started DESC");
        if(!$v->find() || !$v->fetch()) {
            $v = Videosync::setCurrent(1);
        }
        else if(!$v->isCurrent()) {
            $v = Videosync::setCurrent($v->id + 1);
        }

        return $v;
    }

    static function setCurrent($id) {
        $v = new Videosync();
        $v->query("UPDATE videosync SET started = NULL");

        $new = Videosync::staticGet('id', $id);
        if(empty($new)) {
            $new = Videosync::staticGet('id', 1);
        }

        if(!empty($new)) {
            $orig = clone($new);
            $new->started = time();
            $new->update($orig);
        }
        else {
            $new = new Videosync();
            $new->id = "1";
            $new->yt_id = "wmKrQBWc2U4";
            $new->duration = 856;
            $new->started = date('r', time() - 7 * 60);
        }

        return $new;
    }

    function sequenceKey()
    {
        return array(false, false, false);
    }

}
