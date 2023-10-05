<?php

/**
 * Updates storefront-dependent tag cloud
 */

class shopTageditorPluginIndex
{
    const UPDATE_BATCH_SIZE = 100;

    const KEY_UPDATE_ACTION_INCREASE = 'increase';
    const KEY_UPDATE_ACTION_REDUCE   = 'reduce';

    private $product_ids;
    private $index;
    private $changed_index_entries;

    public function __construct($product_ids)
    {
        $this->product_ids = (array) $product_ids;
        $this->changed_index_entries = array();
    }

    public function updateProducts()
    {
        if (empty($this->product_ids)) {
            return;
        }

        //getting both old and new tags associated with submitted products regardless of those products' current status and existence
        $products_tags = $this->getProductsTags();
        if (!$products_tags) {
            //nothing to update
            return;
        }

        $this->index = $this->getProductsTagsIndex($products_tags);
        $this->updateIndexData();

        $this->saveRawIndex();
        $this->saveCleanIndex();
    }

    private function correctIndexKeyCount($action, $key, $new = null, $old = null)
    {
        switch ($action) {
            case self::KEY_UPDATE_ACTION_INCREASE:
                if (isset($this->index[$key])) {
                    $this->index[$key]['count']++;
                } else {
                    $this->index[$key] = array(
                        'tag_id'  => $new['tag_id'],
                        'type_id' => $new['type_id'],
                        'count'   => 1,
                    );
                }
                $this->changed_index_entries[$key] = $this->index[$key];
                break;

            case self::KEY_UPDATE_ACTION_REDUCE:
                if (isset($this->index[$key])) {
                    $this->changed_index_entries[$key] = $this->index[$key];
                    if ($this->index[$key]['count'] > 1) {
                        $this->index[$key]['count']--;
                    } else {
                        unset($this->index[$key]);
                    }
                } elseif (is_array($old)) {
                    $this->changed_index_entries[$key] = $old;
                }
                break;
        }
    }

    private function getProductsTags()
    {
        $products_tags_new = shopTageditorPluginModels::shopProductTags()->
            select('DISTINCT tag_id')->
            where('product_id IN(i:product_ids)', array(
                'product_ids' => $this->product_ids
            ))->fetchAll(null, true);

        $products_tags_old = shopTageditorPluginModels::indexProductTags()->
            select('DISTINCT tag_id')->
            where('product_id IN(i:product_ids)', array(
                'product_ids' => $this->product_ids
            ))->fetchAll(null, true);

        if ($products_tags_new || $products_tags_old) {
            if ($products_tags_new && $products_tags_old) {
                $products_tags = array_merge($products_tags_new, $products_tags_old);
            } else {
                $products_tags = ifempty($products_tags_new, $products_tags_old);
            }
        }

        if (!empty($products_tags)) {
            $products_tags = array_unique($products_tags);
        } else {
            $products_tags = null;
        }

        return $products_tags;
    }

    private function getNewProductData()
    {
        $result = array();

        //getting products' types and statuses
        $product_data = shopTageditorPluginModels::shopProduct()->
            select('id, type_id, status')->
            where('id IN(i:product_ids)', array(
                'product_ids' => $this->product_ids
            ))->fetchAll('id', true);

        //getting product + tag entries
        $new_product_tag_data = shopTageditorPluginModels::shopProductTags()->
            select('*')->
            where('product_id IN(i:product_ids)', array(
                'product_ids' => $this->product_ids
            ))->fetchAll();

        //adding products' types and statuses to product + tag entries
        //and change array keys to 'product_id:tag_id' format
        foreach ($new_product_tag_data as $i => $product_tag_entry) {
            $product_tag_entry['type_id'] = $product_data[$product_tag_entry['product_id']]['type_id'];
            $product_tag_entry['status'] = $product_data[$product_tag_entry['product_id']]['status'];

            $key = $product_tag_entry['product_id'].':'.$product_tag_entry['tag_id'];
            $result[$key] = $product_tag_entry;
        }

        return $result;
    }

    private function getOldProductData()
    {
        $result = array();

        //getting outdated product + tag + type entries from plugin's table
        //to compare them with current data and update plugin's index using the comparison results
        $old_product_tag_data = shopTageditorPluginModels::indexProductTags()->
            select('*')->
            where('product_id IN(i:product_ids)', array(
                'product_ids' => $this->product_ids
            ))->fetchAll();

        //changing this array's keys to 'product_id:tag_id', too
        foreach ($old_product_tag_data as $i => $old_product_tag_entry) {
            $key = $old_product_tag_entry['product_id'].':'.$old_product_tag_entry['tag_id'];
            $result[$key] = $old_product_tag_entry;
        }

        return $result;
    }

    private function getProductsTagsIndex($products_tags)
    {
        $result = array();

        //getting current index to update it
        $index = shopTageditorPluginModels::indexTag()->
            select('*')->
            where('tag_id IN(i:tag_ids)', array(
                'tag_ids' => $products_tags
            ))->fetchAll();

        //changing index array keys to 'tag_id:type_id' format
        foreach ($index as $i => $index_entry) {
            $key = $index_entry['tag_id'].':'.$index_entry['type_id'];
            $result[$key] = $index_entry;
        }

        return $result;
    }

    private function updateIndexData()
    {
        $new_data = $this->getNewProductData();
        $old_data = $this->getOldProductData();

        //updating index by comparing plugin's outdated data with shop's current data
        //1. ...new or existing tags
        foreach ($new_data as $key => $new_product_tag_entry) {
            if (isset($old_data[$key])) {
                //product existed before, was visible, and was linked to this key's tag

                if ($old_data[$key]['type_id'] != $new_product_tag_entry['type_id']) {
                    //product type has changed

                    //index tag entry for product's old type
                    $old_type_key = $old_data[$key]['tag_id'].':'.$old_data[$key]['type_id'];

                    if ($new_product_tag_entry['status']) {
                        //product has remained visible; only its type has changed

                        //reducing count for product's old type by 1 or removing its entry with count=1
                        $this->correctIndexKeyCount(self::KEY_UPDATE_ACTION_REDUCE, $old_type_key);

                        //increasing count for product's new type by 1 or adding a new entry with count=1
                        $new_type_key = $new_product_tag_entry['tag_id'].':'.$new_product_tag_entry['type_id'];
                        $this->correctIndexKeyCount(self::KEY_UPDATE_ACTION_INCREASE, $new_type_key, $new_product_tag_entry);
                    } else {
                        //product has been hidden

                        //reducing count for product's old type by 1 or removing its entry with count=1
                        $this->correctIndexKeyCount(self::KEY_UPDATE_ACTION_REDUCE, $old_type_key);
                    }
                } else {
                    //product type has remained unchanged

                    if (!$new_product_tag_entry['status']) {
                        //updaing index only if its status has changed: from visible to hidden in this case

                        //reducing count for product's new=old type by 1 or removing its entry with count=1
                        $type_key = $new_product_tag_entry['tag_id'].':'.$new_product_tag_entry['type_id'];
                        $this->correctIndexKeyCount(self::KEY_UPDATE_ACTION_REDUCE, $type_key);
                    }
                }
            } else {
                //product was hidden or didn't exist before, or was not linked to this key's tag

                if ($new_product_tag_entry['status']) {
                    //product is visible now

                    //increasing count for product's type by 1 or adding a new entry with count=1
                    $type_key = $new_product_tag_entry['tag_id'].':'.$new_product_tag_entry['type_id'];
                    $this->correctIndexKeyCount(self::KEY_UPDATE_ACTION_INCREASE, $type_key, $new_product_tag_entry);
                }

                //else: if product was hidden before or didn't exist and is hidden now, there is no need to update this key's tag count
            }
        }

        //2. ...deleted tags
        foreach ($old_data as $key => $old_product_tag_entry) {
            if (!isset($new_data[$key])) {
                $old_index_key = $old_product_tag_entry['tag_id'].':'.$old_product_tag_entry['type_id'];
                $this->correctIndexKeyCount(self::KEY_UPDATE_ACTION_REDUCE, $old_index_key, null, $old_product_tag_entry);
            }
        }
    }

    private function saveRawIndex()
    {
        //deleting plugin's outdated product + tag entries
        shopTageditorPluginModels::indexProductTags()->deleteByField('product_id', $this->product_ids);

        //generating and saving updated product + tag entries to plugin's table
        $visible_products_types = shopTageditorPluginModels::shopProduct()->
            select('id, type_id')->
            where('id IN(i:product_ids) AND status <> 0', array(
                'product_ids' => $this->product_ids
            ))->fetchAll('id', true);

        if ($visible_products_types) {
            $visible_products_tags = shopTageditorPluginModels::shopProductTags()->
                select('product_id, tag_id')->
                where('product_id IN(i:product_ids)', array(
                    'product_ids' => array_keys($visible_products_types)
                ))->fetchAll();

            if ($visible_products_tags) {
                $products_tags_index = array();
                foreach ($visible_products_tags as $product_tag_entry) {
                    $products_tags_index[] = array(
                        'product_id' => $product_tag_entry['product_id'],
                        'tag_id'     => $product_tag_entry['tag_id'],
                        'type_id'    => $visible_products_types[$product_tag_entry['product_id']],
                    );
                }

                shopTageditorPluginModels::indexProductTags()->multipleInsert($products_tags_index);
            }
        }
    }

    private function saveCleanIndex()
    {

        //deleting changed entries from index table
        if ($this->changed_index_entries) {
            $delete_clause = array();
            foreach ($this->changed_index_entries as $changed_index_entry) {
                $delete_clause[] = sprintf(
                    '(%s AND %s)',
                    sprintf('tag_id = %u', $changed_index_entry['tag_id']),
                    sprintf('type_id = %u', $changed_index_entry['type_id'])
                );
            }
            $delete_clause = implode(' OR ', $delete_clause);

            shopTageditorPluginModels::indexTag()->exec('DELETE FROM shop_tageditor_index_tag WHERE '.$delete_clause);
        }

        //saving new index entries: changed and new ones
        if ($this->index) {
            //remove non-changed entries from index which is about to be saved to database, because they were not deleted
            foreach ($this->index as $key => $index_entry) {
                if (!isset($this->changed_index_entries[$key])) {
                    unset($this->index[$key]);
                }
            }
            shopTageditorPluginModels::indexTag()->multipleInsert(array_values($this->index));
        }
    }
}
