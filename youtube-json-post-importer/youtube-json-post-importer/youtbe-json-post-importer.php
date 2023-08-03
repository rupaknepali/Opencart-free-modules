<?php
/*
Plugin Name: Youtube JSON To Wordpress Post Importer
Description: Import posts from a youtube JSON file, set featured images, assign to selected category and author.
*/

// Register the JSON Importer menu page
function youtube_video_importer_menu()
{
	add_menu_page(
		'Youtube Video Importer as Post',
		'Youtube Video Importer as Post',
		'manage_options',
		'youtube-video-importer',
		'youtube_video_importer_page',
		'dashicons-upload',
		99
	);
}
add_action('admin_menu', 'youtube_video_importer_menu');

// Display the JSON Importer page content
function youtube_video_importer_page()
{
	if (isset($_POST['submit'])) {
		// Verify the nonce for security
		if (!isset($_POST['json_importer_nonce']) || !wp_verify_nonce($_POST['json_importer_nonce'], 'json_importer_action')) {
			wp_die('Security check failed. Please try again.');
		}

		update_option('channel_id', sanitize_text_field($_POST['channel_id']));
		update_option('google_api_key', sanitize_text_field($_POST['google_api_key']));
		update_option('youtube_selected_category', intval($_POST['cat']));
		update_option('youtube_selected_author', intval($_POST['post_author']));


		$channel_api_url = "https://www.googleapis.com/youtube/v3/search?order=date&part=snippet&channelId=" . $_POST['channel_id'] . "&maxResults=50&key=" . $_POST['google_api_key'];
		$json_data = file_get_contents($channel_api_url);



		// Check if the JSON file is valid
		if (!$json_data) {
			echo 'Invalid JSON file';
			return;
		}

		$posts_data = json_decode($json_data, true);

		if ($posts_data) {
			$selected_category = $_POST['cat'];
			$selected_author = $_POST['post_author'];

			foreach ($posts_data['items'] as $post_data) {

				if (isset($post_data['id']['videoId'])) {
					if (!is_youtube_link_exists('https://www.youtube.com/watch?v=' . $post_data['id']['videoId'])) {
						$json_video_file = file_get_contents('https://www.googleapis.com/youtube/v3/videos?part=snippet&key=' . $_POST['google_api_key'] . '&id=' . $post_data['id']['videoId']);
						if (!$json_video_file) {
							echo 'Invalid JSON file';
							return;
						}
						$video_posts_data = json_decode($json_video_file, true);

						// Create a new post
						$new_post = array(
							'post_title' => $post_data['snippet']['title'],
							'post_content' => $video_posts_data['items'][0]['snippet']['description'],
							'post_status' => 'publish',
							'post_type' => 'post',
							'post_date' => $post_data['snippet']['publishedAt'], // Add post date from the JSON file
							'post_category' => array($selected_category), // Set the selected category
							'post_author' => $selected_author
						);
						$post_id = wp_insert_post($new_post);

						if (!is_wp_error($post_id)) {
							// Upload and set the featured image
							$image_url = $post_data['snippet']['thumbnails']['high']['url'];
							$image_data = file_get_contents($image_url);

							if ($image_data) {
								$upload_dir = wp_upload_dir();
								$image_name = basename($image_url);
								$upload_path = $upload_dir['path'] . '/' . $image_name;

								if (file_put_contents($upload_path, $image_data)) {
									$image_file = array(
										'name' => $post_data['id']['videoId'] . "-" . $image_name,
										'type' => wp_check_filetype($image_name)['type'],
										'tmp_name' => $upload_path,
										'error' => 0,
										'size' => filesize($upload_path)
									);
									$image_id = media_handle_sideload($image_file, $post_id);

									if (!is_wp_error($image_id)) {
										set_post_thumbnail($post_id, $image_id);
									} else {
										echo 'Error setting featured image: ' . $image_id->get_error_message() . '<br>';
									}
								} else {
									echo 'Error uploading the featured image<br>';
								}
							} else {
								echo 'Invalid featured image URL<br>';
							}

							// Update the custom field 'youtube_video_link'
							$youtube_video_link = 'https://www.youtube.com/watch?v=' . $post_data['id']['videoId'];
							update_post_meta($post_id, 'youtube_video_link', $youtube_video_link);
						} else {
							echo 'Error creating post: ' . $post_id->get_error_message() . '<br>';
						}
					}
				}
			}
			echo 'Posts imported successfully!';
		} else {
			echo 'Invalid JSON data';
		}
	}
?>
	<div class="wrap">
		<h1>Youtube JSON Importer as Post</h1>
		<form method="post" enctype="multipart/form-data">
			<table class="form-table">
				<tr>
					<td>
						<?php wp_nonce_field('json_importer_action', 'json_importer_nonce'); ?>
						<label for="channel_id">Channel ID:</label>
					</td>
					<td>
						<input type="text" id="channel_id" name="channel_id" value="<?php echo get_option('channel_id', ''); ?>" required>
					</td>
				</tr>
				<tr>
					<td>
						<label for="google_api_key">Google API Key:</label>
					</td>
					<td>
						<input type="text" id="google_api_key" name="google_api_key" value="<?php echo get_option('google_api_key', ''); ?>" required>
					</td>
				</tr>
				<tr>
					<td>
						<label for="post_category">Select Category:</label>
					</td>
					<td>
						<?php wp_dropdown_categories(array('selected' => get_option('youtube_selected_category', ''))); ?>
					</td>
				</tr>
				<tr>
					<td>
						<label for="post_author">Select Author:</label>
					</td>
					<td>
						<?php wp_dropdown_users(array('name' => 'post_author', 'selected' => get_option('youtube_selected_author', ''))); ?>
					</td>
				</tr>
				<tr>
					<td><input type="submit" name="submit" value="Import"></td>
					<td></td>
				</tr>
			</table>

		</form>
	</div>
<?php
}


// Cron job function to fetch videos every 10 minutes
function fetch_youtube_videos_cron()
{

	$channel_id = get_option('channel_id', '');
	$google_api_key = get_option('google_api_key', '');

	$channel_api_url = "https://www.googleapis.com/youtube/v3/search?order=date&part=snippet&channelId=" . $channel_id . "&maxResults=50&key=" . $google_api_key;
	$json_data = file_get_contents($channel_api_url);

	// Check if the JSON file is valid
	if (!$json_data) {
		echo 'Invalid JSON file';
		return;
	}

	$posts_data = json_decode($json_data, true);



	if ($posts_data) {
		$selected_category = get_option('youtube_selected_category', '');
		$selected_author = get_option('youtube_selected_author', '');
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		foreach ($posts_data['items'] as $post_data) {


			if (isset($post_data['id']['videoId'])) {

				if (!is_youtube_link_exists('https://www.youtube.com/watch?v=' . $post_data['id']['videoId'])) {

					$json_video_file = file_get_contents('https://www.googleapis.com/youtube/v3/videos?part=snippet&key=' . $google_api_key . '&id=' . $post_data['id']['videoId']);
					if (!$json_video_file) {
						echo 'Invalid JSON file';
						return;
					}
					$video_posts_data = json_decode($json_video_file, true);

					// Create a new post
					$new_post = array(
						'post_title' => $post_data['snippet']['title'],
						'post_content' => $video_posts_data['items'][0]['snippet']['description'],
						'post_status' => 'publish',
						'post_type' => 'post',
						'post_date' => $post_data['snippet']['publishedAt'], // Add post date from the JSON file
						'post_category' => array($selected_category), // Set the selected category
						'post_author' => $selected_author,
						'guid' => 'https://www.youtube.com/watch?v=' . $post_data['id']['videoId'],
					);
					$post_id = wp_insert_post($new_post);

					if (!is_wp_error($post_id)) {
						// Upload and set the featured image
						$image_url = $post_data['snippet']['thumbnails']['high']['url'];
						$image_data = file_get_contents($image_url);

						if ($image_data) {
							$upload_dir = wp_upload_dir();
							$image_name = basename($image_url);
							$upload_path = $upload_dir['path'] . '/' . $image_name;

							if (file_put_contents($upload_path, $image_data)) {
								$image_file = array(
									'name' => $post_data['id']['videoId'] . "-" . $image_name,
									'type' => wp_check_filetype($image_name)['type'],
									'tmp_name' => $upload_path,
									'error' => 0,
									'size' => filesize($upload_path)
								);
								$image_id = media_handle_sideload($image_file, $post_id);

								if (!is_wp_error($image_id)) {
									set_post_thumbnail($post_id, $image_id);
								} else {
									echo 'Error setting featured image: ' . $image_id->get_error_message() . '<br>';
								}
							} else {
								echo 'Error uploading the featured image<br>';
							}
						} else {
							echo 'Invalid featured image URL<br>';
						}

						// Update the custom field 'youtube_video_link'
						$youtube_video_link = 'https://www.youtube.com/watch?v=' . $post_data['id']['videoId'];
						update_post_meta($post_id, 'youtube_video_link', $youtube_video_link);
					} else {
						echo 'Error creating post: ' . $post_id->get_error_message() . '<br>';
					}
				}
			}
		}
		echo 'Posts imported successfully!';
	} else {
		echo 'Invalid JSON data';
	}
}

//add_action('init', 'fetch_youtube_videos_cron');

add_action('fetch_youtube_videos_cron', 'fetch_youtube_videos_cron');

// Schedule the cron job to run every 10 minutes
add_action('init', 'schedule_youtube_videos_cron');
function schedule_youtube_videos_cron()
{
	if (!wp_next_scheduled('fetch_youtube_videos_cron')) {
		wp_schedule_event(time(), 'ten_minutes', 'fetch_youtube_videos_cron');
	}
}

// Custom cron schedule interval for 10 minutes
add_filter('cron_schedules', 'add_ten_minutes_cron_schedule');
function add_ten_minutes_cron_schedule($schedules)
{
	$schedules['ten_minutes'] = array(
		'interval' => 600, // 10 minutes in seconds
		'display' => __('Every 10 Minutes 10')
	);
	return $schedules;
}

function is_youtube_link_exists($value)
{
	$args = array(
		'post_type' => 'post',
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key' => 'youtube_video_link',
				'value' => $value,
				'compare' => '='
			)
		)
	);

	$query = new WP_Query($args);

	return $query->have_posts();
}
