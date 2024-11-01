<?php

/*
  Plugin Name: Stars SMTP Mailer
  Plugin URI: https://myriadsolutionz.com/stars-smtp-mailer/
  Description: Stars SMTP Mailer Plugin that throws outgoing email via SMTP through PHP Mailer. Supports all emails like Gmail,Yahoo,Outlook,Zoho etc. Maintain Email Logs and lot more.
  Author: Myriad Solutionz
  Author URI: https://myriadsolutionz.com/
  Version: 1.7
 */


/**
 * @author Myriad Solutionz
 * @copyright Myriad Solutionz, 2019, All Rights Reserved
 * This code is released under the GPL licence version 3 or later, available here
 * http://www.gnu.org/licenses/gpl.txt
 */

if (!defined('ABSPATH')){
    exit;
}

global $wpdb, $stars_smtpm_data, $isAdmin;

//define constant 
$stars_smtpm_plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);        
stars_smtpm_define( 'STARS_SMTPM_PLUGIN_VERSION',$stars_smtpm_plugin_data['Version'] );
stars_smtpm_define( 'STARS_SMTPM_PLUGIN_URL', plugins_url() );
stars_smtpm_define( 'STARS_SMTPM_PLUGIN_DIR', plugin_dir_path(__FILE__) );
stars_smtpm_define( 'STARS_SMTPM_SMTP_SETTINGS', $wpdb->prefix . 'stars_smtp_settings' );
stars_smtpm_define( 'STARS_SMTPM_EMAILS_LOG', $wpdb->prefix . 'stars_emails_log' );    	
stars_smtpm_define( 'STARS_SMTPM_AJAX_LOADER', STARS_SMTPM_PLUGIN_URL.'/' . basename(dirname(__FILE__)) .'/assets/images/ajax-small-loader.gif' );
stars_smtpm_define( 'STARS_SMTPM_PRO_LOGO', STARS_SMTPM_PLUGIN_URL.'/' . basename(dirname(__FILE__)) .'/assets/images/smtp-pro-version.svg' );
stars_smtpm_define( 'STARS_SMTPM_MYRIAD_LOGO', STARS_SMTPM_PLUGIN_URL.'/' . basename(dirname(__FILE__)) .'/assets/images/ms.svg' );

//Configuration files
include 'action/stars_function.php';

/**Create tables */
register_activation_hook(__FILE__,'stars_smtpm_create_table');
function stars_smtpm_create_table() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    if($wpdb->get_var("show tables like '".STARS_SMTPM_SMTP_SETTINGS."'") != STARS_SMTPM_SMTP_SETTINGS){
        $sql = "CREATE TABLE " . STARS_SMTPM_SMTP_SETTINGS . " (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `from_name` varchar(150) NOT NULL,
                `from_email` varchar(255) NOT NULL,
                `reply_to` varchar(255) NOT NULL,
                `cc` varchar(255) DEFAULT NULL,
                `bcc` varchar(255) DEFAULT NULL,
                `add_header` varchar(1000) DEFAULT NULL,
                `smtp_host` varchar(50) NOT NULL,
                `smtp_port` varchar(50) NOT NULL,
                `encryption` varchar(50) NOT NULL,
                `auth` varchar(255) NOT NULL,
                `username` varchar(255) NOT NULL,
                `pass` varchar(255) NOT NULL,
                `smtp_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `status` int(11) NOT NULL,
                PRIMARY KEY (id));";    
        dbDelta($sql);
    }
    
    if($wpdb->get_var("show tables like '".STARS_SMTPM_EMAILS_LOG."'") != STARS_SMTPM_EMAILS_LOG){
        $query = "CREATE TABLE " . STARS_SMTPM_EMAILS_LOG . " (
                `log_id` int(11) NOT NULL AUTO_INCREMENT,
                `from_name` varchar(255) NOT NULL,
                `from_email` varchar(255) NOT NULL,
                `reply_to` varchar(255) NOT NULL,
                `email_id` varchar(255) NOT NULL,
                `cc` varchar(255) NOT NULL,
                `bcc` varchar(255) NOT NULL,
                `sub` text NOT NULL,
                `mail_body` text NOT NULL,
                `status` varchar(100) NOT NULL,
                `response` varchar(100) NOT NULL,
                `debug_op` text NOT NULL,
                `mail_type` varchar(10) NOT NULL,
                `mail_date` timestamp NOT NULL,
                PRIMARY KEY (log_id));";    
        dbDelta($query);
    }
}

/**STARS SMTP init */
add_action('init', 'STARS_SMTPM_init');
function STARS_SMTPM_init(){    
    ob_start("STARS_SMTPM_callback");
    if ( !session_id() )
        add_action('init', 'STARS_SMTPM_session', 1);
        
    global $current_user, $isAdmin, $wpdb;
    
    if( (isset($current_user->roles[0]) && $current_user->roles[0] == "administrator") || (isset($current_user->caps["administrator"]) && $current_user->caps["administrator"] == 1) ){
        $isAdmin = true;
    }else{
        $isAdmin = false;
    }
    
    //add attachment column
    $smtp_check = $wpdb->query("SHOW COLUMNS FROM `".STARS_SMTPM_EMAILS_LOG."` LIKE 'attachment'");
    if(!$smtp_check) $wpdb->query("ALTER TABLE `".STARS_SMTPM_EMAILS_LOG."` ADD `attachment` TEXT NOT NULL AFTER `mail_date`");
    
    //admin notices
    add_action( 'all_admin_notices', 'stars_smtpm_activation_notice' );
    if( (isset($_GET['action']) && $_GET['action'] == "delete") || (isset($_POST['action']) && $_POST['action'] == 'delete') ){
        add_action( 'all_admin_notices', 'stars_smtpm_delete_success_note' );
    }    
    
    //PHP SMTP Mailer..
    require_once ABSPATH . WPINC . '/class-phpmailer.php';
    require_once ABSPATH . WPINC . '/class-smtp.php';
    
    add_action('admin_menu','stars_smtpm_admin_menu');
    add_action( 'admin_enqueue_scripts', 'stars_smtpm_mailer_assets' );
}

function STARS_SMTPM_callback($buffer){
    return $buffer;
}

function STARS_SMTPM_session(){
    session_start();
}

function stars_smtpm_define( $name, $value ) {        
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

function stars_smtpm_delete_success_note() {    
	echo '<div id="message" class="updated notice is-dismissible">';
	echo '<p>Data Deleted Successfully.</p>';
	echo '</div>';    
}

function stars_smtpm_activation_notice() {    
    if( isset($_GET['page']) && $_GET['page'] == "stars-smtpm-test-mail" && isset($_GET['id']) && sanitize_key($_GET['id']) != "" ) $hide_notice = 1;
    if( !isset($hide_notice) ){
        $stars_activated_account = stars_smtpm_get_smtp_account();
        if( !count($stars_activated_account) ){
            echo '<div id="message" class="notice notice-warning is-dismissible">';
        	echo '<p>' . sprintf( __( 'No SMTP Accounts activated. Email wont be sent via <strong>Stars SMTP Mailer</strong>. Please add and/or activate account <a href="%s">here</a>' ), admin_url( 'admin.php?page=stars-smtpm-accounts' ) ) . '</p>';
            
        	echo '</div>'; 
        }
    }        	
}

function stars_smtpm_admin_menu(){
    $emaillog_menu    = add_menu_page( 'Stars SMTP Mailer', 'Stars SMTP Mailer', 0, 'stars-smtpm-email-log', 'stars_smtpm_email_log','dashicons-email-alt');
    $emaillog_menu    = add_submenu_page('stars-smtpm-email-log', "Email Log", "Email Log", 0, "stars-smtpm-email-log", "stars_smtpm_email_log");    
    $smtpaddAcc_menu  = add_submenu_page('stars-smtpm-email-log', "Add New Account", "Add New Account", 0, "stars-smtpm-new-account", "stars_smtpm_new_account");
    $smtpaccount_menu = add_submenu_page('stars-smtpm-email-log', "SMTP Accounts", "SMTP Accounts", 0, "stars-smtpm-accounts", "stars_smtpm_smtp_account");
    $smtpTest_menu    = add_submenu_page('stars-smtpm-email-log', "Test Email", "Test Email", 0, "stars-smtpm-test-mail", "stars_smtpm_mail_test");
        
    add_action( "load-$smtpaccount_menu", 'stars_smtpm_accounts_add_option' ); //To add screen option
    add_action( "load-$emaillog_menu", 'stars_smtpm_email_log_add_option' ); //To add screen option
} 

//Screen option for Email Logs
function stars_smtpm_email_log_add_option() {
  $option = 'per_page';
  $args = array(
    'label' => 'Per Page',
    'default' => 10,
    'option' => 'email_log_per_page'
  );
  add_screen_option( $option, $args ); 
}
add_filter('set-screen-option', 'stars_smtpm_email_log_set_option', 10, 3);
function stars_smtpm_email_log_set_option($status, $option, $value) {
  if ( 'email_log_per_page' == $option ) return $value;
  return $status;
}

//Screen option for smtp account list
function stars_smtpm_accounts_add_option() {
  $option = 'per_page';
  $args = array(
    'label' => 'Per Page',
    'default' => 10,
    'option' => 'smtp_account_per_page'
  );
  add_screen_option( $option, $args ); 
}
add_filter('set-screen-option', 'stars_smtpm_account_set_option', 10, 3);
function stars_smtpm_account_set_option($status, $option, $value) {
  if ( 'smtp_account_per_page' == $option ) return $value;
  return $status;
}

function stars_smtpm_mail_test() {
    include_once("include/stars-test-email.php");
}
function stars_smtpm_new_account(){
    include_once('include/stars-add-new-account.php');
}
function stars_smtpm_smtp_account(){
    include_once('include/stars-smtp-accounts-list.php');
}
function stars_smtpm_email_log(){
    include_once('include/stars-email-logs.php');
}

// Stars smtp mailer{Js& Css}
function stars_smtpm_mailer_assets($hook) {
   
    $style = STARS_SMTPM_PLUGIN_URL .'/' . basename(dirname(__FILE__)) . '/assets/css/stars_style.css';
    wp_enqueue_style( 'stars_style', $style );  
    
    $custom = STARS_SMTPM_PLUGIN_URL.'/' . basename(dirname(__FILE__)) . '/assets/js/stars_smtpm_custom.js';  
    wp_enqueue_script( 'stars_smtpm_custom', $custom );
    
    if($hook == "stars-smtp-mailer_page_stars-smtpm-test-mail" || $hook == "stars-smtp-mailer_page_stars-smtpm-new-account")
        wp_enqueue_script("stars_jquery_validation", STARS_SMTPM_PLUGIN_URL.'/' . basename(dirname(__FILE__)) .'/assets/js/jquery.validate.js');
        
    if($hook == "toplevel_page_stars-smtpm-email-log" || $hook == "stars-smtp-mailer_page_stars-smtpm-accounts") {
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-tooltip' );
        wp_enqueue_script( 'jquery-ui-dialog' );        
        
        wp_enqueue_style("stars_jquery_ui_css", STARS_SMTPM_PLUGIN_URL.'/' . basename(dirname(__FILE__)) .'/assets/css/jquery-ui.css');
        
        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');    
    }
}

/**SMTP accounts - activate / deactivate. */
add_action('wp_ajax_stars_smtpm_change_status', 'stars_smtpm_change_status');
function stars_smtpm_change_status()
{        
    global $wpdb;    
    $query = $wpdb->query("UPDATE ".STARS_SMTPM_SMTP_SETTINGS." SET status = 0");
    if($_POST['status'] == 1)
        $result = $wpdb->update( STARS_SMTPM_SMTP_SETTINGS, array('status' => 1), array('id'=>$_POST['id'])); 
    die();         
}

/**stars dashboard widget */
add_action('wp_dashboard_setup', 'stars_smtpm_add_stars_dashboard_widget');
function stars_smtpm_add_stars_dashboard_widget() {
    wp_add_dashboard_widget('stars_smtpm_dashboard_widget', 'Stars SMTP Mailer', 'stars_smtpm_dashboard_widget');
}
function stars_smtpm_dashboard_widget() {
    global $wpdb;
    
    $email_logs = $wpdb->get_results("SELECT mail_date, status FROM ".STARS_SMTPM_EMAILS_LOG, ARRAY_A);
    $stats = array("today_emails" => 0, "unsent" => 0, "sent" => 0);
    
    foreach($email_logs AS $el_data) {
        if(date("Y-m-d", strtotime($el_data['mail_date'])) == date("Y-m-d"))
            $stats['today_emails']++;
        
        if($el_data['status'] == "Sent")
            $stats['sent']++;
        else if($el_data['status'] == "Unsent")
            $stats['unsent']++;
    }
    $active_account = $acc_id = ""; 
    $stars_active_account = stars_smtpm_get_smtp_account();
    if($stars_active_account) {
        $active_account = $stars_active_account['from_email'];
        $acc_id = '<a class="button-link community-events-toggle-location" title="Manage Account" aria-expanded="false" aria-hidden="false" href="'.admin_url("/admin.php?page=stars-smtpm-new-account&action=edit&id={$stars_active_account['id']}").'"><span class="dashicons dashicons-edit"></span></a>';
    }
    
    $stars_statistics = '<table border="1" style="border-collapse:collapse;width:100%;" cellpadding="7">';
    $stars_statistics .= '<tr><td>Active SMTP Account : </td><td>'.($active_account!=""||$acc_id!=""?$active_account.$acc_id:"-").'</td></tr>';
    $stars_statistics .= '<tr><td>Today\'s Emails : </td><td>'.$stats['today_emails'].'</td></tr>';
    $stars_statistics .= '<tr><td>Total Sent Emails : </td><td>'.$stats['sent'].'</td></tr>';
    $stars_statistics .= '<tr><td>Total Unsent Emails : </td><td>'.$stats['unsent'].'</td></tr></table>';
    
    echo $stars_statistics;
}