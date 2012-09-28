<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class SeizeForm extends ProfileActionForm {

    function target()
    {
        return 'seize';
    }

    function title()
    {
        return _('Seize');
    }

    function description()
    {
        return _('Seize this account');
    }
}

?>
