<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Change profile settings
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Settings
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/settingsaction.php';

/**
 * Change profile settings
 *
 * @category Settings
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Zach Copley <zach@status.net>
 * @author   Sarven Capadisli <csarven@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
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
        return _('Update your RDN Plus-related settings here');
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
        // xxx: fixme
        //$vars = Rdnrefresh::initDB();

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
            $this->checkbox('hideemotes', _('Hide emoticons'),
                            ($this->arg('hideemotes')) ?
                            $this->boolean('hideemotes') : $_COOKIE['hideemotes']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('spoilertags', _('Hide tags'),
                         ($this->arg('spoilertags')) ? $this->arg('spoilertags') : $_COOKIE['spoilertags']);
            $this->elementEnd('li');
            $this->elementStart('li');
            $this->input('usernamestags', _('Hide Users'),
                         ($this->arg('')) ? $this->arg('usernamestags') : $_COOKIE['usernamestags']);
            $this->elementEnd('li');
            $this->elementStart('li');
            $this->input('anyhighlightwords', _('Highlight Words'),
                         ($this->arg('anyhighlightwords')) ? $this->arg('anyhighlightwords') : $_COOKIE['anyhighlightwords']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->checkbox('customstyle', _('Choose a custom style for the homepage'),
                            ($this->arg('customstyle')) ?
                            $this->boolean('customstyle') : $_COOKIE['customstyle']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('logo', _('Logo URL'),
                         ($this->arg('logo')) ? $this->arg('logo') : $_COOKIE['logo']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('pagecolor', _('Text Color'),
                         ($this->arg('pagecolor')) ? $this->arg('pagecolor') : $_COOKIE['pagecolor']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('maincolor', _('Body Color'),
                         ($this->arg('maincolor')) ? $this->arg('maincolor') : $_COOKIE['maincolor']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('asidecolor', _('Sidebar Color'),
                         ($this->arg('asidecolor')) ? $this->arg('asidecolor') : $_COOKIE['asidecolor']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('linkcolor', _('Link Color'),
                         ($this->arg('linkcolor')) ? $this->arg('linkcolor') : $_COOKIE['linkcolor']);
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->input('backgroundimage', _('Background Image URL'),
                         ($this->arg('backgroundimage')) ? $this->arg('backgroundimage') : $_COOKIE['backgroundimage']);
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

            /* fixme
            $vars = Rdnrefresh::initDB();

            $orig = clone($vars);

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
            $vars->hideemotes = $this->boolean('hideemotes');

            $vars->update($orig);
             */

            $expiry = time()+60*60*24*365;
            $path = '/';

            setcookie('usernamestags', substr($this->trimmed('usernamestags'),0,255), $expiry, $path);
            setcookie('anyhighlightwords', substr($this->trimmed('anyhighlightwords'),0,255), $expiry, $path);
            setcookie('logo', substr($this->trimmed('logo'),0,255), $expiry, $path);
            setcookie('backgroundimage', substr($this->trimmed('backgroundimage'),0,255), $expiry, $path);
            setcookie('pagecolor', substr($this->trimmed('pagecolor'),0,7), $expiry, $path);
            setcookie('maincolor', substr($this->trimmed('maincolor'),0,7), $expiry, $path);
            setcookie('asidecolor', substr($this->trimmed('asidecolor'),0,7), $expiry, $path);
            setcookie('linkcolor', substr($this->trimmed('linkcolor'),0,255), $expiry, $path);
            setcookie('customstyle', $this->boolean('customstyle'), $expiry, $path);
            setcookie('hideemotes', $this->boolean('hideemotes'), $expiry, $path);
            setcookie('spoilertags', substr($this->trimmed('spoilertags'),0,255), $expiry, $path);

            $this->showForm(_('Settings saved.'), true);

        }
    }

    function showAside() {
        $user = common_current_user();

        $this->elementStart('div', array('id' => 'aside_primary',
                                         'class' => 'aside'));

        $this->elementStart('div', array('id' => 'account_actions',
                                         'class' => 'section'));
        $this->elementStart('ul');
        if (Event::handle('StartRDNRefreshSettingsActions', array($this))) {
            if ($user->hasRight(Right::BACKUPACCOUNT)) {
                $this->elementStart('li');
                $this->element('a',
                               array('href' => common_local_url('backupaccount')),
                               _('Backup account'));
                $this->elementEnd('li');
            }
            if ($user->hasRight(Right::DELETEACCOUNT)) {
                $this->elementStart('li');
                $this->element('a',
                               array('href' => common_local_url('deleteaccount')),
                               _('Delete account'));
                $this->elementEnd('li');
            }
            if ($user->hasRight(Right::RESTOREACCOUNT)) {
                $this->elementStart('li');
                $this->element('a',
                               array('href' => common_local_url('restoreaccount')),
                               _('Restore account'));
                $this->elementEnd('li');
            }
            Event::handle('EndRDNRefreshSettingsActions', array($this));
        }
        $this->elementEnd('ul');
        $this->elementEnd('div');
        $this->elementEnd('div');
    }
}
