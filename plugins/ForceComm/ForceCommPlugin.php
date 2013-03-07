<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class ForceCommPlugin extends Plugin
{

    function onStartNoticeSave($notice) {
        $n = Notice::publicStream(0, NOTICES_PER_PAGE);

        // Break if no messages on public
        if(empty($n)) {
            return true;
        }

        $newuser = false;
        while($n->fetch()) {
            $p = $n->getProfile();
            if(empty($p)) {
                continue;
            }

            $p->getNotices();
            if($p->N < NOTICES_PER_PAGE) {
                $newuser = $p;
                break;
            }
        }

        $profile = $notice->getProfile();
        // If no new user was found or the new user is the same person making the post, quit.
        if(!$newuser || $profile->id == $newuser->id) {
            return true;
        }

        // If a new user was found, check to see if this post is a response to them.
        foreach(common_find_mentions($notice->content, $notice) as $mention) {
            if($mention['mentioned'][0]->id == $newuser->id) {
                return true;
            }
        }

        // Check to see if new user has already been responded to by the user making the post.
        $reply = $newuser->getUser()->getReplies(0, NOTICES_PER_PAGE * 10);
        while($reply->fetch()) {
            if($reply->getProfile()->id == $profile->id) {
                return true;
            }
        }

        throw new ClientException(sprintf("@%s is new. You should try talking to them.", $newuser->nickname));
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Force Comm',
                            'version' => STATUSNET_VERSION,
                            'author' => 'widget',
                            'homepage' => 'http://status.net/wiki/Plugin:Sample',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('Force people to make at least one post to new members.'));
        return true;
    }
}
?>
