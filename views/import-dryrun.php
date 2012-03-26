<div class="wrapper">

<h2>WP Eldis Options Test</h2>

<div id="wp-eldis-container" class="sidebar-right">
    
    <div class="sidebar">

    </div>
    
    <div id="main">

      <?php if ($import_dry_run): ?>
        <pre>
          <?php var_dump($import_dry_run) ?>
        </pre>
      <?php endif; ?>
      
      <?php if (isset($deleted_posts) && $deleted_posts): ?>
        <p>
          Deleted all resource posts of the author <strong>eldiscommunity</strong>.
        </p>
      <?php endif; ?>
      
      <form action="<?php echo admin_url('admin.php?page=wp_eldis_test'); ?>" method="post" accept-charset="utf-8">
            <h3>Test the Importing of Eldis posts</h3>
            <input type="submit" name="test-eldis-import" value="Test Import Eldis">
            
            <h3>Clear all the Eldis posts</h3>
            <p>
              Warning: this will delete <em>all</em> resource posts of the author <strong>eldiscommunity</strong>.
            </p>
            <input type="submit" name="clear-eldis-import" value="Clear imported Eldis posts">
            
            <h3>Import Eldis posts</h3>
            <p>
              Note: this will effectively import all Eldis posts related to any Eldis objects you may or may not have saved.
            </p>
            <input type="submit" name="do-eldis-import" value="Import Eldis posts">
      </form>
      
    </div>

</div>