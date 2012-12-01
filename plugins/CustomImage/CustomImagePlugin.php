<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Plugin for testing ad layout
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Ads
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Plugin for testing ad layout
 *
 * This plugin uses the UAPPlugin framework to output ad content. However,
 * its ad content is just images with one red pixel stretched to the
 * right size. It's mostly useful for debugging theme layout.
 *
 * To use this plugin, set the parameter for the ad size you want to use
 * to true (or anything non-null). For example, to make a leaderboard:
 *
 *     addPlugin('BlankAd', array('leaderboard' => true));
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 *
 * @seeAlso  Location
 */
class CustomImagePlugin extends UAPPlugin
{
    /**
     * Show a medium rectangle 'ad'
     *
     * @param Action $action Action being shown
     *
     * @return void
     */
    protected function showMediumRectangle($action)
    {
        $action->element('img',
                         array('width' => 300,
                               'height' => 250,
                               'src' => $this->path('redpixel.png')),
                         '');
    }

    /**
     * Show a rectangle 'ad'
     *
     * @param Action $action Action being shown
     *
     * @return void
     */
    protected function showRectangle($action)
    {
       $action->raw(<<<HERE
<div class="section">
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
<input style="border-width: 0;" type="image" src="http://rainbowdash.net/plugins/CustomImage/donate.png" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</div>
HERE
);

}

    /**
     * Show a wide skyscraper ad
     *
     * @param Action $action Action being shown
     *
     * @return void
     */
    protected function showWideSkyscraper($action)
    {
        $action->element('img',
                         array('width' => 160,
                               'height' => 600,
                               'src' => $this->path('redpixel.png')),
                         '');
    }

    /**
     * Show a leaderboard ad
     *
     * @param Action $action Action being shown
     *
     * @return void
     */
    protected function showLeaderboard($action)
    {
        $action->element('img',
                         array('width' => 728,
                               'height' => 90,
                               'src' => $this->path('redpixel.png')),
                         '');
    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Custom Ad',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Cerulean Spark',
                            'homepage' => 'Rainbowdash.net',
                            'rawdescription' =>
                            _m('Plugin for adding user supplied ads'));
        return true;
    }
}
