<?php

/**
 * @file
 * This template is used to print a single field in a view.
 *
 * It is not actually used in default Views, as this is registered as a theme
 * function which has better performance. For single overrides, the template is
 * perfectly okay.
 *
 * Variables available:
 * - $view: The view object
 * - $field: The field handler object that can process the input
 * - $row: The raw SQL result that can be used
 * - $output: The processed output that will normally be used.
 *
 * When fetching output from the $row, this construct should be used:
 * $data = $row->{$field->field_alias}
 *
 * The above will guarantee that you'll always get the correct data,
 * regardless of any changes in the aliasing that might happen if
 * the view is modified.
 */

  $logo_path = drupal_get_path('theme', 'pixelgarage') . '/logo';
  $brand = strtolower($output);
  switch ($brand) {
    case 'hso':
      $logo_path .= '.png';
      break;
    default:
      $logo_path .= '_' . $brand . '.png';
      break;
  }
  if (file_exists($logo_path)) {
    $variables = array(
      'path' => $logo_path,
      'alt' => 'Brand logo',
      'title' => 'Brand logo',
      'width' => 'auto',
      'height' => '25px',
      'attributes' => array('style' => 'margin: 0 auto'),
    );
    $output = theme('image', $variables);
  }

?>
<?php print $output; ?>
