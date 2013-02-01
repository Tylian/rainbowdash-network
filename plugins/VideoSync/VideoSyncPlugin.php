<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

include_once(INSTALLDIR . '/plugins/Realtime/RealtimePlugin.php');
include_once(INSTALLDIR . '/plugins/Meteor/MeteorPlugin.php');

/* At first I was going to extend MeteorPlugin (wouldn't work because of the complications with Realtime), but then I realized instantiating it and calling its functions would probably work. */
class VideoSyncPlugin extends Plugin
{
    public $webserver     = null;
    public $webport       = null;
    public $controlport   = null;
    public $controlserver = null;
    public $channelbase   = null;
    public $persistent    = true;

    function __construct($webserver=null, $webport=4670, $controlport=4671, $controlserver=null, $channelbase='')
    {  
        global $config;

        $this->webserver     = (empty($webserver)) ? $config['site']['server'] : $webserver;
        $this->webport       = $webport;
        $this->controlport   = $controlport;
        $this->controlserver = (empty($controlserver)) ? $webserver : $controlserver;
        $this->channelbase   = $channelbase;

        parent::__construct();
    }

    function onAutoload($cls) {
        $dir = dirname(__FILE__);

        switch ($cls) {
        case 'Videosync':
            require_once $dir . '/' . $cls . '.php';
            return false;
        default:
            return true;
        }
    }

    function initialize() {
        $this->v = Videosync::getCurrent();

    }

    function onCheckSchema() {
        $schema = Schema::get();

        $schema->ensureTable('videosync',
            array(new ColumnDef('id', 'integer', null,
            true, 'PRI', null, null, true),
            new ColumnDef('yt_id', 'varchar', 11, true),
            new ColumnDef('duration', 'integer', 4, true),
            new ColumnDef('started', 'timestamp',  null, false),
            new ColumnDef('toggle', 'integer', 1, true),
        ));

        return true;
    }

    function getMeteor() {
        return new MeteorPlugin(
            $this->webserver,
            $this->webport,
            $this->controlport,
            $this->controlserver,
            $this->channelbase
        );
    }

    function onEndShowScripts($action) {
        if($action instanceof PublicAction) {
            // FIXME: Will put high load on the server. Need to make it so this doesn't run on every page load.
            $m = $this->getMeteor();

            $m->_connect();
            $m->_publish($this->channelbase . '-videosync', array('yt_id' => $this->v->yt_id, 'pos' => time() - strtotime($this->v->started), 'started' => strtotime($this->v->started)));
            $m->_disconnect();

            $action->script($this->path('videosync.min.js'));
            $action->inlineScript('Videosync.init(' . json_encode(array(
                'yt_id' => $this->v->yt_id, 
                'started' => strtotime($this->v->started),
                'channel' => $this->channelbase . '-videosync',
            )) . ');');
        }

        return true;
    }

    function onStartShowNoticeForm($action) {
        if($action instanceof PublicAction) {
            $action->raw('<div id="videosync"><input type="button" value="▼ Watch videos together on the #RDNStream! ▼" id="videosync_btn" /><div id="videosync_box"></div></div>');
        }

        return true;
    }
}
?>
