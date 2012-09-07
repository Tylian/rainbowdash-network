<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class StaffPlugin extends Plugin
{

    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'StaffAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        default:
            return true;
        }

    }    

    function onRouterInitialized($m)
    {
        $m->connect('main/staff',
            array('action' => 'staff'));
        return true;
    }
}
?>
