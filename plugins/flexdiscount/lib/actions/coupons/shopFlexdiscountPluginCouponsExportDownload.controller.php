<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginCouponsExportDownloadController extends waController
{

    public function execute()
    {
        $filename = waRequest::get("file");

        $wa = shopFlexdiscountApp::get('system')['wa'];
        $user = $wa->getUser();
        if (!$user->isAdmin() && !$user->getRights("shop", "flexdiscount_rules")) {
            throw new waRightsException('Access denied');
        }

        $file = $filename == 'import_example.csv' ? $wa->getAppPath('plugins/flexdiscount/templates/import_example' . ($wa->getLocale() == 'en_US' ? '_en' : '') . '.csv', 'shop') : $wa->getTempPath('flexdiscount/csv/export/' . $filename);
        if (file_exists($file)) {
            waFiles::readFile($file, $filename);
        }
    }

}
