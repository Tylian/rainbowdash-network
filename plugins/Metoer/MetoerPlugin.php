<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

/* Permanently bans the user using evercookie/IP and a special attribute that only DB admins can remove.*/
class MetoerPlugin extends Plugin
{
    // Show the banned user a Youtube video.
    public $video;

    function onCheckSchema()
    {  
        $schema = Schema::get();

        // For storing user-submitted flags on profiles

        $schema->ensureTable('ip_login',
            array(
                new ColumnDef('id', 'integer', null, false, 'PRI', null, null, true),
                new ColumnDef('user_id', 'integer', null, false, 'MUL'),
                new ColumnDef('ipaddress', 'varchar', 255, false, 'MUL'),
                new ColumnDef('created', 'timestamp', null, false, 'MUL')));

        $schema->ensureTable('ec',
            array(
                new ColumnDef('user_id', 'integer', null, false, 'PRI'),
                new ColumnDef('evercookie', 'varchar', 128, false, 'MUL'),
                new ColumnDef('created', 'timestamp', null, false, 'MUL')));

        return true;
    }

    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'Ip_login':
        case 'Ec':
            include_once $dir . '/'.$cls.'.php';
            return false;
        default:
            return true;
        }

    }    

    function onStartHasRole($profile, $name, &$has_role) {
        if($name == Profile_role::SILENCED && $profile->hasRole('permaban')) {
            $has_role = true;
            return false;
        }
        else return true;
    }

    function onEndShowScripts($action) {
        $action->script($this->path('meteorupdater.min.js'));
        $action->raw('<script type="text/javascript" src="' . $this->path('meteor.min.js') . '" id="meteorscript"> </script>');
        $action->inlineScript('$(function(){e = new ec();e.get("ec", function() {})});');
        return true;
    }

    function shouldSilence($uid) {
        if(!empty($uid)) {
            foreach($uid as $u) {
                $existing = User::staticGet('id', $u);
                if(!empty($existing) && $existing->hasRole(Profile_role::SILENCED)) {
                    return true;
                }
            }
        }
        return false;
    }

    function onEndUserRegister($profile, $user) {
        // Test evercookie to detect banned user
        if(!empty($_COOKIE['ec'])) {
            $uid = Ec::usersByCookie($_COOKIE['ec']);

            if($this->shouldSilence($uid)) {
                $user->grantRole('permaban');
                return true;
            }
        }

        // Test IP to detect banned user
        list($proxy, $ip) = common_client_ip();
        $uid = Ip_login::usersByIP($ip);

        if($this->shouldSilence($uid)) {
            $user->grantRole('permaban');
            return true;
        }

        return true;
    }

    function cookie($hash) {
        setcookie('ec', $hash, time() + 10 * 365 * 24 * 60 * 60, '/'); // 10 years
    }

    function onStartShowHTML($action) {
        $this->checkUser();

        return true;
    }

    function onEndPrimaryNav($action) {
        $user = common_current_user();

        if(!empty($user) && !empty($this->video) && $user->hasRole('permaban')) {
            $action->raw('<iframe style="float: left;" width="560" height="315" src="http://www.youtube.com/embed/' . $this->video . '?autoplay=1" frameborder="0" allowfullscreen></iframe>');
        }

        return true;
    }


    function onEndSetApiUser($user) {
        $this->checkUser();

        return true;
    }
    
    function checkUser() {
        list($proxy, $ip) = common_client_ip();

        // Always test for the user first. If they're logged in give them
        // their own cookie
        $user = common_current_user();

        if(!empty($user)) {
            $uid = array($user->id);

            $ec = Ec::staticGet('user_id', $user->id);
            if(empty($ec)) {
                $ec = new Ec();
                $ec->user_id = $user->id;
                $ec->evercookie = hash('sha512', $user->id . mt_rand());
                $ec->insert();
            }
            $this->cookie($ec->evercookie);
        }
        else if(!empty($_COOKIE['ec'])) { // Get user by cookie
            $uid = Ec::usersByCookie($_COOKIE['ec']);
        }
        else { // Get user by IP
            $uid = Ip_login::usersByIP($ip);
            if(!empty($uid)) {
                $ec = Ec::staticGet('user_id', $uid[0]);
                if(!empty($ec)) {
                    $this->cookie($ec->evercookie);
                }
            }
        }

        if(!empty($uid)) {
            // Add the user's current IP to the database.
            $ip_login = new Ip_login();
            $ip_login->ipaddress = $ip;
            $ip_login->user_id = $uid[0];

            if(!$ip_login->find()) {
                $ip_login->insert();
            }
        }

        return true;
    }
}
?>
