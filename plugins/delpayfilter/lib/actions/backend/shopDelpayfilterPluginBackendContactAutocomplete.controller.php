<?php

class shopDelpayfilterPluginBackendContactAutocompleteController extends waController
{

    protected $limit = 10;

    public function execute()
    {
        $data = array();
        $q = waRequest::get('term', '', waRequest::TYPE_STRING_TRIM);
        if ($q) {
            wa('contacts');
            $collection = new contactsCollection('search/name*=' . $q);
            $contacts = $collection->getContacts('*');
            if (!$contacts) {
                $collection = new contactsCollection('search/email*=' . $q);
                $contacts = $collection->getContacts('*');
            }

            $data = $this->formatData($contacts);
        }
        echo json_encode($data);
    }

    private function formatData($data)
    {
        $formatted = array();
        foreach ($data as &$item) {
            $item['name'] = waContactNameField::formatName($item);
            $formatted[] = array(
                'id' => $item['id'],
                'label' => trim($item['name']) ? htmlspecialchars($item['name']) : '&lt;' . _wp("No name") . '&gt;'
            );
        }

        return $formatted;
    }

}
