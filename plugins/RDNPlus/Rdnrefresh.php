<?php

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

class Rdnrefresh extends Memcached_DataObject
{
    public $__table = 'rdnrefresh'; // table name

    public $user_id;                         // int(4)  primary_key not_null
    public $spoilertags;
    public $usernamestags;
    public $anyhighlightwords;
    public $maincolor;
    public $asidecolor;
    public $pagecolor;
    public $linkcolor;
    public $customstyle;
    public $logo;
    public $backgroundimage;
    public $hideemotes;
    public $autospoil;

    function staticGet($k, $v=null)
    {
        return Memcached_DataObject::staticGet('Rdnrefresh', $k, $v);
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
        return array('user_id' => DB_DATAOBJECT_INT + DB_DATAOBJECT_NOTNULL,
            'spoilertags' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'usernamestags' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'anyhighlightwords' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'maincolor' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'asidecolor' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'pagecolor' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'linkcolor' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'customstyle' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
            'logo' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'backgroundimage' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'hideemotes' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
            'autospoil' => DB_DATAOBJECT_BOOL + DB_DATAOBJECT_NOTNULL,
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
        return array('user_id' => 'K');
    }

    function sequenceKey()
    {
        return array(false, false, false);
    }

    function fetchDB($userid) {
        $vars = Rdnrefresh::staticGet('user_id', $userid);
        return $vars;
    }

    function getValues() {
        $user = common_current_user();

        if(!empty($user)) {
            $database = Rdnrefresh::fetchDB($user->id);
        }

        $vars = array();
        $defaults = array(
            'user_id'           => (!empty($user->id)) ? $user->id : '0',
            'spoilertags'       => 'spoiler spoilers spoileralert poiler soiler spiler spoler spoier spoilr spoile sspoiler sppoiler spooiler spoiiler spoiller spoileer spoilerr psoiler sopiler spioler spolier spoielr spoilre',
            'maincolor'         => '#373737',
            'asidecolor'        => '#212C37',
            'pagecolor'         => '#FFFFFF',
            'linkcolor'         => '#00EE00',
            'customstyle'       => 0,
            'logo'              => '',
            'backgroundimage'   => '',
            'anyhighlightwords' => '',
            'usernamestags'     => '',
            'hideemotes'        => 0,
            'autospoil'         => 0,
        );

        if(!empty($database)) {
            foreach($defaults as $key => $default) {
                $dbvar = $database->$key;
                $vars[$key] = !is_null($dbvar) ? $dbvar : $default;
            }

            // Prevent leaks.
            $database->free();
            unset($database);
        }
        else {
            foreach($defaults as $key => $default) {
                $vars[$key] = $default;
            }
        }

        return $vars;
    }

}
