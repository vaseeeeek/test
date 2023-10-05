<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

namespace Igaponov\flexdiscount;

class Contact
{
    public function set($fields, $contact = null)
    {
        $contact = $contact === null ? $this->get(true) : $contact;
        foreach ($fields as $field_id => $field) {
            $contact->set($field_id, isset($field['value']) ? $field['value'] : $field);
        }
        $this->save($contact);
        return $contact;
    }

    public function save($contact)
    {
        $app = new \shopFlexdiscountApp();
        $app->set('order.contact', $contact);
        $app->set('order.contact_id', $this->getContactId());
    }

    /**
     * Get contact info
     *
     * @param bool $check_zero_id
     * @return \waContact
     */
    public function get($check_zero_id = false)
    {
        $app = new \shopFlexdiscountApp();
        $wa = $app::get('system')['wa'];

        $contact = $app::get('order.contact');
        if ($contact) {
            return $contact;
        }
        $checkout_params = $wa->getStorage()->get('shop/checkout');
        if (!empty($checkout_params['contact']) && (!$check_zero_id || $checkout_params['contact']->getId())) {
            $contact = $checkout_params['contact'];
        } else {
            $contact = $wa->getUser();
            if (!empty($checkout_params['contact']) && $checkout_params['contact'] instanceof \waContact) {
                if ($data = $checkout_params['contact']->load()) {
                    $contact = $this->set($this->prepareContactDataBeforeSave($data), $contact);
                }
            }
        }
        $this->save($contact);

        return $contact;
    }

    /**
     * Prepare data, that was loaded from the contact
     *
     * @param array $data
     * @return array
     */
    private function prepareContactDataBeforeSave($data)
    {
        $prepared_data = [];
        if (is_array($data)) {
            if (!empty($data['address'])) {
                foreach ($data['address'] as $address) {
                    $key = !empty($address['ext']) ? 'address.' . $address['ext'] : 'address';
                    $prepared_data[$key] = ifempty($address, 'data', []);
                }
                unset($data['address']);
            }
            $prepared_data += $data;
        }
        return $prepared_data;
    }

    private function getContactId()
    {
        $contact = \shopFlexdiscountApp::get('order.contact');
        return ($contact ? ($contact->getId() ? $contact->getId() : 0) : 0);
    }
}