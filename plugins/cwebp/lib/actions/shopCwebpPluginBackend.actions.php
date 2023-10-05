<?php

class shopCwebpPluginBackendActions extends waJsonActions
{
    public function deleteAction()
    {
        foreach (range(0, 4) as $x) {
            foreach ([0, 1] as $y) {
                $path = wa()->getDataPath("products/$y$x/webp/", true, 'shop');
                waFiles::delete($path);
            }
        }
        waFiles::delete(wa()->getDataPath('plugins/cwebp', true, 'shop'));
        (new shopCwebpPluginQueueModel())->exec('TRUNCATE TABLE shop_cwebp_queue');
    }
}