var totalNew = lastPost = styleVar = 0; 
var hasFocus = true; 
var displayReply = true; 		//Used to replace the blip above the post field with what message is being replied to. 
var defaultVal = 10000; 		//Default Reload Interval. 
var docTitle = document.title; 	//Gets the natural document title.
var timeout; 
var curPage = location.href;
var reloading = false;

$(function() {

    var siteDir = $('link[href*="/opensearch/"]').attr('href')
    if(siteDir) {siteDir = siteDir.split('/opensearch/')[0]}

    // Redefine the belongsOnTimeline function to prevent StatusNet from posting the message directly to the timeline.
    SN.U.belongsOnTimeline = function(notice) {
        return false;
    }

	initVar();
	setup();
	setFocusListeners();
	createMenu(); 
	updatePause(); 
    parseLinks(document);
    if(styleLookup('ReloadState')) { timeout = setTimeout(reload, defaultVal); }
});

function createCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name,"",-1);
}

    /* Initialize the lastPost variable */
    function setup() {
        $('head').append('<style>' +
            '.newPost, .newPost:hover { background-color: rgba(239, 182, 29, 0.25) !important; }' +
            ' #header { margin-bottom: 0; }' +
            '</style>');


        // Unbind error handlers???
        lastPost = $('.notices.xoxo li').attr('id');

        var notice_data = document.getElementsByName('status_textarea')[0];
        if(notice_data) {
            notice_data.addEventListener('keyup', 
                    function(event) { 
                        contextClear(this);
                    }, false, true);
            notice_data.addEventListener('change', 
                    function(event) { 
                        contextClear(this);
                    }, false, true);
        }
    }

function contextClear(target) { 
    if ((target.value == null || target.value == "")) { 
        setPost("", "", "reset"); 
    }
}

/* Initialize the variables - To Ensure All Variables have a Default Value*/ 
function initVar() {
    var vars = [
            'ReloadState',
            ];

    for(each in vars) {
        var cookie = readCookie(vars[each]);
        if(cookie === null || cookie === undefined) {
            createCookie(vars[each], styleLookup(vars[each]), 365);
        }
    }
}

/* Calls a fresh version of the page information and replaces the 'ol' from the old with the new. */
function reload(forceReload) {
    if ((styleLookup('ReloadState') && !reloading) || forceReload) {
        reloading = true;
        $.ajax({
            type: 'GET',
            url: curPage,
            success: function(response) {
                var oldPosts, newPosts;

                var timelinEx = new RegExp('<ol class="notices ([\\S\\s]+? )?xoxo">([\\s\\S]*)</ol>', 'gi');
                response = timelinEx.exec(response)[0];

                var holder = document.createElement('div');
                holder.innerHTML = response;

                /* Passing the new page to post checker */
                getNewPosts(holder);

                reloading = false;
            }});
    }
    if(styleLookup('ReloadState')) { timeout = setTimeout(reload, defaultVal); }
}

/* Updates the javascript onClick handling for the in-page reply links */
function parseLinks(newPosts) {
    var links = newPosts.getElementsByClassName('notice_reply');
    for (var i=0, imax=links.length; i<imax; i++) {	
        links[i].addEventListener('click', 
                function(event) {
                    /* Standard Link Cancellation */
                    event.stopPropagation();
                    event.preventDefault();

                    var currentLink = this.href;
                    var currentLink_array = currentLink.split("replyto=");
                    /* 	0: 		url base
                        1:		username&in
                        2:		replyto=postid
                     */
                    var username = currentLink_array[1].split("&");
                    /*	0: 		username
                        1:		=
                     */
                    var postid = currentLink_array[2];

                    /* New Function Call to Set the Post Data */
                    setPost(postid.toString(), username[0], "");
                }, false, true);
    }
}

/* Scroll to Top and Append Field Data to Posting Element */
function setPost(inreplyto, username, content) {
    var notice_data = $('#notice_data-text, .notice_data-text');
    var notice_reply = $('#notice_in-reply-to');
    
        // "Click" the fake notice box if it exists
        if($('#input_form_status').length) {
            $('#input_form_status').show();
            $('#input_form_placeholder').hide();
        }

    if (content == 'reset') { 
        notice_reply.val(""); // Sets the internal reply value
    } else {
        window.scrollTo(0,0); // Scrolls to position 0,0 
        notice_data.focus(); // Ensures that the post box has focus
        notice_reply.val(inreplyto); // Sets the internal reply value
        if (username != null && username.length > 0) {
            notice_data.val('@' + username + " " + content + notice_data.val()); // Updates the Post content with the user name (and optional content).
        } else { 
            notice_data.val(content + notice_data.val()); // Updates the Post content with the user name (and optional content).
        }

        
    }
}

/* Sets up the right hand menu for options */
function createMenu() {
    containerDiv = document.createElement('div');
    $(containerDiv).html('<div id="rdn_refresh" style="float: left; clear: both; width: 100%">' +
            '<h2 style="float: left; margin-right: 25px;">RDN Refresh </h2>' +
            '<img style="display: inline; vertical-align: middle" id="nav_pause" alt="Pause" title="Pause AutoRefresh" width="16" height="16" /><br />' +
            '</div>'
            );

    /* Placing the Element As the First within the Side Panel */
    var aside_target = $('#header');
    aside_target.append(containerDiv);

    document.getElementById('nav_pause').addEventListener('click', 
            function(event) { 
                createCookie('ReloadState', !styleLookup('ReloadState'), 365); 
                if (timeout) { clearTimeout(timeout) };
                updatePause();
                reload();
                event.stopPropagation();
                event.preventDefault();
            }, false, true);
}

/* Refreshes the state of the pause button */
function updatePause() {
    var nav_pause = $('#nav_pause');
    if (styleLookup('ReloadState')) {
        nav_pause.attr({'title': "Pause AutoRefresh",
            'alt':   "Pause",
            'src':   "http://i1196.photobucket.com/albums/aa412/haganbmj/pause_button.png",
        });
    } else {
        nav_pause.attr({'title': "UnPause AutoRefresh",
            'alt':   "UnPause",
            'src':   "http://i1196.photobucket.com/albums/aa412/haganbmj/play_button.png",
        });
    }
}

/* Collects the new and old posts, which are stored to an array for comparison */
function getNewPosts(holder) {

    var newPosts = holder.getElementsByTagName('ol')[0];

    // Display them
    oldPosts = document.getElementsByTagName('ol')[0];
    if (oldPosts != null && newPosts != null) {
        // Determine which posts are actually new
        var reallyNew = $(newPosts).find('.hentry.notice');
        var newPostsID = $.map(reallyNew, function(n, i){
            return $(n).attr('id');
        });
        var oldPostsID = $.map($(oldPosts).find('.hentry.notice'), function(n, i){
            return $(n).attr('id');
        });

        // Get the IDs of new posts
        var reallyNewID = [];
        for(each in newPostsID) {
            if(oldPostsID.indexOf(newPostsID[each]) == -1) {
                reallyNewID.push(newPostsID[each]);
            }
        }
        
        // Find posts that have been removed
        newPostsID = '#' + newPostsID.join(', #');
        $(oldPosts).children('.hentry.notice').not(newPostsID).remove();

        // Bail out if no new posts have been made
        var numPosts = reallyNewID.length;
        if(!numPosts) {
            return;
        }

        //Update the timestamps for the remaining posts
        $(oldPosts).find('.hentry.notice').each(function() {
            var newTime = $(newPosts).find('#' + this.id + ' abbr.published').html()
            $(this).find('abbr.published').html(newTime);
        });


        // Reflash the title
        if ( numPosts > 0) {
            totalNew += numPosts;
            flashTitle(totalNew);
        }

        // Select the new posts
        reallyNewID = '#' + reallyNewID.join(', #');
        reallyNew = reallyNew.filter(reallyNewID).filter(function() {
            return !$(this).parents().filter(reallyNewID).length;
        });

        var slideToggle = true;
        var postHighlight = true;
        
        // Reset highlighting
        $(oldPosts).find('.hentry.notice').removeClass('newPost');

        // Determine the ordering of the notices and insert them
        var topLevel = [];
        reallyNew.each(function(){
            //Slide in new posts and highlight (if enabled)
            if(postHighlight) highlightPosts(this);
            if(slideToggle) $(this).hide();

            // Main timeline (dbl-check to make sure post does not exist already)
            if(!$(oldPosts).find($(this).attr('id')).length) {
                if(!$(this).closest('ol, ul').parent().closest('ol, ul').length) {
                    $(oldPosts).prepend(this);
                }
                // Conversations
                else {
                    var in_reply_to = $(this).parent().closest('li').attr('id');
                    in_reply_to = $(oldPosts).find('#' + in_reply_to);
                    if(!in_reply_to.children('ol, ul').length) {
                        in_reply_to.append('<ol class="notices"></ol>');
                    }
                    in_reply_to.children('ol, ul').append(this);
                }
            }

            parseLinks(this);
            if(slideToggle) $(this).slideDown(500);
        });
        
        // Cut down number of posts to twenty and sort
        var sortable = $(oldPosts).children('li');
        sortable.sort(function(a, b){
            a = $(a).attr('id').split('-')[1];
            b = $(b).attr('id').split('-')[1];
            return b - a;
        });
        $(oldPosts).children('li').remove();
        $(oldPosts).append(sortable);
        $(oldPosts).children('li').filter(':gt(20)').remove();
    }
}

/* Changes/Updates the Window Title if/when New Posts are Available */
function flashTitle(newTitle) {
    if (hasFocus || newTitle == "reset") { //Check if the Window is in Focus, if so - break the function. 
        document.title = ".";
        document.title = docTitle; 
        totalNew = 0;
    } else {
        document.title = ".";
        document.title = "(" + newTitle + ") " + docTitle;
    }
}

/* Highlights the newest posts (from the most recent refresh cycle) */
function highlightPosts(newPosts, num) {
    var hiPosts = $(newPosts);
    hiPosts.addClass('newPost');
}

function blurFunc() {
    hasFocus = false;
}

function focusFunc() {
    hasFocus = true;
    flashTitle("reset");
}

function setFocusListeners() { 
    $(document).ready(function () {
        $(window).bind("focus", function(event) {
            focusFunc();
        }).bind("blur", function(event) {
            blurFunc();
        });
    });

    /* Updates the title when the body of the page is clicked */
    $('body').click(function() {
        flashTitle("reset");
    });
}

function styleLookup(stylevar) {
    var cookie = readCookie(stylevar);
    switch(stylevar) { 
        case 'ReloadState':
            if(cookie === null || cookie === undefined) {
                return true;
            }
            else return cookie == 'true'; break;
	}
}
