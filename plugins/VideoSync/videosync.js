Videosync = {
    // YouTube ID of the current video
    yt_id: null,
    // Time the video started (epoch)
    started: null,
    // Tag to put in the notice box
    tag: null,
    // Tag that names the stream
    streamTag: null,
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
    // Aside container
    asideFrame: 'videosync_aside',
    // ID of the button that toggles the player
    trigger: 'videosync_btn',
    // Notice box ID
    noticeBox: SN.C.S.NoticeDataText,
    // Meteor channel that tracks updates
    syncChannel: null,
    // The original Meteor feed handler
    oldFeedHandler: null,
    // The original YouTube API handler
    oldAPIHandler: null,

    // Initialize the player
    init: function(parms) {

        var V = Videosync;
        // Return if the trigger button doesn't exist. This means we aren't logged
        if(!$('#' + V.trigger).length) {
            return;
        }

        if(parms) {
            V.yt_id = parms.yt_id;
            V.started = parms.started;
            V.syncChannel = parms.channel;
            V.tag = parms.tag;
            V.streamTag = parms.tag.split(' ')[0];
        }

        // Initialize the API first
        if(typeof YT == 'undefined') {
            V.loadApi();
            return;
        }

        V.active = V.getCookie();

        // Event handler for when the document fully loads
        $(function() {
            $('#' + V.trigger).click(V.clickButton);
            $('.form_switchvideo .submit').live('click', function(){
                var field = $('<input name="' + $(this).attr('name') + '" type="hidden" value="' + $(this).attr('value') + '" />');
                var form = $(this).closest('form');
                form.append(field);
                SN.U.FormXHR(form);
                //form.remove(field);
                return false;
            });
            V.toggleFrame();
        });
    },

    initPlayer: function() {
        var V = Videosync;
        V.player = new YT.Player(V.videoFrame, {
            height: V.height,
            width: V.width,
            videoId: V.yt_id,
            events: {
                'onReady': function() { V.updatePlayer(V.yt_id, new Date().getTime() / 1000 - V.started, V.started, V.tag) },
            },
        });
    },

    // Get the cookie that toggles the state of the player
    getCookie: function() {
        if($.cookie(Videosync.cookie)) return true;
        else return false;
    },

    // Setup reset value
    setupReset: function() {
        var V = Videosync;
        var text = $('#' + V.noticeBox);
        var oldText = text.val();
        text.val('');
        if(V.active) {
            V.addTag();
        }
        text.html(function() { return this.value });
        text.val(oldText);
    },

    // Add tag to notice box
    addTag: function() {
        var V = Videosync;
        if(V.tag) {
            var tag = '#' + V.tag + ' ';
            var text = $('#' + V.noticeBox);
            text.val(tag + text.val().replace(tag, ''));
        }
    },

    removeTag: function() {
        var V = Videosync;
        if(V.tag) {
            var tag = '#' + V.tag + ' ';
            var text = $('#' + V.noticeBox);
            text.val(text.val().replace(tag, ''));
        }
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
    updatePlayer: function(yt_id, pos, started, tag) {
        var V = Videosync;
        if(typeof V.player.getCurrentTime != 'undefined') {
            if(yt_id != V.yt_id) {
                V.yt_id = yt_id;
                V.started = started;

                V.removeTag();
                V.tag = tag;
                V.setupReset();
                V.addTag();

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
            $('#' + V.trigger).val("\u25B2 Hide #" + V.streamTag + " \u25B2");
            V.initPlayer();
            V.setupReset();
            V.addTag();
            V.setupFeed();
            $('#' + V.videoFrame).show();
            $('#' + V.asideFrame).show();
        }
        else {
            $('#' + V.trigger).val("\u25BC Watch videos together on the #" + V.streamTag + "! \u25BC");
            $('#' + V.videoFrame).replaceWith('<div id="' + V.videoFrame + '"></div>');
            V.player = null;
            V.setupReset();
            V.removeTag();
            V.removeFeed();
            $('#' + V.videoFrame).hide();
            $('#' + V.asideFrame).hide();
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
        jdata = JSON.parse(data);
        if(typeof jdata.yt_id != 'undefined') {
            V.updatePlayer(jdata.yt_id, jdata.pos, jdata.started, jdata.tag);
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
