<?php
/**
 * Plugin Name: Category Popular Posts
 * Description: Categories wise Popular Posts.
 * Plugin URI: https://webocreation.com
 * Author: Rupak Nepali
 * Author URI: https://webocreation.com
 * Version: 1.0
 * License: GPLv2 or later
 *
 */

// Adds widget: Categories Wise Popular Posts
class Categorywisepopularp_Widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'categorywisepopularp_widget',
            esc_html__('Category wise Popular posts', 'textdomain')
        );
    }

    private $widget_fields = array(
        array(
            'label' => 'Description',
            'id' => 'description_textarea',
            'type' => 'textarea',
        ),
        array(
            'label' => 'Limit',
            'id' => 'limit_number',
            'type' => 'number',
        ),
        array(
            'label' => 'Show date',
            'id' => 'showdate_select',
            'type' => 'select',
            'options' => array(
                'Yes',
                'No',
            ),
        ),
        array(
            'label' => 'Show Author',
            'id' => 'showauthor_select',
            'type' => 'select',
            'options' => array(
                'Yes',
                'No',
            ),
        ),
        array(
            'label' => 'Show featured image',
            'id' => 'showfeaturedima_select',
            'type' => 'select',
            'options' => array(
                'Yes',
                'No',
            ),
        ),
    );

    public function widget($args, $instance)
    {
        if (!isset($args['widget_id'])) {
            $args['widget_id'] = $this->id;
        }

        $title = (!empty($instance['title'])) ? $instance['title'] : __('Recent Posts');

        /** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
        $title = apply_filters('widget_title', $title, $instance, $this->id_base);

        $number = (!empty($instance['limit_number'])) ? absint($instance['limit_number']) : 5;
        if (!$number) {
            $number = 5;
        }
        $show_date = isset($instance['showdate_select']) ? $instance['showdate_select'] : false;

        $r = new WP_Query(
            apply_filters(
                'widget_posts_args',
                array(
                    'meta_key' => 'post_views_count',
                    'cat' => get_query_var('cat'),
                    'orderby' => 'meta_value_num',
                    'posts_per_page' => $number,
                    'no_found_rows' => true,
                    'post_status' => 'publish',
                    'ignore_sticky_posts' => true,
                ),
                $instance
            )
        );

        if (!$r->have_posts()) {
            return;
        }
        echo $args['before_widget'];
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        echo '<p>' . $instance['description_textarea'] . '</p>';
        echo "<style>.post-image{ float:left; width:50%;}.post-content{float:right; width:50%;}</style>";
        echo '<ul>';
        foreach ($r->posts as $recent_post):
            $post_title = get_the_title($recent_post->ID);
            $p_title = (!empty($post_title)) ? $post_title : __('(no title)');
            echo "<li>";
            if ($instance['showfeaturedima_select'] == "Yes") {
                echo "<div class='post-image'><a href='" . esc_url(get_permalink($recent_post->ID)) . "' rel='bookmark'  title='" . $recent_post->post_title . "'>
																<img class='category-popular-posts-image' src='" . get_the_post_thumbnail_url($recent_post->ID, 'thumbnail', 'full') . "' alt='" . $recent_post->post_title . "'>
									                        </a></div>";
            }
            echo "<div class='post-content'><a href='" . esc_url(get_permalink($recent_post->ID)) . "' rel='bookmark' title='" . $recent_post->post_title . "'>" . $recent_post->post_title . "</a>";
            if ($instance['showauthor_select'] == "Yes") {
                echo "<br>&nbsp;- <a href='" . esc_url(get_author_posts_url($recent_post->post_author)) . "'>" .
                get_the_author_meta('display_name', $recent_post->post_author) . "</a>";
            }
            if ($instance['showdate_select'] == "Yes") {
                echo "<br><span class='post-date'>" . get_the_date('', $recent_post->ID) . "</span>";
            }
            echo "</div>";
            echo "<div style='clear:both;'></div>";
            echo "</li>";
        endforeach;
        echo "</ul>";
        echo $args['after_widget'];

    }

    public function field_generator($instance)
    {
        $output = '';
        foreach ($this->widget_fields as $widget_field) {
            $default = '';
            if (isset($widget_field['default'])) {
                $default = $widget_field['default'];
            }
            $widget_value = !empty($instance[$widget_field['id']]) ? $instance[$widget_field['id']] : esc_html__($default, 'textdomain');
            switch ($widget_field['type']) {
                case 'textarea':
                    $output .= '<p>';
                    $output .= '<label for="' . esc_attr($this->get_field_id($widget_field['id'])) . '">' . esc_attr($widget_field['label'], 'textdomain') . ':</label> ';
                    $output .= '<textarea class="widefat" id="' . esc_attr($this->get_field_id($widget_field['id'])) . '" name="' . esc_attr($this->get_field_name($widget_field['id'])) . '" rows="6" cols="6" value="' . esc_attr($widget_value) . '">' . $widget_value . '</textarea>';
                    $output .= '</p>';
                    break;
                case 'select':
                    $output .= '<p>';
                    $output .= '<label for="' . esc_attr($this->get_field_id($widget_field['id'])) . '">' . esc_attr($widget_field['label'], 'textdomain') . ':</label> ';
                    $output .= '<select id="' . esc_attr($this->get_field_id($widget_field['id'])) . '" name="' . esc_attr($this->get_field_name($widget_field['id'])) . '">';
                    foreach ($widget_field['options'] as $option) {
                        if ($widget_value == $option) {
                            $output .= '<option value="' . $option . '" selected>' . $option . '</option>';
                        } else {
                            $output .= '<option value="' . $option . '">' . $option . '</option>';
                        }
                    }
                    $output .= '</select>';
                    $output .= '</p>';
                    break;
                default:
                    $output .= '<p>';
                    $output .= '<label for="' . esc_attr($this->get_field_id($widget_field['id'])) . '">' . esc_attr($widget_field['label'], 'textdomain') . ':</label> ';
                    $output .= '<input class="widefat" id="' . esc_attr($this->get_field_id($widget_field['id'])) . '" name="' . esc_attr($this->get_field_name($widget_field['id'])) . '" type="' . $widget_field['type'] . '" value="' . esc_attr($widget_value) . '">';
                    $output .= '</p>';
            }
        }
        echo $output;
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : esc_html__('', 'textdomain');
        ?>
<p>
    <label
        for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_attr_e('Title:', 'textdomain');?></label>
    <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
        name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
        value="<?php echo esc_attr($title); ?>">
</p>
<?php
$this->field_generator($instance);
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        foreach ($this->widget_fields as $widget_field) {
            switch ($widget_field['type']) {
                default:
                    $instance[$widget_field['id']] = (!empty($new_instance[$widget_field['id']])) ? strip_tags($new_instance[$widget_field['id']]) : '';
            }
        }
        return $instance;
    }
}

function register_categorywisepopularp_widget()
{
    register_widget('Categorywisepopularp_Widget');
}
add_action('widgets_init', 'register_categorywisepopularp_widget');