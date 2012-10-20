<?php

// Usage: addPlugin('PasswdPlugin', array('password' => 'changeme'));

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class PasswdPlugin extends Plugin
{
    public $password = 'changeme';
    public $question = 'What is a term that is used for a dummy password that should be replaced?';

    function onInitializePlugin(){
        if(!isset($this->password) || !isset($this->question)) {
            common_log(LOG_ERR, 'Passwd: Must specify a password and question in config.php');
        }
    }


    function onEndRegistrationFormData($action)
    {
        $action->elementStart('li');
        $action->raw('<label for="site_password">Site Question</label>');
        $action->element('input', array('type'=> 'text', 'id' => 'site_password', 'name' => 'site_password', 'value' => $action->trimmed('site_password')));
        $action->raw("<p class=\"form_guide\">{$this->question}</p>");
        $action->elementEnd('li');

        $action->passwdpluginNeedsOutput = true;
        return true;
    }

    function onStartRegistrationTry($action)
    {
        if (!preg_match("/{$this->password}/i", $action->trimmed('site_password'))) {
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
