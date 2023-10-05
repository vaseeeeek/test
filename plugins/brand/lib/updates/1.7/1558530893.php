<?php

$contacts_model = new waContactModel();
$contacts_query = $contacts_model->select('id')->where('is_user = 1')->query();

foreach ($contacts_query as $contact_row)
{
	try
	{
		$contact = new waContact($contact_row['id']);
	}
	catch (waException $e)
	{
		continue;
	}

	if (!$contact->isAdmin() && $contact->getRights('shop', 'settings') != 0)
	{
		$contact->setRight('shop', 'brand.brand_edit', 1);
	}
}
unset($contacts);
