<?php

class shopServicesetsPlugin extends shopPlugin
{
    public function getGroups() {
        $modelGroups = new shopServicesetsPluginGroupsModel();
        return $modelGroups->getAll();
    }

    public function getImageServices($id)
    {
        $modelService = new shopServicesetsPluginServicesModel();
        $service = $modelService->getByField('id_service', $id);
//        echo "<pre>";
//        var_dump($service);
//        echo "</pre>";
        $srcone = strstr($service['image_one'],'/wa-apps');
        $srctwo = strstr($service['image_one'],'/wa-apps');
        $arrayService = ['image_one' => $srcone,'image_two' => $srctwo];
        return $arrayService;
    }

    public function getDescriptionServices($id)
    {
        $modelService = new shopServicesetsPluginServicesModel();
        $service = $modelService->getByField('id_service', $id);
        $src = $service['description'];
        return $src;
    }

    public function getImageVariants($id)
    {
        $modelVariants = new shopServicesetsPluginVariantsModel();
        $service = $modelVariants->getByField('id_variants', $id);
        $srcone = strstr($service['image_one'],'/wa-apps');
        $srctwo = strstr($service['image_one'],'/wa-apps');
        $arrayService = ['image_one' => $srcone,'image_two' => $srctwo];
        return $arrayService;
    }


}
