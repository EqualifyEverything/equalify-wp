<?php
/**
 * Plugin Name: Equalify
 * Description: Display WCAG accessibility scores via Little Forrest's API.
 * Author: Blake Bertuccelli
 * Author URI: https://github.com/bbertucc
 * Version: 0.0.1
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: equalify
 */

 /*

 LICENSE===================================================================
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

 DEV PHILOSOPHY==============================================================
 Create the thing. Deliver the thing. Develop the thing. 

*/

/**
 * Setup WordPress View
 */
equalify_wp_view();
function equalify_wp_view(){

    // Create Tab
    function add_equalify_admin_page(){

        // add top level menu page
        add_submenu_page(
            'tools.php',
            'Equalify',
            'Equalify',
            'administrator',
            'equalify',
            'equalify_admin_page_html'
        );

    }
    add_action( 'admin_menu', 'add_equalify_admin_page');

    // Create View
    function equalify_admin_page_html() {
                
        ?>        
        <div class="wrap">

            <h1>
                <?php echo esc_html( get_admin_page_title() ); 
?>
            </h1>
            <hr />

            <?php 
            // Loop through posts
            $posts = get_posts(['meta_key' => 'equalify_wcag_errors', 'post_type' => ['post','page']]);

            if(!empty($posts)):
                echo '<table><tr><th>Title</th><th>WCAG Errors</th>';
                foreach ($posts as $post):
                    echo '<tr><td>'.$post->post_title.'</td><td><a target="_blank" href="">'.get_post_meta($post->ID, 'equalify_wcag_errors', true).'</a></td>';
                endforeach;
                echo '</table>';
            else:
                echo '<p>Either you have no posts, or the site hasn\'t been equalified.';
            endif;
            ?>

            <hr />
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="equalify">
                <input type="hidden" name="data" value="<?php echo $_SERVER['REQUEST_URI'];?>">
                <input class="button button-primary" type="submit" value="Equalify Site" />
            </form>
        </div>

        <?php
    }

}

/**
 * Setup Controller 
 */
add_action( 'admin_post_equalify', 'equalify' );
function equalify() {

    // Override file_get_contents() security - https://stackoverflow.com/questions/26148701/file-get-contents-ssl-operation-failed-with-code-1-failed-to-enable-crypto
    $override_https = array(
        "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        )
    );
    
    // Loop through posts
    $posts = get_posts(['post_type' => ['post','page']]);
    foreach ($posts as $post):
        echo $post->guid;

        // Get Little Forrest Page Errors
        $little_forrest_url = 'https://inspector.littleforest.co.uk/TestWS/Accessibility?url='.$post->guid.'&level=WCAG2AA';
        $little_forrest_json = file_get_contents($little_forrest_url, false, stream_context_create($override_https));
        $little_forrest_json_decoded = json_decode($little_forrest_json, true);
        $little_forrest_errors = count($little_forrest_json_decoded['Errors']);

        //Update Post Meta
        update_post_meta( $post->ID, 'equalify_wcag_errors', $little_forrest_errors);
        
    
    endforeach;
        
    // Redirect to Plugin Page
    header('Location: '.$_REQUEST['data']); 
    die();
}
?>
