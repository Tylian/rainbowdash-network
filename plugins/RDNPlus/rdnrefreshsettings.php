<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/settingsaction.php';

class RdnrefreshsettingsAction extends SettingsAction
{
    /**
     * Title of the page
     *
     * @return string Title of the page
     */
    function title()
    {
        return _('RDN Plus settings');
    }

    /**
     * Instructions for use
     *
     * @return instructions for use
     */
    function getInstructions()
    {
        return _('Update your RDN Plus settings here');
    }

    function showScripts()
    {
        parent::showScripts();
        $this->autofocus('usernamestags');
    }

    /**
     * Content area of the page
     *
     * Shows a form for uploading an avatar.
     *
     * @return void
     */
    function showContent()
    {
        $vars = Rdnrefresh::getValues();

        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'form_settings_profile',
                                          'class' => 'form_settings',
                                          'action' => common_local_url('rdnrefreshsettings')));
        $this->elementStart('fieldset');
        $this->hidden('token', common_session_token());

        // too much common patterns here... abstractable?
        $this->elementStart('ul', 'form_data');
        if (Event::handle('StartRDNRefreshFormData', array($this))) {
            $this->elementStart('li');
            $this->checkbox('usemarkdown', _('Parse RDN-Flavor Markdown'),
                            ($this->arg('usemarkdown')) ?
                            $this->boolean('usemarkdown') : $vars['usemarkdown']);
            $this->elementEnd('li');


            $this->elementStart('li');
            $this->checkbox('autospoil', _('Auto unhide spoilers'),
                            ($this->arg('autospoil')) ?
                            $this->boolean('autospoil') : $vars['autospoil']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->checkbox('hideemotes', _('Hide emoticons'),
                            ($this->arg('hideemotes')) ?
                            $this->boolean('hideemotes') : $vars['hideemotes']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('spoilertags', _('Hide tags'),
                         ($this->arg('spoilertags')) ? $this->arg('spoilertags') : $vars['spoilertags']
                         );
            $this->elementEnd('li');
            $this->elementStart('li');
            $this->input('usernamestags', _('Hide Users'),
                         ($this->arg('')) ? $this->arg('usernamestags') : $vars['usernamestags']);
            $this->elementEnd('li');
            $this->elementStart('li');
            $this->input('anyhighlightwords', _('Highlight Words'),
                         ($this->arg('anyhighlightwords')) ? $this->arg('anyhighlightwords') : $vars['anyhighlightwords']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->checkbox('customstyle', _('Choose a custom style for the homepage'),
                            ($this->arg('customstyle')) ?
                            $this->boolean('customstyle') : $vars['customstyle']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('logo', _('Logo URL'),
                         ($this->arg('logo')) ? $this->arg('logo') : $vars['logo']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('pagecolor', _('Text Color'),
                         ($this->arg('pagecolor')) ? $this->arg('pagecolor') : $vars['pagecolor']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('maincolor', _('Body Color'),
                         ($this->arg('maincolor')) ? $this->arg('maincolor') : $vars['maincolor']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('asidecolor', _('Sidebar Color'),
                         ($this->arg('asidecolor')) ? $this->arg('asidecolor') : $vars['asidecolor']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('linkcolor', _('Link Color'),
                         ($this->arg('linkcolor')) ? $this->arg('linkcolor') : $vars['linkcolor']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('backgroundimage', _('Background Image URL'),
                         ($this->arg('backgroundimage')) ? $this->arg('backgroundimage') : $vars['backgroundimage']);
            $this->elementEnd('li');

            Event::handle('EndRDNRefreshFormData', array($this));

        $this->elementEnd('ul');
        $this->submit('save', _m('BUTTON','Save'));

        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }
    }

    /**
     * Handle a post
     *
     * Validate input and save changes. Reload the form with a success
     * or error message.
     *
     * @return void
     */
    function handlePost()
    {
        // CSRF protection
        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->showForm(_('There was a problem with your session token. '.
                              'Try again, please.'));
            return;
        }

        if (Event::handle('StartRDNRefreshSaveForm', array($this))) {

            $user = common_current_user();

            if(!empty($user)) {

                $vars = Rdnrefresh::staticGet('user_id', $user->id);

                if(empty($vars)) {
                    $vars = new Rdnrefresh();
                    $vars->user_id = $user->id;
                }
                else {
                    $orig = clone($vars);
                }

                $vars->spoilertags = substr($this->trimmed('spoilertags'),0,255);
                $vars->usernamestags = substr($this->trimmed('usernamestags'),0,255);
                $vars->anyhighlightwords = substr($this->trimmed('anyhighlightwords'),0,255);
                $vars->logo = substr($this->trimmed('logo'),0,255);
                $vars->backgroundimage = substr($this->trimmed('backgroundimage'),0,255);
                $vars->pagecolor = substr($this->trimmed('pagecolor'),0,7);
                $vars->maincolor = substr($this->trimmed('maincolor'),0,7);
                $vars->asidecolor = substr($this->trimmed('asidecolor'),0,7);
                $vars->linkcolor = substr($this->trimmed('linkcolor'),0,7);
                $vars->customstyle = $this->boolean('customstyle');
                $vars->usemarkdown = $this->boolean('usemarkdown');
                $vars->hideemotes = $this->boolean('hideemotes');
                $vars->autospoil = $this->boolean('autospoil');

                if(isset($orig))
                    $vars->update($orig);
                else
                    $vars->insert();

                $this->showForm(_('Settings saved.'), true);
            }
        }
    }
}
