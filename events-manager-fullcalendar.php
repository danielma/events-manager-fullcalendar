<?php
/**
* Plugin Name: Events Manager Fullcalendar
* Plugin URI: http://events-manager-fullcalendar.github.io/
* Description: Add fullcalendar integration to Events Manager
* Version: 0.0.1
* Author: Daniel Ma
* Author URI: http://github.com/danielma
* License: MIT
*/

function em_fullcalendar_init() {
  global $em_fullcalendar_events;

  $em_fullcalendar_events = new EMFullCalendarEvent();
  add_filter('json_endpoints', [$em_fullcalendar_events, 'register_routes']);
}
add_action('wp_json_server_before_serve', 'em_fullcalendar_init');

class EMFullCalendarEvent {
  public function register_routes($routes) {
    $routes['/events-manager/events'] = [
      [[$this, 'get_posts'], WP_JSON_Server::READABLE]
    ];
    $routes['/events-manager/events/(?P<id>\d+)'] = [
      [[ $this, 'get_posts'], WP_JSON_Server::READABLE]
    ];

    return $routes;
  }

  public function get_posts($filter = array(), $context = 'view', $type = 'event', $page = 1) {
    $query = [];

    $post_list = EM_Events::get(['limit' => 16, 'scope' => [$filter['start'], $filter['end']]]);
    $response  = new WP_JSON_Response();

    if (!$post_list) {
      $response->set_data([]);
      return $response;
    }

    $post_list = array_map([$this, 'prepare_post'], $post_list);

    $response->set_data($post_list);

    return $response;
  }

  /*
   * transform an EM_Event object into a Fullcalendar event object
   * Reference: http://fullcalendar.io/docs/event_data/Event_Object/
   */
  private function prepare_post($post) {
    $id = empty($post->recurrence_id) ? $post->event_id : $post->recurrence_id;
    $id = intval($id);

    $all_day = !!$post->event_all_day;

    $end_unix = $post->end;
    if ($all_day) {
      $end_unix = strtotime('+1 day', $post->end);
    }

    $start = date(\DateTime::ISO8601, $post->start);
    $end = date(\DateTime::ISO8601, $end_unix);

    $categories = array_map(function($cat) {
      return $cat->name;
    }, array_values($post->get_categories()->categories));

    $description = empty($post->post_excerpt) ? $post->post_content : $post->post_excerpt;
    $description = strip_tags($description);

    $newPost = [
      // Official properties
      'id'          => $id,
      'title'       => $post->event_name,
      'allDay'      => $all_day,
      'start'       => $start,
      'end'         => $end,
      'url'         => $post->get_permalink(),

      // extra properties
      'categories'  => $categories,
      'description' => $description
    ];

    return $newPost;
  }
}