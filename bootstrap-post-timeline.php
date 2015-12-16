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

include_once(dirname(__FILE__) . '/functions.php');

//////////////////////////////////////////////////////
// Start the plugin
class bootstrapPostTimeline {

    var $theme;
    var $posts;
    var $output;
    var $yearList;
    var $yearListItems;
    var $moreYears;
    var $postIdsByYears = array();
    var $post_date_from;
    var $ajax = false;
    var $shortcode = false;

    //////////////////////////////////////////
    // construct
    function __construct() {
//        load_plugin_textdomain('bootstrap-post-timeline', false, plugin_basename(dirname(__FILE__)) . '/languages');
        // set plugin post type
        register_activation_hook(__FILE__, array(&$this, 'rewrite_flush'));
        add_action('init', array(&$this, 'create_post_type'));

        // add meta boxes
        add_action('add_meta_boxes_timeline_post', array(&$this, 'add_meta_boxes'));
        add_action('save_post', array(&$this, 'update'), 10, 2);

        //register shorcode
        add_shortcode('bootstrap-post-timeline', array(&$this, 'shortcode'));
        //register ajax calls
        add_action('wp_ajax_getpost_by_year', array(&$this, 'process_getpost_by_year'));
        add_action('wp_ajax_nopriv_getpost_by_year', array(&$this, 'process_getpost_by_year'));
        //add "where" to archive calls to get custom post type from archive
        add_filter('getarchives_where', array(&$this, 'timeline_post_type_archive_where'), 10, 2);

        //set styles&scripts
        add_action('wp_enqueue_scripts', array(&$this, 'add_script'));
        add_action('wp_print_styles', array(&$this, 'add_style'));

        $this->setTheme();
    }

    //////////////////////////////////////////
    // set plugin post type on init
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

    //////////////////////////////////////////
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
        $this->setAJAXnonce();
        wp_enqueue_script('bootstrap-post-timeline');
    }

    //////////////////////////////////////////
    // add css
    function add_style() {
        $filename = plugins_url(dirname('/' . plugin_basename(__FILE__))) . '/css/timeline.css';
        wp_enqueue_style('bootstrap-post-timeline', $filename, false, '1.1');
    }

    //////////////////////////////////////////
    // set AJAX vars
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

    //////////////////////////////////////////
    // set AJAX calls function
    function process_getpost_by_year() {
        check_ajax_referer('my-special-string', 'security');
        $year = sanitize_text_field(intval($_POST['year']));
        $args = array(
            'post_type' => 'timeline_post',
            'ignore_sticky_posts' => 1,
            'year' => $year,
        );
        $this->getPosts($args);
        $this->ajax = true;
        echo $this->posts;
//      wp_send_json();
        wp_die();
    }

    //////////////////////////////////////////
    // ShortCode
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

        // posts per page
        $posts_per_page = $atts['posts_per_page'];
        if (!$posts_per_page) {
            $posts_per_page = get_option('posts_per_page');
        }
        $args['posts_per_page'] = $posts_per_page;

        // page
        $timeline_next = 1;
        if (isset($_GET['timeline_next'])) {
            $timeline_next = $_GET['timeline_next'];
        }

        // get posts
        $args['posts_per_page'] = $posts_per_page;
        $args['offset'] = $posts_per_page * ( $timeline_next - 1 );

        $this->post_date_from = date('Y-01-01');
        $args['year'] = $this->post_date_from;
        $this->shortcode = true;

        $this->getPosts($args);

        $this->getMoreYears();

        $this->getYearList();

        $this->setOutput();

        return $this->output; // $output;
    }

    function getPosts($args) {
        $output = '';
        $the_query = new WP_Query($args);
        if ($the_query->have_posts()) {
            ob_start();
            require_once($this->theme->posts);
            $output = ob_get_clean();
        }
        wp_reset_postdata();
        $this->posts = $output;
    }

    function getYearListItems() {
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
        $navlistItems = trim(preg_replace('/\n/', '', $navlistItems), ";");
        $navlistItems = explode(';', $navlistItems);
        $this->yearListItems = $navlistItems;
    }

    function yearListItem($year) {
        return $this->yearListItems[$year];
    }

    function getYearList() {
        $output = '';
        $this->getYearListItems();
        if ($this->yearListItems) {
            ob_start();
            require_once($this->theme->year_list);
            $output = ob_get_clean();
        }
        $this->yearList = $output;
    }

    function getPostIdsByYears() {
        $post_date = $this->post_date_from;
        $args = array(
            'post_type' => 'timeline_post',
            'post_date' => array($post_date, 'compare' => '<='),
            'posts_per_page' => -1,
            'fields' => array('ID', 'post_date')
        );
        $postIdsDates = get_posts_fields($args);

        $postIdsByYears = array();
        foreach ($postIdsDates as $postIdDate) {
            $postIdsByYears[substr($postIdDate->post_date, 0, 4)][] = $postIdDate->ID;
        }
        $this->postIdsByYears = $postIdsByYears;
    }

    function postIdsByYear($year) {
        return $this->postIdsByYears[$year];
    }

    function getMoreYears() {
        $output = '';
        $this->getPostIdsByYears();
        if ($this->postIdsByYears) {
            if (file_exists($this->theme->posts_more)) {
                ob_start();
                require_once($this->theme->posts_more);
                $output = ob_get_clean();
            } else {
                $output = $this->postIdsByYears;
            }
        }
        $this->moreYears = $output;
    }

    function setOutput() {
        ob_start();
        require_once($this->theme->posts_page);
        $output = ob_get_clean();
        $this->output = $output;
    }

    function setTheme() {
        $this->theme->posts_page = 'themes/default/timeline-page.php';
        $this->theme->posts = 'themes/default/timeline-post.php';
        $this->theme->posts_more = 'themes/default/timeline-post_more.php';
        $this->theme->year_list = 'themes/default/timeline-year-list.php';
    }

}

$bootstrapPostTimeline = new bootstrapPostTimeline();

