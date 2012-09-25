<?php

if (!defined('STATUSNET')) {
    exit(1);
}

include_once INSTALLDIR . '/lib/noticelist.php';

class NoticeonlyAction extends Action
{
    function title()
    {
        return '';
    }

    function prepare($args)
    {
        parent::prepare($args);

        if($this->trimmed('notice')) {
            $this->notice = Notice::staticGet('id', $this->trimmed('notice'));
        }

        return true;
    }

    function handle($args) {
        parent::handle($args);

        $this->showContent();
    }

    function showContent() {
        $nl = new NoticeListItem($this->notice, $this);
        $nl->show();
    }
}
