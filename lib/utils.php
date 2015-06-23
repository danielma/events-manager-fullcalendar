<?php

namespace EMFullCalendar;

function all_image_urls($attachment_id) {
  $original_url = wp_get_attachment_image_src($attachment_id);

  if ($original_url === false) { return null; }

  $image_data   = wp_get_attachment_metadata($attachment_id);

  preg_match("/.+\//", $original_url[0], $matches);

  $folder = $matches[0];
  $sizes  = $image_data['sizes'];

  return array_map(function($key, $size) use ($folder) {
    return [
      'type'   => $key,
      'width'  => $size['width'],
      'height' => $size['height'],
      'url'    => $folder . $size['file']
    ];
  }, array_keys($sizes), array_values($sizes));
}

function option_defaults($defaults, $options) {
  foreach ($defaults as $key => $value) {
    if (isset($options[$key])) { continue; }

    $options[$key] = $defaults[$key];
  }

  return $options;
}
