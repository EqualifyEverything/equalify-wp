<?php
/**
 * Plugin Name: Equalify
 * Description: Display WCAG 2 AA accessibility errors via Little Forrest's API.
 * Author: Blake Bertuccelli
 * Author URI: https://github.com/bbertucc
 * Version: 0.0.1
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: equalify
 * 
 * @package Equalify
 */

 /*
 LICENSE===================================================================
 Equalify is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 2 of the License, or
 any later version. More info: https://www.gnu.org/licenses/gpl-2.0.html/

 DEV PHILOSOPHY==============================================================
 Create the thing. Deliver the thing. Develop the thing. With minimal code.
*/

/**
 * View
 * 
 * @package Equalify
 * @subpackage EqualifyWPView
 */
function equalify_wp_view() {

    /**
     * Create Tab
     * 
     * @package Equalify
     * @subpackage EqualifyWPView
     */
    function add_equalify_admin_page() {

        // Add top level menu page.
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

    /**
     * Create Tab
     * 
     * @package Equalify
     * @subpackage EqualifyWPView
     */
    function equalify_admin_page_html() {
                
        ?>        
        <div id="equalify" class="wrap">

            <h1>
                <?php echo esc_html( get_admin_page_title() ); ?>
            </h1>
            <hr />

            <?php 
            // Loop through posts.
            $posts = get_posts(['meta_key' => 'equalify_wcag_errors', 'numberposts' => -1, 'post_type' => ['post','page']]);
            if(!empty($posts)):
                echo '<table><tr><th scope="col">Title</th><th scope="col">WCAG 2 AA Errors</th>';
                foreach ($posts as $post):
                    echo '<tr><td>'.$post->post_title.'</td><td><a href="https://inspector.littleforest.co.uk/InspectorWS/Inspector?url='.get_permalink($post->ID).'&lang=auto" target="_blank">'.get_post_meta($post->ID, 'equalify_wcag_errors', true).'</a></td>';
                endforeach;
                echo '</table>';
            else:
                echo '<p>Either you have no posts, or the site hasn\'t been equalified.';
            endif;
            ?>

            <hr />

            <?php
            // Send AJAX request using basic WP for reasons explained here: https://www.youtube.com/watch?v=OwBBxwmG49w)
            ?>

            <form id="equalify-trigger">
                <input type="hidden" name="action" value="equalify" />
                <button id="trigger-button" type="submit" class="button button-primary">Equalify Site</button>
                <svg id="trigger-loader" style="display:none; width:50px; height: 50px; display:none; margin-top: -10px" version="1.1" id="L9" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve">
                    <path fill="#000" d="M73,50c0-12.7-10.3-23-23-23S27,37.3,27,50 M30.9,50c0-10.5,8.5-19.1,19.1-19.1S69.1,39.5,69.1,50">
                    <animateTransform 
                        attributeName="transform" 
                        attributeType="XML" 
                        type="rotate"
                        dur="1s" 
                        from="0 50 50"
                        to="360 50 50" 
                        repeatCount="indefinite" />
                    </path>
                </svg>
            </form>
            <script>
                const equalifyTrigger = document.getElementById('equalify-trigger');

                if (equalifyTrigger) {

                    equalifyTrigger.addEventListener('click', (e) => {
                        e.preventDefault();
                        document.querySelector('#trigger-button').disabled = 'disabled';
                        document.querySelector('#trigger-loader').style.display = 'inline-block';
                        fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams(new FormData(equalifyTrigger))

                        }).then(response => {

                            return response.json();

                        }).then(jsonResponse => {
                            document.querySelector('#trigger-loader').style.display = 'none';
                            document.querySelector('#trigger-button').disabled = false;
                            location.reload();
                        });

                    });

                }
            </script>
        </div>

        <?php
    }

}
equalify_wp_view();


/**
 * Enqueue Scripts
 * 
 * @package WordPress
 */
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script( 'admin-ajax', get_stylesheet_directory_uri() . '/assets/admin-ajax.js', '', '', true );
});

/**
 * Equalify
 * 
 * @package Equalify
 */
function equalify() {

    // Override file_get_contents() security - https://stackoverflow.com/questions/26148701/file-get-contents-ssl-operation-failed-with-code-1-failed-to-enable-crypto.
    $override_https = array(
        "ssl"=>array(
            "verify_peer"=> false,
            "verify_peer_name"=> false,
        )
    );
    
    // Loop through posts.
    $posts = get_posts(['post_type' => ['post','page'], 'numberposts' => -1 ]);
    foreach ($posts as $post):

        // Get Little Forrest page errors.
        $little_forrest_url = 'https://inspector.littleforest.co.uk/InspectorWS/Accessibility?url='.get_permalink($post->ID).'&level=WCAG2AA';
        $little_forrest_json = file_get_contents($little_forrest_url, false, stream_context_create($override_https));
        $little_forrest_json_decoded = json_decode($little_forrest_json, true);
        $little_forrest_errors = count($little_forrest_json_decoded['Errors']);

        // Update post meta.
        update_post_meta( $post->ID, 'equalify_wcag_errors', $little_forrest_errors);
    
    endforeach;
        
    // Debug Ajax.
    $api_response_body = wp_remote_retrieve_body($api_response);  
    wp_send_json_success([$api_response_body, $_REQUEST]);

}
add_action( 'wp_ajax_equalify', 'equalify' );
