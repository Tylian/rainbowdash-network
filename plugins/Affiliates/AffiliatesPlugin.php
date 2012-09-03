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

    	$action->raw(
    	<<<EOT
<h2>Affiliates</h2>
<span id ="shuffle">
<span id="afspan-1">
<a href="http://www.bronyuk.org/" target="_blank">
<img src="/plugins/Affiliates/BUCK.png?1" alt="BUCK" title"BUCK" border="0" /></a>
</span>
<span id="broniesuk">
<table style="border: none; border-collapse: collapse; border-spacing: 0;">
<tr>
<td style="padding: 0;">
<a href="https://www.facebook.com/Bronies.UK" target="_blank" style="float:left"><img src="/plugins/Affiliates/BroniesUK1.png?1" alt="Bronies UK" title"Bronies UK" border="0" /></a>
</td>
<td style="padding: 0;">
<a href="https://plus.google.com/111252713260579453782/" style="float:left;"><img src="/plugins/Affiliates/BroniesUK2.png?1" alt="Bronies UK" title"Bronies UK" border="0" /></a>
</td>
</tr>
</table>
</span>

<span id="afspan1">
<a href="http://equestriainspired.net" target="_blank">
<img src="/plugins/Affiliates/EquestriaInspired.png?1" alt="Equestria Inspired" title="Equestria Inspired" border=
"0" /></a>
</span>
<span id="afspan2">
<a href="http://equestriagaming.com" target="_blank">
<img src="/plugins/Affiliates/EquestriaGaming.png?1" alt="EquestriaGaming" title="EquestriaGaming" border=
"0" /></a>
</span>
<span id="afspan3">
<a href="http://www.ponyremixplanet.com" target="_blank">
<img src="/plugins/Affiliates/PonyMixCentral.png?1" alt="Pony Mix Central" title="Pony Mix Central" border=
"0" /></a>
</span>
<span id="afspan4">
<a href="http://ponysquare.com" target="_blank">
<img src="/plugins/Affiliates/PonySquare.png?1" alt="Ponysquare" title="Ponysquare" border=
"0" /></a>
</span>
</span>

EOT
    	);

    	$action->elementEnd('div');
    }
}
