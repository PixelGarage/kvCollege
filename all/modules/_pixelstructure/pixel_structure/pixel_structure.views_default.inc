<?php
/**
 * Created by PhpStorm.
 * User: ralph
 * Date: 27.04.15
 * Time: 17:54
 */

/**
 * Implements hook_views_default_views().
 */
function pixel_structure_views_default_views() {
  // get all views in code (hold in files ending with .view)
  $views = array();
  $files = file_scan_directory(drupal_get_path('module', 'pixel_structure') . '/views', '#\.view$#');
  foreach ($files as $filepath => $file) {
    // import view
    require $filepath;

    // Add view to list of views to provide.
    if (isset($view)) {
      $views[$view->name] = $view;
    }
  }
  return $views;
}

