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
    $query = array();

    $event_list  = EM_Events::get(['limit' => 16, 'scope' => [$filter['start'], $filter['end']]]);
    $response    = new WP_JSON_Response();

    if (!$event_list) {
      $response->set_data([]);
      return $response;
    }

    $response->set_data($event_list);

    return $response;
  }
}

