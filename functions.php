<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//http://wordpress.stackexchange.com/a/108421
function get_posts_fields($args = array()) {
    $valid_fields = array(
        'ID' => '%d', 'post_author' => '%d',
        'post_type' => '%s', 'post_mime_type' => '%s',
        'post_title' => false, 'post_name' => '%s',
        'post_date' => '%s', 'post_modified' => '%s',
        'menu_order' => '%d', 'post_parent' => '%d',
        'post_excerpt' => false, 'post_content' => false,
        'post_status' => '%s', 'comment_status' => false, 'ping_status' => false,
        'to_ping' => false, 'pinged' => false, 'comment_count' => '%d'
    );
    $defaults = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'orderby' => 'post_date',
        'order' => 'DESC',
        'posts_per_page' => get_option('posts_per_page'),
    );
    global $wpdb;
    $args = wp_parse_args($args, $defaults);
    $where = "";
    foreach ($valid_fields as $field => $can_query) {
        if (isset($args[$field]) && $can_query) {
            if ($where != "")
                $where .= " AND ";
            $compare = (isset($args[$field]['compare'])) ? ' ' . $args[$field]['compare'] . ' ' : " = ";
            $where .= $wpdb->prepare($field . $compare . $can_query, $args[$field]);
        }
    }
    if (isset($args['search']) && is_string($args['search'])) {
        if ($where != "")
            $where .= " AND ";
        $where .= $wpdb->prepare("post_title LIKE %s", "%" . $args['search'] . "%");
    }
    if (isset($args['include'])) {
        if (is_string($args['include']))
            $args['include'] = explode(',', $args['include']);
        if (is_array($args['include'])) {
            $args['include'] = array_map('intval', $args['include']);
            if ($where != "")
                $where .= " OR ";
            $where .= "ID IN (" . implode(',', $args['include']) . ")";
        }
    }
    if (isset($args['exclude'])) {
        if (is_string($args['exclude']))
            $args['exclude'] = explode(',', $args['exclude']);
        if (is_array($args['exclude'])) {
            $args['exclude'] = array_map('intval', $args['exclude']);
            if ($where != "")
                $where .= " AND ";
            $where .= "ID NOT IN (" . implode(',', $args['exclude']) . ")";
        }
    }
//    $where .= " AND 'post_date' <= '2015-01-01 00:00:00'";
    extract($args);
    $iscol = false;
    if (isset($fields)) {
        if (is_string($fields))
            $fields = explode(',', $fields);
        if (is_array($fields)) {
            $fields = array_intersect($fields, array_keys($valid_fields));
            if (count($fields) == 1)
                $iscol = true;
            $fields = implode(',', $fields);
        }
    }
    if (empty($fields))
        $fields = '*';
    if (!in_array($orderby, $valid_fields))
        $orderby = 'post_date';
    if (!in_array(strtoupper($order), array('ASC', 'DESC')))
        $order = 'DESC';
    if (!intval($posts_per_page) && $posts_per_page != -1)
        $posts_per_page = $defaults['posts_per_page'];
    if ($where == "")
        $where = "1";
    $q = "SELECT $fields FROM $wpdb->posts WHERE " . $where;
    $q .= " ORDER BY $orderby $order";
    if ($posts_per_page != -1)
        $q .= " LIMIT $posts_per_page";
    return $iscol ? $wpdb->get_col($q) : $wpdb->get_results($q);
}

//        $post_ids = get_posts(array(
//            'numberposts' => -1, // get all posts.
//            'offset' => $args['posts_per_page'],
//            'fields' => 'ids', // Only get post IDs
//            'post_type' => 'timeline_post',
//        ));
//        print_r($post_ids);
?>