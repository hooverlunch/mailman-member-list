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

require_once(WP_PLUGIN_DIR . '/mailman-member-list/settings-page.php');

define('MAILMAN_BIN_PATH', '/home/tsmailman/mailman/bin');

class MailmanMemberList {

  static function init() {
    add_shortcode( 'mailman-members', array( __CLASS__, 'get_all_members' ) );
    if( is_admin() ) new MailmanMemberListSettingsPage();
  }

  // Prints an HTML string consisting of list names, descriptions, and members for all lists on the system.
  static function get_all_members() {
    $lists = self::get_lists();
    $html = array();

    $html[] = '<div id="mailman-lists">';

    foreach($lists as $list) {

      $html[] = <<<HTML
  <div class="list">
    <div class="list-name">{$list[0]}</div>
    <div class="list-description">{$list[1]}</div>
    <ul>
HTML;

      $members = self::get_members($list[0]);

      foreach($members as $member) {

        $html[] = '<li>';

        if (count($member) == 1) {
          $html[] = <<<HTML
      <div class="member-name no-name"></div>
HTML;
        } else {
          $html[] = <<<HTML
      <div class="member-name">{$member[1]}</div>
HTML;
        }

        $html[] = <<<HTML
      <div class="member-email">{$member[0]}</div>
    </li>
HTML;
      }
      $html[] = '</ul></div>';
    }

    $html[] = '</div>';
    echo implode("\n", $html);
  }

  // Given string of form '   list-name - List description', returns array of form ['list-name', 'List Description']
  static function parse_list_name_description($str) {
    preg_match('/^\\s+([^\\s]+)\\s+-\\s+(.+?)\\s*$/', $str, $matches);
    return array_slice($matches, 1);
  }

  // Returns an array of form [['list1-name', 'List 1 description'], ['list2-name', 'List 2 description']]
  private static function get_lists() {
    exec(MAILMAN_BIN_PATH . '/list_lists', $lists);

    // First line is intro text.
    $lists = array_slice($lists, 1);

    return array_map(array(__CLASS__, 'parse_list_name_description'), $lists);
  }

  // Returns an array of form ['email1@example.com', 'Jane Doe'], or ['email1@example.com'] if no name present.
  private static function parse_member_name_email($str) {
    if (preg_match('/^(.+?)\s+<(.+)>$/', $str, $matches) === 1)
      return array($matches[2], $matches[1]);
    else
      return array($str);
  }

  // Returns an array of form [['email1@example.com', 'Jane Doe'], ['email2@example.com', 'John Doe'], ['email2@example.com']]
  // Note that second element of sub array, person name, may not be present.
  private static function get_members($list_name) {
    exec(MAILMAN_BIN_PATH . "/list_members -f $list_name", $members);
    return array_map(array(__CLASS__, 'parse_member_name_email'), $members);
  }
}

MailmanMemberList::init();
?>
