<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Groups with the most members section
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
 * @category  Widget
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

/**
 * Groups with the most members section
 *
 * @category Widget
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

define('MAXLEN', 250);

class GroupsByMembersSection extends GroupSection
{

    function getGroups()
    {
        $qry = 'SELECT * ' .
            'FROM group_inbox JOIN user_group '.
          'ON user_group.id = group_inbox.group_id ' .
          'JOIN notice ' .
          'ON group_inbox.notice_id = notice.id ' .
          'WHERE group_inbox.created > DATE_SUB(NOW(), INTERVAL 1 MONTH) ' .
          'ORDER BY RAND()';

        $limit = GROUPS_PER_SECTION;
        $offset = 0;

        if (common_config('db','type') == 'pgsql') {
            $qry .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        } else {
            $qry .= ' LIMIT ' . $offset . ', ' . $limit;
        }

        $group = Memcached_DataObject::cachedQuery('User_group',
                                                   $qry,
                                                   3600);
        return $group;
    }

    function showGroup($group)
    {
        $this->out->elementStart('li', 'hentry notice');
        $this->out->elementStart('div', 'entry-title');
        $this->out->elementStart('span', 'vcard');
        $this->out->elementStart('a', array('title' => ($group->fullname) ?
                                            $group->fullname :
                                            $group->nickname,
                                            'href' => $group->homeUrl(),
                                            'rel' => 'contact group',
                                            'class' => 'url'));
        $this->out->text(' ');
        $logo = ($group->stream_logo) ?
          $group->stream_logo : User_group::defaultLogo(AVATAR_STREAM_SIZE);
        $this->out->element('img', array('src' => $logo,
                                         'width' => AVATAR_MINI_SIZE,
                                         'height' => AVATAR_MINI_SIZE,
                                         'class' => 'avatar photo',
                                         'alt' =>  ($group->fullname) ?
                                         $group->fullname :
                                         $group->nickname));
        $this->out->text(' ');
        $this->out->element('span', 'fn org nickname', $group->nickname);
        $this->out->elementEnd('a');
        $this->out->elementEnd('span');

        $this->out->elementStart('p', 'entry-content');
        $this->out->raw($group->rendered);
        $this->out->elementEnd('p');

        $this->out->elementStart('div', 'entry_content');
        class_exists('NoticeList');
        $nli = new NoticeListItem(Notice::staticGet($group->notice_id), $this->out);
        $nli->showNoticeLink();
        $this->out->elementEnd('div');
        $this->out->elementEnd('div');
        $this->out->elementEnd('li');
    }

    function title()
    {
        // TRANS: Title for groups with the most members section.
        return _('Recently active groups');
    }

    function divId()
    {
        return 'top_groups_by_member';
    }
}
