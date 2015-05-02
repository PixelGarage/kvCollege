<?php
/**
 * Created by PhpStorm.
 * User: ralph
 * Date: 27.04.15
 * Time: 18:01
 */

/* =============================================================================
 *   Constants
 * ========================================================================== */
/**
 * A bit flag used to let us know if a node type is in the database.
 */
define('ENTITY_TYPE_IN_DATABASE', 0x01);

/**
 * A bit flag used to let us know if a node type is in code and not imported yet.
 */
define('ENTITY_TYPE_IN_CODE', 0x02);


/* =============================================================================
 *   Main form
 * ========================================================================== */
/**
 * Page callback for the pixel structure summary list. All changed
 * bundles per entity type are listed and bulk operations for import and export are provided.
 *
 * @param $form
 * @param $form_state
 */
function pixel_structure_summary_list() {
  $bc_info = bundle_copy_get_info();

  // define summary page
  $page = array();
  foreach ($bc_info as $entity_type => $info) {
    $module = $entity_type == 'taxonomy_term' ? 'taxonomy' : $entity_type;
    if (!module_exists($module)) continue;

    // add table of changed bundles
    $title = drupal_ucfirst(str_replace('_', ' ', $entity_type));
    $table = pixel_structure_entity_type_list($entity_type, TRUE);
    $page[$entity_type]['table'] = $table;
    $page[$entity_type]['#prefix'] = '<div><br><h2>' . $title . 's</h2>';
    $page[$entity_type]['#suffix'] = '</div><br>';

    // add bulk import link, if changed bundles are available
    if (count($table['#rows']) > 0) {
      $url = 'admin/structure/pixel_structure/' . $entity_type. '/import_changed';
      $options = array('attributes' => array('class' => array('button'), 'style' => 'float: right'));
      $page[$entity_type]['#suffix'] = '</div><div>' . l(t('Import All'), $url, $options) . '</div><br>';
    }
  }
  $page['#suffix'] = '<br><br>';

  return $page;
}


/* =============================================================================
 *   Entity type manage forms and callbacks
 * ========================================================================== */
/**
 * Page callback to list all bundles of an entity type available in code and in the db in a table.
 * Optionally only changed bundles in code can be output. The list contains operations for each bundle, e.g
 * the bundles can be imported, exported and deleted (from the db).
 *
 * @param string  $entity_type            The entity type to be listed
 * @param bool    $changed_bundles_only   Flag to output only changed bundles, when TRUE.
 *
 * @return array The render array of the table.
 */
function pixel_structure_entity_type_list($entity_type, $changed_bundles_only = FALSE) {
  $header = array(
    array('data' => t('Bundles')),
    array('data' => t('Last import/export')),
    array('data' => t('Code created')),
    array('data' => t('Status')),
    array('data' => t('Operations'), 'colspan' => 3),
  );
  $rows = array();

  // create bundle rows
  $deletable_bundle = in_array($entity_type, array('node', 'taxonomy_term'));
  $bundles = _pixel_structure_entity_type_bundles($entity_type);

  foreach ($bundles as $key => $bundle) {
    // initialize
    $url_base = 'admin/structure/pixel_structure/' . $entity_type. '/' . $key;
    $url_import = $url_base . '/import';
    $url_export = $url_base . '/export';
    $url_delete = $url_base . '/delete';
    $url_clone  = $url_base . '/clone';
    $status = '';
    $import = '';
    $export = '';
    $delete = '';
    $last_import_export = '';
    $code_exported = '';

    // build row according to bundle storage
    switch ($bundle['storage']) {
      case ENTITY_TYPE_IN_DATABASE | ENTITY_TYPE_IN_CODE:
        if ($bundle['last_import_export'] == $bundle['code_exported']) {
          if (_pixel_structure_db_equals_code($entity_type, $key, $bundle['data'])) {
            // output only changed bundles
            if ($changed_bundles_only) continue 2;

            // node is up to date
            $status = t('<strong>Up-to-date</strong>');
          } else {
            // bundle in db has changed
            $status = t('<strong>DB Changed</strong>');
            $export = l(t('Export'), $url_export);
          }

        } else {
          // file has changed
          $status = t('<strong>Code Changed</strong>');
          $import = l(t('Update'), $url_import);

        }
        $delete = l(t('Delete'), $url_delete);
        $last_import_export = ($bundle['last_import_export'] == -1) ? 'never' : date('d-M-Y H:i:s', $bundle['last_import_export']);
        $code_exported = ($bundle['code_exported'] == 0) ? 'undefined' : date('d-M-Y H:i:s', $bundle['code_exported']);
        break;

      case ENTITY_TYPE_IN_DATABASE:
        // output only changed bundles
        if ($changed_bundles_only) continue 2;

        $status = t('<strong>DB only</strong>');
        $export = l(t('Export'), $url_export);
        $delete = l(t('Delete'), $url_delete);
        $last_import_export = ($bundle['last_import_export'] == -1) ? 'never' : date('d-M-Y H:i:s', $bundle['last_import_export']);
        break;

      case ENTITY_TYPE_IN_CODE:
        $status = t('<strong>Code only</strong>');
        $import = l(t('Import'), $url_import);
        $code_exported = ($bundle['code_exported'] == 0) ? 'undefined' : date('d-M-Y H:i:s', $bundle['code_exported']);
        break;
    }

    $tablerow = array(
      array('data' => check_plain($bundle['title'])),
      array('data' => $last_import_export),
      array('data' => $code_exported),
      array('data' => $status),
      array('data' => $import),
      array('data' => $export),
      array('data' => $deletable_bundle ? $delete : ''),
      //array('data' => l(t('Clone'), $url_clone)),
    );
    $rows[] = $tablerow;
  }

  if (empty($rows)) {
    if ($changed_bundles_only) {
      $rows[] = array(array('data' => t('All bundles are up-to-date.'), 'colspan' => 7));
    } else {
      $rows[] = array(array('data' => t('No bundles available.'), 'colspan' => 7));
    }
  }

  $build = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array('id' => 'pixel-structure-' . $entity_type),
  );
  return $build;
}


/**
 * Imports the bundle of entity type from code.
 *
 * @param $entity_type  string    The entity type
 * @param $bundle_key   string    The bundle name to be imported.
 *
 * @return array
 */
function pixel_structure_entity_type_import($entity_type, $bundle_key, $bulk_mode = FALSE) {
  // read import data
  $bundle_data = pixel_structure_entity_type_import_data($entity_type, $bundle_key);
  if ($bundle_data) {
    // import bundle data
    bundle_copy_import_submit(array(), $bundle_data);

    // update import time stamp (also in code when not available)
    $code_exported = $bundle_data['code_exported'];
    if ($code_exported == 0) {
      // update export file with new time stamp
      $code_exported = time();
      $bundle_data['code_exported'] = $code_exported;
      pixel_structure_entity_type_export_data($entity_type, $bundle_key, $bundle_data);

    } else {
      // update import time stamp
      variable_set('pixel_structure_last_import_export_' . $entity_type . '_' . $bundle_key, $code_exported);
    }


  } else {
    // import data couldn't be read
    drupal_set_message(t('%bundle data of entity type "%type" could not be imported. File not available or data corrupt!', array('%bundle' => $bundle_data['title'], '%type' => $entity_type)), 'error');
  }

  // leave without redirect in bulk mode
  if ($bulk_mode) return;

  // return to entity type list
  drupal_goto('admin/structure/pixel_structure');
}

/**
 * Exports the bundle of an entity type to code.
 *
 * @param $entity_type  string  The entity type
 * @param $bundle_key   string    The bundle name to be imported.
 *
 * @return  array The export form.
 */
function pixel_structure_entity_type_export_form($form, &$form_state, $entity_type, $bundle_key) {
  // TODO: Export without form (all fields and field groups exported)

  $form_state['entity_type'] = $entity_type;

  if (!isset($form_state['step'])) {
    // skip first step (bundle selection), only export given node type
    $form_state['step'] = 2;
    $bundle_title = _bundle_copy_bundle_info($entity_type)[$bundle_key];
    $form_state['bundle_key'] = $bundle_key;
    $form_state['bundle_title'] = $bundle_title;
    $form_state['page_values'][1] = array(
      'bundles' => array($bundle_key => $bundle_key),
    );
    // create form for step 2
    $form = bundle_copy_export($form, $form_state, $entity_type);

    // adapt cancel button for step 2
    $form['actions']['cancel'] = array(
      '#markup' => l(t('Cancel'), 'admin/structure/pixel_structure'),
    );

  }

  return $form;
}

/**
 * Submit callback: export data.
 */
function pixel_structure_entity_type_export_form_submit($form, &$form_state) {
  // update form state for multi-step form
  $entity_type = $form_state['entity_type'];
  bundle_copy_export_submit($form, $form_state);

  // overwrite third step: export node type directly to file
  if (isset($form_state['step']) && $form_state['step'] == 3) {

    // check if bundle is available
    if (isset($form_state['bundle_key'])) {
      // create export data
      $bundle_key = $form_state['bundle_key'];
      $data = _bundle_copy_export_data($entity_type, $form_state['page_values']);
      $bundle_data = array(
        'title' => $form_state['bundle_title'],
        'code_exported' => time(),
        'data' => $data,
      );

      // export data to file
      pixel_structure_entity_type_export_data($entity_type, $bundle_key, $bundle_data);

    } else {
      // bundle not defined
      drupal_set_message(t('Bundle of type "%type" not available. No export performed!', array('%type' => $entity_type)), 'error');

    }

    // redirect to pixel structure main config page
    $form_state['rebuild'] = FALSE;
    $form_state['redirect'] = 'admin/structure/pixel_structure';
  }

}

/**
 * Returns confirm form requesting delete confirmation for a bundle of a given entity type.
 */
function pixel_structure_entity_type_delete_confirm($form, &$form_state, $entity_type, $bundle_key) {
  $confirm = null;
  $form_state['entity_type'] = $entity_type;

  switch ($entity_type) {
    case 'node':
      module_load_include('inc', 'node', 'content_types');
      $type = node_type_load($bundle_key);
      $confirm = node_type_delete_confirm($form, $form_state, $type);
      break;

    case 'taxonomy_term':
      module_load_include('inc', 'taxonomy', 'taxonomy.admin');
      $vid = taxonomy_vocabulary_get_names()[$bundle_key]->vid;
      $confirm = taxonomy_vocabulary_confirm_delete($form, $form_state, $vid);
      break;
  }

  // set correct cancel path
  $confirm['actions']['cancel']['#href'] = 'admin/structure/pixel_structure';
  $confirm['actions']['cancel']['#options']['path'] = 'admin/structure/pixel_structure';

  return $confirm;
}

/**
 * Deletes an existing node type.
 *
 * @see pixel_structure_entity_type_delete_form()
 */
function pixel_structure_entity_type_delete_confirm_submit($form, &$form_state) {
  $entity_type = $form_state['entity_type'];
  switch ($entity_type) {
    case 'node':
      node_type_delete_confirm_submit($form, $form_state);
      break;

    case 'taxonomy_term':
      taxonomy_vocabulary_confirm_delete_submit($form, $form_state);
      break;
  }

  // redirect to pixel structure main config page
  $form_state['redirect'] = 'admin/structure/pixel_structure';
}

/**
 * Clones an existing bundle of the given entity type.
 *
 * @param $entity_type  string  The entity type
 * @return array    Render array of the entry page.
 */
function pixel_structure_node_type_clone($entity_type) {
  // TODO: clone the node type

  // return to entity type list
  drupal_goto('admin/structure/pixel_structure/' . $entity_type);
}

/* =============================================================================
 *   Entity type bulk operations
 * ========================================================================== */
/**
 * Imports all changed bundle code files at once for an entity type.
 *
 * @param $entity_type
 */
function pixel_structure_entity_type_import_changed($entity_type) {
  // get all bundles of entity type
  $bundles = _pixel_structure_entity_type_bundles($entity_type);

  foreach ($bundles as $key => $bundle) {
    // get all bundles with changed code
    switch ($bundle['storage']) {
      case ENTITY_TYPE_IN_DATABASE | ENTITY_TYPE_IN_CODE:
        if ($bundle['last_import_export'] == $bundle['code_exported']) {
          // code not changed
          continue 2;
        }
        break;

      case ENTITY_TYPE_IN_DATABASE:
        // no code
        continue 2;
        break;
    }

    // import bundle code
    pixel_structure_entity_type_import($entity_type, $key, TRUE);

    // reset status messages
    $messages = drupal_set_message();
    drupal_get_messages('status');
    if (!isset($messages['error'])) {
      drupal_set_message(t('Bundle "%title" has been successfully imported', array('%title' => $bundle['title'])));
    }
  }



  // return to entity type list
  drupal_goto('admin/structure/pixel_structure');
}


/* =============================================================================
 *   Helper functions
 * ========================================================================== */
/**
 * Helper function to return an array of all available bundles of an entity type found in code and db.
 *
 * @param $entity_type  string  The entity type
 *
 * @return array    An associative array of all bundles of an entity type (stored in code files or db).
 *                  The key is the name of the bundle and the value is an associative array  with the bundle data.
 */
function _pixel_structure_entity_type_bundles($entity_type) {
  // get all bundles of an entity types stored in the database
  $bundles = array();
  foreach (_bundle_copy_bundle_info($entity_type) as $key => $title) {
    $last_import_export = variable_get('pixel_structure_last_import_export_' . $entity_type . '_' . $key, -1);
    $bundles[$key] = array('title' => $title,
                           'storage' => ENTITY_TYPE_IN_DATABASE,
                           'last_import_export' => $last_import_export);
  }

  // scan entity type directory for bundles in code
  $dir = '/' . $entity_type . 's/';
  $preg_match = '#\.' . substr($entity_type, 0, 4) . '$#';
  $files = file_scan_directory(drupal_get_path('module', 'pixel_structure') . $dir, $preg_match);

  foreach ($files as $filepath => $file) {
    // import bundle code
    require $filepath;

    // Add data to list of nodes to provide.
    if (isset($data) && is_array($data)) {
      // get export timestamp in file
      $code_exported = isset($export_time) ? $export_time : 0;

      // Create array entries.
      foreach ($data['bundles'] as $key => $bundle) {
        $title = $key; // fallback for title is machine name
        if (is_object($bundle)) {
          $title = $bundle->name;
        }
        elseif (is_array($bundle)) {
          $title = $bundle['name'];
        }

        if (array_key_exists($key, $bundles)) {
          // node type already in db
          $bundles[$key]['storage'] |= ENTITY_TYPE_IN_CODE;
          $bundles[$key]['code_exported'] = $code_exported;
          $bundles[$key]['data'] = $data;

        } else {
          // node type only in code
          $bundles[$key] = array('title' => $title,
                                 'storage' => ENTITY_TYPE_IN_CODE,
                                 'code_exported' => $code_exported,
                                 'data' => $data);
        }
      }
    }
  }

  return $bundles;
}

/**
 * Helper function to compare bundles code and db version.
 *
 * @param $entity_type
 * @param $bundle_key
 * @param $import_data  array The imported data array.
 *
 * @return bool True, if bundles code is equal to the stored version.
 */
function _pixel_structure_db_equals_code($entity_type, $bundle_key, $import_data) {
  // TODO: compare export string for exact comparison
  // get fields and field group counts in code
  $code_fields = 0;
  if (isset($import_data['fields'])) {
    $code_fields = count($import_data['fields']);
  }
  $code_fieldgroups = 0;
  if (isset($import_data['fieldgroups'])) {
    $code_fieldgroups = count($import_data['fieldgroups']);
  }

  // get counts in db
  $form = array();
  $form_state['step'] = 2;
  $form_state['page_values'][1] = array(
    'bundles' => array($bundle_key => $bundle_key),
  );
  // get fields and field groups for bundle
  $form = bundle_copy_export($form, $form_state, $entity_type);
  $db_fields = 0;
  if (isset($form['fields'])) {
    $db_fields = count($form['fields']['#options']);
  }
  $db_fieldgroups = 0;
  if (isset($form['fieldgroups'])) {
    $db_fieldgroups = count($form['fieldgroups']['#options']);
  }

  return $code_fields == $db_fields && $code_fieldgroups == $db_fieldgroups;
}