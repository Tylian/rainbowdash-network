<?php

if (!defined('STATUSNET')) {
    exit(1);
}

/* Plugin for dropdown-based primary navigation
 *
 * A replacement navbar that uses lists of sub-items to compact the primary nav
 */


class DropdownNavPlugin extends Plugin
{
	
	function onStartPrimaryNav($action)
	{
        $user = common_current_user();
		
        // TRANS: Tooltip for main menu option "Home".
        $tooltip = _m('TOOLTIP', 'Home');
        $action->menuItem(common_local_url('public'),
            _m('MENU', 'Home'), $tooltip, false, 'nav_home');

		// TRANS: Tooltip for main menu option "Rules".
		$tooltip = _m('TOOLTIP', 'Site Rules!');
		$action->menuItem(common_local_url('doc', array('title' => 'rules')),
						_m('MENU', 'Rules'), $tooltip, false, 'nav_rules');
		
		$this->startDropdown($action, 'Links', 'nav_links');

            // TRANS: Tooltip for main menu option "Rules".
            $tooltip = _m('TOOLTIP', 'List of Site Staff');
            $action->menuItem(common_local_url('staff'),
                            _m('MENU', 'Staff'), $tooltip, false, 'nav_admins');
							
			if($user && common_config('invite', 'enabled')) {
                // TRANS: Tooltip for main menu option "Invite".
                $tooltip = _m('TOOLTIP', 'Invite friends and colleagues to join you on %s');
                $action->menuItem(common_local_url('invite'),
                                _m('MENU', 'Invite'),
                                sprintf($tooltip,
                                        common_config('site', 'name')),
                                false, 'nav_invitecontact');
			}
			
		$this->endDropdown($action);
		
		if($user) {
            if ($user->hasRight(Right::CONFIGURESITE)) {
                // TRANS: Tooltip for menu option "Admin".
                $tooltip = _m('TOOLTIP', 'Change site configuration');
                $action->menuItem(common_local_url('siteadminpanel'),
                                // TRANS: Main menu option when logged in and site admin for access to site configuration.
                                _m('MENU', 'Admin'), $tooltip, false, 'nav_admin');
            }
		}
			
        if ($user) {

			$this->startDropdown($action, '@' . $user->nickname, 'nav_userlinks');
                // TRANS: Tooltip for main menu option "Personal".
                $tooltip = _m('TOOLTIP', 'Personal profile and friends timeline');
                $action->menuItem(common_local_url('all', array('nickname' => $user->nickname)),
                                // TRANS: Main menu option when logged in for access to personal profile and friends timeline.
                                _m('MENU', 'Personal'), $tooltip, false, 'nav_personal');

                // TRANS: Tooltip for main menu option "Account".
                $tooltip = _m('TOOLTIP', 'Change your email, avatar, password, profile');
                $action->menuItem(common_local_url('inbox', array('nickname' => $user->nickname)),
                                // TRANS: Main menu option when logged in for access to user settings.
                                _('Inbox'), $tooltip, false, 'nav_dmcounter');

                // TRANS: Tooltip for main menu option "Account".
                $tooltip = _m('TOOLTIP', 'Change your email, avatar, password, profile');
                $action->menuItem(common_local_url('replies', array('nickname' => $user->nickname)),
                                // TRANS: Main menu option when logged in for access to user settings.
                                _('Replies'), $tooltip, false, 'nav_replies');

                // TRANS: Tooltip for main menu option "Account".
                $tooltip = _m('TOOLTIP', 'Change your email, avatar, password, profile');
                $action->menuItem(common_local_url('profilesettings'),
                                // TRANS: Main menu option when logged in for access to user settings.
                                _('Settings'), $tooltip, false, 'nav_account');
								
                // TRANS: Tooltip for main menu option "Services".
               $tooltip = _m('TOOLTIP', 'Connect to services');
               $action->menuItem(common_local_url('oauthconnectionssettings'),
                                // TRANS: Main menu option when logged in and connection are possible for access to options to connect to other services.
                                _('Connect'), $tooltip, false, 'nav_connect');
			$this->endDropdown($action);
            
			// TRANS: Tooltip for main menu option "Logout"
            $tooltip = _m('TOOLTIP', 'Logout from the site');
            $action->menuItem(common_local_url('logout'),
                            // TRANS: Main menu option when logged in to log out the current user.
                            _m('MENU', 'Logout'), $tooltip, false, 'nav_logout');
        }
        else {
            if (!common_config('site', 'closed') && !common_config('site', 'inviteonly')) {
                // TRANS: Tooltip for main menu option "Register".
                $tooltip = _m('TOOLTIP', 'Create an account');
                $action->menuItem(common_local_url('register'),
                                // TRANS: Main menu option when not logged in to register a new account.
                                _m('MENU', 'Register'), $tooltip, false, 'nav_register');
            }
            // TRANS: Tooltip for main menu option "Login".
            $tooltip = _m('TOOLTIP', 'Login to the site');
            $action->menuItem(common_local_url('login'),
                            // TRANS: Main menu option when not logged in to log in.
                            _m('MENU', 'Login'), $tooltip, false, 'nav_login');
        }
		
		return false;
	}
	
	function startDropdown($action, $name=null, $id=null)
	{
		$action->elementStart('li', array ('class' => 'nav_dropdown',
										   'id' => $id));
		$action->element('span', null, $name);
		$action->elementStart('ol');
	}
	
	function endDropdown($action)
	{
		$action->elementEnd('ol');
		$action->elementEnd('li');
	}
	
    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'DropdownNav',
                            'version' => STATUSNET_VERSION,
                            'author' => 'RedEnchilada',
                            'homepage' => 'http://rainbowdash.net/redenchilada',
                            'rawdescription' =>
                            _m('The navbar\'s getting too full. Let\'s compact it a bit.'));
        return true;
    }
}