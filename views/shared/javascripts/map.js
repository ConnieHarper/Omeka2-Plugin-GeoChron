function OmekaMap(mapDivId, center, options) {
    this.mapDivId = mapDivId;
    this.center = center;
    this.options = options;
    
}

OmekaMap.prototype = {
    
    map: null,
    mapDivId: null,
    mapSize: 'small',
    markers: [],
    options: {},
    center: null,
addMarker: function (pan,lat, lng, options, bindHtml)
    {        
        if (!options) {
            options = {};
        }
        options.position = new google.maps.LatLng(lat, lng);
	options.draggable=true;
        options.map = this.map;
          
        var marker = new google.maps.Marker(options);
	if(pan){
		this.map.panTo(marker.getPosition());
	}
        google.maps.event.addListener(marker, 'mouseover', function (event) {
		jQuery('.newLocation').removeClass('myLocationSelected');
		jQuery('#'+options.form.id).addClass('myLocationSelected');
	});
        google.maps.event.addListener(marker, 'drag', function (event) {
		jQuery('#'+options.form.id).addClass('myLocationSelected');
	});
        google.maps.event.addListener(marker, 'dragend', function (event) {
            	var point = event.latLng;
		jQuery('#'+options.form.id).find('.hiddenLat').val(point.lat());
		jQuery('#'+options.form.id).find('.hiddenLon').val(point.lng());
	});
/**  How to change the window Html every time the lat long changes?
        google.maps.event.addListener(marker, 'click', function () {
		if (!bindHtml){
			var lat = marker.getPosition().lat();
			var lng = marker.getPosition().lng();
			var desc = jQuery('#'+options.form.id).find('.geochron-description').val();
			bindHtml=desc+"<br/>Latitude:"+lat+"<br/>Longitude:"+lng;
		} 
       	        var infoWindow = new google.maps.InfoWindow();
		infoWindow.setContent(bindHtml);
       	        infoWindow.open(marker.getMap(), marker);
        });
	// link marker to data on the form about the marker
        jQuery("#"+options.form.id).hover(function(){
		google.maps.event.trigger(marker, 'click');
	});
***/
        this.markers.push(marker);
        return marker;
    },
    
    initMap: function () {
        
        // Build the map.
        var mapOptions = {
            zoom: this.center.zoomLevel,
            center: new google.maps.LatLng(this.center.latitude, this.center.longitude),
            mapTypeId: google.maps.MapTypeId.HYBRID,
            navigationControl: true,
            mapTypeControl: true
        };    
        switch (this.mapSize) {
        case 'small':
            mapOptions.navigationControlOptions = {
                style: google.maps.NavigationControlStyle.SMALL
            };
            break;
        case 'large':
        default:
            mapOptions.navigationControlOptions = {
                style: google.maps.NavigationControlStyle.DEFAULT
            };
        }

        this.map = new google.maps.Map(document.getElementById(this.mapDivId), mapOptions); 

        if (!this.center) {
            alert('Error: The center of the map has not been set!');
            return;
        }

    }
};

function OmekaMapForm(mapDivId, markers) {
        
    document.write("<div><input type='text'   id='geochron_address' size='80'  /> <button type='button'  name='geochron' id='geochron_find_location_by_address'>Find By Address</button></div>");
    var that = this;
    var omekaMap = new OmekaMap(mapDivId, markers[0]['center'], markers[0]['options']);
    jQuery.extend(true, this, omekaMap);
    this.initMap();
    
    // Make the map clickable to add a location point.
    google.maps.event.addListener(this.map, 'click', function (event) {
        // If we are clicking a new spot on the map
            jQuery('.geochron_address').val('');
            var point = event.latLng;
	    that.addLocation(point);
    });
	
    // Make the map update on zoom changes.
    google.maps.event.addListener(this.map, 'zoom_changed', function () {
        that.updateZoomForm();
    });
    // Make the Find By Address button lookup the geocode of an address and add a marker.
    jQuery('#geochron_find_location_by_address').bind('click', function (event) {
        var address = jQuery('#geochron_address').val();
        that.findAddress(address);

        //Don't submit the form
        event.stopPropagation();
        return false;
    });
	
    // Make the return key in the geochron address input box click the button to find the address.
    jQuery('#geochron_address').bind('keydown', function (event) {
        if (event.which == 13) {
            jQuery('#geochron_find_location_by_address').click();
            event.stopPropagation();
            return false;
        }
    });

    // Add the existing map points.
    //this.formDiv = jQuery('#' + markers[0].options.form.id);       
    if (markers[0]['options'].point){ 
    for (var i=0;i<markers.length;i++){
        this.map.setZoom(markers[i].options.point.zoomLevel);

        var point = new google.maps.LatLng(markers[i].options.point.latitude, markers[i].options.point.longitude);
        var marker = this.addMarker(false,markers[i].options.point.latitude, markers[i].options.point.longitude, markers[i].options);
        this.map.setCenter(marker.getPosition());

   	that.addFormFields(i,markers[i].options.point.latitude, markers[i].options.point.longitude, markers[i].options.form);
	
	}
    }
}
function clickedDelete(count){
	if (jQuery('#geochron-delete'+count).is(':checked')){
		//set hidden val true of false
		jQuery('#hiddenDelete'+count).val('1');
		jQuery('#hiddenDelete'+count).parent().find(':text').addClass('myLocationSelectForDelete');
		jQuery('#hiddenDelete'+count).parent().find(':text').attr('disabled', 'diabled');
	} else {
		jQuery('#hiddenDelete'+count).val('0');
		jQuery('#hiddenDelete'+count).parent().find(':text').removeClass('myLocationSelectForDelete');
		jQuery('#hiddenDelete'+count).parent().find(':text').removeAttr('disabled');
	}
}
OmekaMapForm.prototype = {
    mapSize: 'large',
     
    
    /* Get the geochron of the address and add marker. */
    findAddress: function (address) {
        var that = this;
        if (!this.geocoder) {
            this.geocoder = new google.maps.Geocoder();
        }    
        this.geocoder.geocode({'address': address}, function (results, status) {
            // If the point was found, then put the marker on that spot
            if (status == google.maps.GeocoderStatus.OK) {
                var point = results[0].geometry.location;
                that.addLocation(point);
            } else {
                // If no point was found, give us an alert
                alert('Error: "' + address + '" was not found!');
                return null;
            }
        });
    },
    
    /* Update the zoom input of the form to be the current zoom on the map. */
    updateZoomForm: function () {
        var zoomElement = jQuery('#hiddenZoom').val;
        zoomElement.value = this.map.getZoom();
    },
    
    /* Clear the form of all markers. */
    clearForm: function () {
        // Remove the markers from the map
        for (var i = 0; i < this.markers.length; i++) {
            this.markers[i].setMap(null);
        }
        
        // Clear the markers array
        this.markers = [];
        
        // Update the form
        this.updateForm();
    },
    
    /* Resize the map and center it on the first marker. */
    resize: function () {
        google.maps.event.trigger(this.map, 'resize');
        var point;
        if (this.markers.length) {
            var marker = this.markers[0];
            point = marker.getPosition();
        } else {
            point = new google.maps.LatLng(this.center.latitude, this.center.longitude);
        }
        this.map.setCenter(point);
    },
   addFormFields: function(mapsCount,lat, lng,formData){
	if (!formData){
		var formData=[];
		formData['id']='';
		formData['description']='New Location';
		formData['begin_date']='';
		formData['end_date']='';
	}  
	var ht = "<div id='marker-info"+mapsCount+"' class = 'newLocation'>";
	ht += "<table><tr><td>Description</td><td>Begin Date</td><td> End Date </td><td>Delete</td></tr>";
	ht += "<tr><td><input type='text' id='geochron-description"+ mapsCount +"' name='geochron["+mapsCount+"][description]' value='"+formData.description+"' class='geochron-description' size='40' value=''></input> ";
	ht += "</td><td>";
	ht += "<input type='text' id='geochron-begin-date"+ mapsCount +"' name='geochron["+mapsCount+"][time_begin]' value='"+formData.begin_date+"' class='geochron-begin-date' size='15' value=''></input> ";
	ht += "</td><td>";
	ht += "<input type='text' id='geochron-end-date"+ mapsCount +"' name='geochron["+mapsCount+"][time_end]' value='"+formData.end_date+"' class='geochron-end-date' size='15' value=''></input> ";
	ht += "</td><td><input type='checkbox' id='geochron-delete"+mapsCount+"' onclick='clickedDelete("+mapsCount+")' class='geochron-delete' /></td></tr>";
	ht += "</table>";
	ht += "<input type='hidden'  class='hiddenID' name='geochron["+mapsCount+"][id]' class='geochron-id'  value="+formData.geochron_id+"></input> ";
	ht += "<input type='hidden'  id='hiddenLat"+mapsCount+"' class='hiddenLat' name='geochron["+mapsCount+"][latitude]' class='geochron-lat'  value="+lat+"></input> ";
	ht += "<input  type='hidden' id='hiddenLon"+mapsCount+"' class='hiddenLon' name='geochron["+mapsCount+"][longitude]' class='geochron-lng'  value="+lng+"></input> ";
        ht += "<input type='hidden'  id='hiddenZoom"+mapsCount+"' class'hiddenZoom' name='geochron["+mapsCount+"][zoom_level]' value=11></input>";
        ht += "<input type='hidden'  id='hiddenDelete"+mapsCount+"' class='hiddenDelete'  name='geochron["+mapsCount+"][delete]' value =0></input>";
	ht += "</div>";
	// Show 
    	jQuery('#geochron_find_location_by_address').after(ht);
   },
   addLocation: function(point){
	var mapsCount = jQuery('.newLocation').length;
	mapsCount += jQuery('.myLocation').length+1;
	this.addFormFields(mapsCount, point.lat(), point.lng());

	
	//Add new Marker
	var newDivId = 'marker-info'+mapsCount; 
	var newOptions = {'form':{'id':newDivId}}
        this.addMarker(true,point.lat(), point.lng(), newOptions);

	}

};
function OmekaMapBrowse(mapDivId, center, options) {
    var omekaMap = new OmekaMap(mapDivId, center, options);
    jQuery.extend(true, this, omekaMap);
    this.initMap();
// Set the placemark's location.  
for (var i=0;i<this.options.length;i++){
            this.addMarker(this.options[i].point.latitude,
                           this.options[i].point.longitude,
                           {title: "(" + this.options[i].point.latitude + ',' + this.options[i].point.longitude + ")"},
                           this.options[i]['markerHtml']);
                }
};
