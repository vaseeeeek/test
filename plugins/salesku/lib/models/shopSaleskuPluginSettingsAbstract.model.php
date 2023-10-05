<?php
abstract class shopSaleskuPluginSettingsAbstractModel extends waModel
{
    abstract public function getByStorefront($storefront_id, $data_id = null);
    abstract public function saveByStorefront($storefront_id, $data_id, $values = null);
}

