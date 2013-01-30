Videosync = {
    // YouTube ID of the current video
    yt_id: null,
    // Time the video started (epoch)
    started: null,
    // The YT.Player instance
    player: null,
    // Height of the player
    height: 390,
    // width of the player
    width: 640,
    // Is the player active/visible?
    active: false,
    // Name of the state cookie
    cookie: 'VideoSyncState',
    // second tolerance for stream correction. Any variation lower than this will not cause the stream to jump.
    tolerance: 2,
    // ID of the video frame
    videoFrame: 'videosync_box',
    // ID of the button that toggles the player
    trigger: 'videosync_btn',
    // Meteor channel that tracks updates
    syncChannel: null,
    // The original Meteor feed handler
    oldFeedHandler: null,
    // The original YouTube API handler
    oldAPIHandler: null,

    // Initialize the player
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

    // Get the cookie that toggles the state of the player
    getCookie: function() {
        if($.cookie(Videosync.cookie)) return true;
        else return false;
    },

    // Toggle the state cookie and the state variable
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

    // Update the player position
    updatePlayer: function(yt_id, pos) {
        var V = Videosync;
        V.yt_id = yt_id;
        if(typeof V.player.getCurrentTime != 'undefined') {
            if(yt_id != V.yt_id) {
                V.player.loadVideoById(V.yt_id, pos, 'large');
            }
            else {
                if(Math.abs(V.player.getCurrentTime() - pos) > V.tolerance) {
                    V.player.seekTo(pos);
                }
            }
        }
    },

    // Handler for the toggle button
    clickButton: function() {
        var V = Videosync;
        V.toggleCookie();
        V.toggleFrame();
    },

    // YouTube API loader
    loadApi: function() {
        $.getScript('//www.youtube.com/iframe_api');
    },

    // Toggles the frame view
    toggleFrame: function() {
        var V = Videosync;
        if(V.active) {
            $('#' + V.trigger).val("\u25B2 Hide live stream \u25B2");
            V.initPlayer();
            V.setupFeed();
            $('#' + V.videoFrame).show();
        }
        else {
            $('#' + V.trigger).val("\u25BC Watch our live stream! \u25BC");
            $('#' + V.videoFrame).replaceWith('<div id="' + V.videoFrame + '"></div>');
            V.player = null;
            V.removeFeed();
            $('#' + V.videoFrame).hide();
        }
    },

    // Sets up the Meteor feed
    setupFeed: function() {
        var V = Videosync;
        V.oldFeedHandler = Meteor.callbacks['process'];
        Meteor.callbacks['process'] = function(data) {V.handleFeed(data)};
        Meteor.joinChannel(V.syncChannel, 0);
    },

    // Handles data received from the Meteor feed, passing along any that doesn't belong to it
    handleFeed: function(data) {
        var V = Videosync;
        if(typeof data.yt_id != 'undefined') {
            data = JSON.parse(data);
            V.updatePlayer(data.yt_id, data.pos);
        }
        else {
            V.oldFeedHandler(data);
        }
    },

    // Removes the Meteor feed
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
