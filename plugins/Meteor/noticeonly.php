<?php

if (!defined('STATUSNET')) {
    exit(1);
}

class NoticeonlyAction extends Action
{
    function title()
    {
        return '';
    }

    function showHeader()
    {
    }

    function prepare()
    {
        if($this->trimmed('notice')) {
            $this->notice = Notice::getStreamByIds(array($this->trimmed('notice')));
        }
    }

    function showContent() {
        $nl = new NoticeList($this->notice, $this);
        $nl->show();
    }
}
