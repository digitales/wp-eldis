<div class="wrapper">

<h2>WP Eldis Options Test</h2>

<div id="wp-eldis-container" class="sidebar-right">
    
    <div class="sidebar">

    </div>
    
    <div id="main">
        
        <?php if ($has_api_key): ?>
            <h3>Test the API</h3>
            
            <form action="<?php echo admin_url('admin.php?page=wp_eldis_test'); ?>" method="post">
                <p>
                    <label for="object">Object:</label>
                    <select name="object">
                        <option value="documents" <?php echo isset($object) && $object=='documents'? ' selected="selected"' : '' ?>>Documents</option>
                        <option value="organisations"<?php echo isset($object) && $object=='organisations'? ' selected="selected"' : '' ?>>Organisations</option>
                        <option value="themes"<?php echo isset($object) && $object=='themes'? ' selected="selected"' : '' ?>>Themes</option>
                        <option value="items"<?php echo isset($object) && $object=='items'? ' selected="selected"' : '' ?>>Items</option>
                        <option value="subjects"<?php echo isset($object) && $object=='subjects'? ' selected="selected"' : '' ?>>Subjects</option>
                        <option value="sectors"<?php echo isset($object) && $object=='sectors'? ' selected="selected"' : '' ?>>Sectors</option>
                        <option value="countries"<?php echo isset($object) && $object=='countries'? ' selected="selected"' : '' ?>>Countries</option>
                        <option value="regions"<?php echo isset($object) && $object=='regions'? ' selected="selected"' : '' ?>>Regions</option>
                        <option value="itemtypes"<?php echo isset($object) && $object=='itemtypes'? ' selected="selected"' : '' ?>>Item types</option>
                    </select>
                </p>
                
                <p>
                    <label value="term">Search term:</label>
                    <input type="text" name="term" value="<?php echo isset($term)? $term : '' ;?>" />
                </p>
                
                <p>
                    <input type="hidden" name="task" value="test" />
                    <input type="submit" value="Test" class="button-primary" />
                </p>
                
            </form>
            <p>
            
            </p>
        <?php endif; ?>
        
        <?php if ( isset($total_results) ): ?>
            <h3>Test results</h3>
        
            <?php if( isset($total_results) and $total_results > 0 ): ?>
                <p><?php echo $total_results;?> result<?php echo $total_results==1? '' : 's';?> found</p>
        
                <h4>Sample results:</h4>
                <ul>
                    <?php foreach( $results AS $row ): ?>               
                        <li><?php echo $row->title;?></li>
                    <?php endforeach; ?>
                </ul>
        
            <?php else: ?>
                <p>No results found</p>
            <?php endif; ?>
        <?php endif;?>
    </div>

</div>