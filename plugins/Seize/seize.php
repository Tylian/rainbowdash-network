<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class SeizeAction extends ProfileFormAction
{
    var $profile = null;

    function prepare($args)
    {
        if (!parent::prepare($args)) {
            return false;
        }

        $cur = common_current_user();

        assert(!empty($cur)); // checked by parent

        if (!$cur->hasRole(Profile_role::ADMINISTRATOR)
            //|| !$cur->hasRole(Profile_role:MODERATOR)
        ) {
            // TRANS: Client error displayed when seizing a user that has already been seized.
            $this->clientError(_('You do not have the necessary permissions to perform this action.'));
            return false;
        }

        return true;
    }

    function handle($args)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($this->arg('no')) {
                $this->returnToPrevious();
            } elseif ($this->arg('yes')) {
                $this->handlePost();
            } else {
                $this->showPage();
            }
        } else {
            $this->showPage();
        }
    }

    function showContent() {
        $this->areYouSureForm();
    }

    function title() {
        // TRANS: Title for seize user page.
        return _('Seize user');
    }

    function showNoticeForm() {
        // nop
    }

    function areYouSureForm()
    {
        // @fixme if we ajaxify the confirmation form, skip the preview on ajax hits
        $profile = new ArrayWrapper(array($this->profile));
        $preview = new ProfileList($profile, $this);
        $preview->show();


        $id = $this->profile->id;
        $this->elementStart('form', array('id' => 'seize-' . $id,
                                           'method' => 'post',
                                           'class' => 'form_settings form_entity_seize',
                                           'action' => common_local_url('seize')));
        $this->elementStart('fieldset');
        $this->hidden('token', common_session_token());
        // TRANS: Legend for seize user form.
        $this->element('legend', _('Seize user'));
        $this->element('p', null,
                // TRANS: Explanation of consequences when seizing a user on the seize user page.
                       _('Are you sure you want to seize this user? '.
                       'It will change their password so you can log in as them, ' .
                       'but as a consequence they will be unable to.'));
        $this->element('input', array('id' => 'seizeto-' . $id,
                                      'name' => 'profileid',
                                      'type' => 'hidden',
                                      'value' => $id));
        foreach ($this->args as $k => $v) {
            if (substr($k, 0, 9) == 'returnto-') {
                $this->hidden($k, $v);
            }
        }
        $this->submit('form_action-no',
                      // TRANS: Button label on the user seize form.
                      _m('BUTTON','No'),
                      'submit form_action-primary',
                      'no',
                      // TRANS: Submit button title for 'No' when seizing a user.
                      _('Do not seize this user.'));
        $this->submit('form_action-yes',
                      // TRANS: Button label on the user seize form.
                      _m('BUTTON','Yes'),
                      'submit form_action-secondary',
                      'yes',
                      // TRANS: Submit button title for 'Yes' when seizing a user.
                      _('Seize this user.'));
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    function handlePost()
    {
        $cur = common_current_user();

        if($cur->hasRole(Profile_role::ADMINISTRATOR)) {
            if (Event::handle('StartSeizeProfile', array($cur, $this->profile))) {
                $user = $this->profile->getUser();
                $orig = clone($user);

                $pass = common_good_rand(30);
                $user->password = common_munge_password($pass, $user->id);

                $result = $user->update($orig);

                if ($result) {
                    Event::handle('EndSeizeProfile', array($cur, $this->profile));
                }
            }
        }

        if (!$result) {
            // TRANS: Server error displayed when seizing a user fails.
            $this->serverError(_('Failed to seize account.'));
            return;
        }
        else {
            // Notify all administrators of this action
            $admins = User::adminUsers(array(Profile_role::ADMINISTRATOR));

            while($admin = $admins->fetch()) {
                mail_to_user($admin,
                    sprintf(_("%s Seized %s's Account"), $cur->nickname, $user->nickname),
                    sprintf(_(
                        "Dear %s,\n\n" .
                        "%s has just seized %s's account on %s. " . 
                        "If you believe this action is in error, please notify the user at %s and give them this password so they can log in: %s\n\n" .
                        "Sincerely,\n%s"),
                    $admin->nickname,
                    $cur->nickname,
                    $user-nickname,
                    common_config('site', 'name'),
                    $user->email,
                    $pass,
                    common_config('site', 'name')));
            }

            $this->serverError(sprintf(_('Account successfully seized. Password is %s. Please note that all administrators have been notified of this action via email.'), $pass));
        }

    }

    function showScripts()
    {
        parent::showScripts();
        $this->autofocus('form_action-yes');
    }

    function checkSessionToken()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' ||
            $this->arg('yes') ||
            $this->arg('no')) {

            return parent::checkSessionToken();
        }
    }

    function defaultReturnTo()
    {
        $user = common_current_user();
        if ($user) {
            return common_local_url('subscribers',
                                    array('nickname' => $user->nickname));
        } else {
            return common_local_url('public');
        }
    }
}
