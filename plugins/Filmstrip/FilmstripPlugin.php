<?php
// Test.

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class FilmstripPlugin extends Plugin
{
    function onEndShowStyles($action) {
        $path = $this->path('');
        $action->style(<<<HERE
.filmstrip {
clear: both;
width: 100%;
height:59px;
overflow:hidden;
border-top-left-radius:5px;
border-bottom-left-radius:5px;
padding:8px;
margin-bottom:10px;
}

.filmstrip span.vcard {
position:absolute;
right:0;
bottom:0;
margin:0;
}

#core .filmstrip span.vcard .photo {
margin:0;
margin-right:5px;
margin-bottom:3px;
padding:0;
}

.filmstrip .inline-attachment {
display:block;
float:left;
position:relative;
}
HERE
        );
    }

    function outStrip($action, $attachment, $hideavatar=false) {

        if(!empty($attachment)) {
            $action->elementStart('div', array('class' => 'filmstrip thumbnails'));
            //$totalwidth = 0;
            while($attachment->fetch()) {

                if($attachment->noticeCount()) {
                    $at = new AttachmentListItem($attachment, $action);

                    $notice = $attachment->stream();
                    $notice->fetch();

                    $thumb = $at->getThumbInfo();
                    if ($thumb) {
                        //$totalwidth += $thumb->width / $thumb->height * 63 + 6;
                        //if ($totalwidth > 768) break;

                        $action->elementStart('span', array('class' => 'inline-attachment'));
                        $action->elementStart('a', $at->linkAttr());

                        $action->element('img', array('alt' => '', 'src' => $thumb->url, 'width' => (int) ($thumb->width / $thumb->height * 63), 'height' => '63'));

                        $action->elementEnd('a');

                        if(!$hideavatar) $this->showProfile($notice, $action);
                        $action->elementEnd('span');
                    }
                }
            }
            $action->elementEnd('div');

            if(!empty($notice)) {
                $notice->free();
                unset($notice);
            }
        }
    }

    function onShowStreamNoticeList($notice, $action, $pnl) {
        $notices = $action->user->getNotices(($action->page-1)*NOTICES_PER_PAGE, NOTICES_PER_PAGE + 1, null, null, true);

        $attachments = array();
        while($notices->fetch()) {
            if($notices->hasAttachments()) {
                $attachments = array_merge($attachments, $notices->attachments());
            }
        }

        $attachments = new ArrayWrapper($attachments);

        $this->outStrip($action, $attachments, false);

        return true;
    }

    function onEndShowAside($action) {
        if(get_class($action) == 'PublicAction') {
            $qry = "SELECT * FROM file WHERE modified > DATE_SUB(NOW(), INTERVAL 2 WEEK) ORDER BY RAND() LIMIT 20";

            $attachment = Memcached_DataObject::cachedQuery('File',
                $qry,
                30);

            $this->outStrip($action, $attachment);
        }

        return true;
    }

    function showProfile($notice, $action)
    {
        $profile = $notice->getProfile();

        $action->elementStart('span', 'vcard');
        $action->elementStart('a', array('title' => ($profile->fullname) ?
                                       $profile->fullname :
                                       $profile->nickname,
                                       'href' => $notice->bestUrl(),
                                       'rel' => 'contact member',
                                       'class' => 'url'));
        $action->text(' ');
        $avatar = $profile->getAvatar(AVATAR_MINI_SIZE);
        $action->element('img', array('src' => (($avatar) ? $avatar->displayUrl() :  Avatar::defaultImage(AVATAR_MINI_SIZE)),
                                    'width' => AVATAR_MINI_SIZE,
                                    'height' => AVATAR_MINI_SIZE,
                                    'class' => 'avatar photo',
                                    'alt' =>  ($profile->fullname) ?
                                    $profile->fullname :
                                    $profile->nickname));
        $action->elementEnd('a');
        $action->elementEnd('span');

    }

}
?>
