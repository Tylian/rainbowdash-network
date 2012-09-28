<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

// Seize an account from a user, changing their password to something arbitrary.
class SeizePlugin extends Plugin
{
    function onRouterInitialized($m) {
        $m->connect('main/seize', array('action' => 'seize'));

        return true;
    }

    function onAutoload($cls) {
        $dir = dirname(__FILE__);

        switch ($cls) {
        case 'SeizeAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'SeizeForm':
            include_once $dir . '/' . strtolower($cls) . '.php';
        default:
            return true;
        }

    }    

    function onEndProfilePageActionsElements($action, $profile) {
        $cur = common_current_user();

        if(($cur->hasRole(Profile_role::ADMINISTRATOR)
            //|| $cur->hasRole(Profile_role::MODERATOR)
        ) && $cur->id != $profile->id) {
            $sf = new SeizeForm($action, $profile,
                array('nickname' => $profile->nickname,
                'action' => $action->trimmed('action')));
            $sf->show();
        }

        return true;
    }
}
?>
