<?php
/**
 * Table Definition for message
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Message extends Memcached_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'message';                         // table name
    public $id;                              // int(4)  primary_key not_null
    public $uri;                             // varchar(255)  unique_key
    public $from_profile;                    // int(4)   not_null
    public $to_profile;                      // int(4)   not_null
    public $content;                         // text()
    public $rendered;                        // text()
    public $url;                             // varchar(255)
    public $created;                         // datetime()   not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP
    public $source;                          // varchar(32)

    /* Static get */
    function staticGet($k,$v=NULL) { return Memcached_DataObject::staticGet('Message',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    function getFrom()
    {
        return Profile::staticGet('id', $this->from_profile);
    }

    function getTo()
    {
        return Profile::staticGet('id', $this->to_profile);
    }

    static function saveNew($from, $to, $content, $source) {
        $sender = Profile::staticGet('id', $from);

        if (!$sender->hasRight(Right::NEWMESSAGE)) {
            // TRANS: Client exception thrown when a user tries to send a direct message while being banned from sending them.
            throw new ClientException(_('You are banned from sending direct messages.'));
        }

        if (common_config('throttle', 'enabled') && !Message::checkEditThrottle($from)) {
            common_log(LOG_WARNING, 'Excessive posting by profile #' . $from . '; throttled.');
            // TRANS: Client exception thrown when a user tries to post too many notices in a given time frame.
            throw new ClientException(_('Too many notices too fast; take a breather '.
                                        'and post again in a few minutes.'));
        }

        $user = User::staticGet('id', $sender->id);

        $msg = new Message();

        $msg->from_profile = $from;
        $msg->to_profile = $to;
        if ($user) {
            // Use the sender's URL shortening options.
            $msg->content = $user->shortenLinks($content);
        } else {
            $msg->content = common_shorten_links($content);
        }
        $msg->rendered = common_render_text($msg->content);
        $msg->created = common_sql_now();
        $msg->source = $source;

        $result = $msg->insert();

        if (!$result) {
            common_log_db_error($msg, 'INSERT', __FILE__);
            // TRANS: Message given when a message could not be stored on the server.
            return _('Could not insert message.');
        }

        $orig = clone($msg);
        $msg->uri = common_local_url('showmessage', array('message' => $msg->id));

        $result = $msg->update($orig);

        if (!$result) {
            common_log_db_error($msg, 'UPDATE', __FILE__);
            // TRANS: Message given when a message could not be updated on the server.
            return _('Could not update message with new URI.');
        }

        return $msg;
    }

    static function checkEditThrottle($profile_id) {
        $msg = new Message();
        $msg->from_profile = $profile_id;
        $msg->orderBy('id DESC');
        $msg->limit(common_config('throttle', 'count') - 1, 1);

        if ($msg->find() && $msg->fetch()) {
            # If the Nth message was posted less than timespan seconds ago
            if (time() - strtotime($msg->created) <= common_config('throttle', 'timespan')) {
                # Then we throttle
                return false;
            }
        }
        # Either not N notices in the stream, OR the Nth was not posted within timespan seconds
        return true;
    }

    static function maxContent()
    {
        $desclimit = common_config('message', 'contentlimit');
        // null => use global limit (distinct from 0!)
        if (is_null($desclimit)) {
            $desclimit = common_config('site', 'textlimit');
        }
        return $desclimit;
    }

    static function contentTooLong($content)
    {
        $contentlimit = self::maxContent();
        return ($contentlimit > 0 && !empty($content) && (mb_strlen($content) > $contentlimit));
    }

    function notify()
    {
        $from = User::staticGet('id', $this->from_profile);
        $to   = User::staticGet('id', $this->to_profile);

        mail_notify_message($this, $from, $to);
    }
}
