Videosync = {
    yt_id: null,
    started: null,
    player: null,
    height: 390,
    width: 640,
    active: false,
    cookie: 'VideoSyncState',
    videoFrame: 'videosync_box',
    trigger: 'videosync_btn',
    syncChannel: null,
    oldFeedHandler: null,
    oldAPIHandler: null,

    init: function(parms) {

        var V = Videosync;
        if(parms) {
            V.yt_id = parms.yt_id;
            V.started = parms.started;
            V.syncChannel = parms.channel;
        }

        // Initialize the API first
        if(typeof YT == 'undefined') {
            V.loadApi();
            return;
        }

        V.active = V.getCookie();
        $('#' + V.trigger).click(V.clickButton);

        // Event handler for when the document fully loads
        $(V.toggleFrame);
    },

    initPlayer: function() {
        V = Videosync;
        V.player = new YT.Player(V.videoFrame, {
            height: V.height,
            width: V.width,
            videoId: V.yt_id,
            events: {
                'onReady': function() { V.updatePlayer(V.yt_id, new Date().getTime() / 1000 - V.started) },
            },
        });
    },

    getCookie: function() {
        if($.cookie(Videosync.cookie)) return true;
        else return false;
    },

    toggleCookie: function() {
        var V = Videosync;
        if($.cookie(V.cookie)) {
            V.active = false;
            $.cookie(V.cookie, null, {expires: 1, path: '/'});
        }
        else {
            V.active = true;
            $.cookie(V.cookie, 'true', {expires: 1, path: '/'});
        }
    },

    updatePlayer: function(yt_id, pos) {
        var V = Videosync;
        if(yt_id != V.yt_id) {
            V.yt_id = yt_id;
            V.player.loadVideoById(V.yt_id, pos, 'large');
        }
        else {
            V.player.seekTo(pos);
        }
    },

    clickButton: function() {
        var V = Videosync;
        V.toggleCookie();
        V.toggleFrame();
    },

    loadApi: function() {
        $.getScript('//www.youtube.com/iframe_api');
    },

    toggleFrame: function() {
        var V = Videosync;
        if(V.active) {
            $('#' + V.trigger).val("Hide");
            V.initPlayer();
            V.setupFeed();
            $('#' + V.videoFrame).show();
        }
        else {
            $('#' + V.trigger).val("Show");
            $('#' + V.videoFrame).replaceWith('<div id="videosync_box"></div>');
            V.removeFeed();
            $('#' + V.videoFrame).hide();
        }
    },

    setupFeed: function() {
        var V = Videosync;
        V.oldFeedHandler = Meteor.callbacks['process'];
        Meteor.callbacks['process'] = function(data) {V.handleFeed(JSON.parse(data))};
        Meteor.joinChannel(V.syncChannel, 0);
    },

    handleFeed: function(data) {
        var V = Videosync;
        if(data.yt_id) {
            V.updatePlayer(data.yt_id, data.pos);
        }
        else {
            V.oldFeedHandler(data);
        }
    },

    removeFeed: function() {
        var V = Videosync;
        Meteor.leaveChannel(V.syncChannel);
        Meteor.callbacks['process'] = V.oldFeedHandler;
        V.oldFeedHandler = null;
    },
}

// Set up the API ready handler, politely calling the old handler when complete.
if(typeof onYouTubeIframeAPIReady != 'undefined') {
    Videosync.oldAPIHandler = onYouTubeIframeAPIReady;
}

onYouTubeIframeAPIReady = function() {
    Videosync.init();
    if(Videosync.oldAPIHandler) Videosync.oldAPIHandler();
};
