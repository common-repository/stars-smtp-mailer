<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$path = STARS_SMTPM_PLUGIN_DIR.'/action/stars-class-table-layout.php';
include ($path);    
$Show_List_Table->set_tablename(STARS_SMTPM_EMAILS_LOG);
$Show_List_Table->set_id('log_id');   
$Show_List_Table->prepare_items();
global $isAdmin; ?>

<div id="wpbody" role="main">  
    <div id="wpbody-content" aria-label="Main content" tabindex="0">
    <div class="wrap stars-email-logs">
        <div id="icon-users" class="icon32"></div>
        <h1 class="wp-heading-inline">Email Logs</h1>
        <form action="<?php _e(admin_url("/admin.php")); ?>" method="GET" class="stars-float-right star-margin-top-18">
            <p class="search-box">
            <input type="hidden" name="page" value="stars-smtpm-email-log">
            <?php if(isset($_GET['paged']) && sanitize_key($_GET['paged']) != "")  { ?>
            <input type="hidden" name="paged" value="<?php _e(sanitize_key($_GET['paged'])); ?>">
            <?php }?>
            <label class="screen-reader-text" for="post-search-input">Search:</label>
            <input type="search" id="post-search-input" name="s" value="<?php echo (isset($_GET['s']) ? $_GET['s'] : "") ?>" />
            <input type="submit" id="search-submit" class="button" value="Search" /></p>
        </form>        
        <form method="POST" name="smtp_accounts_list" id="my-content-id">                   
        <?php $Show_List_Table->display(); ?>
        </form>
        <input type="hidden" id="check_admin" value="<?php echo(!$isAdmin ? 0 : 1); ?>" />
    </div>
    </div>
</div>
<script type="text/javascript">
    var Permission = true;
    <?php if( !$isAdmin ){ ?>
        Permission = false;
    <?php } ?>
    jQuery(document).ready(function($){            
        $( "#sdate" ).datepicker({
    		dateFormat:"dd/mm/yy",
    		changeMonth: true,
    		changeYear: true,
    		numberOfMonths: 1,
            maxDate : 0,
    		onClose: function( selectedDate ) {
    		  $( "#edate" ).datepicker( "option", "minDate", selectedDate );
    		}
    	});
        $( "#edate" ).datepicker({
    		dateFormat:"dd/mm/yy",
    		changeMonth: true,
    		changeYear: true,
    		numberOfMonths: 1,
            maxDate : 0,
    		onClose: function( selectedDate ) {
    		  $( "#sdate" ).datepicker( "option", "maxDate", selectedDate );
    		}
    	});
        $(".stars-email-logs input[type='checkbox']").click(function(e){
            if(Permission === false){
                jQuery(this).removeProp("checked").change();
                OpenPopup('Access Restricted','This feature is available in PRO version!');
            }                      
        });         
    });
</script>