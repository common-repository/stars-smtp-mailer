<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $wpdb;
$site_url = site_url();
$submitted = $submittedErr = "";
if (isset($_POST['send_test']) && $_POST['send_test'] == "Send" && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], "stars_smtpm-testing-email")) {
    $header = array('Content-Type' => "Content-Type: text/html; charset=UTF-8");
    if($_POST['email_cc'] != "")
        $header['Cc'] = "Cc:".sanitize_email($_POST['email_cc']); 
    if($_POST['email_bcc'] != "")
        $header['Bcc'] = "Bcc:".sanitize_email($_POST['email_bcc']);
    $attached_files = $_FILES;
    $plugin_upload_dir = stars_smtpm_get_upload_path();
    $all_uploaded_files = stars_smtpm_move_uploaded_files($attached_files);
    $all_files_path = array();
    if(!empty($all_uploaded_files)){
        foreach ($all_uploaded_files as $uploaded_file) {
          $all_files_path[] = $plugin_upload_dir.'/'.$uploaded_file;
        }
    }
   
    update_option("_mail_type", "Test");
    
    $response = wp_mail(sanitize_email($_POST['to_email']), sanitize_text_field($_POST['email_subject']), stripslashes($_POST['email_content']), $header, $all_files_path);    
    if($response){
        $mail_log = stars_smtpm_get_mail_log($response);
        if($mail_log['status'] == "Unsent")
            $submittedErr = "Something went wrong. Please check email log to check what went wrong. <span style='color:red;'>Error : ".$mail_log['debug_op']."</span>";
        else
            $submitted = "Test Email Sent.";
    }
    else{
        $submittedErr = "Something went wrong. Please check email log to check what went wrong.";
    }
}
?>
<div id="wpbody">
   <div id="wpbody-content wrap">
        <h1 class="wp-heading-inline"> Send Test Email </h1>
        <?php if ($submitted != "") { ?>
            <div class="updated notice is-dismissible stars_save_msg"><p><strong><?php echo $submitted; ?></strong></p></div>
        <?php }else if ($submittedErr != ""){ ?>
            <div id="message" class="error is-dismissible stars_save_msg"><p><strong><?php echo $submittedErr; ?></strong></p></div>
        <?php } ?> 
        <div class="wrap stars_wrap col-md-9">               
         <div class="wrap-body">
            <div class="sidebar-content ">
               
               <div style="clear:both"></div>
               <form action="<?php admin_url('admin.php?page=stars-smtpm-test-mail') ?>" method="POST" id="send_test_form" enctype="multipart/form-data">
                  <div class="wrapper" id="header">
                     <div class="form-group">
                        <label for="to_email">Email Address : </label>
                        <div class="input-area">
                           <input type="text" name="to_email" id="to_email" value="" class="required email" />
                           <?php if(isset($_GET['id'])) { ?>
                           <input type="hidden" name="stars_test_row_id" id="stars_test_row_id" value="<?php echo $_GET['id']; ?>" />
                           <?php } ?>                   
                        </div>
                     </div>
                     <div class="form-group">
                        <label for="email_cc">CC : </label>
                        <div class="input-area">
                           <input type="text" name="email_cc" id="email_cc" value="" class="email" />                           
                        </div>
                     </div>
                     <div class="form-group">
                        <label for="email_bcc">BCC : </label>
                        <div class="input-area">
                           <input type="text" name="email_bcc" id="email_bcc" value="" class="email" />                           
                        </div>
                     </div>
                     <div class="form-group">
                        <label for="email_subject">Subject : </label>
                        <div class="input-area">
                           <input type="text" name="email_subject" id="email_subject" value="" class="required" />                           
                        </div>
                     </div>
                     <div class="form-group">
                        <label for="email_content">Body : </label>
                        <div class="input-area">
                            <?php wp_editor( "<p>This is a Test Email from ".get_bloginfo("name")." - Stars PHP Mailer</p>", 'email_content', $settings = array('textarea_rows'=> '10', 'media_buttons' => FALSE, 'width' => '780', 'editor_class' => "required") ); ?>
                        </div>
                     </div>

                      <div class="form-group">
                        <label for="email_content">Add Attachments : </label>
                        <div class="input-area">
                            <input type="file" name="email_attach[]" id="email_attach" value="" class="" multiple="multiple"/> 
                        </div>
                     </div>
                     
                     <div class="form-group">
                        <input type="submit" class="button button-primary" name="send_test" id="send_test" value="Send" onclick="return SetEmailBody();" />
                        <?php wp_nonce_field('stars_smtpm-testing-email'); ?>
                     </div>
                  </div>
               </form>
            </div>
         </div>
      </div>
      <div class="col-md-3">
            <div class="star-pro-version">
              <img src="<?php echo STARS_SMTPM_PRO_LOGO; ?>" alt="banner" title="Stars SMTP Mailer Pro Version">
              <h2>SMTP Mailer Pro Features</h2>
              <div class="star-pro-version-features">
                    <ul>  
                            <li>Unlimited Emails Log</li>
                            <li>Resend Emails</li>
                            <li>Track Receipt ( Read / Unread Email )</li>
                            <li>Add Unlimited SMTP Accounts</li>
                            <li>Unlimited Active SMTP Accounts </li>
                            <li>Email Tracking</li>
                            <li>Send emails via multiple accounts</li>
                            <li>Advanced Sending Rules</li>
                            <li><a href="https://myriadsolutionz.com/stars-smtp-mailer/" target="_blank" class="button-primary">Get Pro </a></li>
                    </ul>
              </div>
            </div>
      </div>
      <div class="stars_footer">
         <a href="https://myriadsolutionz.com/" target="_blank"><img src="<?php echo STARS_SMTPM_MYRIAD_LOGO; ?>" alt="logo" title="Myriad Solutionz" /></a>
      </div>
   </div>
</div>