<?php
/*
Plugin Name: ShareUsers
Plugin URI: http://shareusers.net/plugins/WP/
Description: ShareUsers Plugin for WordPress
Author: Niuma (niuma@niumasoft.com)
Version: 1.0
Author URI: http://shareusers.net/
*/
 
/*  
    Copyright 2009  Niuma Soft (email : niuma@niumasoft.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$shareusers_version = "1.0";

class ShareUsers {
    /*
     * Prefix for tables in DB
     */
    var $prefix = 'shareusers_';
    
    /*
     * This blog's hostname
     */
    var $host;
    
    /*
     * Destination URL's format to use in sprintf
     */
    var $link_url_format = 'http://%s.shareusers.net/go/%s/%s';
    
    /*
     * URL format to get remote keywords for this site (to use in sprintf)
     */
    var $remote_url_format = 'http://shareusers.net/sites/output/site/%s';
    
    /*
     * Array of keywords to be linked
     */
    var $link_keywords;
    
    /*
     * Result of keywords update
     */
    var $update_res = NULL;
    
    /*
     * Result of keywords remove
     */
    var $remove_res = NULL;
    
    /*
     * Database keywords table name
     */
    var $table_name;
    
    /*
     * Public URL
     */
    var $public_url;
    
    function ShareUsers() {
        // Instantiation of the wpdb class already set up to talk to the WordPress database
        // See: http://codex.wordpress.org/Function_Reference/wpdb_Class
        global $wpdb;
        $this->table_name = $wpdb->prefix. $this->prefix. 'keywords';
        // Public URL
        $this->public_url = WP_PLUGIN_URL. '/'. dirname(plugin_basename(__FILE__));
        // Load translation
        load_plugin_textdomain('shareusers', FALSE, dirname(plugin_basename(__FILE__)));
        // Register actions depending on the app section
        if (is_admin()) {
            // Define Install / Uninstall methods
            register_activation_hook( __FILE__, array(&$this, 'install'));
    	    register_deactivation_hook( __FILE__, array(&$this, 'uninstall'));
            add_action('admin_menu', array(&$this, 'menu'));
//            add_action('admin_init', array(&$this, 'register_settings'));
        } else {
            if (get_option('shareusers_autolink')) {
                add_action('the_content', array(&$this, 'autoLink'));
            }            
        }
        // Get the blog's URL, clean it and save the hostname
        $home = get_option('home');
        $parts = parse_url($home);
        $this->host = $parts['host'];
        // Remove keywords?
        if (get_option('shareusers_remove_now')) {
            update_option('shareusers_remove_now', FALSE);
            $this->remove_res = $this->removeKeywords();
        }
        // Update now?
        if (get_option('shareusers_update_now')) {
            update_option('shareusers_update_now', FALSE);
            $this->update_res = $this->updateKeywords();
        }
        $frequence = get_option('shareusers_frequence');
        if (empty($frequence)) {
            // Default value
            $frequence = 'once_a_day';
            add_option('shareusers_frequence', $frequence);
        }
        switch ($frequence) {
            case 'manually':        break;
            case 'once_a_day':      $last_update = (int) get_option('shareusers_last_update');
                                    $now         = time();
                                    $last_day    = date('Ymd', $last_update);
                                    $today       = date('Ymd', $now);
                                    if ($today > $last_day) {
                                        $this->updateKeywords();
                                    }
                                    break;
            case 'every_n_minutes': $minutes     = (int) get_option('shareusers_minutes');
                                    update_option('shareusers_minutes', $minutes);
                                    $last_update = (int) get_option('shareusers_last_update');
                                    $now         = time();
                                    if (($now - $last_update) > ($minutes * 60)) {
                                        $this->updateKeywords();
                                    }
                                    break;
            case 'always':          $this->updateKeywords();
                                    break;
        }
        // Default values for link style, color and underline
        $style = get_option('shareusers_style');
        if (empty($style)) add_option('shareusers_style', 'none');
        $color = get_option('shareusers_color');
        if (empty($color)) add_option('shareusers_color', '#0000FF');        
        $underline = get_option('shareusers_underline');
        if (empty($underline)) add_option('shareusers_underline', TRUE);
    }

    function getRemoteKeywords() {
        // Results array
        $results = array();
        // Load xml file
        $dom = DOMDocument::load(sprintf($this->remote_url_format, urlencode($this->host)));
        if (($dom instanceof DOMDocument) && ($keywords = $dom->getElementsByTagName('KEYWORD'))) {
            for ($i = 0; $i < $keywords->length; $i++) {
                if ($keywords->item($i)->hasChildNodes()) {
                    $result = array();
                    foreach ($keywords->item($i)->childNodes as $node) {
                        switch ($node->nodeName) {
                            case 'MAIN':        $result['main']        = $node->nodeValue;
                                                break;
                            case 'SECOND':      $result['second']      = $node->nodeValue;
                                                break;
                            case 'DESCRIPTION': $result['description'] = $node->nodeValue;
                                                break;
                            case 'LANGUAGE':    $result['language']    = $node->nodeValue;
                                                break;
                        }
                    }
                    $results[] = $result;
                }
            }
        }
        return $results;
    }   

    function saveKeywords(array $keywords) {
        if (count($keywords)) {
            global $wpdb;
            $sql = 'INSERT INTO `'. $this->table_name. '` VALUES ';
            $tmp = array();
            foreach ($keywords as $keyword) {
                $tmp[] = "(NULL, '". $wpdb->escape($keyword['main']). "', '". $wpdb->escape($keyword['second']). "', '". $wpdb->escape($keyword['description']). "', '". $wpdb->escape($keyword['language']). "')";
            }
            $sql .= join(', ', $tmp);
            return $wpdb->query($sql);
        } else {
            return TRUE;
        }
    }

    function removeKeyword($id) {
        global $wpdb;
        // Empty the table
        $sql = 'DELETE FROM `'. $this->table_name. '` WHERE id = '. (int) $id. ';';
        return $wpdb->query($sql);
    }

    function removeKeywords() {
        global $wpdb;
        // Empty the table
        $sql = 'TRUNCATE TABLE `'. $this->table_name. '`;';
        $wpdb->query($sql);
        return $wpdb->rows_affected;
    }

    function updateKeywords() {
        $this->removeKeywords();
        // Get website's output keywords and store them in the database
        $keywords = $this->getRemoteKeywords();
        $res = $this->saveKeywords($keywords);
        update_option('shareusers_last_update', time());
        return $res;
    }
    
    function install() {
        global $wpdb;
        // Create an empty table
	    $sql = 'CREATE TABLE IF NOT EXISTS `'. $this->table_name. '` (
            `id` int(11) unsigned NOT NULL auto_increment,
            `main` varchar(31) NOT NULL,
            `second` varchar(31) default NULL,
            `description` text NOT NULL,
            `language` varchar(2) NOT NULL,
            PRIMARY KEY  (`id`)
	    );';
        $wpdb->query($sql);
        // Empty the table
        $this->removeKeywords();
        // Update keywords?
        if (get_option('shareusers_frequence') == 'only_once') {
            $this->updateKeywords();
        }
        // Register version number
        global $shareusers_version;
        add_option('shareusers_version', $shareusers_version);
        // Set by default
        add_option('shareusers_autolink', TRUE);
    }
    
    function uninstall() {
        global $wpdb;
        // Delete table, if had ever been created        
        $sql = 'DROP TABLE IF EXISTS `'. $this->table_name. '`;';
	    $wpdb->query($sql);
    }
    
    function menu() {
        add_options_page(__('ShareUsers Options', 'shareusers'), 'ShareUsers', 8, __FILE__, array(&$this, 'options'));
        add_management_page(__('ShareUsers: Keywords Management', 'shareusers'), 'ShareUsers', 8, __FILE__, array(&$this, 'manage'));
    }

    function register_settings() {
//        register_setting('options', 'shareusers_frequence');
    }
    
    function options() {
        include('shareusers-options.php');
    }

    function manage() {
        include('shareusers-manage.php');
    }

    function _getKwLink($main, $second, $lang) {
        return sprintf($this->link_url_format, $lang, urlencode(utf8_decode($main)), urlencode(utf8_decode($second)));
    }

    function prepareAutoLink() {
        global $wpdb;
        // Initialize
        $this->link_keywords = array();
        // Get keywords from database (match longest keywords first)
        $sql = 'SELECT * FROM `'. $this->table_name. '` ORDER BY LENGTH(main) DESC;';
        if ($results = $wpdb->get_results($sql)) {
            foreach ($results as $result) {
                $this->link_keywords[] = array(
                    'term'        => $result->main, 
                    'link'        => $this->_getKwLink($result->main, $result->second, $result->language), 
                    'description' => $result->description, 
                );
            }
        }
    }

	/**
	 * Replace text by link to tag
	 *
	 * @param string $content
	 * @return string
	 */
	function autoLink($content = '') {
		// Get currents keywords if no exists
		if ( $this->link_keywords == null ) {
			$this->prepareAutoLink();
		}
		
		// only continue if the database actually returned any links
		if (is_array($this->link_keywords) && count($this->link_keywords) > 0 ) {
		
			$must_tokenize = TRUE; // will perform basic tokenization
			$tokens = NULL; // two kinds of tokens: markup and text

			$case = 'i';

            $target = (get_option('shareusers_newwindow') ? ' target="_blank"' : '');
            $style  = get_option('shareusers_style');
            switch ($style) {
                case 'none':  $class = '';
                              break;
                case 'class': $class = ' class="'. get_option('shareusers_class'). '"';
                              break;
                case 'user':  $class_name = 'shareusers_link';
                              $class = ' class="'. $class_name. '"';
                              // Define css style
                              $decoration = get_option('shareusers_underline') ? 'underline' : 'none';
                              $css = "<style>\n.". $class_name. " {\n". 
                              '  text-decoration: '. $decoration. ";\n". 
                              '  color: '. get_option('shareusers_color'). ";\n". 
                              "}\n</style>\n";
                              // Prepent to content
                              $content = $css. $content;
                              break;
            }
			foreach ($this->link_keywords as $keyword) {
				$filtered = ""; // will filter text token by token
				$match = "/\b" . preg_quote($keyword['term'], "/") . "\b/". $case;
				$substitute = '<a href="'. $keyword['link']. '"'. $target. $class. ' title="'. attribute_escape($keyword['description']) ."\">$0</a>";

				// for efficiency only tokenize if forced to do so
				if ( $must_tokenize ) {
					// this regexp is taken from PHP Markdown by Michel Fortin: http://www.michelf.com/projects/php-markdown/
					$comment = '(?s:<!(?:--.*?--\s*)+>)|';
					$processing_instruction = '(?s:<\?.*?\?>)|';
					$tag = '(?:<[/!$]?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)';

					$markup = $comment . $processing_instruction . $tag;
					$flags = PREG_SPLIT_DELIM_CAPTURE;
					$tokens = preg_split("{($markup)}", $content, -1, $flags);
					$must_tokenize = FALSE;
				}

				// there should always be at least one token, but check just in case
				if ( isset($tokens) && is_array($tokens) && count($tokens) > 0 ) {
					$i = 0;
					foreach ($tokens as $token) {
						if (++$i % 2 && $token != '') { // this token is (non-markup) text
                            $token = html_entity_decode($token, NULL, get_option('blog_charset'));
							if ($anchor_level == 0) { // linkify if not inside anchor tags
								if ( preg_match($match, $token) ) { // use preg_match for compatibility with PHP 4
									$token = preg_replace($match, $substitute, $token); // only PHP 5 supports calling preg_replace with 5 arguments
									$must_tokenize = TRUE; // re-tokenize next time around
								}
							}
//                            $token = htmlentities($token, NULL, DB_CHARSET);
						}
						else { // this token is markup
							if ( preg_match("#<\s*a\s+[^>]*>#i", $token) ) { // found <a ...>
								$anchor_level++;
							} elseif ( preg_match("#<\s*/\s*a\s*>#i", $token) ) { // found </a>
								$anchor_level--;
							}
						}
						$filtered .= $token; // this token has now been filtered
					}
					$content = $filtered; // filtering completed for this link
				}
			}
		}

		return $content;
	}
}

$shareusers = new ShareUsers();

