<?php
/*
Plugin Name: Theme Reset
Plugin URI: http://www.stillbreathing.co.uk/wordpress/theme-reset/
Description: Resets the theme in all sites in a MultiSite network.
Version: 0.1
Author: Chris Taylor
Author Email: mrwiblog@gmail.com
License:

  Copyright 2011 Chris Taylor (mrwiblog@gmail.com)

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

class ThemeReset {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'Theme Reset';
	const slug = 'theme_reset';
	
	/**
	 * Constructor
	 */
	function __construct() {
		//Hook up to the init action
		add_action( 'init', array( &$this, 'init_theme_reset' ) );
	}
  
	/**
	 * Runs when the plugin is activated
	 */  
	function install_theme_reset() {
		// do not generate any output here
	}
  
	/**
	 * Runs when the plugin is initialized
	 */
	function init_theme_reset() {
		// Setup localization
		load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

		/*
		 * Add the page item to the network admin menu
		 *
		 * For more information: 
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( 'network_admin_menu', array( &$this, 'add_network_admin_menu_item' ) );  
	}

        /**
	 * Adds an item to the themes menu of the network admin menu
	 */
	function add_network_admin_menu_item() {
		add_submenu_page( 'themes.php', self::name, self::name, 'manage_sites', self::slug, array( &$this, 'admin_page' ) );
	}
  
	/**
	 * Shows the page to reset the themes
	 */
	function admin_page() {
            
            // get all themes
            $themes = wp_get_themes();
            
            echo '
                <h2>Theme Reset</h2>
            ';
            
            if ( count( $_POST ) != 0 ) {
                if ( check_admin_referer( 'reset-themes' ) ) {
                    $this->reset_themes( $_POST['theme'] );
                } else {
                    echo '
                        <div class="error">
                            <p>Invalid request. Try again.</p>
                        </div>
                    ';
                }
            }
            
            echo '
                <form action="themes.php?page=theme_reset" method="post">
                <fieldset>
                    <p>Reset all sites in the network to use this theme:</p>
                    <p>
                        <select name="theme">
            ';

            // loop themes
            foreach ( $themes as $theme ) {
                echo '<option value="' . $theme[ "Template" ] . '">' . $theme->get( 'Name' ) . '</option>
                ';
            }

            echo '
                        </select>
                    </p>
                    <p><input type="submit" class="button button-primary" value="Reset themes"></p>
            ';

            wp_nonce_field( 'reset-themes' );

            echo '
                </fieldset>
                </form>
            ';
	}
        
        /**
	 * Reset the theme for all sites
	 */
        function reset_themes( $theme ) {
            global $wpdb;
            
            // get the current blog ID, we don't want to change the theme for it
            $current_blog_id = get_current_blog_id();
            
            // get the blog IDs
            $blogs = $wpdb->get_col( "SELECT blog_id FROM `" . $wpdb->blogs . "` WHERE public = '1' AND archived = '0' AND mature = '0' AND spam = '0' ORDER BY blog_id DESC" );
            
            echo '
            <div class="success">
                <ul>
            ';
            
            // get the number of blogs
            $blog_count = count( $blogs );
            
            foreach ( $blogs as $blog ) {
                
                // don't update the current blog
                if ( $current_blog_id == $blog ) {
                    continue;
                }
                
                // get the blog details
                $blog_details = get_blog_details( $blog, true );
                
                // update the options
                update_blog_option( $blog, 'template', $theme );
                update_blog_option( $blog, 'stylesheet', $theme );
                
                // for small networks show the names and links to the updated blogs
                if ( $blog_count < 250 ) {
                
                echo '
                    <li>Updated <a href="http://' . $blog_details->domain . $blog_details->path . '">' . $blog_details->blogname . '</a></li>
                ';
                
                }
            }
            
            // for large networks just show the number of updated blogs
            if ( $blog_count >= 250 ) {
                
                echo '
                    <li>Updated ' . $blog_count . ' blogs</li>
                ';
                
            }
            
            echo '
                </ul>
            </div>
            ';
            
        }
  
} // end class
new ThemeReset();
?>