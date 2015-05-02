<?php
/**
 * @file
 * template.php
 */

global $pixelgarage_path;
$pixelgarage_path = drupal_get_path('theme', 'pixelgarage');

include_once($pixelgarage_path . '/inc/login.inc');        // alters login forms
include_once($pixelgarage_path . '/inc/alter.inc');        // all alter hooks
include_once($pixelgarage_path . '/inc/preprocess.inc');   // all preprocess overrides
include_once($pixelgarage_path . '/inc/process.inc');      // all process overrides
include_once($pixelgarage_path . '/inc/theme.inc');        // all theme overrides



/* =============================================================================
 *
 *      PxlTest theming
 *
 * ========================================================================== */
/**
 * ONLY for PXLTEST
 * To prevent soft line-breaks in HTML pasted to the body field of pxlTest, clean text from <br>.
 * This cleanup is needed for a fully functional Bootstrap test page.
 */
function pixelgarage_field__body__pxltest ($variables) {
  $output = '';

  // Render the label, if it's not hidden.
  if (!$variables['label_hidden']) {
    $output .= '<div class="field-label"' . $variables['title_attributes'] . '>' . $variables['label'] . ':&nbsp;</div>';
  }

  // Render the items.
  $output .= '<div class="field-items"' . $variables['content_attributes'] . '>';
  foreach ($variables['items'] as $delta => $item) {
    $classes = 'field-item ' . ($delta % 2 ? 'odd' : 'even');
    // clean HTML of soft line-breaks
    $item = str_replace(array('<br>', '<br />'), "\n", $item);
    $output .= '<div class="' . $classes . '"' . $variables['item_attributes'][$delta] . '>' . drupal_render($item) . '</div>';
  }
  $output .= '</div>';

  // Render the top-level DIV.
  $output = '<div class="' . $variables['classes'] . '"' . $variables['attributes'] . '>' . $output . '</div>';

  return $output;
}