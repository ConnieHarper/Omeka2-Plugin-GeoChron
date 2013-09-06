<?php
class GeoChronTable extends Omeka_Db_Table
{
    /**
     * Returns a location (or array of locations) for an item (or array of items)
     * @param array|Item|int $item An item or item id, or an array of items or item ids
     * @param boolean $findOnlyOne Whether or not to return only one location if it exists for the item
     * @return array|Location A location or an array of locations
     **/
    public function findLocationByItem($item, $findOnlyOne = false)
    {
        $db = get_db();
        
        if (($item instanceof Item) && !$item->exists()) {
            return array();
        } else if (is_array($item) && !count($item)) {
            return array();
        }
        
        // Create a SELECT statement for the Location table
        $select = $db->select()->from(array('l' => $db->GeoChron), 'l.*');
        
        // Create a WHERE condition that will pull down all the location info
        if (is_array($item)) {
            $itemIds = array();
            foreach ($item as $it) {
                $itemIds[] = (int)(($it instanceof Item) ? $it->id : $it);
            }
            $select->where('l.item_id IN (?)', $itemIds);
        } else {
            $itemId = (int)(($item instanceof Item) ? $item->id : $item);
            $select->where('l.item_id = ?', $itemId);
        }
        
        // Get the locations
        $locations = $this->fetchObjects($select);
        
        // If only a single location is request, return the first one found.
        if ($findOnlyOne) {
            return current($locations);
        }
        
        // Return an associative array of locations where the key is the id of the location
        $indexedLocations = array();
        foreach ($locations as $k => $loc) {
            $indexedLocations[$loc['id']] = $loc;
        }
        return $indexedLocations;
    }
    public function findLocations()
    {
        $db = get_db();
        // Create a SELECT statement for the Location table
        $select = $db->select()->from(array('l' => $db->GeoChron), 'l.*');
        
	// Get the locations
        $locations = $this->fetchObjects($select);
        
        $indexedLocations = array();
        foreach ($locations as $k => $loc) {
            $indexedLocations[$loc['id']] = $loc;
        }
        return $indexedLocations;
    }
}
