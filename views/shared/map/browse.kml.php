<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<kml xmlns="http://earth.google.com/kml/2.0">
    <Document>
        <name>Omeka Items KML</name>
        <?php /* Here is the styling for the balloon that appears on the map */ ?>
        <Style id="item-info-balloon">
            <BalloonStyle>
                <text><![CDATA[
                    <div class="geochron_balloon">
                        <div class="geochron_balloon_title">$[namewithlink]</div>
                        <div class="body">$[description]</div>
                        <div class="geochron_balloon_description">$[Snippet]</div>
                    </div>
                ]]></text>
            </BalloonStyle>
        </Style>
        <?php
        while(loop_items()):
        $item = get_current_item();
        $location = $locations[$item->id];
        ?>
        <Placemark>
            <name><![CDATA[<?php echo item('Dublin Core', 'Title');?>]]></name>
            <namewithlink><![CDATA[<?php echo link_to_item(item('Dublin Core', 'Title'), array('class' => 'view-item')); ?>]]></namewithlink>
            <Snippet maxLines="2"><![CDATA[<?php
            echo item('Dublin Core', 'Description', array('snippet' => 150));
            ?>]]></Snippet>    
            <description><![CDATA[<?php 
            // @since 3/26/08: movies do not display properly on the map in IE6, 
            // so can't use display_files(). Description field contains the HTML 
            // for displaying the first file (if possible).
            if (item_has_thumbnail($item)) {
                echo link_to_item(item_thumbnail(), array('class' => 'view-item'));                
            }
            ?>]]></description>
            <Point>
                <coordinates><?php echo $location[0]['longitude']; ?>,<?php echo $location[0]['latitude']; ?></coordinates>
            </Point>
            <?php if ($location['address']): ?>
            <address><![CDATA[<?php echo $location[0]['address']; ?>]]></address>
            <?php endif; ?>
            <div><![CDATA[<?php echo $location[0]['time_begin']; ?>]]></div>
            <div><![CDATA[<?php echo $location[0]['time_end']; ?>]]></div>
            <div><![CDATA[<?php echo $location[0]['longitude']; ?>]]></div>
            <div><![CDATA[<?php echo $location[0]['latitude']; ?>]]></div>
        </Placemark>
        <?php endwhile; ?>
    </Document>
</kml>
