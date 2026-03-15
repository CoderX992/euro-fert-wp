<?php
// IDE-only ACF stubs for Intelephense.
// Do NOT include/require this file in WordPress runtime.

if (!function_exists('get_field')) {
  /**
   * ACF: get_field()
   * @param string $selector
   * @param mixed $post_id
   * @param bool $format_value
   * @return mixed
   */
  function get_field($selector, $post_id = false, $format_value = true) {}

  /**
   * ACF: the_field()
   * @param string $selector
   * @param mixed $post_id
   * @param bool $format_value
   * @return void
   */
  function the_field($selector, $post_id = false, $format_value = true) {}
} 
?>