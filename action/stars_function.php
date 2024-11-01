<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Password encryption
function stars_smtpm_pass_enc_dec($value, $type = "enc"){
	$password = 'Q+eedg3+Why/Eac8z3VpkRxFON2sN4J3/hcPSfpaa9E=';
   	$method = 'aes-256-cbc';
   	$password = substr(hash('sha256', $password, true), 0, 32);
   	$iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);
   	$plaintext = $value;
   	if($type == 'enc'){
   		$encrypted = base64_encode(openssl_encrypt($plaintext, $method, $password, OPENSSL_RAW_DATA, $iv));
   		return $encrypted;	
   	}else if($type == 'dec'){
   		$decrypted = openssl_decrypt(base64_decode($value), $method, $password, OPENSSL_RAW_DATA, $iv);
   		return $decrypted; 
   	}
}
   
//add new account
 function stars_smtpm_config_insert_data($smtp_config)
{  
   global $wpdb;
   foreach($smtp_config as $key => $val){
        if($val == ""){
            unset($smtp_config[$key]);    
        } 
   }
   $rowcount = $wpdb->get_var("SELECT COUNT(*) FROM ".STARS_SMTPM_SMTP_SETTINGS);
   if($rowcount <= 0){
      $smtp_config['status'] = 1;
   }
   if($rowcount >= 3){
      return false;
   }else{
    $wpdb->insert( STARS_SMTPM_SMTP_SETTINGS, $smtp_config);
    return $wpdb->insert_id ;
   }
}

// Check Port & host
function stars_smtpm_check_host_server(){
	 $host = sanitize_text_field($_POST['check_host']);
	 $port = sanitize_key($_POST['check_port']);
	 
	 $connection = @fsockopen($host , $port , $errno, $errstr,5); 
	    if (is_resource($connection)){
	        $response['valid'] = " {$host } : {$port} is open. ";
	        echo json_encode($response);
	        fclose($connection);
	        exit();
	    }else{
	        $response['error'] ='Error Code :' . $errno .' - ' . $errstr;
	        echo json_encode($response);
	    }
    die(0);
}
add_action( 'wp_ajax_stars_smtpm_check_host_server', 'stars_smtpm_check_host_server' );

// Check User exist
function stars_smtpm_check_user(){
	global $wpdb;
	$response = "";
	$user = sanitize_text_field($_POST['uname']);

	$str = "";
	if(sanitize_key($_POST['id'] != ""))
		$str .= " AND id != {$_POST['id']}";

  	$query = "SELECT * FROM ".STARS_SMTPM_SMTP_SETTINGS." WHERE username = '{$user}' $str;";
    $result = $wpdb->get_row($query,ARRAY_A);
    if($result){
    	$response = "Account already exist";
    }else { 
    	return false;
   	}
	 echo $response; 
	 
    die(0);
}
add_action( 'wp_ajax_stars_smtpm_check_user', 'stars_smtpm_check_user' );

//get accounts data
function stars_smtpm_get_account_data($id){
	global $wpdb;
  	$query = "SELECT * FROM ".STARS_SMTPM_SMTP_SETTINGS." WHERE id = {$id}";
    $result = $wpdb->get_row($query,ARRAY_A); 
    return $result;   
}

//update config form
function stars_smtpm_config_update_data($edit_data,$e_id)
{
	global $wpdb;
	$result = $wpdb->update( STARS_SMTPM_SMTP_SETTINGS, $edit_data , array('id'=>$e_id));
	return $result;
}

//WP_mail() Mail override
if(!function_exists('wp_mail') ){
    global $stars_smtpm_data;
    $stars_smtpm_data = array();
    
    if(isset($_POST['stars_test_row_id']))
        $stars_smtpm_data = stars_smtpm_get_smtp_account(sanitize_key($_POST['stars_test_row_id']));
    else
        $stars_smtpm_data = stars_smtpm_get_smtp_account(); 
    
    if(!$stars_smtpm_data) $stars_smtpm_data = array();
    if(count($stars_smtpm_data)) {
        function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() )
        {
            global $stars_smtpm_data;
        	$atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );        	
        	
        	if ( isset( $atts['to'] ) ) {
        		$to = $atts['to'];
        	}
        	
        	if ( !is_array( $to ) ) {
        		$to = explode( ',', $to );
        	}
        
        	if ( isset( $atts['subject'] ) ) {
        		$subject = $atts['subject'];
        	}
        
        	if ( isset( $atts['message'] ) ) {
        		$message = $atts['message'];
        	}
            
            //header array 
            $headers = array();                                   
            if($stars_smtpm_data['add_header'] != ""){
                $array = explode(",",$stars_smtpm_data['add_header']);
                if(is_array($array)){
                    foreach($array as $attHead){
                        $attHead = explode(":",$attHead);
                        if(count($attHead) == 2){
                            $headers[strtolower($attHead[0])] = $attHead[1];    
                        }                            
                    }
                }
            }
            if($stars_smtpm_data['reply_to'] !=''){
    			$headers['reply-to']= $stars_smtpm_data['reply_to'];
    		}
            if($stars_smtpm_data['cc'] != ''){
    			$headers['cc']= $stars_smtpm_data['cc'];
    		}
            if($stars_smtpm_data['bcc'] != ''){
    			$headers['bcc']= $stars_smtpm_data['bcc'];
    		}
            if($stars_smtpm_data['from_email'] != ''){
    			$headers['from'] = $stars_smtpm_data['from_name']." <".$stars_smtpm_data['from_email'].">";
    		}

            if ( isset( $atts['headers'] ) && !empty($atts['headers']) ) {
                $atts['headers'] = (is_array($atts['headers']) ? $atts['headers'] : explode("\n",$atts['headers']));                                                   
                if(is_array($atts['headers'])){
                    foreach($atts['headers'] as $attHead){
                        $attHead = explode(":",$attHead);
                        if(count($attHead) == 2){
                            $headers[strtolower($attHead[0])] = $attHead[1];    
                        }                            
                    }
                }                       		
        	}                      
            //End: header array
        
        	if ( isset( $atts['attachments'] ) ) {
        		$attachments = $atts['attachments'];
        	}
        
        	if ( ! is_array( $attachments ) ) {
        		$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
        	}
                
        	global $phpmailer;
        	
        	$phpmailer = new PHPMailer( true );
        	$cc = $bcc = $reply_to = array();
        
        	if ( empty( $headers ) ) {
        		$headers = array();
        	} else {
        	    $tempheaders = $headers;                
        		$headers = array();
        
        		if ( !empty( $tempheaders ) ) {
        			foreach ( (array) $tempheaders as $name => $content ) {        				
        				$name    = trim( $name );
        				$content = trim( $content );
        
        				switch ( strtolower( $name ) ) {
        					case 'from':
        						$bracket_pos = strpos( $content, '<' );
        						if ( $bracket_pos !== false ) {
        							if ( $bracket_pos > 0 ) {
        								$from_name = substr( $content, 0, $bracket_pos - 1 );
        								$from_name = str_replace( '"', '', $from_name );
        								$from_name = trim( $from_name );
        							}
        
        							$from_email = substr( $content, $bracket_pos + 1 );
        							$from_email = str_replace( '>', '', $from_email );
        							$from_email = trim( $from_email );
        
        						} elseif ( '' !== trim( $content ) ) {
        							$from_email = trim( $content );
        						}
        						break;
        					case 'content-type':
        						if ( strpos( $content, ';' ) !== false ) {
        							list( $type, $charset_content ) = explode( ';', $content );
        							$content_type = trim( $type );
        							if ( false !== stripos( $charset_content, 'charset=' ) ) {
        								$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset_content ) );
        							} elseif ( false !== stripos( $charset_content, 'boundary=' ) ) {
        								$boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset_content ) );
        								$charset = '';
        							}
        
        						} elseif ( '' !== trim( $content ) ) {
        							$content_type = trim( $content );
        						}
        						break;
        					case 'cc':
        						$cc = array_merge( (array) $cc, explode( ',', $content ) );
        						break;
        					case 'bcc':
        						$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
        						break;
        					case 'reply-to':
        						$reply_to = array_merge( (array) $reply_to, explode( ',', $content ) );
        						break;
        					default:
        						$headers[trim( $name )] = trim( $content );
        						break;
        				}
        			}
        		}
        	}
        
        	$phpmailer->clearAllRecipients();
        	$phpmailer->clearAttachments();
        	$phpmailer->clearCustomHeaders();
        	$phpmailer->clearReplyTos();
        	
        	if ( !isset( $from_name ) ){
        		
              		$from_name = $stars_smtpm_data['from_name'];
                }
        
        	if ( !isset( $from_email ) ) {
        		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
        		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
        			$sitename = substr( $sitename, 4 );
        		}
        		
              		$from_email = $stars_smtpm_data['from_email'];
              	
        		
        	}
        
        	$from_email = apply_filters( 'wp_mail_from', $from_email );	
        	$from_name = apply_filters( 'wp_mail_from_name', $from_name );
        
        	try {
        		
        	  		$phpmailer->setFrom( $from_email, $from_name, false );
        	  	
        	} catch ( phpmailerException $e ) {
        		$mail_error_data = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
        		$mail_error_data['phpmailer_exception_code'] = $e->getCode();
        
        		do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_error_data ) );
        
        		return false;
        	}
        	
        	$address_headers = array();
        	$phpmailer->Subject = $subject;
        	
        
        
        	$address_headers = compact( 'to', 'cc', 'bcc', 'reply_to' );
        
        	foreach ( $address_headers as $address_header => $addresses ) {
        		if ( empty( $addresses ) ) {
        				continue;
        			}
        
        		foreach ( (array) $addresses as $address ) {
        			try {
        				$recipient_name = '';
        
        				if ( preg_match( '/(.*)<(.+)>/', $address, $matches ) ) {
        					if ( count( $matches ) == 3 ) {
        						$recipient_name = $matches[1];
        						$address        = $matches[2];
        					}
        				}
        
        				switch ( $address_header ) {
        					case 'to':
        						$phpmailer->addAddress( $address, $recipient_name );
        						break;
        					case 'cc':
        						$phpmailer->addCc( $address, $recipient_name );
        						break;
        					case 'bcc':
        						$phpmailer->addBcc( $address, $recipient_name );
        						break;
        					case 'reply_to':
        						$phpmailer->addReplyTo( $address, $recipient_name );
        						break;
        				}
        			} catch ( phpmailerException $e ) {
        				continue;
        			}
        		}
        	}
        
        	$phpmailer->isSMTP();        
                $phpmailer->Host = $stars_smtpm_data['smtp_host'];
                
                if(isset($stars_smtpm_data['auth']) && $stars_smtpm_data['auth'] == 1){
                    $phpmailer->SMTPAuth = true;
                    $phpmailer->Username = $stars_smtpm_data['username'];          
                    $phpmailer->Password = stars_smtpm_pass_enc_dec($stars_smtpm_data['pass'], "dec");  
                }
                $type_of_encryption = $stars_smtpm_data['encryption'];
                 
                if($type_of_encryption == '0'){
                    $type_of_encryption = '';  
                }
        
                $phpmailer->SMTPSecure = $type_of_encryption;
                $phpmailer->Port = $stars_smtpm_data['smtp_port'];  
                $phpmailer->SMTPAutoTLS = false;
                /*
                if(isset($_POST['smtp_mailer_send_test_email'])){
                    $phpmailer->SMTPDebug = 4;
                    // Ask for HTML-friendly debug output
                    $phpmailer->Debugoutput = 'html';
                }
                */  
        	if ( !isset( $content_type ) ){
        		$content_type = 'text/html';
        		global $msg;
        		$msg = htmlspecialchars(($message));
        		$msg = nl2br($msg);
        		$phpmailer->Body    = ($msg);
        	}
        
        	$content_type = apply_filters( 'wp_mail_content_type', $content_type );
        
        	$phpmailer->ContentType = $content_type;
        	if ( 'text/html' == $content_type ){
        		global $msg;
        		$msg = $message;
        		$phpmailer->Body    = ($msg);
        	}
        	$phpmailer->isHTML( true );
        	
        	if ( !isset( $charset ) )
        		$charset = get_bloginfo( 'charset' );
        
        	$phpmailer->CharSet = apply_filters( 'wp_mail_charset', $charset );
        
        	if ( !empty( $headers ) ) {
        		foreach ( (array) $headers as $name => $content ) {
        			$phpmailer->addCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
        		}
        
        		if ( false !== stripos( $content_type, 'multipart' ) && ! empty($boundary) )
        			$phpmailer->addCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
        	}
            
            $attachmentData = array();            
        	if ( !empty( $attachments ) ) {
        		foreach ( $attachments as $attachment ) {
        			try {
        				$phpmailer->addAttachment($attachment);
                                                
                        $fileName = basename($attachment);
                        if( file_exists(stars_smtpm_get_upload_path()."/".$fileName) ){
                            $attachmentData[$fileName] = stars_smtpm_get_upload_path(true)."/".$fileName;
                        }else{
                            $time   = time();
                            $moveTo = stars_smtpm_get_upload_path()."/".$time.$fileName;
                            copy($attachment, $moveTo);
                            if(file_exists($moveTo)) $attachmentData[$fileName] = stars_smtpm_get_upload_path(true)."/".$time.$fileName;
                        }
                        
                    } catch ( phpmailerException $e ) {
        				continue;
        			}
        		}
            }
        
        	do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );
        	try {
        		if($phpmailer->send()){
        
        		global $msg;
        		$mail_date= date("Y-m-d H:i:s", time());
        		$to = implode( ',', $to );
                $reply_to = $reply_to != "" ? implode( ',', $reply_to ) : "";
        		$cc = implode( ',', $address_headers['cc'] );
        		$bcc =implode( ',', $address_headers['bcc'] );
        		$cc =  $cc == '' ? $stars_smtpm_data['cc'] : implode( ',', $address_headers['cc'] );
        		$bcc = $bcc == '' ? $stars_smtpm_data['bcc'] : implode( ',', $address_headers['bcc'] );
        		$mail_tyte = get_option('_mail_type');
        		$mail_tyte = $mail_tyte == 'test' ? 'test' : 'general';	
        		$mail_log = array(
					'from_email' 	=> $from_email,
					'reply_to' 		=> $reply_to,
					'from_name' 	=> $from_name, 
					'email_id' 		=> $to, 
					'cc' 			=> $cc, 
					'bcc' 			=> $bcc, 
					'sub' 			=> $subject, 
					'mail_body' 	=> $msg, 
					'status' 		=> "Sent",
					'debug_op' 		=> "Email has been sent successfully",
					'mail_type' 	=> $mail_tyte,
					'mail_date' 	=> $mail_date,
                    'attachment'    => (is_array($attachmentData) ? serialize($attachmentData) : "")

				 );
        		if (empty($mail_log['reply_to'])) {
        			unset($mail_log['reply_to']);
        		}
                
        		if($from_email && $to){
					$response = stars_smtpm_insert_email_log($mail_log);
					if($response){
    					delete_option('_mail_type');
    					$success = $response;    		   			
    				 }			
        		}
                return true;       
            }
        	} catch ( phpmailerException $e ) {
        
        		$mail_error_data = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
        		$mail_error_data['phpmailer_exception_code'] = $e->getCode();
        		global $msg;
        		$mail_date= date("Y-m-d H:i:s", time());
        		$to = implode( ',', $to );
        		$cc = implode( ',', $address_headers['cc'] );
        		$bcc =implode( ',', $address_headers['bcc'] );
        		$cc =  $cc == '' ? $stars_smtpm_data['cc'] : implode( ',', $address_headers['cc'] );
        		$bcc = $bcc == '' ? $stars_smtpm_data['bcc'] : implode( ',', $address_headers['bcc'] );
        		$mail_tyte = get_option('_mail_type');
        		$mail_tyte = $mail_tyte == 'test' ? 'test' : 'general';
        		$mail_log = array(
        							'from_email' 	=> $from_email,
        							'reply_to' 		=> $stars_smtpm_data['reply_to'],
        							'from_name' 	=> $from_email, 
        							'email_id' 		=> $to, 
        							'cc' 			=> $cc, 
        							'bcc' 			=> $bcc, 
        							'sub' 			=> $subject, 
        							'mail_body' 	=> $msg, 
        							'status' 		=> "Unsent",
        							'debug_op' 		=> $e->getMessage(),// $e->getCode().':' .
        							'mail_type' 	=> $mail_tyte,
        							'mail_date' 	=> $mail_date,
                                    'attachment'    => (is_array($attachmentData) ? serialize($attachmentData) : "") 
        						 );
        		if (empty($mail_log['reply_to'])) {
        			unset($mail_log['reply_to']);
        		} 	
        		if($from_email && $to){        
					$response_data = stars_smtpm_insert_email_log($mail_log);
					if($response_data){
						delete_option('_mail_type');
						$error = $response_data;
			   			return $error;
				    }	
        		}
        		do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_error_data ) );
        		return false;
        	}
         }
    }
}

/** stars get SMTP account. IF $row_id == 0 , get activated account */
function stars_smtpm_get_smtp_account($row_id = 0) {
    global $wpdb;
    $str = ($row_id != 0 ? " AND id = $row_id" : " AND status = 1");
    $result = $wpdb->get_row("SELECT * FROM ".STARS_SMTPM_SMTP_SETTINGS. " WHERE 1 = 1 $str", ARRAY_A);
    return (is_array($result) ? $result : array());
}

//Insert email logs
function stars_smtpm_insert_email_log($mail_log)
{
	global $wpdb;
	if($mail_log['cc'] == "")
		unset($mail_log['cc']);
	if($mail_log['bcc'] == "")
		unset($mail_log['bcc']);

	$rowcount = $wpdb->get_var("SELECT COUNT(*) FROM ".STARS_SMTPM_EMAILS_LOG);

	if($rowcount >= 200){
    		$result = $wpdb->query( "DELETE FROM ".STARS_SMTPM_EMAILS_LOG." ORDER BY log_id ASC LIMIT 1");
    		if($result){
    		$wpdb->insert( STARS_SMTPM_EMAILS_LOG, $mail_log);
			return $wpdb->insert_id;
			}
    }else{
    	$wpdb->insert( STARS_SMTPM_EMAILS_LOG, $mail_log);
		return $wpdb->insert_id;
	}
}
function stars_smtpm_get_mail_log($log_id) {
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM ".STARS_SMTPM_EMAILS_LOG." WHERE log_id = $log_id", ARRAY_A);
}

/*New - 21-May-2018*/

//For moving file to upload folder
function stars_smtpm_move_uploaded_files($files) {
    global $wpdb;    
    $upload_dir = stars_smtpm_get_upload_path();
    $_FILES     = $files;
    $total      = count($_FILES['email_attach']['name']);
    $all_uploaded_files = $all_files_path = array();
    for( $i=0 ; $i < $total ; $i++ ) {
      $tmpFilePath = $_FILES['email_attach']['tmp_name'][$i];
      if ($tmpFilePath != ""){
        $newFilePath = $upload_dir."/" . time() .$_FILES['email_attach']['name'][$i];
        if(move_uploaded_file($tmpFilePath, $newFilePath)) {
            $all_uploaded_files[] = time() .$_FILES['email_attach']['name'][$i];
        }
      }
    }
    return $all_uploaded_files;
}
function stars_smtpm_get_upload_path($url = false){
    $upload = wp_upload_dir();
    $upload_dir = ($url ? $upload['baseurl'] : $upload['basedir']);
    $upload_dir = $upload_dir . '/stars_smtp_attachments';
    if (! is_dir($upload_dir)) {
       mkdir( $upload_dir, 0700 );
    }
    return $upload_dir;  
}
