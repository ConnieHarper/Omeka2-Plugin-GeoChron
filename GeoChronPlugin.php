<?php
define('GEOCHRON_MAX_LOCATIONS_PER_PAGE', 50);
define('GEOCHRON_DEFAULT_LOCATIONS_PER_PAGE', 10);
define('GEOCHRON_PLUGIN_DIR', PLUGIN_DIR . '/GeoChron');
define('GEOCHRON_DIR', dirname(__FILE__));


class GeoChronPlugin extends Omeka_Plugin_AbstractPlugin
{protected $_hooks = array(
            'install', 
			'uninstall', 
			'config_form', 
			'config', 
			'define_acl', 
			//'define_routes', 
			'after_save_item', 
			'admin_items_show_sidebar', 
		//	'public_items_show', 
		//	'admin_items_search', 
		//	'public_items_search', 
		//	'items_browse_sql', 
		//	'public_head', 
			'admin_head');

    protected $_filters = array( 
//			'admin_navigation_main', 
//			'public_navigation_main', 
//			'response_contexts', 
//			'action_contexts', 
			'admin_items_form_tabs', 
			'public_navigation_items'
			);


	public function hookAdminHead($args)
	{

	}

// Hook Functions
function hookInstall()
{    
    $db = get_db();
    $sql = "
    CREATE TABLE IF NOT EXISTS $db->GeoChron(
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `item_id` BIGINT UNSIGNED NOT NULL ,
    `latitude` DOUBLE NOT NULL ,
    `longitude` DOUBLE NOT NULL ,
    `zoom_level` tinyint NOT NULL ,
    `time_begin` datetime NOT NULL ,
    `time_end` datetime NOT NULL ,
    `description` varchar(400) NULL,
    `address` varchar (200)  NULL ,
    INDEX (`item_id`)) ENGINE = MYISAM";
    $db->query($sql);
    
    
    set_option('geochron_default_latitude', '38');
    set_option('geochron_default_longitude', '-77');
    set_option('geochron_default_zoom_level', '5');
    set_option('geochron_per_page', GEOCHRON_DEFAULT_LOCATIONS_PER_PAGE);
    set_option('geochron_add_map_to_contribution_form', '1');
}

function hookUninstall()
{
    // Delete the plugin options
    delete_option('geochron_default_latitude');
	delete_option('geochron_default_longitude');
	delete_option('geochron_default_zoom_level');
	delete_option('geochron_per_page');
    delete_option('geochron_add_map_to_contribution_form');
    
    // Drop the Location table
	$db = get_db();
	$db->query("DROP TABLE $db->GeoChron");
}

function hookConfigForm()
{
    // If necessary, upgrade the plugin options
    geochron_upgrade_options();

	include 'config_form.php';
}

function hookConfig()
{   
    // Use the form to set a bunch of default options in the db
    set_option('geochron_default_latitude', $_POST['default_latitude']);
    set_option('geochron_default_longitude', $_POST['default_longitude']);
    set_option('geochron_default_zoom_level', $_POST['default_zoomlevel']); 
    set_option('geochron_item_map_width', $_POST['item_map_width']); 
    set_option('geochron_item_map_height', $_POST['item_map_height']); 
    $perPage = (int)$_POST['per_page'];
    if ($perPage <= 0) {
        $perPage = GEOCHRON_DEFAULT_LOCATIONS_PER_PAGE;
    } else if ($perPage > GEOCHRON_MAX_LOCATIONS_PER_PAGE) {
        $perPage = GEOCHRON_MAX_LOCATIONS_PER_PAGE;
    }
    set_option('geochron_per_page', $perPage);
    set_option('geochron_add_map_to_contribution_form', $_POST['geochron_add_map_to_contribution_form']);
    set_option('geochron_link_to_nav', $_POST['geochron_link_to_nav']);
}

function geochron_upgrade_options() 
{
    // Check for old plugin options, and if necessary, transfer to new options
    $options = array('default_latitude', 'default_longitude', 'default_zoom_level', 'per_page');
    foreach($options as $option) {
        $oldOptionValue = get_option('geochron_' . $option);
        if ($oldOptionValue != '') {
            set_option('geochron_' . $option, $oldOptionValue);
            delete_option('geochron_' . $option);        
        }
    }
}

function hookDefineAcl($args)
{
        $acl = $args['acl'];
        $acl->allow(null, 'Items', 'modifyPerPage');        
}

function filterPublicNavigationItems($nav)
{
    if (get_option('geochron_link_to_nav')) {
//        $nav['Browse Map'] = uri('geo-chron/map/browse');
    }
    return $nav;
}


/**
 * Each time we save an item, check the POST to see if we are also saving a 
 * location
 * @return void
 **/
function hookAfterSaveItem($args)
{
//Connie - save map items separately
    $post = $_POST;    
	$item = $args['record'];

    // If we don't have the geochron form on the page, don't do anything!
    if (!isset($post['geochron'])) {
        return;
    }
        
    // If we have filled out info for the geochron, then submit to the db
    // CONNIE - need to update or delete and re-insert updated locations... 
    $geochronPosts = $post['geochron'];
    $count = 0;
    foreach ($geochronPosts as $geochronPost) {
    if (!empty($geochronPost)) {
        $location = new GeoChron;
        $location->item_id = $item->id;
        $location->id = $geochronPost['id'];
        $location->delete = $geochronPost['delete'];
        $location->description = $geochronPost['description'];
        $location->zoom_level = $geochronPost['zoom_level'];
        $location->latitude = $geochronPost['latitude'];
        $location->longitude = $geochronPost['longitude'];
        $location->time_begin = $geochronPost['time_begin'];
        $location->time_end = $geochronPost['time_end'];
        $location->address = '';//$geochronPost['address'];
	//if there is a long and lat, then save it, else delete it
        if ((((string)$geochronPost['latitude']) != '') && 
        (((string)$geochronPost['longitude']) != '') && (!($location->delete>0))){
            $location->save($geochronPost);
        } else {
            $location->delete();
        }
    }
}
}
function hookAdminItemsShowSidebar($args)
    {
		/*
		        $view = $args['view'];
		        $item = $args['item'];
		        $location = $this->_db->getTable('GeoChron')->findLocationByItem($item, true);

		        if ($location) {
		            $html = '';
		            $html .= "<div id='geolocation' class='info-panel panel'>";
		            $html .= $view->itemGoogleMap($item, '224px', '270px' );
		            $html .= "</div>";
		            echo $html;
				}
				*/
}
// Filter Functions
function hookAdminNavigationMain($navArray)
{
    $geoNav = array('Geo Chron Map' => uri('geo-chron/map/browse'));
    $navArray += $geoNav;
    return $navArray;
}

function geochron_kml_response_context($context)
{
    $context['kml'] = array('suffix'  => 'kml', 
                            'headers' => array('Content-Type' => 'text/xml'));
    return $context;
}

function geochron_kml_action_context($context, $controller)
{
    if ($controller instanceof GeoChron_MapController) {
        $context['browse'] = array('kml');
    }
    return $context;
}

function geochron_get_map_items_per_page()
{
    $itemsPerMap = (int)get_option('geochron_per_page') or $itemsPerMap = 100;
    return $itemsPerMap;
}

/**
 * Add a Map tab to the edit item page
 * @return array
 **/
function filterAdminItemsFormTabs($tabs, $args)
{
    // insert the map tab before the Miscellaneous tab
	$item = $args['item'];
    $ht = '';
    $ht .= $this->geochron_scripts();
    $ht .= $this->geochron_map_form1($item);
    $tabs['GeoChron'] = $ht;
    return $tabs;
}

// Helpers

/**
 * Returns the html for loading the javascripts used by the plugin.
 *
 * @param bool $pageLoaded Whether or not the page is already loaded.  
 * If this function is used with AJAX, this parameter may need to be set to true.
 * @return string
 */
function geochron_scripts()
{
    $ht = '';
    $ht .= $this->geochron_load_google_maps();
    $ht .= $this->geochron_load_my_map();
    //$ht .= queue_js_file('maps');
    //$ht .= queue_js_url("http://maps.google.com/maps/api/js?sensor=false");
    return $ht;
}

/**
 * Returns the html for loading the Google Maps javascript
 *
 * @param bool $pageLoaded Whether or not the page is already loaded.  
 * If this function is used with AJAX, this parameter may need to be set to true.
 * @return string
 */
function geochron_load_google_maps()
{
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'){
    return '<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>';
	} else {
	return '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>';		
	}
}
function geochron_load_my_map()
{
	$js_file = WEB_PLUGIN."/GeoChron/views/shared/javascripts/map.js";
    return '<script type="text/javascript" src='.$js_file.'></script>';
}

/**
 * Returns a location (or array of locations) for an item (or array of items)
 * @param array|Item|int $item An item or item id, or an array of items or item ids
 * @param boolean $findOnlyOne Whether or not to return only one location if it exists for the item
 * @return array|Location A location or an array of locations
 **/
function geochron_get_location_for_item($item, $findOnlyOne = false)
{

    return get_db()->getTable('GeoChron')->findLocationByItem($item, $findOnlyOne);
}
/**
**/
function geochron_get_locations()
{
	return get_db()->getTable('GeoChron')->findLocations();
}

/**
 * Returns the default center point for the Google Map
 * @return array
 **/
function geochron_get_center()
{
    return array(
        'latitude'=>  (double) get_option('geochron_default_latitude'), 
        'longitude'=> (double) get_option('geochron_default_longitude'), 
        'zoomLevel'=> (double) get_option('geochron_default_zoom_level'));
}

function geochron_header($request)
{
    $module = $request->getModuleName();
    $controller = $request->getControllerName();
    $action = $request->getActionName();
    if ( ($module == 'geochron' && $controller == 'map')
      || ($module == 'contribution' && $controller == 'contribution' && $action == 'contribute' && get_option('geochron_add_map_to_contribution_form') == '1')):
?>
    <!-- Scripts for the Geolocation items/map page -->
    <?php echo geochron_scripts(); ?>
    
    <!-- Styles for the Geolocation items/map page -->
    <link rel="stylesheet" href="<?php echo css('geochron-items-map'); ?>" />
    <link rel="stylesheet" href="<?php echo css('geochron-marker'); ?>" />
    
<?php
    endif;
}


/**
 * Returns the google map code for an item
 * @param Item $item
 * @param int $width
 * @param int $height
 * @param boolean $hasBalloonForMarker
 * @return string
 **/
function geochron_google_map_for_item($item = null, $width = '200px', $height = '200px', $hasBalloonForMarker = true, $markerHtmlClassName = 'geochron_balloon') {  
    if (!$item) {
        $item = get_current_record('item');
    }      
    $ht = '';
    $divId = "item-map-{$item->id}";
    ob_start();
    if ($hasBalloonForMarker) {
        echo geochron_marker_style();        
    }
?>
<style type="text/css" media="screen">
    /* The map for the items page needs a bit of styling on it */
    #address_balloon dt {
        font-weight: bold;
    }
    #address_balloon {
        width: 100px;
    }
    #<?php echo $divId;?> {
        width: <?php echo $width; ?>;
        height: <?php echo $height; ?>;
    }
    div.map-notification {
        width: <?php echo $width; ?>;
        height: <?php echo $height; ?>;
        display:block;
        border: 1px dotted #ccc;
        text-align:center;
        font-size: 2em;
    }
</style>
<?php        
//connie - here show multiple locations and put time info in title
    $locations = geochron_get_location_for_item($item,false);
    // Only set the center of the map if this item actually has a location 
    // associated with it
    $i=0;
    $options = array();
    if ($locations){
    foreach ($locations as $location) {
    if ($location) {
        $center['latitude']     = $location['latitude'];
        $center['longitude']    = $location['longitude'];
        $center['zoomLevel']    = $location['zoom_level'];
        $center['show']         = true;
        $options[$i]['point'] = array('latitude' => $location['latitude'], 
                                  'longitude' => $location['longitude'], 
                                  'zoomLevel' => $location['zoom_level']);
        if ($hasBalloonForMarker) {
            	$center['markerHtml']   = '<div> '.$location['time_begin'].'-'.$location['time_end'].'</div>'; 
            	$options[$i]['markerHtml']   = '<div> '.$location['time_begin'].'-'.$location['time_end'].'</div>';
    $titleLink = link_to_item(item('Dublin Core', 'Title', array(), $item), array(), 'show', $item);
    $thumbnailLink = !(item_has_thumbnail($item)) ? '' : link_to_item(item_thumbnail(array(), 0, $item), array(), 'show', $item);
    $description = item('Dublin Core', 'Description', array('snippet'=>150), $item);
    $description = $timestart.'-'.$timeend.' '.$description;
    $options[$i]['markerHtml'] = '<div class="' . $markerHtmlClassName . '"><p class="geochron_marker_title">' . $titleLink . '</p>' . $thumbnailLink . '<p>' . $description . '</p></div>';
    //        	$options[$i]['markerHtml']   = '<div> '.$location['time_begin'].'-'.$location['time_end'].'</div>';
            	//$options[$i]['markerHtml']   = "test".$i;//geochron_get_marker_html_for_item($item, $markerHtmlClassName);            
        }
	}
	$i++;
	}
        $center = json_encode($center);
        $options = json_encode($options);
        echo '<div id="' . $divId . '" class="map"></div>';
?>        
        <script type="text/javascript">
        //<![CDATA[
            var <?php echo Inflector::variablize($divId); ?>OmekaMapBrowse = new OmekaMapBrowse(<?php echo json_encode($divId); ?>, <?php echo $center; ?>, <?php echo $options; ?>);
        //]]>
        </script>
<?php         
    } else {
?>
        <p class="map-notification">This item has no location info associated with it.</p>
<?php
    }
    $ht .= ob_get_contents();
    ob_end_clean();
    return $ht;
}


function geochron_get_marker_html_for_item($item, $markerHtmlClassName = 'geochron_balloon')
{
    $returnString ='';
    $titleLink = link_to_item(item('Dublin Core', 'Title', array(), $item), array(), 'show', $item);
    $thumbnailLink = !(item_has_thumbnail($item)) ? '' : link_to_item(item_thumbnail(array(), 0, $item), array(), 'show', $item);
    //connie - here put time begin and end
    //$locations = geochron_get_location_for_item($item, false);
    //foreach($locations as $location){
     //       $timestart = $location['time_begin'];
      //      $timeend = $location['time_end'];
    $description = item('Dublin Core', 'Description', array('snippet'=>150), $item);
    //$description = $timestart.'-'.$timeend.' '.$description;
    $returnString .= '<div class="' . $markerHtmlClassName . '"><p class="geochron_marker_title">' . $titleLink . '</p>' . $thumbnailLink . '<p>' . $description . '</p></div>';
//	}
    return $returnString; 
}
function geochron_get_marker_html_for_location($location, $item, $markerHtmlClassName = 'geochron_balloon')
{
    $returnString ='';
    $titleLink = link_to_item(item('Dublin Core', 'Title', array(), $item), array(), 'show', $item);
    $thumbnailLink = !(item_has_thumbnail($item)) ? '' : link_to_item(item_thumbnail(array(), 0, $item), array(), 'show', $item);
    //connie - here put time begin and end
    $timestart = $location['time_begin'];
    $timeend = $location['time_end'];
    $description = item('Dublin Core', 'Description', array('snippet'=>150), $item);
    $description = $timestart.'-'.$timeend.' '.$description;
    $returnString .= '<div class="' . $markerHtmlClassName . '"><p class="geochron_marker_title">' . $titleLink . '</p>' . $thumbnailLink . '<p>' . $description . '</p></div>';
	
    return $returnString; 
}


/**
 * Returns the form code for geographically searching for items
 * @param Item $item
 * @param int $width
 * @param int $height
 * @return string
 **/
function geochron_map_blank($item, $width = '500px', $height = '320px', $label = 'Find A Location For The Item:', $confirmLocationChange = true,  $count = 0) { 	
	$center = geochron_get_center();    
	$center['show'] = false;    
	$options = array();
        ob_start();
	$id = $lng = $lat = $zoom = $timestart = $timeend = $addr = '';
}
function geochron_map_form1($item, $width = '500px', $height = '320px', $label = 'Find A Location For The Item:', $confirmLocationChange = true,  $post = null) { 	
{

	$center = $this->geochron_get_center();    
	$center['show'] = false;    
	$options = array();
	$locations = $this->geochron_get_location_for_item($item, false);    
        
	$ht = '<style type="text/css" media="screen">';
        /* Need a bit of styling for the geocoder balloon */
	$ht .= "</style>";
    $ht .= "<div id='omeka-map-form' class='myMap' style='width:500px; height:320px;' >    </div>";
	/** put a map and create markers for existing geochron records **/
	$count = 0;
	$markers = array();
	$options = array();
    	foreach ($locations as $location)
	{
	 	$options = array();
		$formname  = "marker-info".$count;
		$options['form'] = array('id' => $formname,
                             'posted' => false,
				'geochron_id' => (int) $location['id'],
				'item_id' => (int) $location['item_id'],
				'description'=>$location['description'],
				'begin_date'=>$location['time_begin'],
				'end_date'=>$location['time_end'],
				'orig_lat'=>(double) $location['latitude'],
				'orig_lng'=>(double) $location['longitude']);
       		$options['point'] = array('latitude' => (double) $location['latitude'],
                             'longitude' => (double) $location['longitude'],
                             'zoomLevel' => (int) $location['zoom_level']);

	        $options['confirmLocationChange'] = $confirmLocationChange;
	
		$markers[$count]['center']= $center;
		$markers[$count]['options']= $options;
		$count++;
	} //for each location
	if ($markers){
		$markers = json_encode($markers);
	} else {
		//Make a default marker - default center and options to initialize the map with no location
		$divid = "marker-info";
		$markers[0]['center']=$center;
		$markers[0]['options']['form']['id']=$divid;
		$markers = json_encode($markers);
	}
	//Now, add the map, with list of markers.  map.js will add the markers and input boxes
	 $js = "var anOmekaMapForm = new OmekaMapForm(" . js_escape('omeka-map-form') . ", $markers);";
     $js .= " jQuery(document).bind('omeka:tabselected', function () {
					anOmekaMapForm.resize();
		       });                        
		    ";
	 $ht .= "<script type='text/javascript'>" . $js . "</script>";
    return $ht;
} // end of function omeka_map_form
/**
 * Returns the html for the marker CSS
 * @return string
 **/
function geochron_marker_style()
{
    $html = '<link rel="stylesheet" href="'.css("geochron-marker").'" />';
    return $html;
}

/**
 * Shows a small map on the admin show page in the secondary column
 * @param Item $item
 * @return void
 **/
function geochron_admin_show_item_map($item)
{

        include 'geochron_secondary.php';
/*
$firephp = FirePHP::getInstance(true);
$firephp->log('Hello World testing');
    $html = geochron_scripts()
          . '<div class="info-panel">'
          . '<h2>GeoChron</h2>'
          . geochron_google_map_for_item($item,'224px','270px')
          . '</div>';
    return $html;
*/
}

function geochron_public_show_item_map($width = null, $height = null, $item = null)
{
$firephp = FirePHP::getInstance(true);
$firephp->log('Public Hello World');
    if (!$width) {
        $width = get_option('geochron_item_map_width') ? get_option('geochron_item_map_width') : '100%';
    }
    
    if (!$height) {
        $height = get_option('geochron_item_map_height') ? get_option('geochron_item_map_height') : '300px';
    }
    
    if (!$item) {
        $item = get_current_record('item');
    }
    
    $html = $this->geochron_scripts()
          . '<div class="info-panel">'
          . '<h3>GeoChron</h3>'
          . $this->geochron_google_map_for_item($item, $width, $height)
	  . '</div>';

    return $html;
}

function geochron_append_contribution_form($contributionType)
{
    if (get_option('geochron_add_map_to_contribution_form') == '1') {
        $html = '<div id="geochron_contribution">'
              . $this->geochron_map_form1(null, '500px', '410px', 'Find A Geographic Location For The ' . $contributionType->display_name . ':', false)
              . '</div>'
              . '<script type="text/javascript">'
              . 'jQuery("#contribution-type-form").bind("contribution-form-shown", function () {anOmekaMapForm.resize();});'
              . '</script>';
        echo $html;
    }
}

function geochron_save_contribution_form($contributionType, $item, $post)
{
    if (get_option('geochron_add_map_to_contribution_form') == '1') {
        geochron_save_location($item);
    }
}

function geochron_item_browse_sql($select, $params)
{
    // It would be nice if the item_browse_sql hook also passed in the request 
    // object.
    if (($request = Omeka_Context::getInstance()->getRequest())) {

        $db = get_db();

        // Get the address, latitude, longitude, and the radius from parameters
        $address = trim($request->getParam('geochron-address'));
        $currentLat = trim($request->getParam('geochron-latitude'));
        $currentLng = trim($request->getParam('geochron-longitude'));
//connie - add to advanced search - time begin and end
        $currentTimeBegin = trim($request->getParam('geochron-timebegin'));
        $currentTimeEnd = trim($request->getParam('geochron-timeend'));
        $radius = trim($request->getParam('geochron-radius'));

        if ($request->get('only_map_items') || $address != '') {
            //INNER JOIN the locations table
            $select->joinInner(array('l' => $db->GeoChron), 'l.item_id = i.id', 
                array('latitude', 'longitude', 'address', 'time_begin', 'time_end'));
        }
        
        // Limit items to those that exist within a geographic radius if an address and radius are provided 
        if ($address != '' && is_numeric($currentLat) && is_numeric($currentLng) && is_numeric($radius)) {
            // SELECT distance based upon haversine forumula
            $select->columns('3956 * 2 * ASIN(SQRT(  POWER(SIN(('.$currentLat.' - l.latitude) * pi()/180 / 2), 2) + COS('.$currentLat.' * pi()/180) *  COS(l.latitude * pi()/180) *  POWER(SIN(('.$currentLng.' -l.longitude) * pi()/180 / 2), 2)  )) as distance');
            // WHERE the distance is within radius miles of the specified lat & long
             $select->where('(latitude BETWEEN '.$currentLat.' - ' . $radius . '/69 AND ' . $currentLat . ' + ' . $radius .  '/69)
             AND (longitude BETWEEN ' . $currentLng . ' - ' . $radius . '/69 AND ' . $currentLng  . ' + ' . $radius .  '/69)'
	     .' AND (time_begin > "'.$currentTimeBegin.'" or time_end <"'.$currentTimeEnd.'")');
            //ORDER by the closest distances
            $select->order('distance');
        }
    
        // This would be better as a filter that actually manipulated the 
        // 'per_page' value via this plugin. Until then, we need to hack the 
        // LIMIT clause for the SQL query that determines how many items to 
        // return.
        if ($request->get('use_map_per_page')) {            
            // If the limit of the SQL query is 1, we're probably doing a 
            // COUNT(*)
            $limitCount = $select->getPart(Zend_Db_Select::LIMIT_COUNT);
            if ($limitCount != 1) {                
                $select->reset(Zend_Db_Select::LIMIT_COUNT);
                $select->reset(Zend_Db_Select::LIMIT_OFFSET);
                $pageNum = $request->get('page') or $pageNum = 1;                
                $select->limitPage($pageNum, geochron_get_map_items_per_page());
            }
        }
    }
}
}
} //Class GeoChron 
