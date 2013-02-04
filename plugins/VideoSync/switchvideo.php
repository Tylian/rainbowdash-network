<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class SwitchvideoAction extends Action
{
    /**
     * Class handler.
     *
     * @param array $args query arguments
     *
     * @return void
     */
    function handle($args)
    {
        parent::handle($args);
        if (!common_logged_in()) {
            $this->clientError(_('Not logged in.'));
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo 'berp';
            common_redirect(common_local_url('public'));
            return;
        }
        $user = common_current_user();
        if(!$user->hasRight(Right::CONFIGURESITE)) {
            $this->clientError(_('Not authorized'));
            return;
        }
        $selected     = $this->trimmed('videoswitch-submit');
        $v = new Videosync();
        $v->tag = $selected;
        if(!$v->find() || !$v->fetch()) {
            $this->clientError(_('Invalid video ID'));
            return;
        }
        $token  = $this->trimmed('token-videoswitch');
        if (!$token || $token != common_session_token()) {
            $this->clientError(_('There was a problem with your session token. Try again, please.'));
            return;
        }
        Videosync::setCurrent($v->id);
        if ($this->boolean('ajax')) {
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            // TRANS: Page title for page on which favorite notices can be unfavourited.
            $this->element('title', null, _('Switch video.'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $v = new Videosync();
            $v->find();
            $videoswitch = new SwitchForm($this, $v);
            $videoswitch->show();
            $this->elementEnd('body');
            $this->elementEnd('html');
        } else {
            echo 'borp';
            common_redirect(common_local_url('public'));
        }
    }
}
