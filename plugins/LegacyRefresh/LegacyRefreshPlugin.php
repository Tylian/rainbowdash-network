<?php
// Test.

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class LegacyRefreshPlugin extends Plugin
{
    function onEndShowScripts($action)
    {
        $action->script($this->path('legacyrefresh.js'));
    }
}
?>
