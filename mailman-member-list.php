<?php
/**
 * Plugin Name: Mailman Member List
 * Plugin URI: http://github.com/hooverlunch/mailman-member-list
 * Description: Fetches mailman lists and their members using mailman shell commands.
 * Version: 0.1
 * Author: Tom Smyth
 * Author URI: http://sassafras.coop
 * License: GPL2
 */

/*  Copyright 2015 Tom Smyth (email: tom@sassafras.coop)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

  define('MAILMAN_BIN_PATH', '/home/tsmailman/mailman/bin');

  // Given string of form '   list-name - List description', returns array of form ['list-name', 'List Description']
  function parse_list_name_description($str) {
    preg_match('/^\\s+([^\\s]+)\\s+-\\s+(.+?)\\s*$/', $str, $matches);
    return array_slice($matches, 1);
  }

  // Returns an array of form [['list1-name', 'List 1 description'], ['list2-name', 'List 2 description']]
  function get_lists() {
    exec(MAILMAN_BIN_PATH . '/list_lists', $lists);

    // First line is intro text.
    $lists = array_slice($lists, 1);

    return array_map('parse_list_name_description', $lists);
  }

  // Returns an array of form ['email1@example.com', 'Jane Doe'], or ['email1@example.com'] if no name present.
  function parse_member_name_email($str) {
    if (preg_match('/^(.+?)\s+<(.+)>$/', $str, $matches) === 1)
      return array($matches[2], $matches[1]);
    else
      return array($str);
  }

  // Returns an array of form [['email1@example.com', 'Jane Doe'], ['email2@example.com', 'John Doe'], ['email2@example.com']]
  // Note that second element of sub array, person name, may not be present.
  function get_members($list_name) {
    exec(MAILMAN_BIN_PATH . "/list_members -f $list_name", $members);
    return array_map('parse_member_name_email', $members);
  }

  // Prints an HTML string consisting of list names, descriptions, and members for all lists on the system.
  function get_all_members() {
    $lists = get_lists(); ?>

    <div id="mailman-lists">

<?php foreach($lists as $list) { ?>
      <div class="list">
        <div class="list-name"><?php echo $list[0] ?></div>
        <div class="list-description"><?php echo $list[1] ?></div>
        <ul>
  <?php $members = get_members($list[0]);
        foreach($members as $member) { ?>
          <li>
            <?php if (count($member) == 1) { ?>
              <div class="member-name no-name"></div>
            <?php } else { ?>
              <div class="member-name"><?php echo $member[1]; ?></div>
            <?php } ?>
            <div class="member-email"><?php echo $member[0]; ?></div>
          </li>
  <?php } ?>
        </ul>
      </div>
<?php } ?>

    </div>

<?php
  }

  add_shortcode( 'mailman-members', 'get_all_members' );
?>