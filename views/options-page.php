<div class="wrapper">

<h2>WP Eldis Options</h2>
<p class="description">Options for using the Eldis API. </p>


<?php $this->display_feedback(); ?>

<?php echo $this->open_form(); ?>

<div id="wp-eldis-container" class="sidebar-right">
    
    <div class="sidebar"></div>
    
    <div id="main">
        
        <h3>API Key</h3>
        <p>
            To use WP Eldis, you need to provide your <a href="http://api.ids.ac.uk/profiles/view/" target="_blank">Eldis API key</a>.  Just paste that key here:
        </p>
        
        <p>
            <label>Eldis API Key: </label>
            <input type="text" name="api_key" size="30" value="<?php echo $this->options->get('api_key'); ?>" />
        </p>
        
        <p>
            <input type="submit" value="Update Options" class="button-primary" />
        </p>

        
        <?php echo $this->close_form(); ?>
        
        <?php if ($has_api_key): ?>
            <h3>Test the API</h3>
            
            <form action="<?php echo admin_url('admin.php?page=wp_eldis_test') ;?>" method="post">
                <p>
                    <label for="object">Object:</label>
                    <select name="object">
                        <option value="documents">Documents</option>
                        <option value="organisations">Organisations</option>
                        <option value="themes">Themes</option>
                        <option value="items">Items</option>
                        <option value="subjects">Subjects</option>
                        <option value="sectors">Sectors</option>
                        <option value="countries">Countries</option>
                        <option value="regions">Regions</option>
                        <option value="itemtypes">Item types</option>
                    </select>
                </p>
                
                <p>
                    <label value="term">Search term:</label>
                    <input type="text" name="term" value="" />
                </p>
                
                <p>
                    <input type="hidden" name="task" value="test" />
                    <input type="submit" value="Test" class="button-primary" />
                </p>
                
            </form>
            <p>
            
            </p>
        <?php endif; ?>
        
        <h3>Link News category to Eldis resources</h3>

        <?php if (!$link_status): ?>
            <p>Older Eldis posts may have been imported without the News category. <br />You can use the following button to make sure the News category is linked to those posts.</p>

            <form action="<?php echo admin_url('admin.php?page=wp_eldis') ;?>" method="post" accept-charset="utf-8">
                <input type="submit" name="link_eldis_news" value="Link News to Eldis resources" class="button-primary">
            </form>
        <?php else: ?>
            <p><?php echo $link_status ?></p>
        <?php endif; ?>

    </div>  

</div>