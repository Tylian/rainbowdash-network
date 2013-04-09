<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class PromoteEverythingPlugin extends Plugin
{
    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'PromoteForm':
        case 'UnpromoteForm':
        case 'PromotedNoticeSection':
            include_once $dir . '/lib/' . strtolower($cls) . '.php';
            return false;
        case 'PromoteAction':
        case 'UnpromoteAction':
        case 'PromotedAction':
            include_once $dir . '/action/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'Promote':
            include_once $dir . '/'.$cls.'.php';
            return false;
        default:
            return true;
        }

    }    

    function onStartInitializeRouter($m)
    {
        $m->connect('promoted',
            array('action' => 'promoted'));
        $m->connect('main/promote',
            array('action' => 'promote'));
        $m->connect('main/unpromote',
            array('action' => 'unpromote'));
        return true;
    }

    function onStartNoticeSave($notice) {
        $notice->blowStream('promote:notice_ids');
        $notice->blowStream('promote:notice_ids;last');

        return true;
    }

    function onCheckSchema() {
        $schema = Schema::get();

        $schema->ensureTable('promote',
            array(new ColumnDef('id', 'integer', null,
            true, 'PRI', null, null, true),
            new ColumnDef('type', 'char', 10, false),
            new ColumnDef('item_id', 'varchar', 30, false),
            new ColumnDef('created', 'timestamp', null, false),
        ));

        return true;
    }

    function onEndShowScripts($action) {
        $action->inlineScript("$('.form_promote').live('click', function() { SN.U.FormXHR($(this)); return false; });");
        $action->inlineScript("$('.form_unpromote').live('click', function() { SN.U.FormXHR($(this)); return false; });");

        return true;
    }

    function onStartShowSections($action) {
        if($action instanceof PublicAction) {
            $pns = new PromotedNoticeSection($action);
            $pns->show();
        }

        return true;
    }

    function onStartShowFaveForm($item) {
        $user = common_current_user();
        $notice = $item->notice;
        $action = $item->out;
        if ($user && $user->hasRight(Right::CONFIGURESITE) &&
            Event::handle('StartShowPromoteForm', array($action))) {
            $promote = new Promote();
            $promote->type = 'notice';
            $promote->item_id = $notice->id;
            if ($promote->find()) {
                $unpromote = new UnpromoteForm($action, $notice);
                $unpromote->show();
            } else {
                $promote = new PromoteForm($action, $notice);
                $promote->show();
            }
            Event::handle('EndShowPromoteForm', array($action));
        }
    }


    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Promote Everything',
                            'version' => STATUSNET_VERSION,
                            'author' => 'widget',
                            'homepage' => 'http://status.net/wiki/Plugin:Sample',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('Promote users, groups, tags, and notices.'));
        return true;
    }
}
?>
