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

function add_youtube_video_link_meta_box() {
    add_meta_box(
        'youtube_video_link_meta_box',
        'YouTube Video Link',
        'display_youtube_video_link_meta_box',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_youtube_video_link_meta_box');
function display_youtube_video_link_meta_box($post) {
    $youtube_video_link = get_post_meta($post->ID, 'youtube_video_link', true);
    ?>
    <label for="youtube_video_link">YouTube Video Link:</label>
    <input type="text" id="youtube_video_link" name="youtube_video_link" value="<?php echo esc_attr($youtube_video_link); ?>" style="width: 100%;">
    <?php
}
function prefix_add_youtube_top_of_the_post ($content){
    $post_id = get_the_ID();
	$youtube_video_link = get_post_meta($post_id, 'youtube_video_link', true);
    if (!empty($youtube_video_link)) {
        $video_id= get_youtube_thumbnail($youtube_video_link);
        $content = "<iframe width='100%' height='415' src='https://www.youtube.com/embed/$video_id' title='YouTube video player' frameborder='0' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share' allowfullscreen></iframe>".$content;
    }
	return $content;
}
add_filter ('the_content', 'prefix_add_youtube_top_of_the_post');
