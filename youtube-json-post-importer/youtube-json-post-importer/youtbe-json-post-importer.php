<?php
/*
Plugin Name: Youtube JSON To Wordpress Post Importer
Description: Import posts from a youtube JSON file, set featured images, assign to selected category and author.
*/

// Register the JSON Importer menu page
function json_importer_menu() {
    add_menu_page(
        'Youtube JSON Importer as Post',
        'Youtube JSON Importer as Post',
        'manage_options',
        'json-importer',
        'json_importer_page',
        'dashicons-upload',
        99
    );
}
add_action('admin_menu', 'json_importer_menu');

// Display the JSON Importer page content
function json_importer_page() {
    if (isset($_POST['submit'])) {
        // Verify the nonce for security
        if (!isset($_POST['json_importer_nonce']) || !wp_verify_nonce($_POST['json_importer_nonce'], 'json_importer_action')) {
            wp_die('Security check failed. Please try again.');
        }

        $json_file = $_FILES['json_file'];
        $json_data = file_get_contents($json_file['tmp_name']);

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

				if(isset($post_data['id']['videoId'])){
					// Create a new post
					$new_post = array(
						'post_title' => $post_data['snippet']['title'],
						'post_content' => $post_data['snippet']['description'],
						'post_status' => 'publish',
						'post_type' => 'post',
						'post_date' => $post_data['snippet']['publishedAt'] , // Add post date from the JSON file
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
							$upload_path = $upload_dir['path'] . '/'. $image_name;

							if (file_put_contents($upload_path, $image_data)) {
								$image_file = array(
									'name' => $post_data['id']['videoId']."-".$image_name,
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
						$youtube_video_link = 'https://www.youtube.com/watch?v='.$post_data['id']['videoId'];
						update_post_meta($post_id, 'youtube_video_link', $youtube_video_link);
				

					} else {
						echo 'Error creating post: ' . $post_id->get_error_message() . '<br>';
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
            		<label for="json_file">Select JSON File:</label>
					</td>
					<td>
					<input type="file" id="json_file" name="json_file" required>
					</td>
				</tr>
				<tr>
					<td>
					<label for="post_category">Select Category:</label>
					</td>
					<td>
					<?php wp_dropdown_categories( ); ?>
					</td>
				</tr>
				<tr>
					<td>
					<label for="post_author">Select Author:</label>
					</td>
					<td>
					<?php wp_dropdown_users(array('name' => 'post_author')); ?>
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