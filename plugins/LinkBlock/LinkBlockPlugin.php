<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class LinkBlockPlugin extends Plugin
{
    function onStartNoticeSave($notice)
    {
        if($notice->getProfile()->noticeCount() == 0) {
            common_replace_urls_callback($notice->content, array($this, 'rejectLink'));
        }
    }

    function onStartProfilePageProfileElements($action, $profile) {
        if($profile->noticeCount() == 0 && !empty($profile->homepage)) {
            $profile->homepage = _('BLOCKED. POST SOMETHING FIRST.');
        }
    }

    function rejectLink($url) {
        throw new ClientException(_("You cannot put a link in your first post."));
    }

}
?>
