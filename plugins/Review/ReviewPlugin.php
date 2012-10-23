<?php
// Test.

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

include_once(INSTALLDIR . '/lib/noticelist.php');

class ReviewPlugin extends Plugin
{
    public $filename = '/tmp/deletednotices';

    function onEndPrimaryNav($action) {
        $user = common_current_user();

        if(!empty($user) && ($user->hasRole(Profile_role::ADMINISTRATOR) || $user->hasRole(Profile_role::MODERATOR))) {
            $tooltip = _m('TOOLTIP', 'View deleted notices');
            $action->menuItem(common_local_url('deletednotices'),
                _m('MENU', 'Deleted'), $tooltip, false, 'nav_deleted');
        }
    }

    function onRouterInitialized($m) {
        $m->connect('main/deletednotices',
            array('action' => 'deletednotices',
            'file' => $this->filename,
        ));

        return true;
    }

    function onAutoload($cls) {
        $dir = dirname(__FILE__);

        switch ($cls) {
        case 'DeletednoticesAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        default:
            return true;
        }
    }

    function onEndDeleteOwnNotice($user, $notice) {
        $tempfile = tempnam(sys_get_temp_dir(), 'deletednotice-');
        $n = new NoticeListItem($notice, new HTMLOutputter("file://{$tempfile}"));
        $n->showStart();
        $n->showNotice();
        $n->showNoticeAttachments();
        $n->out->elementStart('div', 'entry-content');
        $n->showContext();
        $n->out->elementEnd('div');
        $n->showEnd();
        unset($n);

        $n = file_get_contents($tempfile);
        $notices = $n . file_get_contents($this->filename);
        $noticesfile = fopen($this->filename, 'w');
        fwrite($noticesfile, $notices);

        fclose($noticesfile);
        unlink($tempfile);

        return true;
    }

}
?>
