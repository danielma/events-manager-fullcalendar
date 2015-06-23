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

namespace EMFullCalendar;

function em_fullcalendar_init() {
  global $em_fullcalendar_events;

  include_lib();

  $em_fullcalendar_events = new EMFullCalendarEvent();
  add_filter('json_endpoints', [$em_fullcalendar_events, 'register_routes']);
}
add_action('wp_json_server_before_serve', __NAMESPACE__ . '\\em_fullcalendar_init');

function include_lib() {
  require_once(dirname(__FILE__) . '/lib/utils.php');
}

class EMFullCalendarEvent {
  public function register_routes($routes) {
    $routes['/events-manager/events'] = [
      [[$this, 'get_posts'], \WP_JSON_Server::READABLE]
    ];
    $routes['/events-manager/events/(?P<id>\d+)'] = [
      [[ $this, 'get_post'], \WP_JSON_Server::READABLE]
    ];

    return $routes;
  }

  public function get_post($id, $context = 'view') {
    $event = em_get_event($id);

    $response = new \WP_JSON_Response();
    $response->set_data($this->prepare_post($event, ['include_image' => true]));

    return $response;
  }

  public function get_posts($filter = array(), $context = 'view', $type = 'event', $page = 1) {
    $query = [];

    $start = isset($filter['start']) ? $filter['start'] : null;
    $end   = isset($filter['end'])   ? $filter['end']   : null;
    $limit = isset($filter['limit']) ? $filter['limit'] :
      (!(is_null($start) || is_null($end)) ? 0 : 100);

    $post_list = \EM_Events::get(['scope' => [$start, $end], 'limit' => $limit]);
    $response  = new \WP_JSON_Response();

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
  private function prepare_post($post, $options = []) {
    $id = intval($post->event_id);
    $recurrence_id = intval($post->recurrence_id);
    $recurrence_id = $recurrence_id > 0 ? $recurrence_id : null;

    $options = option_defaults([
      'include_image' => false
    ], $options);

    $all_day = !!$post->event_all_day;

    $end_unix = $post->end;
    if ($all_day) {
      $end_unix = strtotime('+1 day', $post->end);
    }

    $category = $this->get_category($post);

    $start = date(\DateTime::ISO8601, $post->start);
    $end = date(\DateTime::ISO8601, $end_unix);

    $description = empty($post->post_excerpt) ? $post->post_content : $post->post_excerpt;
    $description = strip_tags($description);

    $newPost = [
      // Official properties
      'id'            => $id,
      'recurrence_id' => $recurrence_id,
      'title'         => $post->event_name,
      'allDay'        => $all_day,
      'start'         => $start,
      'end'           => $end,
      'url'           => $post->get_permalink(),
      'color'         => $category['color'],

      // extra properties
      'category'      => $category['id'],
      'description'   => $description
    ];

    if ($options['include_image']) {
      $thumbnail_id = get_post_thumbnail_id($post->ID);
      $newPost['featured_image'] = all_image_urls($thumbnail_id);
    }

    return $newPost;
  }

  private function get_category($post) {
    $categories = array_values($post->get_categories()->categories);

    if (empty($categories)) {
      return [
        'id'    => null,
        'name'  => null,
        'color' => null
      ];
    }

    $category = $categories[0];

    return [
      'id'    => $category->id,
      'name'  => $category->name,
      'color' => $category->get_color()
    ];
  }
}
