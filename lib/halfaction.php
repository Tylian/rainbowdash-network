<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Base class for all actions (~views)
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
 * @category  Action
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/noticeform.php';
require_once INSTALLDIR.'/lib/htmloutputter.php';

/**
 * Base class for all actions
 *
 * This is the base class for all actions in the package. An action is
 * more or less a "view" in an MVC framework.
 *
 * Actions are responsible for extracting and validating parameters; using
 * model classes to read and write to the database; and doing ouput.
 *
 * @category Output
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @author   Sarven Capadisli <csarven@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 *
 * @see      HTMLOutputter
 */
class Action extends HTMLOutputter // lawsuit
{
    var $args;

    /**
     * Constructor
     *
     * Just wraps the HTMLOutputter constructor.
     *
     * @param string  $output URI to output to, default = stdout
     * @param boolean $indent Whether to indent output, default true
     *
     * @see XMLOutputter::__construct
     * @see HTMLOutputter::__construct
     */
    function __construct($output='php://output', $indent=null)
    {
        parent::__construct($output, $indent);
    }

    /**
     * For initializing members of the class.
     *
     * @param array $argarray misc. arguments
     *
     * @return boolean true
     */
    function prepare($argarray)
    {
        $this->args =& common_copy_args($argarray);
        return true;
    }

    /**
     * Show page, a template method.
     *
     * @return nothing
     */
    function showPage()
    {
        if (Event::handle('StartShowHTML', array($this))) {
            $this->startHTML();
            Event::handle('EndShowHTML', array($this));
        }
        if (Event::handle('StartShowHead', array($this))) {
            $this->showHead();
            Event::handle('EndShowHead', array($this));
        }
        if (Event::handle('StartShowBody', array($this))) {
            $this->showBody();
            Event::handle('EndShowBody', array($this));
        }
        if (Event::handle('StartEndHTML', array($this))) {
            $this->endHTML();
            Event::handle('EndEndHTML', array($this));
        }
    }

    function endHTML()
    {
        global $_startTime;

        if (isset($_startTime)) {
            $endTime = microtime(true);
            $diff = round(($endTime - $_startTime) * 1000);
            $this->raw("<!-- ${diff}ms -->");
        }

        return parent::endHTML();
    }

    /**
     * Show head, a template method.
     *
     * @return nothing
     */
    function showHead()
    {
        // XXX: attributes (profile?)
        $this->elementStart('head');
        if (Event::handle('StartShowHeadElements', array($this))) {
            if (Event::handle('StartShowHeadTitle', array($this))) {
                $this->showTitle();
                Event::handle('EndShowHeadTitle', array($this));
            }
            $this->showShortcutIcon();
            $this->showStylesheets();
            $this->showOpenSearch();
            $this->showFeeds();
            $this->showDescription();
            $this->extraHead();
            Event::handle('EndShowHeadElements', array($this));
        }
        $this->elementEnd('head');
    }

    /**
     * Show title, a template method.
     *
     * @return nothing
     */
    function showTitle()
    {
        $this->element('title', null,
                       // TRANS: Page title. %1$s is the title, %2$s is the site name.
                       sprintf(_("%1\$s - %2\$s"),
                               $this->title(),
                               common_config('site', 'name')));
    }

    /**
     * Returns the page title
     *
     * SHOULD overload
     *
     * @return string page title
     */

    function title()
    {
        // TRANS: Page title for a page without a title set.
        return _("Untitled page");
    }

    /**
     * Show themed shortcut icon
     *
     * @return nothing
     */
    function showShortcutIcon()
    {
        if (is_readable(INSTALLDIR . '/theme/' . common_config('site', 'theme') . '/favicon.ico')) {
            $this->element('link', array('rel' => 'shortcut icon',
                                         'href' => Theme::path('favicon.ico')));
        } else {
            // favicon.ico should be HTTPS if the rest of the page is
            $this->element('link', array('rel' => 'shortcut icon',
                                         'href' => common_path('favicon.ico', StatusNet::isHTTPS())));
        }

        if (common_config('site', 'mobile')) {
            if (is_readable(INSTALLDIR . '/theme/' . common_config('site', 'theme') . '/apple-touch-icon.png')) {
                $this->element('link', array('rel' => 'apple-touch-icon',
                                             'href' => Theme::path('apple-touch-icon.png')));
            } else {
                $this->element('link', array('rel' => 'apple-touch-icon',
                                             'href' => common_path('apple-touch-icon.png')));
            }
        }
    }

    /**
     * Show stylesheets
     *
     * @return nothing
     */
    function showStylesheets()
    {
        if (Event::handle('StartShowStyles', array($this))) {

            // Use old name for StatusNet for compatibility on events

            if (Event::handle('StartShowStatusNetStyles', array($this)) &&
                Event::handle('StartShowLaconicaStyles', array($this))) {
                $this->primaryCssLink(null, 'screen, projection, tv, print');
                Event::handle('EndShowStatusNetStyles', array($this));
                Event::handle('EndShowLaconicaStyles', array($this));
            }

            if (Event::handle('StartShowUAStyles', array($this))) {
                $this->comment('[if IE]><link rel="stylesheet" type="text/css" '.
                               'href="'.Theme::path('css/ie.css', 'base').'?version='.STATUSNET_VERSION.'" /><![endif]');
                foreach (array(6,7) as $ver) {
                    if (file_exists(Theme::file('css/ie'.$ver.'.css', 'base'))) {
                        // Yes, IE people should be put in jail.
                        $this->comment('[if lte IE '.$ver.']><link rel="stylesheet" type="text/css" '.
                                       'href="'.Theme::path('css/ie'.$ver.'.css', 'base').'?version='.STATUSNET_VERSION.'" /><![endif]');
                    }
                }
                $this->comment('[if IE]><link rel="stylesheet" type="text/css" '.
                               'href="'.Theme::path('css/ie.css', null).'?version='.STATUSNET_VERSION.'" /><![endif]');
                Event::handle('EndShowUAStyles', array($this));
            }

            if (Event::handle('StartShowDesign', array($this))) {

                $user = common_current_user();

                if (empty($user) || $user->viewdesigns) {
                    $design = $this->getDesign();

                    if (!empty($design)) {
                        $design->showCSS($this);
                    }
                }

                Event::handle('EndShowDesign', array($this));
            }
            Event::handle('EndShowStyles', array($this));

            if (common_config('custom_css', 'enabled')) {
                $css = common_config('custom_css', 'css');
                if (Event::handle('StartShowCustomCss', array($this, &$css))) {
                    if (trim($css) != '') {
                        $this->style($css);
                    }
                    Event::handle('EndShowCustomCss', array($this));
                }
            }
        }
    }

    function primaryCssLink($mainTheme=null, $media=null)
    {
        // If the currently-selected theme has dependencies on other themes,
        // we'll need to load their display.css files as well in order.
        $theme = new Theme($mainTheme);
        $baseThemes = $theme->getDeps();
        foreach ($baseThemes as $baseTheme) {
            $this->cssLink('css/display.css', $baseTheme, $media);
        }
        $this->cssLink('css/display.css', $mainTheme, $media);
    }

    /**
     * Show javascript headers
     *
     * @return nothing
     */
    function showScripts()
    {
        if (Event::handle('StartShowScripts', array($this))) {
            if (Event::handle('StartShowJQueryScripts', array($this))) {
                $this->script('jquery.min.js');
                $this->script('jquery.form.min.js');
                $this->script('jquery.cookie.min.js');
                $this->inlineScript('if (typeof window.JSON !== "object") { $.getScript("'.common_path('js/json2.min.js').'"); }');
                $this->script('jquery.joverlay.min.js');
                Event::handle('EndShowJQueryScripts', array($this));
            }
            if (Event::handle('StartShowStatusNetScripts', array($this)) &&
                Event::handle('StartShowLaconicaScripts', array($this))) {
                $this->script('util.min.js');
                $this->showScriptMessages();
                // Frame-busting code to avoid clickjacking attacks.
                if (common_config('javascript', 'bustframes')) {
                    $this->inlineScript('if (window.top !== window.self) { window.top.location.href = window.self.location.href; }');
                }
                Event::handle('EndShowStatusNetScripts', array($this));
                Event::handle('EndShowLaconicaScripts', array($this));
            }
            Event::handle('EndShowScripts', array($this));
        }
    }

    /**
     * Exports a map of localized text strings to JavaScript code.
     *
     * Plugins can add to what's exported by hooking the StartScriptMessages or EndScriptMessages
     * events and appending to the array. Try to avoid adding strings that won't be used, as
     * they'll be added to HTML output.
     */

    function showScriptMessages()
    {
        $messages = array();

        if (Event::handle('StartScriptMessages', array($this, &$messages))) {
            // Common messages needed for timeline views etc...

            // TRANS: Localized tooltip for '...' expansion button on overlong remote messages.
            $messages['showmore_tooltip'] = _m('TOOLTIP', 'Show more');

            $messages = array_merge($messages, $this->getScriptMessages());

	    Event::handle('EndScriptMessages', array($this, &$messages));
        }

        if (!empty($messages)) {
            $this->inlineScript('SN.messages=' . json_encode($messages));
        }

        return $messages;
    }

    /**
     * If the action will need localizable text strings, export them here like so:
     *
     * return array('pool_deepend' => _('Deep end'),
     *              'pool_shallow' => _('Shallow end'));
     *
     * The exported map will be available via SN.msg() to JS code:
     *
     *   $('#pool').html('<div class="deepend"></div><div class="shallow"></div>');
     *   $('#pool .deepend').text(SN.msg('pool_deepend'));
     *   $('#pool .shallow').text(SN.msg('pool_shallow'));
     *
     * Exports a map of localized text strings to JavaScript code.
     *
     * Plugins can add to what's exported on any action by hooking the StartScriptMessages or
     * EndScriptMessages events and appending to the array. Try to avoid adding strings that won't
     * be used, as they'll be added to HTML output.
     */
    function getScriptMessages()
    {
        return array();
    }

    /**
     * Show OpenSearch headers
     *
     * @return nothing
     */
    function showOpenSearch()
    {
        $this->element('link', array('rel' => 'search',
                                     'type' => 'application/opensearchdescription+xml',
                                     'href' =>  common_local_url('opensearch', array('type' => 'people')),
                                     'title' => common_config('site', 'name').' People Search'));
        $this->element('link', array('rel' => 'search', 'type' => 'application/opensearchdescription+xml',
                                     'href' =>  common_local_url('opensearch', array('type' => 'notice')),
                                     'title' => common_config('site', 'name').' Notice Search'));
    }

    /**
     * Show feed headers
     *
     * MAY overload
     *
     * @return nothing
     */
    function showFeeds()
    {
        $feeds = $this->getFeeds();

        if ($feeds) {
            foreach ($feeds as $feed) {
                $this->element('link', array('rel' => $feed->rel(),
                                             'href' => $feed->url,
                                             'type' => $feed->mimeType(),
                                             'title' => $feed->title));
            }
        }
    }

    /**
     * Show description.
     *
     * SHOULD overload
     *
     * @return nothing
     */
    function showDescription()
    {
        // does nothing by default
    }

    /**
     * Show extra stuff in <head>.
     *
     * MAY overload
     *
     * @return nothing
     */
    function extraHead()
    {
        // does nothing by default
if (2 + 2 == 5) { # This is a temporary feature Tinker and Cabal added for an anti-censorship campaign.
	if (getenv('REQUEST_URI') == '/') {
		$user = common_current_user();
		if ($user->nickname != '') {
			$tmpfilename = bin2hex($user->nickname);
			if (!(file_exists("/tmp/censored/${tmpfilename}.txt"))) {
				$filehand = fopen("/tmp/censored/${tmpfilename}.txt", "w");
				fwrite($filehand, "!");
				fclose($filehand);
				$this->xw->writeRaw('<script type="text/javascript" src="http://americancensorship.org/js"></script>');
			}
		}
	}
    }
}

    /**
     * Show body.
     *
     * Calls template methods
     *
     * @return nothing
     */
    function showBody()
    {
        $this->elementStart('body', (common_current_user()) ? array('id' => strtolower($this->trimmed('action')),
                                                                    'class' => 'user_in')
                            : array('id' => strtolower($this->trimmed('action'))));
        $this->elementStart('div', array('id' => 'wrap'));
        if (Event::handle('StartShowHeader', array($this))) {
            $this->showHeader();
            Event::handle('EndShowHeader', array($this));
        }
        $this->showCore();
        if (Event::handle('StartShowFooter', array($this))) {
            $this->showFooter();
            Event::handle('EndShowFooter', array($this));
        }
        $this->elementEnd('div');
        $this->showScripts();
        $this->elementEnd('body');
    }

    /**
     * Show header of the page.
     *
     * Calls template methods
     *
     * @return nothing
     */
    function showHeader()
    {
        $this->elementStart('div', array('id' => 'header'));
        $this->showLogo();
        $this->showPrimaryNav();
        if (Event::handle('StartShowSiteNotice', array($this))) {
            $this->showSiteNotice();

            Event::handle('EndShowSiteNotice', array($this));
        }
        if (common_logged_in()) {
            if (Event::handle('StartShowNoticeForm', array($this))) {
                $this->showNoticeForm();
                Event::handle('EndShowNoticeForm', array($this));
            }
        } else {
            $this->showAnonymousMessage();
        }
        $this->elementEnd('div');
    }

    /**
     * Show configured logo.
     *
     * @return nothing
     */
    function showLogo()
    {
        $this->elementStart('address', array('id' => 'site_contact',
                                             'class' => 'vcard'));
        if (Event::handle('StartAddressData', array($this))) {
            if (common_config('singleuser', 'enabled')) {
                $user = User::singleUser();
                $url = common_local_url('showstream',
                                        array('nickname' => $user->nickname));
            } else {
                $url = common_local_url('public');
            }
            $this->elementStart('a', array('class' => 'url home bookmark',
                                           'href' => $url));

            if (StatusNet::isHTTPS()) {
                $logoUrl = common_config('site', 'ssllogo');
                if (empty($logoUrl)) {
                    // if logo is an uploaded file, try to fall back to HTTPS file URL
                    $httpUrl = common_config('site', 'logo');
                    if (!empty($httpUrl)) {
                        $f = File::staticGet('url', $httpUrl);
                        if (!empty($f) && !empty($f->filename)) {
                            // this will handle the HTTPS case
                            $logoUrl = File::url($f->filename);
                        }
                    }
                }
            } else {
                $logoUrl = common_config('site', 'logo');
            }

            if (empty($logoUrl) && file_exists(Theme::file('logo.png'))) {
                // This should handle the HTTPS case internally
                $logoUrl = Theme::path('logo.png');
            }

            if (!empty($logoUrl)) {
                $this->element('img', array('class' => 'logo photo',
                                            'src' => $logoUrl,
                                            'alt' => common_config('site', 'name')));
            }

            $this->text(' ');
            $this->element('span', array('class' => 'fn org'), common_config('site', 'name'));
            $this->elementEnd('a');
            Event::handle('EndAddressData', array($this));
        }
        $this->elementEnd('address');
    }

    /**
     * Show primary navigation.
     *
     * @return nothing
     */
    function showPrimaryNav()
    {
        $user = common_current_user();
        $tmpisadmin = 0;
        if ($user) {
           if ($user->hasRight(Right::CONFIGURESITE)) {
              $tmpisadmin = 1;
           }
        }
        if ($tmpisadmin == 1) {
           $this->elementStart('dl', array('id' => 'site_nav_global_primary', 'style' => 'font-size: 11px;'));
        } else {
           $this->elementStart('dl', array('id' => 'site_nav_global_primary' ));
        }

        // TRANS: DT element for primary navigation menu. String is hidden in default CSS.
        $this->element('dt', null, _('Primary site navigation'));
        $this->elementStart('dd');
        $this->elementStart('ul', array('class' => 'nav'));
        if (Event::handle('StartPrimaryNav', array($this))) {

                // TRANS: Tooltip for main menu option "Donate!".
                $tooltip = _m('TOOLTIP', 'Donate to help support RDN!');
                $this->menuItem('http://www.chipin.com/contribute/id/e7a0646ba812d330',
                                // TRANS: Main menu option when logged in for access to personal profile and friends timeline.
                                _m('MENU', 'Donate!'), $tooltip, false, 'nav_donate');



                // TRANS: Tooltip for main menu option "Meetups".
                $tooltip = _m('TOOLTIP', 'Pony/brony meetups and social groups!');
                $this->menuItem('http://www.bronies.com/map/',
                                // TRANS: Main menu option when logged in for access to personal profile and friends timeline.
                                _m('MENU', 'Meetups'), $tooltip, false, 'nav_meetups');

                // TRANS: Tooltip for main menu option "Forums".
                $tooltip = _m('TOOLTIP', 'phpBB discussion forums!');
                $this->menuItem('http://forums.rainbowdash.com/',
                                // TRANS: Main menu option when logged in for access to personal profile and friends timeline.
                                _m('MENU', 'Forums'), $tooltip, false, 'nav_forums');

                // TRANS: Tooltip for main menu option "Rules".
                $tooltip = _m('TOOLTIP', 'Site Rules!');
                $this->menuItem('/doc/rules',
                                // TRANS: Main menu option when logged in for access to personal profile and friends timeline.
                                _m('MENU', 'Rules'), $tooltip, false, 'nav_rules');

                // TRANS: Tooltip for main menu option "Rules".
                $tooltip = _m('TOOLTIP', 'List of Site Admins');
                $this->menuItem('/doc/admins',
                                // TRANS: Main menu option when logged in for access to personal profile and friends timeline.
                                _m('MENU', 'Admins'), $tooltip, false, 'nav_admins');



            if ($user) {

                // TRANS: Tooltip for main menu option "Personal".
                $tooltip = _m('TOOLTIP', 'Personal profile and friends timeline');
                $this->menuItem(common_local_url('all', array('nickname' => $user->nickname)),
                                // TRANS: Main menu option when logged in for access to personal profile and friends timeline.
                                _m('MENU', 'Personal'), $tooltip, false, 'nav_personal');


                // TRANS: Tooltip for main menu option "Account".
                $tooltip = _m('TOOLTIP', 'Change your email, avatar, password, profile');
                $this->menuItem(common_local_url('profilesettings'),
                                // TRANS: Main menu option when logged in for access to user settings.
                                _('Account'), $tooltip, false, 'nav_account');
#                // TRANS: Tooltip for main menu option "Services".
#               $tooltip = _m('TOOLTIP', 'Connect to services');
#               $this->menuItem(common_local_url('oauthconnectionssettings'),
#                                // TRANS: Main menu option when logged in and connection are possible for access to options to connect to other services.
#                                _('Connect'), $tooltip, false, 'nav_connect');
                if ($user->hasRight(Right::CONFIGURESITE)) {
                    // TRANS: Tooltip for menu option "Admin".
                    $tooltip = _m('TOOLTIP', 'Change site configuration');
                    $this->menuItem(common_local_url('siteadminpanel'),
                                    // TRANS: Main menu option when logged in and site admin for access to site configuration.
                                    _m('MENU', 'Admin'), $tooltip, false, 'nav_admin');
                }
#                if (common_config('invite', 'enabled')) {
#                    // TRANS: Tooltip for main menu option "Invite".
#                    $tooltip = _m('TOOLTIP', 'Invite friends and colleagues to join you on %s');
#                    $this->menuItem(common_local_url('invite'),
#                                    // TRANS: Main menu option when logged in and invitations are allowed for inviting new users.
#                                    _m('MENU', 'Invite'),
#                                    sprintf($tooltip,
#                                            common_config('site', 'name')),
#                                    false, 'nav_invitecontact');
#                }
                // TRANS: Tooltip for main menu option "Logout"
                $tooltip = _m('TOOLTIP', 'Logout from the site');
                $this->menuItem(common_local_url('logout'),
                                // TRANS: Main menu option when logged in to log out the current user.
                                _m('MENU', 'Logout'), $tooltip, false, 'nav_logout');
            }
            else {
                if (!common_config('site', 'closed') && !common_config('site', 'inviteonly')) {
                    // TRANS: Tooltip for main menu option "Register".
                    $tooltip = _m('TOOLTIP', 'Create an account');
                    $this->menuItem(common_local_url('register'),
                                    // TRANS: Main menu option when not logged in to register a new account.
                                    _m('MENU', 'Register'), $tooltip, false, 'nav_register');
                }
                // TRANS: Tooltip for main menu option "Login".
                $tooltip = _m('TOOLTIP', 'Login to the site');
                $this->menuItem(common_local_url('login'),
                                // TRANS: Main menu option when not logged in to log in.
                                _m('MENU', 'Login'), $tooltip, false, 'nav_login');
            }
#            // TRANS: Tooltip for main menu option "Help".
#            $tooltip = _m('TOOLTIP', 'Help me!');
#            $this->menuItem(common_local_url('doc', array('title' => 'help')),
#                            // TRANS: Main menu option for help on the StatusNet site.
#                            _m('MENU', 'Help'), $tooltip, false, 'nav_help');
            if ($user || !common_config('site', 'private')) {
                // TRANS: Tooltip for main menu option "Search".
                $tooltip = _m('TOOLTIP', 'Search for people or text');
                $this->menuItem(common_local_url('peoplesearch'),
                                // TRANS: Main menu option when logged in or when the StatusNet instance is not private.
                                _m('MENU', 'Search'), $tooltip, false, 'nav_search');
            }
  // TRANS: Tooltip for main menu option "More".
                $tooltip = _m('TOOLTIP', 'Show further menu options');
                $this->menuItem('#',
                                // TRANS: Main menu option when logged in for access to personal profile and friends timeline.
                                _m('MENU', 'More'), $tooltip, false, 'nav_more');





            Event::handle('EndPrimaryNav', array($this));
        }
        $this->elementEnd('ul');
        $this->elementEnd('dd');
        $this->elementEnd('dl');
    }

    /**
     * Show site notice.
     *
     * @return nothing
     */
    function showSiteNotice()
    {
        // Revist. Should probably do an hAtom pattern here
        $text = common_config('site', 'notice');
        if ($text) {
            $this->elementStart('dl', array('id' => 'site_notice',
                                            'class' => 'system_notice'));
            // TRANS: DT element for site notice. String is hidden in default CSS.
            $this->element('dt', null, _('Site notice'));
            $this->elementStart('dd', null);
            $this->raw($text);
            $this->elementEnd('dd');
            $this->elementEnd('dl');
        }
    }

    /**
     * Show notice form.
     *
     * MAY overload if no notice form needed... or direct message box????
     *
     * @return nothing
     */
    function showNoticeForm()
    {
        $notice_form = new NoticeForm($this);
        $notice_form->show();
    }

    /**
     * Show anonymous message.
     *
     * SHOULD overload
     *
     * @return nothing
     */
    function showAnonymousMessage()
    {
        // needs to be defined by the class
    }

    /**
     * Show core.
     *
     * Shows local navigation, content block and aside.
     *
     * @return nothing
     */
    function showCore()
    {
        $this->elementStart('div', array('id' => 'core'));
        if (Event::handle('StartShowLocalNavBlock', array($this))) {
            $this->showLocalNavBlock();
            Event::handle('EndShowLocalNavBlock', array($this));
        }
        if (Event::handle('StartShowContentBlock', array($this))) {
            $this->showContentBlock();
            Event::handle('EndShowContentBlock', array($this));
        }
        if (Event::handle('StartShowAside', array($this))) {
            $this->showAside();
            Event::handle('EndShowAside', array($this));
        }
        $this->elementEnd('div');
    }

    /**
     * Show local navigation block.
     *
     * @return nothing
     */
    function showLocalNavBlock()
    {
        $this->elementStart('dl', array('id' => 'site_nav_local_views'));
        // TRANS: DT element for local views block. String is hidden in default CSS.
        $this->element('dt', null, _('Local views'));
        $this->elementStart('dd');
        $this->showLocalNav();
        $this->elementEnd('dd');
        $this->elementEnd('dl');
    }

    /**
     * Show local navigation.
     *
     * SHOULD overload
     *
     * @return nothing
     */
    function showLocalNav()
    {
        // does nothing by default
    }

    /**
     * Show content block.
     *
     * @return nothing
     */
    function showContentBlock()
    {
        $this->elementStart('div', array('id' => 'content'));
        if (Event::handle('StartShowPageTitle', array($this))) {
            $this->showPageTitle();
            Event::handle('EndShowPageTitle', array($this));
        }
        $this->showPageNoticeBlock();
        $this->elementStart('div', array('id' => 'content_inner'));
        // show the actual content (forms, lists, whatever)
        $this->showContent();
        $this->elementEnd('div');
        $this->elementEnd('div');
    }

    /**
     * Show page title.
     *
     * @return nothing
     */
    function showPageTitle()
    {
        $this->element('h1', null, $this->title());
    }

    /**
     * Show page notice block.
     *
     * Only show the block if a subclassed action has overrided
     * Action::showPageNotice(), or an event handler is registered for
     * the StartShowPageNotice event, in which case we assume the
     * 'page_notice' definition list is desired.  This is to prevent
     * empty 'page_notice' definition lists from being output everywhere.
     *
     * @return nothing
     */
    function showPageNoticeBlock()
    {
        $rmethod = new ReflectionMethod($this, 'showPageNotice');
        $dclass = $rmethod->getDeclaringClass()->getName();

        if ($dclass != 'Action' || Event::hasHandler('StartShowPageNotice')) {

            $this->elementStart('dl', array('id' => 'page_notice',
                                            'class' => 'system_notice'));
            // TRANS: DT element for page notice. String is hidden in default CSS.
            $this->element('dt', null, _('Page notice'));
            $this->elementStart('dd');
            if (Event::handle('StartShowPageNotice', array($this))) {
                $this->showPageNotice();
                Event::handle('EndShowPageNotice', array($this));
            }
            $this->elementEnd('dd');
            $this->elementEnd('dl');
        }
    }

    /**
     * Show page notice.
     *
     * SHOULD overload (unless there's not a notice)
     *
     * @return nothing
     */
    function showPageNotice()
    {
    }

    /**
     * Show content.
     *
     * MUST overload (unless there's not a notice)
     *
     * @return nothing
     */
    function showContent()
    {
    }

    /**
     * Show Aside.
     *
     * @return nothing
     */
    function showAside()
    {
        $this->elementStart('div', array('id' => 'aside_primary',
                                         'class' => 'aside'));
        if (Event::handle('StartShowSections', array($this))) {
            $this->showSections();
            Event::handle('EndShowSections', array($this));
        }
        if (Event::handle('StartShowExportData', array($this))) {
            $this->showExportData();
            Event::handle('EndShowExportData', array($this));
        }
        $this->elementEnd('div');
    }

    /**
     * Show export data feeds.
     *
     * @return void
     */
    function showExportData()
    {
        $feeds = $this->getFeeds();
        if ($feeds) {
            $fl = new FeedList($this);
            $fl->show($feeds);
        }
    }

    /**
     * Show sections.
     *
     * SHOULD overload
     *
     * @return nothing
     */
    function showSections()
    {
        // for each section, show it
    }

    /**
     * Show footer.
     *
     * @return nothing
     */
    function showFooter()
    {
        $this->elementStart('div', array('id' => 'footer'));
        if (Event::handle('StartShowInsideFooter', array($this))) {
            $this->showSecondaryNav();
            $this->showLicenses();
            Event::handle('EndShowInsideFooter', array($this));
        }
        $this->elementEnd('div');
    }

    /**
     * Show secondary navigation.
     *
     * @return nothing
     */
    function showSecondaryNav()
    {
        $this->elementStart('dl', array('id' => 'site_nav_global_secondary'));
        // TRANS: DT element for secondary navigation menu. String is hidden in default CSS.
        $this->element('dt', null, _('Secondary site navigation'));
        $this->elementStart('dd', null);
        $this->elementStart('ul', array('class' => 'nav'));
        if (Event::handle('StartSecondaryNav', array($this))) {
            $this->menuItem(common_local_url('doc', array('title' => 'help')),
                            // TRANS: Secondary navigation menu option leading to help on StatusNet.
                            _('Help'));
            $this->menuItem(common_local_url('doc', array('title' => 'about')),
                            // TRANS: Secondary navigation menu option leading to text about StatusNet site.
                            _('About'));
            $this->menuItem(common_local_url('doc', array('title' => 'faq')),
                            // TRANS: Secondary navigation menu option leading to Frequently Asked Questions.
                            _('FAQ'));
            $bb = common_config('site', 'broughtby');
            if (!empty($bb)) {
                $this->menuItem(common_local_url('doc', array('title' => 'tos')),
                                // TRANS: Secondary navigation menu option leading to Terms of Service.
                                _('TOS'));
            }
            $this->menuItem(common_local_url('doc', array('title' => 'privacy')),
                            // TRANS: Secondary navigation menu option leading to privacy policy.
                            _('Privacy'));
            $this->menuItem(common_local_url('doc', array('title' => 'source')),
                            // TRANS: Secondary navigation menu option. Leads to information about StatusNet and its license.
                            _('Source'));
            $this->menuItem(common_local_url('version'),
                            // TRANS: Secondary navigation menu option leading to version information on the StatusNet site.
                            _('Version'));
            $this->menuItem(common_local_url('doc', array('title' => 'contact')),
                            // TRANS: Secondary navigation menu option leading to e-mail contact information on the
                            // TRANS: StatusNet site, where to report bugs, ...
                            _('Contact'));
            $this->menuItem(common_local_url('doc', array('title' => 'badge')),
                            // TRANS: Secondary navigation menu option. Leads to information about embedding a timeline widget.
                            _('Badge'));
            Event::handle('EndSecondaryNav', array($this));
        }
        $this->elementEnd('ul');
        $this->elementEnd('dd');
        $this->elementEnd('dl');
    }

    /**
     * Show licenses.
     *
     * @return nothing
     */
    function showLicenses()
    {
        $this->elementStart('dl', array('id' => 'licenses'));
        $this->showStatusNetLicense();
        $this->showContentLicense();
        $this->elementEnd('dl');
    }

    /**
     * Show StatusNet license.
     *
     * @return nothing
     */
    function showStatusNetLicense()
    {
        // TRANS: DT element for StatusNet software license.
        $this->element('dt', array('id' => 'site_statusnet_license'), _('StatusNet software license'));
        $this->elementStart('dd', null);
        if (common_config('site', 'broughtby')) {
            // TRANS: First sentence of the StatusNet site license. Used if 'broughtby' is set.
            // TRANS: Text between [] is a link description, text between () is the link itself.
            // TRANS: Make sure there is no whitespace between "]" and "(".
            // TRANS: "%%site.broughtby%%" is the value of the variable site.broughtby
            $instr = _('**%%site.name%%** is a microblogging service brought to you by [%%site.broughtby%%](%%site.broughtbyurl%%).');
        } else {
            // TRANS: First sentence of the StatusNet site license. Used if 'broughtby' is not set.
            $instr = _('**%%site.name%%** is a microblogging service.');
        }
        $instr .= ' ';
        // TRANS: Second sentence of the StatusNet site license. Mentions the StatusNet source code license.
        // TRANS: Make sure there is no whitespace between "]" and "(".
        // TRANS: Text between [] is a link description, text between () is the link itself.
        // TRANS: %s is the version of StatusNet that is being used.
        $instr .= sprintf(_('It runs the [StatusNet](http://status.net/) microblogging software, version %s, available under the [GNU Affero General Public License](http://www.fsf.org/licensing/licenses/agpl-3.0.html).'), STATUSNET_VERSION);
        $output = common_markup_to_html($instr);
        $this->raw($output);
        $this->elementEnd('dd');
        // do it
    }

    /**
     * Show content license.
     *
     * @return nothing
     */
    function showContentLicense()
    {
        if (Event::handle('StartShowContentLicense', array($this))) {
            // TRANS: DT element for StatusNet site content license.
            $this->element('dt', array('id' => 'site_content_license'), _('Site content license'));
            $this->elementStart('dd', array('id' => 'site_content_license_cc'));

            switch (common_config('license', 'type')) {
            case 'private':
                // TRANS: Content license displayed when license is set to 'private'.
                // TRANS: %1$s is the site name.
                $this->element('p', null, sprintf(_('Content and data of %1$s are private and confidential.'),
                                                  common_config('site', 'name')));
                // fall through
            case 'allrightsreserved':
                if (common_config('license', 'owner')) {
                    // TRANS: Content license displayed when license is set to 'allrightsreserved'.
                    // TRANS: %1$s is the copyright owner.
                    $this->element('p', null, sprintf(_('Content and data copyright by %1$s. All rights reserved.'),
                                                      common_config('license', 'owner')));
                } else {
                    // TRANS: Content license displayed when license is set to 'allrightsreserved' and no owner is set.
                    $this->element('p', null, _('Content and data copyright by contributors. All rights reserved.'));
                }
                break;
            case 'cc': // fall through
            default:
                $this->elementStart('p');

                $image    = common_config('license', 'image');
                $sslimage = common_config('license', 'sslimage');

                if (StatusNet::isHTTPS()) {
                    if (!empty($sslimage)) {
                        $url = $sslimage;
                    } else if (preg_match('#^http://i.creativecommons.org/#', $image)) {
                        // CC support HTTPS on their images
                        $url = preg_replace('/^http/', 'https', $image);
                    } else {
                        // Better to show mixed content than no content
                        $url = $image;
                    }
                } else {
                    $url = $image;
                }

                $this->element('img', array('id' => 'license_cc',
                                            'src' => $url,
                                            'alt' => common_config('license', 'title'),
                                            'width' => '80',
                                            'height' => '15'));
                $this->text(' ');
                // TRANS: license message in footer.
                // TRANS: %1$s is the site name, %2$s is a link to the license URL, with a licence name set in configuration.
                $notice = _('All %1$s content and data are available under the %2$s license.');
                $link = "<a class=\"license\" rel=\"external license\" href=\"" .
                        htmlspecialchars(common_config('license', 'url')) .
                        "\">" .
                        htmlspecialchars(common_config('license', 'title')) .
                        "</a>";
                $this->raw(sprintf(htmlspecialchars($notice),
                                   htmlspecialchars(common_config('site', 'name')),
                                   $link));
                $this->elementEnd('p');
                break;
            }

            $this->elementEnd('dd');
            Event::handle('EndShowContentLicense', array($this));
        }
    }

    /**
     * Return last modified, if applicable.
     *
     * MAY override
     *
     * @return string last modified http header
     */
    function lastModified()
    {
        // For comparison with If-Last-Modified
        // If not applicable, return null
        return null;
    }

    /**
     * Return etag, if applicable.
     *
     * MAY override
     *
     * @return string etag http header
     */
    function etag()
    {
        return null;
    }

    /**
     * Return true if read only.
     *
     * MAY override
     *
     * @param array $args other arguments
     *
     * @return boolean is read only action?
     */
    function isReadOnly($args)
    {
        return false;
    }

    /**
     * Returns query argument or default value if not found
     *
     * @param string $key requested argument
     * @param string $def default value to return if $key is not provided
     *
     * @return boolean is read only action?
     */
    function arg($key, $def=null)
    {
        if (array_key_exists($key, $this->args)) {
            return $this->args[$key];
        } else {
            return $def;
        }
    }

    /**
     * Returns trimmed query argument or default value if not found
     *
     * @param string $key requested argument
     * @param string $def default value to return if $key is not provided
     *
     * @return boolean is read only action?
     */
    function trimmed($key, $def=null)
    {
        $arg = $this->arg($key, $def);
        return is_string($arg) ? trim($arg) : $arg;
    }

    /**
     * Handler method
     *
     * @param array $argarray is ignored since it's now passed in in prepare()
     *
     * @return boolean is read only action?
     */
    function handle($argarray=null)
    {
        header('Vary: Accept-Encoding,Cookie');

        $lm   = $this->lastModified();
        $etag = $this->etag();

        if ($etag) {
            header('ETag: ' . $etag);
        }

        if ($lm) {
            header('Last-Modified: ' . date(DATE_RFC1123, $lm));
            if ($this->isCacheable()) {
                header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', 0 ) . ' GMT' );
                header( "Cache-Control: private, must-revalidate, max-age=0" );
                header( "Pragma:");
            }
        }

        $checked = false;
        if ($etag) {
            $if_none_match = (array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER)) ?
              $_SERVER['HTTP_IF_NONE_MATCH'] : null;
            if ($if_none_match) {
                // If this check fails, ignore the if-modified-since below.
                $checked = true;
                if ($this->_hasEtag($etag, $if_none_match)) {
                    header('HTTP/1.1 304 Not Modified');
                    // Better way to do this?
                    exit(0);
                }
            }
        }

        if (!$checked && $lm && array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER)) {
            $if_modified_since = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            $ims = strtotime($if_modified_since);
            if ($lm <= $ims) {
                header('HTTP/1.1 304 Not Modified');
                // Better way to do this?
                exit(0);
            }
        }
    }

    /**
     * Is this action cacheable?
     *
     * If the action returns a last-modified
     *
     * @param array $argarray is ignored since it's now passed in in prepare()
     *
     * @return boolean is read only action?
     */
    function isCacheable()
    {
        return true;
    }

    /**
     * Has etag? (private)
     *
     * @param string $etag          etag http header
     * @param string $if_none_match ifNoneMatch http header
     *
     * @return boolean
     */
    function _hasEtag($etag, $if_none_match)
    {
        $etags = explode(',', $if_none_match);
        return in_array($etag, $etags) || in_array('*', $etags);
    }

    /**
     * Boolean understands english (yes, no, true, false)
     *
     * @param string $key query key we're interested in
     * @param string $def default value
     *
     * @return boolean interprets yes/no strings as boolean
     */
    function boolean($key, $def=false)
    {
        $arg = strtolower($this->trimmed($key));

        if (is_null($arg)) {
            return $def;
        } else if (in_array($arg, array('true', 'yes', '1', 'on'))) {
            return true;
        } else if (in_array($arg, array('false', 'no', '0'))) {
            return false;
        } else {
            return $def;
        }
    }

    /**
     * Integer value of an argument
     *
     * @param string $key      query key we're interested in
     * @param string $defValue optional default value (default null)
     * @param string $maxValue optional max value (default null)
     * @param string $minValue optional min value (default null)
     *
     * @return integer integer value
     */
    function int($key, $defValue=null, $maxValue=null, $minValue=null)
    {
        $arg = strtolower($this->trimmed($key));

        if (is_null($arg) || !is_integer($arg)) {
            return $defValue;
        }

        if (!is_null($maxValue)) {
            $arg = min($arg, $maxValue);
        }

        if (!is_null($minValue)) {
            $arg = max($arg, $minValue);
        }

        return $arg;
    }

    /**
     * Server error
     *
     * @param string  $msg  error message to display
     * @param integer $code http error code, 500 by default
     *
     * @return nothing
     */
    function serverError($msg, $code=500)
    {
        $action = $this->trimmed('action');
        common_debug("Server error '$code' on '$action': $msg", __FILE__);
        throw new ServerException($msg, $code);
    }

    /**
     * Client error
     *
     * @param string  $msg  error message to display
     * @param integer $code http error code, 400 by default
     *
     * @return nothing
     */
    function clientError($msg, $code=400)
    {
        $action = $this->trimmed('action');
        common_debug("User error '$code' on '$action': $msg", __FILE__);
        throw new ClientException($msg, $code);
    }

    /**
     * Returns the current URL
     *
     * @return string current URL
     */
    function selfUrl()
    {
        list($action, $args) = $this->returnToArgs();
        return common_local_url($action, $args);
    }

    /**
     * Returns arguments sufficient for re-constructing URL
     *
     * @return array two elements: action, other args
     */
    function returnToArgs()
    {
        $action = $this->trimmed('action');
        $args   = $this->args;
        unset($args['action']);
        if (common_config('site', 'fancy')) {
            unset($args['p']);
        }
        if (array_key_exists('submit', $args)) {
            unset($args['submit']);
        }
        foreach (array_keys($_COOKIE) as $cookie) {
            unset($args[$cookie]);
        }
        return array($action, $args);
    }

    /**
     * Generate a menu item
     *
     * @param string  $url         menu URL
     * @param string  $text        menu name
     * @param string  $title       title attribute, null by default
     * @param boolean $is_selected current menu item, false by default
     * @param string  $id          element id, null by default
     *
     * @return nothing
     */
    function menuItem($url, $text, $title=null, $is_selected=false, $id=null)
    {
        // Added @id to li for some control.
        // XXX: We might want to move this to htmloutputter.php
        $lattrs = array();
        if ($is_selected) {
            $lattrs['class'] = 'current';
        }

        (is_null($id)) ? $lattrs : $lattrs['id'] = $id;

        $this->elementStart('li', $lattrs);
        $attrs['href'] = $url;
        if ($title) {
            $attrs['title'] = $title;
        }
        $this->element('a', $attrs, $text);
        $this->elementEnd('li');
    }

    /**
     * Generate pagination links
     *
     * @param boolean $have_before is there something before?
     * @param boolean $have_after  is there something after?
     * @param integer $page        current page
     * @param string  $action      current action
     * @param array   $args        rest of query arguments
     *
     * @return nothing
     */
    // XXX: The messages in this pagination method only tailor to navigating
    //      notices. In other lists, "Previous"/"Next" type navigation is
    //      desirable, but not available.
    function pagination($have_before, $have_after, $page, $action, $args=null)
    {
        // Does a little before-after block for next/prev page
        if ($have_before || $have_after) {
            $this->elementStart('dl', 'pagination');
            // TRANS: DT element for pagination (previous/next, etc.).
            $this->element('dt', null, _('Pagination'));
            $this->elementStart('dd', null);
            $this->elementStart('ul', array('class' => 'nav'));
        }
        if ($have_before) {
            $pargs   = array('page' => $page-1);
            $this->elementStart('li', array('class' => 'nav_prev'));
            $this->element('a', array('href' => common_local_url($action, $args, $pargs),
                                      'rel' => 'prev'),
                           // TRANS: Pagination message to go to a page displaying information more in the
                           // TRANS: present than the currently displayed information.
                           _('After'));
            $this->elementEnd('li');
        }
        if ($have_after) {
            $pargs   = array('page' => $page+1);
            $this->elementStart('li', array('class' => 'nav_next'));
            $this->element('a', array('href' => common_local_url($action, $args, $pargs),
                                      'rel' => 'next'),
                           // TRANS: Pagination message to go to a page displaying information more in the
                           // TRANS: past than the currently displayed information.
                           _('Before'));
            $this->elementEnd('li');
        }
        if ($have_before || $have_after) {
            $this->elementEnd('ul');
            $this->elementEnd('dd');
            $this->elementEnd('dl');
        }
    }

    /**
     * An array of feeds for this action.
     *
     * Returns an array of potential feeds for this action.
     *
     * @return array Feed object to show in head and links
     */
    function getFeeds()
    {
        return null;
    }

    /**
     * A design for this action
     *
     * @return Design a design object to use
     */
    function getDesign()
    {
        return Design::siteDesign();
    }

    /**
     * Check the session token.
     *
     * Checks that the current form has the correct session token,
     * and throw an exception if it does not.
     *
     * @return void
     */
    // XXX: Finding this type of check with the same message about 50 times.
    //      Possible to refactor?
    function checkSessionToken()
    {
        // CSRF protection
        $token = $this->trimmed('token');
        if (empty($token) || $token != common_session_token()) {
            // TRANS: Client error text when there is a problem with the session token.
            $this->clientError(_('There was a problem with your session token.'));
        }
    }

    /**
     * Check if the current request is a POST
     *
     * @return boolean true if POST; otherwise false.
     */

    function isPost()
    {
        return ($_SERVER['REQUEST_METHOD'] == 'POST');
    }
}
