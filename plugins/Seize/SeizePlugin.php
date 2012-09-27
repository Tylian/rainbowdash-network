<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

// Seize an account from a user, changing their password to something arbitrary.
class SeizePlugin extends Plugin
{
    function onAutoload($cls) {
        $dir = dirname(__FILE__);

        switch ($cls) {
        case 'SeizeAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        default:
            return true;
        }

    }    

    function onRouterInitialized($m) {
        $m->connect('main/seize', array('action' => 'seize'));

        return true;
    }

    function onEndProfilePageActionsElements($action, $profile) {
        $cur = common_current_user();

        if(($cur->hasRole(Profile_role::OWNER)
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

class SeizeForm extends ProfileActionForm {

    function target()
    {
        return 'seize';
    }

    function title()
    {
        return _('Seize');
    }

    function description()
    {
        return _('Seize this account');
    }
}

?>
