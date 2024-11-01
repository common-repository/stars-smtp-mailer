<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$path = STARS_SMTPM_PLUGIN_DIR.'/action/stars-class-table-layout.php';
include ($path);    
$Show_List_Table->set_tablename(STARS_SMTPM_SMTP_SETTINGS);
$Show_List_Table->set_id('id');
$Show_List_Table->remove_table_columns(array('id','from_name','reply_to','cc','bcc','add_header','pass','smtp_date'));    
$Show_List_Table->prepare_items();
global $isAdmin; ?>

<div id="wpbody" role="main">
    <div id="wpbody-content" aria-label="Main content" tabindex="0">
    <div class="wrap stars-smtp-account-list">
        <div id="icon-users" class="icon32"></div>
        <h1 class="wp-heading-inline">SMTP Accounts</h1>
        <?php if (isset($_SESSION['acc_msg']) && !empty($_SESSION['acc_msg'])){ ?>
            <div class="updated below-h2 stars_save_msg"><p><strong><?php _e( $_SESSION['acc_msg']) ?></strong></p></div>
        <?php unset($_SESSION['acc_msg']);
        }else if (isset($_SESSION['acc_err']) && !empty($_SESSION['acc_err'])){ ?>
            <div class="error below-h2 stars_save_msg"><p><strong><?php _e( $_SESSION['acc_err']) ?></strong></p></div>
        <?php unset($_SESSION['acc_err']);
        } ?> 
        <a href="?page=stars-smtpm-new-account" class="page-title-action">Add New</a>
        <form method="POST" name="smtp_accounts_list">
        <?php $Show_List_Table->display(); ?>
        </form>
        <input type="hidden" id="check_admin" value="<?php echo(!$isAdmin ? 0 : 1); ?>" />
    </div> 
    </div>
</div>
<script type="text/javascript">
    var Permission = true;
    <?php if(!$isAdmin ){ ?>
        Permission = false;
    <?php } ?>
    jQuery(document).ready(function($){        
        $(".smtp-activation").click(function(){
            if(Permission === true){
                $(this).after("<img src='<?php echo STARS_SMTPM_AJAX_LOADER ?>' id='ajax-load' style='position: relative;top: 6px;right: -10px;' />");
                var status = 1;
                if($(this).hasClass('deactivate')) status = 0;
                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: {
                        action: "stars_smtpm_change_status",
                        status: status,
                        id: $(this).attr('id')
                    },
                    success: function(response) {
                        window.location = '?page=stars-smtpm-accounts'; 
                    }
                });
            }else{
                OpenPopup('Access Restricted','This feature is available in PRO version!');    
            }
        });        
        $(".stars-smtp-account-list input[type='checkbox']").click(function(e){
            if(Permission === false){
                jQuery(this).removeProp("checked").change();
                OpenPopup('Access Restricted','This feature is available in PRO version!');
            }                      
        });        
    });    
</script>