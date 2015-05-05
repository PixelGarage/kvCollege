<?php
	if (!empty($row->field_field_maximum) && !empty($row->field_field_maximum[0]['raw']['value'])) {
		$max_tn = $row->field_field_maximum[0]['raw']['value'];
		$taken_places = !empty($row->field_field_taken_places) ? $row->field_field_taken_places[0]['raw']['value'] : 0;
		$limit = floor($max_tn / 4);
		if ($max_tn - $taken_places > $limit) {
			$output .= '<p><strong>' . t('Anzahl') . ':</strong> &gt;' . $limit;
		} elseif ($max_tn - $taken_places > 0) {
			$output .= '<p><strong>' . t('Anzahl') . ':</strong> &gt;' . ($max_tn - $taken_places);
		} else {
			$output .= '<p><strong>' . t('Anzahl') . ':</strong> 0';
		}
	}
?>
<?php print $output; ?>