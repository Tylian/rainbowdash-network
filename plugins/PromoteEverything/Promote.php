<?php

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

class Promote extends Memcached_DataObject
{
    public $__table = 'promote'; // table name

    public $id;
    public $type; // 'profile'|'group'|'tag'|'notice'. Should display the last notice of that stream in all cases.
    public $item_id; // The ID of the promoted item.

    function staticGet($k, $v=null)
    {
        return Memcached_DataObject::staticGet('Promote', $k, $v);
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
            'type' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'item_id' => DB_DATAOBJECT_STR + DB_DATAOBJECT_NOTNULL,
            'created' => DB_DATAOBJECT_MYSQLTIMESTAMP + DB_DATAOBJECT_NOTNULL,
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

    function sequenceKey()
    {
        return array(false, false, false);
    }

    static function getStream($offset=0, $limit=20) {

        $ids = Notice::stream(array('Promote', '_streamDirect'),
            array(),
            'promote:notice_ids',
            $offset, $limit);

        return Notice::getStreamByIds($ids);
    }

    function _streamDirect($offset=0, $limit=20, $since_id=0, $max_id=0)
    {
        $promote = new Promote();
        
        $promote->orderBy('created DESC, id DESC');
            
        if (!is_null($offset)) {
            $promote->limit($offset, $limit);
        }   
            
        $ids = array();

        if($promote->find()) {
            while ($promote->fetch()) {
                switch($promote->type) {
                    case 'group':
                        $item = new User_group();
                        break;
                    case 'profile':
                        $item = new Profile();
                        break;
                    case 'tag':
                        $item = new Notice_tag();
                        break;
                    case 'notice':
                        $ids[] = intval($promote->item_id);
                }

                if(empty($item)) continue;

                if($item instanceof Notice_tag) {
                    $ids = array_merge($ids, $item->_streamDirect($promote->item_id, 0, 1));
                }
                else {
                    $item->id = intval($promote->item_id);
                    $ids = array_merge($ids, $item->_streamDirect(0, 1));
                }

                $item->free();
                $item = NULL;
            }
        }

        $promote->free();
        $promote = NULL;

        return $ids;
    }

}
