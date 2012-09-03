<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class EmotesPlugin extends Plugin
{
    function onStartNoticeSave($notice) {

        $dir = dirname(__FILE__);
        
        // Emoticons
        $search = array(
            '@:\\)@', //happy
            '@:\\(@', //sad
            '@;[\\)\\|]@', //wink
            '@:[Pp]@', //tongue
            '@[oO0][_\\-][oO0]@', //eyepop
            '@:D@', //joy
            '@\\&lt;:[D\\|\\(]@', //angry
            '@\\@[_\\-]\\@@', //dizzy bored
            '@-_-@', // sleepy / not amused
            '@\\?:[\\)\\|/\\\\]@', // confused
            '@[8B]\\)@', // glasses
            '@:\'[\\)\\|\\(/\\\\]@', // tears
            '@:\\|@', // flat / not amused
        );

        $emotepath = 'e/';
        $emotefix = '<img class="emote" src="' . $this->path($emotepath);

        $replacements = array(
            $emotefix . 'smile.gif" title=":)" alt=":)" />',
            $emotefix . 'sad.gif" title=":(" alt=":(" />',
            $emotefix . 'wink.gif" title=";)" alt=";)" />',
            $emotefix . 'tongue.gif" title=":P" alt=":P" />',
            $emotefix . 'blink.gif" title="o_O" alt="o_O" />',
            $emotefix . 'biggrin.gif" title=":D" alt=":D" />',
            $emotefix . 'mad.gif" title="&gt;:(" alt="&gt;:(" />',
            $emotefix . 'bored.gif" title="@_@" alt="@_@" />',
            $emotefix . 'sleep.gif" title="-_-" alt="-_-" />',
            $emotefix . 'confused.gif" title="?:/" alt="?:/" />',
            $emotefix . 'cool.gif" title="B)" alt="B)" />',
            $emotefix . 'crying.gif" title=":\'(" alt=":\'(" />',
            $emotefix . 'mellow.gif" title=":|" alt=":|" />',
        );

        $notice->rendered = preg_replace($search, $replacements, $notice->rendered);

        // Custom emoticons
        $emotex = '@:(\\w+?):@';
        preg_match_all($emotex, $notice->content, $matches, PREG_SET_ORDER);
        foreach($matches as $match) {
            $emote = $emotepath . strtolower($match[1]) . ".gif";
            if(file_exists("$dir/$emote")) {
                $notice->rendered = str_replace($match[0], '<img src="' . $this->path($emote) . "\" alt=\"$match[1]\" title=\"$match[1]\" class=\"emote\" />" , $notice->rendered);
            }
        }

        return true;

    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Emotes',
                            'version' => STATUSNET_VERSION,
                            'author' => 'ponydude+minti',
                            'homepage' => 'http://status.net/wiki/Plugin:Sample',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('Add Emoticons to StatusNet'));
        return true;
    }

}
?>
