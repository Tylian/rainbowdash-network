<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Plugin to add additional awesomenss to StatusNet
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
 * @category  Plugin
 * @package   StatusNet
 * @author    Jeroen De Dauw <jeroendedauw@gmail.com>
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * Adds an Affiliates section to the sidebar
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Jeroen De Dauw <jeroendedauw@gmail.com>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class AffiliatesPlugin extends Plugin
{
    public $affiliates;

	const VERSION = '0.0.1';

    public function onPluginVersion(&$versions)
    {
        $versions[] = array(
            'name' => 'Affiliates',
            'version' => self::VERSION,
            'author' => 'Cerulean Spark',
            'homepage' => 'http://flattr.com',
            // TRANS: Plugin description for a sample plugin.
            'rawdescription' => _m(
                'Adds affiliate links to the sidebar'
            )
        );
        return true;
    }

    /**
     * Add the Affilliates Section
     *
     * @param Action $action the current action
     *
     * @return void
     */
    function onEndShowSections(Action $action)
    {
        $action->elementStart('div', array('id' => 'affilliate_section',
                                         'class' => 'section'));

        $action->raw(<<<HTML
<h2>Affiliates</h2>
<span id ="shuffle">
HTML
    	);

        $path = $this->path("");
        foreach($this->affiliates as $key => $a) {
            $action->raw(<<<HTML
<span id="afspan{$key}">
<a href="{$a->href}" target="_blank">
<img src="{$path}/{$a->image}" alt="{$a->alt}" title="{$a->title}" border="0" /></a>
</span>
HTML
            );
        }

    	$action->elementEnd('div');
    }
}
