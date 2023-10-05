<?php

class shopCwebpPluginRunCli extends waCliController
{
    public function execute()
    {
        $model = new shopCwebpPluginQueueModel();
        while ($model->countAll() > 0) {
            $pair = $model->query("SELECT * FROM shop_cwebp_queue LIMIT 1")->fetchAssoc();
            $model->deleteById($pair['source']);
            if (!file_exists($pair['destination'])) {
                (new shopCwebpPluginConvert($pair['source'], $pair['destination'], 'Thumb by cli'))->convert();
            }
        }
    }
}