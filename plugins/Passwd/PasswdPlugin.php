<?php

// Usage: addPlugin('PasswdPlugin', array('password' => 'changeme'));

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class PasswdPlugin extends Plugin
{
    public $password = 'changeme';

    function onInitializePlugin(){
        if(!isset($this->password)) {
            common_log(LOG_ERR, 'Passwd: Must specify a password in config.php');
        }
    }


    function onEndRegistrationFormData($action)
    {
        $action->elementStart('li');
        $action->raw('<label for="site_password">Access Code</label>');
        $action->element('input', array('type'=> 'password', 'id' => 'site_password', 'name' => 'site_password', 'value' => $action->trimmed('site_password')));
        $action->raw('<p class="form_guide">Who is the pony in the logo?</p>');
        $action->elementEnd('li');

        $action->passwdpluginNeedsOutput = true;
        return true;
    }

    function onStartRegistrationTry($action)
    {
        if ($action->trimmed('site_password') !== $this->password) {
            $action->showForm("You forgot to answer the registration question!");
            return false;
        }
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Passwd',
            'version' => STATUSNET_VERSION,
            'author' => 'Minti',
            'homepage' => 'http://localhost/',
            'rawdescription' => 'Password protected registration.'
        );
        return true;
    }
}
