<?php

if (!defined('STATUSNET')) {
    exit(1);
}

class StaffAction extends Action
{

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

        $this->mods = Profile::adminProfiles(array(Profile_role::MODERATOR));
        $this->admins = Profile::adminProfiles(array(Profile_role::ADMINISTRATOR));
        $this->owners = Profile::adminProfiles(array(Profile_role::OWNER));

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
        return _m('Staff');
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

        $this->raw('<p style="clear: both;"><b>The Head Administrators of Rainbowdash Network (Responsible for moderator promotion/demotion)</b></p>');

        $owners = new ProfileList($this->owners, $this);
        $owners->show();

        $this->raw('<p style="clear: both;"><b>The Administrators of Rainbowdash Network</b></p>');

        $admins = new ProfileList($this->admins, $this);
        $admins->show();

        $this->raw('<p style="clear: both;"><b>The Moderators of Rainbowdash Network (Please go to these for basic rule issues)</b></p>');

        $mods = new ProfileList($this->mods, $this);
        $mods->show();

        $this->raw(<<<HERE
<p><b>Hoofy information for mods.</b></p>

<p><b>I)</b> When Mod status is enabled. A new button will start appearing on post of regular users. It will be the shape of a trash can. That button is used to delete posts. It does not however, delete the picture content, that needs to be done by the administrator. When you delete a post with a illegal picture. Contact an Admin.</p>

<p><b>II)</b> For reference I would like all mods that do any silencing of users to email webmaster@rainbowdash.net people come to me with emails if they are silenced and I think it's best if we keep this under control so I know who has done what. CC: <a href="/user/2959">@Widget</a>, <a href="/user/3175">@CeruleanSpark</a>, <a href="/user/1199">@Colfax</a></p>

<p><b>III)</b> It is worth noting that every action you perform is logged by the database. Do not mess around with the administration and your powers. Widget will see it.</p>

<p><b>IV)</b> When you visit the profile of a user, you should see a box that says 'Moderate' You are given three options, Sandbox, silence, delete. Use sandbox for timeouts for small issues. Use silence when its time to shut up the user, and delete only if the account is 1. New 2. spammy 3. and posts illegal content. If it does not meet the first criteria. DO NOT DELETE. Silence instead. PM admin.</p>

<p><b>V)</b> Directives are sometimes issued by the administrator. You will get either a message on the site, or a direct message.</p>
HERE
        );

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
