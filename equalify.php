<?php
/**
 * Plugin Name: Equalify
 * Description: WCAG Accessibility Testing via Little Forrest's API
 * Author: Blake Bertuccelli
 * Author URI: https://github.com/bbertucc
 * Version: 0.0.1
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: equalify
 */

 /*
Equalify is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Equalify is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Equalify. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

// drop a custom database table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mytable");

/**
 * Activitation Functions
 */
function equalify_activation () { 
    
    // Equalify
    equalify ();

    // Initialize  View
    initialize_equalify_view ();

}
register_activation_hook( __FILE__, 'equalify_activation' );

/**
 * Deactivitation Functions
 */
function equalify_deactivation () {

}
register_deactivation_hook( __FILE__, 'equalify_deactivation' );

/**
 * Uninstall Functions
 */
function equalify_unistall () { 

    // Delete the Page Meta

}
register_unistall_hook( __FILE__, 'equalify_unistall' );


/**
 * Equalify
 */
function equalify () {

    // Override file_get_contents() security - https://stackoverflow.com/questions/26148701/file-get-contents-ssl-operation-failed-with-code-1-failed-to-enable-crypto
    $fgc_override = array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            )
        );   

    // Loop Pages via Little Forrest API: https://inspector.littleforest.co.uk/TestWS/Accessibility?url=...&level=WCAG2AA
    $pages = get_pages();
    foreach ($pages as $page) {

        // Get Little Forrest Page Errors
        $little_forrest_url = 'https://inspector.littleforest.co.uk/TestWS/Accessibility?url='.$page->url.'&level=WCAG2AA';
        $little_forrest_json = file_get_contents($little_forrest_url, false, stream_context_create($arrContextOptions));
        $little_forrest_json_decoded = json_decode($little_forrest_json, true);
        $little_forrest_errors = count($little_forrest_json_decoded['Errors']);
        
        // Save Values to Page Meta
        add_post_meta($page->id, 'equalify_errors', $little_forrest_errors, true );

    }

}

/**
 * Initialize Admin View
 */
function initialize_equalify_view () {

    // On Admin Page..
    if ( is_admin() ) {

        // Create Tab
        function add_equalify_admin_page() {

            // add top level menu page
            add_menu_page(
                'equalify', //Page Title
                'Equalify', //Menu Title
                'manage_options', //Capability
                'equalify', //Page slug
                'admin_page_html', //Callback to print html
                'dashicons-yes' // Icon
            );
        }
        add_action( 'admin_menu', 'add_equalify_admin_page');

        // Create View

            // If test results exist, Page WCAG and Site Average

            // Return Button to "Run Test"

    }
}


?>
