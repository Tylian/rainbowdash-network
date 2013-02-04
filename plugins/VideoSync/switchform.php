<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/form.php';

class SwitchForm extends Form
{
    /**
     * Notice to favor
     */
    var $video = null;

    /**
     * Constructor
     *
     * @param HTMLOutputter $out    output channel
     * @param Notice        $notice notice to favor
     */
    function __construct($out=null, $video=null)
    {
        parent::__construct($out);

        $this->video = $video;
    }

    /**
     * ID of the form
     *
     * @return int ID of the form
     */
    function id()
    {
        return 'videosync_switchvideo';
    }

    /**
     * Action of the form
     *
     * @return string URL of the action
     */
    function action()
    {
        return common_local_url('switchvideo');
    }

    /**
     * Include a session token for CSRF protection
     *
     * @return void
     */
    function sessionToken()
    {
        $this->out->hidden('token-videoswitch',
                           common_session_token());
    }

    /**
     * Legend of the Form
     *
     * @return void
     */
    function formLegend()
    {
        $this->out->element('legend', null, _('Switch video'));
    }

    /**
     * Action elements
     *
     * @return void
     */
    function formActions()
    {
        if(!empty($this->video)) {
            while($this->video->fetch()) {
                $this->out->element('input',
                    array(
                        'type' => 'submit',
                        'name' => 'videoswitch-submit',
                        'class' => 'submit',
                        'title' => 'Switch video',
                        'value' => $this->video->tag ?: $this->video->id,
                    ));
            }
        }
    }

    /**
     * Class of the form.
     *
     * @return string the form's class
     */
    function formClass()
    {
        return 'form_switchvideo';
    }
}
