<?php
module_load_include('inc', 'webform', 'includes/webform.submissions');
$submission = webform_get_submission($node->nid, $sid);

// get course time node
$webform_components = $node->webform['components'];
foreach ($webform_components as $key => $data) {
	if ($data['form_key'] == 'course_times_nid') {
		$course_time_nid = $submission->data[$key][0];
		break;
	}
}
$pdf_link = '#';
if ($course_time_nid) {
	$tracking_price = 0;
	$course_time_node = node_load($course_time_nid);
	if ($course_time_node && !empty($course_time_node->field_course)) {
		$course = node_load($course_time_node->field_course[LANGUAGE_NONE][0]['target_id']);
		$segment = !empty($course->field_segment) ? taxonomy_term_load($course->field_segment[LANGUAGE_NONE][0]['tid']) : FALSE;
		$location = !empty($course_time_node->field_location) ? node_load($course_time_node->field_location[LANGUAGE_NONE][0]['target_id']) : FALSE;
		$pdf_link = url('get_anmeldung/' . md5($sid . '2I7L1N1'));
		$tracking_price = $course_time_node->field_brutto_price[LANGUAGE_NONE][0]['value'];
	}
}
?>
<div class="webform-confirmation">
  <p>Besten Dank für Ihre Anmeldung.<p>
  <ul>
    <li>Den <a target="_blank" href="<?php print $pdf_link; ?>">Ausbildungsvertrag (PDF)</a> können Sie nun herunterladen</li>  
    <li>Per E-Mail erhalten Sie denselben Ausbildungsvertrag in einigen Minuten zugestellt</li>
  </ul>

  <h2>Weiteres Vorgehen</h2>
  <p>Bitte retournieren Sie uns den unterschriebenen Ausbildungsvertrag in den nächsten Tagen per Fax oder per Post. 
  	Damit wird Ihre Anmeldung rechtskräftig.</p>

  <h2>Anmeldenummer</h2>
  <p>Dies ist Ihre Anmeldenummer: <b><?php echo str_pad($sid, 6, '0', STR_PAD_LEFT); ?></b><br />
	  Halten Sie diese bei Anfragen jeweils bereit.</p>

  <h2>Rückgängig machen</h2>
  <p>Wollen Sie Ihre Anmeldung rückgängig machen, nehmen Sie bitte telefonisch mit uns
  	Kontakt auf.</p>

  <p><a href="<?php print url('node/' . $course->nid); ?>">Zurück zum Kurs/Lehrgang</a></p>
</div>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '<?php print variable_get('googleanalytics_account', ''); ?>']);
	_gaq.push(['_addTrans',
    '<?php print $sid; ?>-<?php print $submission->remote_addr; ?>', // transaction ID - required
    'HSO <?php print addslashes($location->title); ?>', // affiliation or store name
    '<?php print $tracking_price; ?>', // total - required
    '0', // tax
    '0', // shipping
    '<?php print addslashes($location->title); ?>', // city
    'XX', // state or province
    'CH' // country
  ]);
  _gaq.push(['_addItem',
    '<?php print $sid; ?>-<?php print $submission->remote_addr; ?>', // transaction ID - required
    '<?php print $course_time_node->field_internal_id[LANGUAGE_NONE][0]['value']; ?>', // SKU/code - required
    '<?php print addslashes($course->title); ?>', // product name
    '<?php print addslashes($segment->name); ?>', // category or variation
    '<?php print $tracking_price; ?>', // unit price - required
    '1' // quantity - required
  ]);
  _gaq.push(['_trackTrans']); //submits transaction to the Analytics servers
</script>
<script src="http://cdn.cxense.com/cx.js" type="text/javascript"></script>
<script>cX.library.setCustomParameters({'u_hsoch':'hsosignoff'});</script>
<div id="cX-root" style="display:none"></div>
<script type="text/javascript">
var cX = cX || {}; cX.callQueue = cX.callQueue || [];
cX.callQueue.push(['setSiteId', '9222314110606567882']);
cX.callQueue.push(['sendPageViewEvent']);
</script>
<script type="text/javascript">
(function() { try { var scriptEl = document.createElement('script'); scriptEl.type = 'text/javascript'; scriptEl.async = 'async';
scriptEl.src = ('https:' == document.location.protocol) ? 'https://scdn.cxense.com/cx.js' : 'http://cdn.cxense.com/cx.js';
var targetEl = document.getElementsByTagName('script')[0]; targetEl.parentNode.insertBefore(scriptEl, targetEl); } catch (e) {};} ());
</script>

