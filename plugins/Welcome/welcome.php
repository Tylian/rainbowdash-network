<?php

if (!defined('STATUSNET')) {
    exit(1);
}

class WelcomeAction extends Action
{
    protected $validated = true;
    protected $group;
    protected $notice;

    /**
     * Take arguments for running
     *
     * This method is called first, and it lets the action class get
     * all its arguments and validate them. It's also the time
     * to fetch any relevant data from the database.
     *
     * Action classes should run parent::prepare($args) as the first
     * line of this method to make sure the default argument-processing
     * happens.
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     */
    function prepare($args)
    {
        parent::prepare($args);

        $user = common_current_user();

        if(!empty($user)) {
            if(!$user->email) {
                $this->validated = false;
                return true;
            }

            // Search for groups based on the user's location.
            $q = $user->getProfile()->location;

            $this->group = new User_group;
            $this->group->limit(0, 10);
            $wheres = array('nickname', 'fullname', 'homepage', 'description', 'location');
            foreach ($wheres as $where) {
                $where_q = "$where like '%" . trim($this->group->escape($q), '\'') . '%\'';
                $this->group->whereAdd($where_q, 'OR');
            }

            $this->group->find();

            // Get a notice for the user to play with. Make sure it contains a group and hash tag.
            $this->notice = new Notice;
            Memcached_DataObject::cachedQuery('Notice', 'SELECT notice.* FROM notice ' .
                'JOIN notice_tag ON notice_tag.notice_id = notice.id ' .
                'JOIN group_inbox ON group_inbox.notice_id = notice.id ' .
                'GROUP BY notice.id HAVING COUNT(*) >= 2', null);
        }

        return true;
    }

    /**
     * Handle request
     *
     * This is the main method for handling a request. Note that
     * most preparation should be done in the prepare() method;
     * by the time handle() is called the action should be
     * more or less ready to go.
     *
     * @param array $args $_REQUEST args; handled in prepare()
     *
     * @return void
     */
    function handle($args)
    {
        parent::handle($args);

        $this->showPage();
    }

    /**
     * Title of this page
     *
     * Override this method to show a custom title.
     *
     * @return string Title of the page
     */
    function title()
    {
        return _m('Welcome!');
    }

    /**
     * Show content in the content area
     *
     * The default StatusNet page has a lot of decorations: menus,
     * logos, tabs, all that jazz. This method is used to show
     * content in the content area of the page; it's the main
     * thing you want to overload.
     *
     * This method also demonstrates use of a plural localized string.
     *
     * @return void
     */
    function showContent()
    {
        $sitename = common_config('site', 'name');
        $user = common_current_user();

        $this->element('p', null, sprintf('Congratulations! And welcome to %s!', $sitename));

        if(!$this->validated) {
            $this->element('p', null, 'You should receive a message by email momentarily, with instructions on how to confirm your email address.');

            $this->elementStart('p');
            $this->element('a', array('href' => common_local_url('welcome')), 'Click here when you\'ve validated your email address to get started!');
            $this->elementEnd('p');

            return true;
        }

        if(!empty($this->group) && $this->group->N) {
            $this->element('p', null, 'To start you off, we\'ve picked out some groups for you that might interest you. Click the join button to become a member of the group. This will allow you to post messages there. If you\'d like to skip this step, keep scrolling down to read the rest of the tutorial.');

            $gl = new GroupList($this->group, null, $this);
            $gl->show();
        }

        if(!empty($this->notice) && $this->notice->fetch()) {
            $this->element('p', null, 'Right underneath this paragraph is a dash (sometimes also called a notice). It\'s what gets posted when you put something into the white box at the top of the page and click "Send". Dashes are sent to the public timeline (the homepage), as well as everyone subscribed to you.');

            $this->elementStart('ol', 'notices');
            $nli = new NoticeListItem($this->notice, $this);
            $nli->show();
            $this->elementEnd('ol');

            $this->element('p', null, 'The buttons at the bottom right of the dash are important, so I\'ll explain them to you:');

            $this->elementStart('ul');
            $nli->showStart();
            $this->elementStart('div', array('class' => 'notice-options', 'style' => 'width: auto;'));
            $this->elementStart('ul', array('style' => 'list-style-type: none; margin-left: 20px; margin-bottom: 10px;'));

            $this->elementStart('li');
            $ff = new FavorForm($this, $this->notice);
            $ff->show();
            $this->text('This button lets you favorite a person\'s dash. You should click this if you think a dash was funny, interesting, informative, or you just want to save it for later. Favorited dashes go to your');
            $this->element('a',
                array(
                    'style' => 'text-indent: 0px; width: auto; height: auto; float: none; display: inline;',
                    'href' => !empty($user) ? common_local_url('showfavorites', array('nickname' => $user->nickname)) : common_local_url('favorited')
                ),
                _('Favorites'));
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->elementStart('div', array('style' => 'display: none;'));
            $nli->showNotice();
            $this->elementEnd('div');

            $nli->showReplyLink();
            $this->text('This is the reply button. You should click it when you want to reply to another person\'s dash. Clicking reply puts the person\'s name into the post box and makes it easier for others to know which dash you\'re replying to.');
            $this->elementEnd('li');

            $this->elementStart('li');
            $deleteurl = common_local_url('deletenotice',
                array('notice' => $this->notice->id));
            $this->element('a', array('href' => $deleteurl,
                'class' => 'notice_delete',
                'title' => _('Delete this notice')), _('Delete'));

            $this->text('On your own dashes, you\'ll see a delete button. This should be pretty self-explanatory.');
            $this->elementEnd('li');

            $this->elementStart('li');
            $rf = new RepeatForm($this, $this->notice);
            $rf->show();

            $this->text('The redash/repeat button allows you to repost something someone else said, while giving them credit for it. The dash will appear as it did on your own timeline, with "redashed by X" or "redash of X" underneath it.');
            $this->elementEnd('li');

            $this->elementEnd('ul');
            $this->elementEnd('div');

            $this->element('p', null, 'There are a few other parts to a dash:');

            $this->elementStart('ul', array('style' => 'position: relative; list-style-type: none; margin-top: 10px; margin-left: 20px; margin-bottom: 10px;'));
            
            $this->elementStart('li');
            $this->elementStart('div', 'entry-title');
            $nli->showAuthor();
            $this->element('p', 'entry-content', 'This is the user\'s name and avatar. Clicking on it will take you to their timeline, which has a list of all their dashes.');
            $this->elementEnd('div');
            $this->elementEnd('li');

            $this->elementStart('li');
            $this->elementStart('div', array('class' => 'entry-content'));
            $nli->showNoticeLink();
            $this->text('This is the time the dash was posted. This is a direct link to the dash, and is useful if you want to share a dash somewhere offsite.');
            $this->elementEnd('div');
            $this->elementEnd('li');

            $this->elementEnd('ul');

            $nli->showEnd();
            $this->elementEnd('ul');

            $this->raw(common_markup_to_html(sprintf('That\'s it! be sure to check the [Rules](%%%%doc.rules%%%%), and then click the %s logo to go to the [home page](%%%%action.public%%%%)!', $sitename)));
        }

        return true;

    }

    /**
     * Return true if read only.
     *
     * Some actions only read from the database; others read and write.
     * The simple database load-balancer built into StatusNet will
     * direct read-only actions to database mirrors (if they are configured),
     * and read-write actions to the master database.
     *
     * This defaults to false to avoid data integrity issues, but you
     * should make sure to overload it for performance gains.
     *
     * @param array $args other arguments, if RO/RW status depends on them.
     *
     * @return boolean is read only action?
     */
    function isReadOnly($args)
    {
        return true;
    }
}
?>
