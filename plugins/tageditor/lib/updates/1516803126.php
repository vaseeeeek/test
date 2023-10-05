<?php

$model = new waModel();

//Cleaning tag data remaining after inaccurate deletion of empty tags:
//deleting entries with id's of non-existent tags.
$model->exec('
    DELETE FROM shop_tageditor_tag
    WHERE id NOT IN (
        SELECT id
        FROM shop_tag
    )
');
