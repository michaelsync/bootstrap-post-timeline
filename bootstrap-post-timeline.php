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
defined('ABSPATH') or die();

//////////////////////////////////////////////////////
// Wordpress 3.0+
global $wp_version;
if (version_compare($wp_version, "3.8", "<")) {
    return false;
}

include_once(dirname(__FILE__) . '/includes/functions.php');

//////////////////////////////////////////////////////
// Start the plugin
class bootstrapPostTimeline {

    var $theme = 'default';
    var $posts;
    var $output;
    var $yearList;
    var $yearListItems;
    var $moreYears;
    var $postIdsByYears = array();
    var $post_from_year;
    var $ajax = false;
    var $shortcode = false;
    var $maxPages = 0;
    var $offset = 0;
    var $show_year_list;
    var $post_type = 'timeline_post';
    var $postID;
    var $page;
    var $pagination;
    var $query_atts;
    var $posts_per_page;

    //////////////////////////////////////////
    // construct
    function __construct() {
        load_plugin_textdomain('bootstrap-post-timeline', false, plugin_basename(dirname(__FILE__)) . '/languages');
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
//        add_filter('query_vars', array(&$this, 'add_query_vars_filter'));
        //add "where" to archive calls to get custom post type from archive
        add_filter('getarchives_where', array(&$this, 'timeline_post_type_archive_where'), 10, 2);

        // register styles and js
        add_action('wp_enqueue_scripts', array(&$this, 'registerJSnCSS'));
        //add styles
        add_action('wp_print_styles', array(&$this, 'add_style'));
        // add scripts in footer
        add_action('wp_footer', array($this, 'add_script'));

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

    /*
     * 
     */
    function registerJSnCSS() {
        // Register styles; use min if not debuging
        if (!WP_DEBUG) {
            $jsFile = plugins_url(dirname('/' . plugin_basename(__FILE__))) . '/js/bootstrap-post-timeline.min.js';
            $cssFile = plugins_url(dirname('/' . plugin_basename(__FILE__))) . '/css/timeline.min.css';
        } else {
            $jsFile = plugins_url(dirname('/' . plugin_basename(__FILE__))) . '/js/bootstrap-post-timeline.js';
            $cssFile = plugins_url(dirname('/' . plugin_basename(__FILE__))) . '/css/timeline.css';
        }
        wp_register_script('bootstrap-post-timeline', $jsFile, array('jquery'), '1.0');
        wp_register_style('bootstrap-post-timeline', $cssFile, false, '1.1');
    }

    //////////////////////////////////////////
    // add JavaScript
    function add_script() {
        if (!$this->shortcode) {
            return;
        }
        $this->setAJAXnonce();
        wp_enqueue_script('bootstrap-post-timeline');
    }

    //////////////////////////////////////////
    // add css
    function add_style() {
        wp_enqueue_style('bootstrap-post-timeline');
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
            'start' => $this->offset,
            'get' => $this->posts_per_page,
            'maxPages' => $this->maxPages,
            'nextLink' => next_posts($this->maxPages, false),
            'security' => $ajax_nonce
                )
        );
    }

//    function add_query_vars_filter($vars) {
//        $vars[] = 'from_year';
//        $vars[] = 'get';
//        $vars[] = 'start';
//        return $vars;
//    }
    //////////////////////////////////////////
    // set AJAX calls function
    function process_getpost_by_year() {
        $this->ajax = true;
        check_ajax_referer('my-special-string', 'security');

        $year = sanitize_text_field(intval($_GET['from_year'])); //get_query_var('from_year'); //sanitize_text_field(intval($_POST['year']));
        $get = sanitize_text_field(intval($_GET['get'])); //get_query_var('get'); //sanitize_text_field(intval($_POST['get']));
        $start = sanitize_text_field(intval($_GET['start'])); //get_query_var('start'); //sanitize_text_field(intval($_POST['start']));
        $paged = sanitize_text_field(intval($_GET['paged']));
        $args = array(
            'from_year' => $year,
            'posts_per_page' => $get,
            'offset' => $start,
            'paged' => $paged
        );
        $this->phraseAtts($args);
        $this->getPosts();
        echo $this->posts;
//      wp_send_json();
        wp_die();
    }

    //////////////////////////////////////////
    // ShortCode
    function shortcode($atts) {
        global $post; //, $wp_rewrite;
        $this->postID = $post->ID;

        $this->phraseAtts($atts);

        // set to load js files
        $this->shortcode = true;

        // set and return posts page
        $this->setOutput();
        return $this->output; // $output;
    }

    function phraseAtts($shortcodeAtts) {
        // option
        $atts = shortcode_atts(array(
            'category_name' => '',
            'tag' => '',
            'post_type' => $this->post_type,
            'posts_per_page' => $this->posts_per_page,
            'offset' => 0,
            'from_year' => '',
            'show_year_list' => 1,
            'theme' => $this->theme,
            'paged' => false), $shortcodeAtts, 'bootstrap-post-timeline');

        $args = array('post_type' => $atts['post_type']); //$this->post_type
        // Show year list
        $this->show_year_list = ($atts['show_year_list']) ? true : false;

        // category name
        $category_name = $atts['category_name'];
        if ($category_name) {
            $args['category_name'] = $category_name;
        }

        $tag = $atts['tag'];
        if ($tag) {
            $args['tag'] = $tag;
        }

        // page
        $page = ($atts['paged']) ? $atts['paged'] : 1; //get_query_var('paged') | get_query_var('page') | 1;
        $this->page = $page;

        // posts per page
        $posts_per_page = $atts['posts_per_page'];
        if (!$posts_per_page) {
            $posts_per_page = get_option('posts_per_page');
        }
        $args['posts_per_page'] = $posts_per_page;
        $this->posts_per_page = $args['posts_per_page'];

        // get posts
        $args['offset'] = $atts['offset'] + $posts_per_page * ( $page - 1 );
        $this->offset = $args['offset'];

        // start from year - or get latest
        $post_from_year = $atts['from_year'];
        if (!$post_from_year) {
            $recent_posts = wp_get_recent_posts(array('numberposts' => 1, 'post_type' => $this->post_type));
            $post_from_year = date('Y', strtotime($recent_posts[0]['post_date']));
        }
        $args['year'] = $post_from_year;
        $this->post_from_year = $args['year'];

        $this->query_atts = $args;
    }

    function getPosts() {
        // add $this->post_type to args! $args
        $output = '';
        $the_query = new WP_Query($this->query_atts);
        if ($the_query->have_posts()) {
            $this->maxPages = $the_query->max_num_pages;
            $this->getPagination();
            ob_start();
            require_once($this->theme->posts);
            $output = ob_get_clean();
        }
        wp_reset_postdata();
        $this->posts = $output;
    }

    function getYearList() {
        add_filter('get_archives_link', array(&$this, 'clearLink'));
        $args = array(
            'post_type' => $this->post_type,
            'type' => 'yearly',
            'limit' => '',
            'format' => 'custom',
            'before' => '',
            'after' => ';',
            'show_post_count' => false,
            'echo' => 0,
            'order' => 'DESC'
        );
        $navlistItems = explode(';', trim(wp_get_archives($args), ';'));
        $this->yearListItems = $navlistItems;
    }

    function clearLink($link) {
        preg_match_all('/<a .*?>(.*?)<\/a>/', $link, $matches);
        $link = $matches[1][0];
        return $link . ';';
    }

    function yearListItem($year) {
        return $this->yearListItems[$year];
    }

    function setYearList() {
        if (!$this->show_year_list) {
            return;
        }
        $output = '';
        $this->getYearList();
        if ($this->yearListItems) {
            ob_start();
            require_once($this->theme->year_list);
            $output = ob_get_clean();
        }
        $this->yearList = $output;
    }

    function getPostIdsByYears() {
        $post_date = $this->post_from_year;
        $args = array(
            'post_type' => $this->post_type,
//            'post_date' => array($post_date, 'compare' => '<='),
            'offset' => $this->posts_per_page,
            'posts_per_page' => 1000,//-1,
            'fields' => array('ID', 'post_date')
        );
        $postIdsDates = get_posts_fields($args);

        $postIdsByYears = array();
        foreach ($postIdsDates as $postIdDate) {
            $postIdsByYears[substr($postIdDate->post_date, 0, 4)][] = $postIdDate->ID;
        }
        $this->postIdsByYears = $postIdsByYears;
    }

    function getPostIdsByYear($year) {
        return $this->postIdsByYears[$year];
    }

    function setMoreYears() {
        $output = '';
        $this->getPostIdsByYears();
        if ($this->postIdsByYears) {
            $moreYears = $this->postIdsByYears;
            unset($moreYears[$this->post_from_year]);
            $this->moreYears = $moreYears;
            if (file_exists($this->theme->posts_more)) {
                ob_start();
                require_once($this->theme->posts_more);
                $output = ob_get_clean();
            } else {
                $output = $this->moreYears;
            }
        }
        $this->moreYears = $output;
    }

    function getPaginationURL() {
        if ($this->maxPages > $this->page) {
            return add_query_arg(array('page_id' => $this->postID, 'from_year' => $this->post_from_year, 'paged' => $this->page + 1, 'cur' => $this->page, 'max' => $this->maxPages), site_url());
        }
    }

    function getPagination() {
        $this->pagination = $this->getPaginationURL();
    }

    function setOutput() {
        $this->getPosts();
        $this->setMoreYears();
        $this->setYearList();
        $this->getPagination();

        ob_start();
        require_once($this->theme->posts_page);
        $output = ob_get_clean();
        $this->output = $output;
    }

    function setTheme() {
        $theme = $this->theme;
        $this->theme = (object) array(
                    "posts_page" => 'themes/' . $theme . '/timeline-page.php',
                    "posts" => 'themes/' . $theme . '/timeline-post.php',
                    "posts_more" => 'themes/' . $theme . '/timeline-post_more.php',
                    "year_list" => 'themes/' . $theme . '/timeline-year-list.php',
        );
    }

}

$bootstrapPostTimeline = new bootstrapPostTimeline();

