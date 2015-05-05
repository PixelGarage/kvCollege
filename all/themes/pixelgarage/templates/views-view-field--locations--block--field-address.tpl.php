<?php $address = $row->field_field_address[0]['raw']; ?>
<p>
	<strong><?php print check_plain($address['organisation_name']); ?></strong><br />
	<?php print check_plain($address['thoroughfare']); ?><br />
	<?php print check_plain($address['country']); ?>-<?php print check_plain($address['postal_code']); ?> <?php print check_plain($address['locality']); ?>
</p>
<p><span class="phone"><?php print check_plain($address['phone_number']); ?></span></p>
<p><span class="fax"><?php print check_plain($address['fax_number']); ?></span></p>