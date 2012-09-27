<?php
// Test.

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class RDNPlusPlugin extends Plugin
{

    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'RdnrefreshsettingsAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'Rdnrefresh':
            include_once $dir . '/'.$cls.'.php';
            return false;
        default:
            return true;
        }

    }    

    function onRouterInitialized($m)
    {
        $m->connect('settings/rdnrefresh',
            array('action' => 'rdnrefreshsettings'));
        return true;
    }
// Different comment    
    function onEndAccountSettingsNav($action) {
        $action->menuItem(common_local_url('rdnrefreshsettings'),
            // TRANS: Menu item in settings navigation panel.
            _m('MENU','RDN Plus'),
            // TRANS: Menu item title in settings navigation panel.
            _('Change your RDN Plus settings'),
            $actionName == 'rdnrefreshsettings');
    }

    function onCheckSchema() {
        $schema = Schema::get();

        $schema->ensureTable('rdnrefresh',
            array(new ColumnDef('user_id', 'integer', null,
            true, 'PRI'),
            new ColumnDef('spoilertags', 'varchar', 255, true),
            new ColumnDef('usernamestags', 'varchar', 255, true),
            new ColumnDef('anyhighlightwords', 'varchar', 255, true),
            new ColumnDef('maincolor', 'char', 7, true),
            new ColumnDef('asidecolor', 'char', 7, true),
            new ColumnDef('pagecolor', 'char', 7, true),
            new ColumnDef('linkcolor', 'char', 7, true),
            new ColumnDef('customstyle', 'integer', 1, true),
            new ColumnDef('logo', 'varchar', 255, true),
            new ColumnDef('backgroundimage', 'varchar', 255, true),
            new ColumnDef('hideemotes', 'integer', 1, true),
        ));

        return true;
    }
    
    function onEndShowStyles($action)
    {
        if(!isset($this->vars)) {
            $this->vars = Rdnrefresh::getValues();
        }

        $vars = $this->vars;

        if($vars['customstyle']) {

            $backgroundimage = '';
            $vars['backgroundimage'] = $vars['backgroundimage'] ? "body { background-image: url(" . $vars['backgroundimage'] . "); }" : '';

            $vars['maincolor'] = $this->hex2rgb($vars['maincolor']);
            $vars['asidecolor'] = $this->hex2rgb($vars['asidecolor']);

            $refreshstyle = <<<HERE
#content, #content_wrapper { color: $vars[pagecolor]; background-color: rgba($vars[maincolor],0.9);}
$vars[backgroundimage]
#form_notice { color: $vars[pagecolor]; }
body a { color: $vars[linkcolor]; }
#aside_primary_wrapper, #site_nav_local_views_wrapper, #aside_primary, #site_nav_local_views { color: $vars[pagecolor]; background-color: rgba($vars[asidecolor], 0.9); }
#wrap { border: 0; background-color: transparent; background-image: none;}
HERE;
            $action->style($refreshstyle);
        }

        $action->cssLink($this->path('css/rdnrefresh.css'), null, 'screen, tv, projection, handheld');

        // Kill RDN Refresh
        $action->inlineScript('RDNDIE = true; ');

        return true;
    }

    function onEndShowScripts($action)
    {
        if(!isset($this->vars)) {
            $this->vars = Rdnrefresh::getValues();
        }

        $vars = $this->vars;

        $user = common_current_user();
        $nick = $user->nickname;
        $localurl = explode('?', common_local_url('public'));
        $localurl = $localurl[0];

        $refreshscript = <<<HERE
var selectedText = ''; var currentUser = "$nick".toLowerCase(); var SPOILERTAGS = "$vars[spoilertags]"; var USERNAMESTAGS = "$vars[usernamestags]"; var ANYHIGHLIGHTWORDS = "$vars[anyhighlightwords]"; var siteDir = "$localurl"; var customstyle = '$vars[customstyle]'; var logo = '$vars[logo]'; var hideemotes = '$vars[hideemotes]';
HERE;
        $action->inlineScript($refreshscript);
        $action->script($this->path('js/rdnrefresh.js'));

        return true;
    }

    function onEndShowNoticeFormData($action) {
        $action->out->raw('<ul class="bui bbTools">' .
            '<li style="width: 80px;" class="text_rot13"><r>Spoiler</r></li>' .
            '<li class="text_bold"><b>B</b></li>' .
            '<li class="text_underline"><u>U</u></li>' .
            '<li class="text_italic"><i>i</i></li>' .
            '<li class="text_strike"><s>S</s></li>' .
            '<li class="text_small"><t class="smallt">t</t></li>' .
            '</ul>');

        return true;
    }

    function onStartNoticeSave($notice) {

        $dir = dirname(__FILE__);

        // HTML code
        $bbcode = array(
            '@(\[b\])(.*?)(\[/b\])@i',
            '@(\[u\])(.*?)(\[/u\])@i',
            '@(\[i\])(.*?)(\[/i\])@i',
            '@(\[s\])(.*?)(\[/s\])@i',
            '@(\[t\])(.*?)(\[/t\])@i',
        );

        $markdown = array(
            '@(\s|^)\*([a-z].*?[a-z])\*(\s|$)@i',
            '@(\s|^)_([a-z].*?[a-z])_(\s|$)@i',
            '@(\s|^)/([a-z].*?[a-z])/(\s|$)@i',
            '@(\s|^)-([a-z].*?[a-z])-(\s|$)@i',
            '@(\s|^)=([a-z].*?[a-z])=(\s|$)@i',
        );

        $plaintext = array(
            ' *$2* ',
            ' _$2_ ',
            ' /$2/ ',
            ' -$2- ',
            ' =$2= '
        );

        $markup = array(
            ' <b>$2</b> ',
            ' <u>$2</u> ',
            ' <i>$2</i> ',
            ' <span class="striket">$2</span> ',
            ' <span class="smallt">$2</span> ',
        );

        $notice->content = preg_replace($bbcode, $plaintext, $notice->content);
        $notice->content = preg_replace($markdown, $plaintext, $notice->content);

        $notice->rendered = preg_replace($bbcode, $markup, $notice->rendered);
        $notice->rendered = preg_replace($markdown, $markup, $notice->rendered);

        //ROT13 - WARNING. Strips previously incorporated HTML.
        $rotex = '@\[(r|sp)\](.*?)\[/(r|sp)\]@i';
        preg_match_all($rotex, $notice->content, $matches, PREG_SET_ORDER);
        foreach($matches as $match) {
            if(strtolower($match[1]) == 'r') {
                $replacematch = strtr($match[2], 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 'nopqrstuvwxyzabcdefghijklmNOPQRSTUVWXYZABCDEFGHIJKLM');
            }
            else {
                $replacematch = $match[2];
            }
            $notice->content = str_replace($match[0], "[sp]$replacematch\[/sp]", $notice->content);
            $rreplacematch = str_replace(array('&','<','>'), array('&amp;','&lt;','&gt;'), $replacematch);
            $notice->rendered = preg_replace($rotex, '<span class="rotd">' . $rreplacematch . '</span>', $notice->rendered, 1);
        }

        if(trim(str_replace(array('*','_','/','-','='), '', $notice->content))) return true;
        else throw new ClientException('Notice cannot be blank');
    }


    function onStartShowFaveForm($action)
    {
        $action->out->element('img', array('src' => $this->path('img/rot13_button.png'),
            'title' => _m('Encrypt/Decrypt Spoiler'),
            'class' => 'rot13',
        ));
        /*
        $action->out->element('img', array('src' => $this->path('img/bird_16_blue.png'),
            'title' => _m('Retweet to Twitter'),
            'class' => 'retweet',
        ));
         */

        return true;
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'RDN Plus',
                            'version' => STATUSNET_VERSION,
                            'author' => 'ponydude+minti',
                            'homepage' => 'http://status.net/wiki/Plugin:Sample',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('RDN Refresh enhancements for all.'));
        return true;
    }

    function hex2rgb($color){
        $color = str_replace('#','',$color);
        $rgb[] = hexdec(substr($color,0,2));
        $rgb[] = hexdec(substr($color,2,2));
        $rgb[] = hexdec(substr($color,4,2));
        $color = join(', ', $rgb);
        return $color;
    }
}
?>
