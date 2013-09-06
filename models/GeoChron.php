<?php
require_once 'GeoChronTable.php';
/**
 * Location
 * @package: Omeka
 */
class GeoChron extends Omeka_Record_AbstractRecord
{
    public $id;
    public $item_id;
    public $latitude;
    public $longitude;
    public $zoom_level;
    public $address;
    public $description;
    public $time_begin;
    public $time_end;
    
    protected function _validate()
    {
        if (empty($this->item_id)) {
            $this->addError('item_id', 'GeoChronLocation requires an item id.');
        }
    }
}
