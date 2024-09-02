<?php
/*
Plugin Name: Newsfeed Token With Username
Description: Protect RSS and Atom feeds by requiring a token which contains username
Version: 1.0
Author: Dan W and LLM
*/

// Function to generate a token
function generate_token($username) {
    $unique_token = bin2hex(random_bytes(16)); // Generate a unique token
    return $username . '_' . $unique_token;    // Combine username and unique token
}

// Generate and assign a token to new users upon registration
function add_user_newsfeed_token($user_id) {
    $user = get_userdata($user_id);
    $username = $user->user_login; // Get the username
    $token = generate_token($username);
    update_user_meta($user_id, 'user_newsfeed_token', $token);
}
add_action('user_register', 'add_user_newsfeed_token');

// Assign tokens to existing users or regenerate if missing
function add_tokens_to_existing_users() {
    $users = get_users();
    foreach ($users as $user) {
        $token = get_user_meta($user->ID, 'user_newsfeed_token', true);
        if (empty($token)) { // Check if the token is missing
            $username = $user->user_login; // Get the username
            $token = generate_token($username);
            update_user_meta($user->ID, 'user_newsfeed_token', $token);
        }
    }
}
add_action('init', 'add_tokens_to_existing_users');

// Validate the token for feed access
function validate_feed_token() {
    if (is_feed()) {
        if (isset($_GET['t'])) {
            $token = sanitize_text_field($_GET['t']); // Sanitize the token input
            $user_query = new WP_User_Query(array(
                'meta_key' => 'user_newsfeed_token',
                'meta_value' => $token,
            ));
            if (empty($user_query->get_results())) {
                // Redirect to a custom error page without exposing details
                // wp_redirect(home_url('/'));  // homepage instead of 403 if did find matching user token
                wp_die('Access denied.', 'Error', array('response' => 403));
                exit;
            }
        } else {
            // Redirect to a custom error page without exposing details
            // wp_redirect(home_url('/'));  // homepage instead of 403 if user token not in URL
            wp_die('Access denied.', 'Error', array('response' => 403));
            exit;
        }
    }
}
add_action('template_redirect', 'validate_feed_token');

// Generate the user feed URLs with token
function user_feed_urls($user_id) {
    $user = get_userdata($user_id);
    $username = $user->user_login; // Get the username
    $token = get_user_meta($user_id, 'user_newsfeed_token', true);
    
    // Regenerate token if missing
    if (empty($token)) {
        $token = generate_token($username);
        update_user_meta($user_id, 'user_newsfeed_token', $token);
    }
    
    if (!empty($token)) {
        // Below, a5a92a7c2067594d50b68cfb160db67f is from the Authenticator plugin by Inpsyde GmbH
        // with the Wordpress > Settings > Reading > Authenticator Options > Token Authentication 
        // setting set.  Therefore:
        //   1. change this sitewide-token below whenever this whole-site auth token changes or
        //   2. remove the sitewide-token lines altogether if Authenticator plugin is not used.

        $rss_url = add_query_arg(array(
            'a5a92a7c2067594d50b68cfb160db67f' => '',
            't' => urlencode($token),
        ), get_feed_link('rss2'));
        $atom_url = add_query_arg(array(
            'a5a92a7c2067594d50b68cfb160db67f' => '',
            't' => urlencode($token),
        ), get_feed_link('atom'));
        return array('rss' => $rss_url, 'atom' => $atom_url);
    }
    return array('rss' => '', 'atom' => '');
}

// Shortcode to display the user's RSS feed URL
// The shortcode [user_rss_feed_url] can be used in the Wordpress template files where RSS feeds are linked.
function show_user_rss_feed_url($atts, $content = null) {
    $user_id = get_current_user_id();
    if ($user_id) {
        $urls = user_feed_urls($user_id);
        return $urls['rss'];
    }
    return 'You must be logged in to view your feed URL.';
}
add_shortcode('user_rss_feed_url', 'show_user_rss_feed_url');

// Shortcode to display the user's Atom feed URL
// The shortcode [user_atom_feed_url] can be used in the Wordpress template files where Atom feeds are linked.
function show_user_atom_feed_url($atts, $content = null) {
    $user_id = get_current_user_id();
    if ($user_id) {
        $urls = user_feed_urls($user_id);
        return $urls['atom'];
    }
    return 'You must be logged in to view your feed URL.';
}
add_shortcode('user_atom_feed_url', 'show_user_atom_feed_url');

// START adding Admin page and tool to reset a user token manually
// Add a menu item under "Tools" in the admin dashboard
function custom_newsfeed_token_management_menu() {
    add_submenu_page(
        'tools.php',                    // Parent slug
        'Feed Token Admin',             // Page title
        'Feed Token Admin',             // Menu title
        'manage_options',               // Menu title 
        'token-management',             // Capability required to see this submenu
        'custom_token_management_page'  // Function to display the page content
    );
}
add_action('admin_menu', 'custom_newsfeed_token_management_menu');

// Create the admin page for token management
function custom_token_management_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        delete_user_meta($user_id, 'user_newsfeed_token');
        echo '<div class="updated"><p>Newsfeed Token cleared for user ID: ' . esc_html($user_id) . '.</p></div>';
    }

    ?>
    <div class="wrap">
        <h2>Feed Token Admin</h2>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="user_id">User ID</label></th>
                    <td><input name="user_id" type="number" id="user_id" value="" class="regular-text" required></td>
                </tr>
            </table>
            <?php submit_button('Clear Newsfeed Token'); ?>
        </form>
    </div>
    <?php
}
// END adding Admin tool to clear tokens
