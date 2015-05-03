<?php
/**
 * Created by PhpStorm.
 * User: ralph
 * Date: 27.04.15
 * Time: 17:54
 */

/**
 * Implements hook_default_page_manager_pages() to automatically import default pages.
 */
function pixel_structure_default_page_manager_pages() {
  // get all pages in code (hold in files ending with .page)
  $pages = array();
  $files = file_scan_directory(drupal_get_path('module', 'pixel_structure') . '/pages', '#\.page$#');
  foreach ($files as $filepath => $file) {
    // import page
    require $filepath;

    // Add page to list of default pages.
    if (isset($page)) {
      $pages[$page->name] = $page;
    }
  }
  return $pages;
}

