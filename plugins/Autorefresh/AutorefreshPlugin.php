<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Plugin to enable nickname completion in the enter status box
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
 * @author    Craig Andrews <candrews@integralblue.com>
 * @copyright 2010 Free Software Foundation http://fsf.org
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

class AutorefreshPlugin extends Plugin
{
    function __construct()
    {
        parent::__construct();
    }


    function onEndShowHeadElements($action) {
	if(is_subclass_of($action,'ProfileAction'))
		$action->element('meta',array('http-equiv'=>'refresh','content'=>'10'));

    }

    function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Autorefresh',
                            'version' => STATUSNET_VERSION,
                            'author' => 'Michele Azzolari',
                            'homepage' => 'http://status.net/wiki/Plugin:Autocomplete',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('Autorefresh plugin refresh the page after n seconds. A trivial substitue of Meteor server'));
        return true;
    }
}
