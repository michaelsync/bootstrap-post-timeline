<?php
/*
  Plugin Name: Bootstrap Posts Timeline
  Plugin URI:
  Description: The short-code displays posts on vertical timeline with bootstrap collapse
  Author: mikostn sysbird
  Author URI:
  Version: 1.0
  License: GPLv2 or later
  Text Domain: bootstrap-post-timeline
 */

//////////////////////////////////////////////////////
// Wordpress 3.0+
global $wp_version;
if (version_compare($wp_version, "3.8", "<")) {
    return false;
}

//////////////////////////////////////////////////////
// Start the plugin
class bootstrapPostTimeline {

    //////////////////////////////////////////
    // construct
    function __construct() {
//        load_plugin_textdomain('bootstrap-post-timeline', false, plugin_basename(dirname(__FILE__)) . '/languages');
        add_shortcode('bootstrap-post-timeline', array(&$this, 'shortcode'));
        add_action('wp_enqueue_scripts', array(&$this, 'add_script'));
        add_action('wp_print_styles', array(&$this, 'add_style'));

        register_activation_hook(__FILE__, array(&$this, 'rewrite_flush'));
        add_action('init', array(&$this, 'create_post_type'));

        add_filter('getarchives_where', array(&$this, 'timeline_post_type_archive_where'), 10, 2);

        add_action('add_meta_boxes_timeline_post', array(&$this, 'add_meta_boxes'));
        add_action('save_post', array(&$this, 'update'), 10, 2);

        add_action('wp_ajax_getpost_by_year', array(&$this, 'process_getpost_by_year'));
        add_action('wp_ajax_nopriv_getpost_by_year', array(&$this, 'process_getpost_by_year'));
    }

    function rewrite_flush() {
        // First, we "add" the custom post type via the above written function.
        // Note: "add" is written with quotes, as CPTs don't get added to the DB,
        // They are only referenced in the post_type column with a post entry, 
        // when you add a post of this CPT.
        bootstrapPostTimeline::create_post_type();

        // ATTENTION: This is *only* done during plugin activation hook in this example!
        // You should *NEVER EVER* do this on every page load!!
        flush_rewrite_rules();
    }

    function create_post_type() {
        register_post_type('timeline_post', array(
            'labels' => array(
                'name' => __('Timeline Post'),
                'singular_name' => __('Timeline Post')
            ),
            'menu_icon' => 'dashicons-feedback',
            'public' => true,
            'has_archive' => true,
            'menu_position' => 5,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'post-formats')
                )
        );
    }

    //add filter to get_archive function http://wordpress.stackexchange.com/a/42071
    function timeline_post_type_archive_where($where, $args) {
        $post_type = isset($args['post_type']) ? $args['post_type'] : 'post';
        $where = "WHERE post_type = '$post_type' AND post_status = 'publish'";
        return $where;
    }

//////////////////////////////////////////
    // add subtitles support
    function add_meta_boxes() {
        add_meta_box('timelineSubtitle', 'Subtitle', array(&$this, 'box'), 'timeline_post', 'normal');
    }

    function box($post) {
        wp_nonce_field('a_save', 'n_tsub');
        ?>
        <input class="widefat" type="text" id="timeline-subtitle" name="timeline_subtitle" value="<?php echo get_post_meta($post->ID, 'timeline_subtitle', true); ?>" />
        <?php
    }

    function update($post_id) {
        if (!isset($_POST['timeline_subtitle']))
            return;

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        if (!isset($_POST['timeline_subtitle']))
            return;

        if (!wp_verify_nonce($_POST['n_tsub'], 'a_save'))
            return;

        // Check permissions
        if ('post' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id))
                return;
        } else {
            if (!current_user_can('edit_page', $post_id))
                return;
        }
        $subtitle = $_POST['timeline_subtitle'];

        update_post_meta($post_id, 'timeline_subtitle', $subtitle);
    }

    //////////////////////////////////////////
    // add JavaScript
    function add_script() {
//        $filename = plugins_url(dirname('/' . plugin_basename(__FILE__))) . '/js/imagesloaded.pkgd.js';
//        wp_enqueue_script('bootstrap-post-timeline-imagesloaded.pkgd', $filename, array('jquery'), '3.1.8');
//        $filename = plugins_url(dirname('/' . plugin_basename(__FILE__))) . '/js/jquery.infinitescroll.js';
//        wp_enqueue_script('bootstrap-post-timeline-infinitescroll', $filename, array('jquery'), '2.1.0');

        $filename = plugins_url(dirname('/' . plugin_basename(__FILE__))) . '/js/bootstrap-post-timeline.js';
        wp_register_script('bootstrap-post-timeline', $filename, array('jquery'), '1.0');
        bootstrapPostTimeline::setAJAXnonce();
        wp_enqueue_script('bootstrap-post-timeline');
    }

    //////////////////////////////////////////
    // add css
    function add_style() {
        $filename = plugins_url(dirname('/' . plugin_basename(__FILE__))) . '/css/timeline.css';
        wp_enqueue_style('bootstrap-post-timeline', $filename, false, '1.1');
    }

    function setAJAXnonce() {
        //Set Nonce for AJAX calls
        $ajax_nonce = wp_create_nonce("my-special-string");
        // Add some parameters for the JS.
        wp_localize_script(
                'bootstrap-post-timeline', 'bpt', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'startPage' => $paged,
            'maxPages' => $max,
            'nextLink' => next_posts($max, false),
            'security' => $ajax_nonce
                )
        );
    }

    function process_getpost_by_year() {
        check_ajax_referer('my-special-string', 'security');

        $year = sanitize_text_field(intval($_POST['year']));

        $args = array(
            'post_type' => 'timeline_post',
            'ignore_sticky_posts' => 1,
            'year' => $year,
        );

        $the_query = new WP_Query($args);
        if ($the_query->have_posts()) {
            $output = '';
            while ($the_query->have_posts()) {
                $the_query->the_post();
                $post = $the_query->post;

                $add_class = '';
                if ($count % 2) {
                    $add_class .= ' right';
                } else {
                    $add_class .= ' left';
                }

                $time_current = (integer) get_post_time();
                if (!$time_last) {
                    $opened = true;
                    $time_last = (integer) get_post_time();
                }

                $days = ceil(abs($time_current - $time_last) / (60 * 60 * 24));
                $time_last = $time_current;
                $opened = FALSE;

                $add_style = '';
                if ($year_top) {
                    $add_class .= ' year_top';
                } else {
                    $add_style = ' style="margin-top: ' . $days . 'px;"';
                }

                $size = 'large';
                if (wp_is_mobile()) {
                    $size = 'medium';
                }

                if (!empty($post->post_excerpt)) {
                    $content = $post->post_excerpt;
                } else {
                    $pieces = get_extended($post->post_content);
                    //var_dump($pieces); // debug
                    $content = apply_filters('the_content', $pieces['main']);
                }
                $subtitle = get_post_meta($post->ID, 'timeline_subtitle', true);

                $output .= '<li id="post-' . $post->ID . '" name="post-' . $post->ID . '" class="item' . $add_class . '"' . $add_style . '>';
                $output .= '<div class="item-content">';
                $output .= '<a href="' . get_permalink() . '">';
                $output .= get_the_post_thumbnail($post->ID, $size);
//				$output .= '<div class="title">' .get_post_time( get_option( 'date_format' ) ) .'<br>' .$title .'</div>';
                $output .= '<h4 class="title">' . $title . '</h4>';
                $output .= (!empty($subtitle)) ? '<h5 class="subtitle">' . $subtitle . '</h5>' : '';
                $output .= $content;
                $output .= '</a>';
                $output .= '</div>';
                $output .= '</li>';
            }
            echo $output;
        }
        wp_reset_postdata();
//        wp_send_json();
        wp_die();
    }

    //////////////////////////////////////////
    // ShoetCode
    function shortcode($atts) {

        global $post, $wp_rewrite;
        $output = '';

        // option
        $atts = shortcode_atts(array('category_name' => '',
            'tag' => '',
            'post_type' => 'timeline_post',
            'posts_per_page' => 0), $atts);

        $args = array('post_type' => $atts['post_type']);

        // category name
        $category_name = $atts['category_name'];
        if ($category_name) {
            $args['category_name'] = $category_name;
        }

        // tag
        $tag = $atts['tag'];
        if ($tag) {
            $args['tag'] = $tag;
        }

        // page
        $timeline_next = 1;
        if (isset($_GET['timeline_next'])) {
            $timeline_next = $_GET['timeline_next'];
        }

        // posts per page
        $posts_per_page = $atts['posts_per_page'];
        if (!$posts_per_page) {
            $posts_per_page = get_option('posts_per_page');
        }
        $args['posts_per_page'] = $posts_per_page;




        $args = array(
            'post_type' => 'timeline_post',
            'type' => 'yearly',
            'limit' => '',
            'format' => 'custom',
            'before' => '',
            'after' => ';',
            'show_post_count' => false,
            'echo' => 0,
            'order' => 'DESC'
        );
        $navlistItems = wp_get_archives($args);


        // prev post
        $year_prev = 0;
        if (1 < $timeline_next) {
            $args['posts_per_page'] = 1;
            $args['offset'] = $posts_per_page * ( $timeline_next - 1 ) - 1;
            $myposts = get_posts($args);
            if ($myposts) {
                foreach ($myposts as $post) {
                    setup_postdata($post);
                    $year_prev = (integer) get_post_time('Y');
                }
            }
            wp_reset_postdata();
        }

        // get posts
        $args['posts_per_page'] = $posts_per_page;
        $args['offset'] = $posts_per_page * ( $timeline_next - 1 );
        $myposts = get_posts($args);
        $time_last = 0;
        $year_last = 0;
        $year_top = 0;
//        $years = '';
        $count = 0;
        if ($myposts) {
            include 'bootstrap-post.php';
        }
        wp_reset_postdata();

        return $output;
    }

}

$bootstrapPostTimeline = new bootstrapPostTimeline();
?>