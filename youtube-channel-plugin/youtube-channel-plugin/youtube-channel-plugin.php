<?php
/*
Plugin Name: YouTube Channel Plugin
Description: Plugin to get YouTube channel ID from channel name.
*/

// Register shortcode
function youtube_channel_shortcode() {
    ob_start();
    ?>
    <form id="youtube-channel-form">
        <input type="text" id="youtube-channel-name" placeholder="Enter YouTube Channel Name">
        <button type="button" id="youtube-channel-submit">Get Channel ID</button>
    </form>
    <div id="youtube-channel-result"></div>
    <?php
    return ob_get_clean();
}
add_shortcode('youtube_channel', 'youtube_channel_shortcode');

// Enqueue necessary scripts
function youtube_channel_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('youtube-channel-script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'youtube_channel_enqueue_scripts');
