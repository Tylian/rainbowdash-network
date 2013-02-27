#!/usr/bin/env php
<?php
/*
 * StatusNet - a distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
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
 */

define('INSTALLDIR', realpath(dirname(__FILE__) . '/../../..'));

$shortoptions = 't';
$longoptions = array('test');

$helptext = <<<END_OF_BACKFILTER_HELP
backfilter.php [options]
Goes back through the database and filters all the notices

  -t --test Test the filter without filtering anything

END_OF_BACKFILTER_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

if (!have_option('t', 'test')) {
    print "About to PERMANENTLY filter all notice records. Are you sure? [y/N] ";
    $response = fgets(STDIN);
    if (strtolower(trim($response)) != 'y') {
        print "Aborting.\n";
        exit(0);
    }
}

print "Updating records...\n";
$plugin = new NoSwearPlugin();

$notice = new Notice();
$notice->find();
while($notice->fetch()) {
    if(!have_option('t', 'test')) {
        $orig = clone($notice);
        $plugin->onStartNoticeSave(&$notice);
        if($orig->content != $notice->content) {
            $notice->update($orig);
        }
    }
    else {
        $orig = clone($notice);
        $plugin->onStartNoticeSave(&$notice);
        if($orig->content != $notice->content) {
            print $orig->content . "\n";
            print $notice->content . "\n";
            print "Press ENTER to continue...\n";
            $response = fgets(STDIN);
        }
    }
}

print "DONE.\n";
