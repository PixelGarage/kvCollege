<?php
	$link = TRUE;
	if (!empty($row->field_field_maximum) && !empty($row->field_field_maximum[0]['raw']['value'])) {
		if (!empty($row->field_field_taken_places)) {
			$link = $row->field_field_maximum[0]['raw']['value'] > $row->field_field_taken_places[0]['raw']['value'];
		}
	}
	if (!empty($row->field_field_no_vacancy) && !empty($row->field_field_no_vacancy[0]['raw']['value'])) {
		$link = FALSE;
	}
	if ($link) {
    if (!empty($row->field_field_anmeldungs_link) && !empty($row->field_field_anmeldungs_link[0]['raw']['value'])) {
      // if Anmeldungs link is available, set this link
      $output = l(t('Anzeigen'), $row->field_field_anmeldungs_link[0]['raw']['value'], array('html' => TRUE));
    } else {
      $output = l($output, 'node/' . $row->nid . '/anmeldung', array('html' => TRUE));
    }
	} else {
		$output = '';
	}
?>
<?php print $output; ?>