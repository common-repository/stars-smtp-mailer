<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
   global $wpdb;
   $message="";
   $edit_id = 0;
   if(isset($_REQUEST['id'])){  $edit_id = sanitize_key($_REQUEST['id']); };
   if(isset($_POST['form-action'])){
       if( isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], "stars_smtpm-add_edit_account")){
       $from_name = sanitize_text_field( $_POST['from_name'] );
       $from_email = sanitize_email( $_POST['from_email'] );
       $reply_to = sanitize_email( $_POST['reply_to'] );
       $cc = sanitize_email( $_POST['cc'] );
       $bcc = sanitize_email( $_POST['bcc'] );
       $add_header = sanitize_text_field( $_POST['add_header'] );
       $smtp_host = sanitize_text_field( $_POST['smtp_host'] );
       $encryption = sanitize_text_field( $_POST['encryption'] );
       $smtp_port = sanitize_key( $_POST['smtp_port'] );
       $auth = sanitize_key( $_POST['auth'] );
       $username = sanitize_text_field( $_POST['username'] );
       $pass = sanitize_text_field( $_POST['pass'] );
       $smtp_date= date("Y-m-d H:i:s", time());
       if($from_name == "" || $from_email == "" || $smtp_host == "" || $smtp_port == "" || $username == "" || $pass == ""){
        $errMessage = "Please enter mandatory fields!"; 
       }else{
            $getdata = array( 
                          "from_name"     => $from_name,
                          "from_email"    => $from_email,
                          "reply_to"      => $reply_to,
                          "cc"            => $cc,
                          "bcc"           => $bcc,
                          "add_header"    => $add_header,
                          "smtp_host"     => $smtp_host,
                          "encryption"    => $encryption,
                          "smtp_port"     => $smtp_port,
                          "auth"          => $auth,
                          "username"      => $username,
                          "pass"          => $pass,
                          "smtp_date"     => $smtp_date
                       );
           if(isset($_POST['add_new'])){
              if($getdata['pass'] != ''){
              $plaintext = $getdata['pass'];
              $encrypted = stars_smtpm_pass_enc_dec($plaintext,"enc");
              $getdata['pass'] = $encrypted ;
              }
              $data = stars_smtpm_config_insert_data($getdata);
               if($data){
                 $_SESSION['acc_msg'] = "Account Successfully Saved.  <a href='".admin_url('admin.php?page=stars-smtpm-test-mail&id='.$data)."' class='button button-primary'> Send Test Mail</a> ";
              }else{
                 $_SESSION['acc_err'] = 'Oops !!! You can not Add more than 3 accounts.';
              }
              wp_redirect(admin_url("admin.php?page=stars-smtpm-accounts"));          
           }else{
                 $data = stars_smtpm_get_account_data($edit_id);
                 if($data['pass'] == $getdata['pass']){
                    $getdata['pass'] = $data['pass'];
                 }else{
                    if($getdata['pass'] != ''){
                       $plaintext = $getdata['pass'];
                       $encrypted = stars_smtpm_pass_enc_dec($plaintext,"enc");
                       $getdata['pass'] = $encrypted ;
                    }
                 }
                 $data = stars_smtpm_config_update_data($getdata,$edit_id);
                 if($data == 1){
                    $message = "Account Successfully Edited";
                 }else{
                    $errMessage = 'Something went wrong please try again !!';
                 }
           }
       }
    }else{
        $errMessage = 'Invalid nonce specified. Please try again!';
    }
}
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit'){
   $query = "SELECT * FROM ".STARS_SMTPM_SMTP_SETTINGS." WHERE id = {$edit_id}";
   $e_result = $wpdb->get_row($query,ARRAY_A); 
}
?>
<div id="wpbody">
   <div id="wpbody-content">
      <h1> <?php $title = "Add New Account";
         if(isset($e_result)){ $title = ("Edit Account"); } 
         _e($title);
      ?>        
      </h1>
      <?php if (!empty($message) ){ ?>
            <div class="updated notice is-dismissible stars_save_msg"><p><strong><?php _e( $message) ?></strong></p></div>
        <?php }else if (!empty($errMessage) ){ ?>
            <div class="error is-dismissible stars_save_msg"><p><strong><?php _e( $errMessage) ?></strong></p></div>
        <?php } ?> 
      <div class="wrap stars_wrap">
         <div class="wrap-body">
            <div class="sidebar-content">
               <div style="clear:both"></div>
               <form id="stars-add-new-account" method="POST" >
                  <div class="wrapper" id="header">
                     <div class="form-group">
                        <label>SMTP Host *</label>
                        <div class="input-area">
                           <input id="smtp_host"  type="text" placeholder="Enter SMTP host name" name="<?php _e('smtp_host','smtp_host') ?>" value="<?php _e(isset($e_result) ? $e_result['smtp_host'] : '') ?>"  class="required" />
                           <p class="stars-font-italic stars-input-tooltip">Enter from name only if you want to override Default Name</p>
                        </div>
                     </div>
                     <div class="form-group">
                        <label>SMTP Port *</label>
                        <div class="input-area">
                            <input id="smtp_port" type="text" placeholder="Enter SMTP port no." name="<?php _e('smtp_port','smtp_port') ?>" value="<?php _e( isset($e_result) ? $e_result['smtp_port'] : '') ?>" class="required number" />
                            <label class="check_error  none"></label>
                           <p class="stars-font-italic stars-input-tooltip">Enter from name only if you want to override Default Name</p>
                        </div>
                     </div>
                     <div class="form-group">
                        <label>Authentication *</label>
                        <div class="input-area">
                           <span class="input_radio"><input name="<?php _e('auth','auth') ?>" type="radio" id="true" value="1" <?php _e( isset($e_result) ? ($e_result['auth'] == 1 ? 'checked' : '') : 'checked') ?> /> True</span>
                           <span class="input_radio"><input name="<?php _e('auth','auth') ?>" type="radio" id="false" value="0" <?php _e( isset($e_result) ? ($e_result['auth'] == 0 ? 'checked' : '') : '') ?>  /> False</span>
                           <p class="stars-font-italic stars-input-tooltip">Whether to use SMTP Authentication when sending an email (Recommended : True )</p>
                        </div>
                     </div>
                     <div class="form-group">
                        <label>Encryption *</label>
                        <div class="input-area">
                           <span class="input_radio"><input name="<?php _e('encryption','encryption') ?>" type="radio" id="tls" value="tls" <?php _e( isset($e_result) ? ($e_result['encryption'] == 'tls' ? 'checked' : '') : 'checked') ?> /> TLS</span></span>
                           <span class="input_radio"><input name="<?php _e('encryption','encryption') ?>" type="radio" id="ssl" value="ssl" <?php _e( isset($e_result) ? ($e_result['encryption'] == 'ssl' ? 'checked' : '') : '') ?> /> SSL</span>
                           <span class="input_radio"><input name="<?php _e('encryption','encryption') ?>" type="radio" id="none" value="0" <?php _e( isset($e_result) ? ($e_result['encryption'] == 'none' ? 'checked' : '') : '') ?> />None</span>
                           <p class="stars-font-italic stars-input-tooltip"></p>
                        </div>
                     </div>
                     <div class="form-group">
                        <label>Username *</label>
                        <div class="input-area">
                           <input type="text" readonly="readonly" onfocus="this.removeAttribute('readonly')" id="username" placeholder="Enter Username" name="<?php _e('username','username') ?>" value="<?php _e( isset($e_result) ? $e_result['username'] : '') ?>" class="required" style="background: #fff;" />
                           <label class="user_error  none"></label>
                           <p class="stars-font-italic stars-input-tooltip"></p>
                        </div>
                     </div>
                     <div class="form-group">
                        <label>Password *</label>
                        <div class="input-area">
                           <input type="password" readonly="readonly" onfocus="this.removeAttribute('readonly')"  placeholder="Enter Password" name="<?php _e('pass','pass') ?>" value="<?php _e( isset($e_result) ? $e_result['pass'] : '') ?>" class="required acc-password" style="background: #fff;" /> 
                           <p class="stars-font-italic stars-input-tooltip"></p>
                        </div>
                     </div>                     
                     <div class="form-group">
                        <label>From Name *</label>
                        <div class="input-area">
                           <input type="text" placeholder="Enter from name" value="<?php _e( isset($e_result) ? $e_result['from_name'] : "" )?>"  name="<?php _e('from_name','from_name') ?>" class="required" />
                           <p class="stars-font-italic stars-input-tooltip">Enter from name only if you want to override Default Name</p>
                        </div>
                     </div>
                     <div class="form-group">
                        <label>From Email *</label>
                        <div class="input-area">
                           <input type="email" placeholder="Enter from Email" value="<?php _e( isset($e_result) ? $e_result['from_email'] : "") ?>"  name="<?php _e('from_email','from_email') ?>" class="required" />
                           <p class="stars-font-italic stars-input-tooltip">Enter from Email only if you want to override Default Email</p>
                        </div>
                     </div>
                     <div class="form-group">
                        <label>Cc</label>
                        <div class="input-area">
                           <input type="email" placeholder="Enter CC Email"  value="<?php _e( isset($e_result) ? $e_result['cc'] : '' ) ?>" name="<?php _e('cc','cc') ?>"  />
                           <p class="stars-font-italic stars-input-tooltip">Leave CC blank if you don't want to override them.<br>Please note all emails sent from this account will be copied to this email.</p>
                        </div>
                     </div>
                     <div class="form-group">
                        <label>Bcc</label>
                        <div class="input-area">
                           <input type="email" placeholder="Enter Bcc Email" value="<?php _e( isset($e_result) ? $e_result['bcc'] : '' ) ?>" name="<?php _e('bcc','bcc') ?>"  />
                           <p class="stars-font-italic stars-input-tooltip">Leave Bcc blank if you don't want to override them.<br>Please note all emails sent from this account will be blind copied to this emails.</p>
                        </div>
                     </div>
                     <div class="form-group">
                        <label>Reply To</label>
                        <div class="input-area">
                           <input type="email" placeholder="Enter Reply To Email Id" value="<?php _e( isset($e_result) ? $e_result['reply_to'] : '' )?>" name="<?php _e('reply_to','reply_to') ?>" />
                           <p class="stars-font-italic stars-input-tooltip">Enter Reply To Email Id if you want to Set as a Default Reply To Email Id</p>
                        </div>
                     </div>
                     <div class="form-group">
                        <label>Add Headers</label>
                        <div class="input-area">
                           <textarea placeholder="MIME-Version: 1.0 , Content-Type: text/html; charset=UTF-8" name="<?php _e('add_header','add_header') ?>" cols="30" rows="5" class="form-control no-resize" ><?php _e( isset($e_result) ? $e_result['add_header'] : '') ?></textarea>
                           <p class="stars-font-italic stars-input-tooltip" style="max-width: 420px;">Please follow this Format Only (Each Headers must be separated by comma " , ")<br />MIME-Version: 1.0,<br />Content-Type: text/html; charset=UTF-8 </p>
                        </div>
                     </div>
                     <div class="form-group">
                        <input type="hidden" value="form-submit" name="form-action" />
                        <?php if(isset($e_result)){ ?>
                        <input type="submit" class="button button-primary" name="update" id="submit" value="Update" />
                        <?php  } else{ ?>
                        <input type="submit" class="button button-primary" name="add_new" id="submit" value="Submit" />
                        <?php } ?>
                        <?php wp_nonce_field('stars_smtpm-add_edit_account'); ?>
                     </div>
                  </div>
               </form>
            </div>
         </div>
      </div>
      <div class="stars_footer">
         <a href="https://myriadsolutionz.com/" target="_blank"><img src="<?php echo STARS_SMTPM_MYRIAD_LOGO; ?>" alt="logo" title="Myriad Solutionz" /></a>
      </div>
   </div>
</div>
<script type="text/javascript">
   document.title = '<?php  _e($title); ?>';
</script>