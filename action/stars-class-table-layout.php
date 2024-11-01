 <?php
 if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$Show_List_Table = new STARS_SMTPM_Show_List_Table();

class STARS_SMTPM_Show_List_Table extends WP_List_Table
{
    private $table_name;
    private $unique_id;
    private $remove_columns = array();
    public function __construct( $args = array() ) {
        $args = wp_parse_args(
            $args, array(
                'plural'   => "plural",
                'singular' => '',
                'ajax'     => false,
                'screen'   => null,
            )
        );
        $this->screen = convert_to_screen( $args['screen'] );
        add_filter( "manage_{$this->screen->id}_columns", array( $this, 'get_columns' ), 0 );
        if ( ! $args['plural'] ) {
            $args['plural'] = $this->screen->base;
        }
        $args['plural']   = sanitize_key( $args['plural'] );
        $args['singular'] = sanitize_key( $args['singular'] );
        $this->_args = $args;
        
    }    
    public function set_tablename($tableName){
        $this->table_name = $tableName;    
    }
    public function remove_table_columns($columns){
        $this->remove_columns = $columns;    
    }
    public function set_id($id){        
        $this->unique_id = $id;
    }
    
    public function column_from_email($item){
        if($this->table_name == STARS_SMTPM_SMTP_SETTINGS){
            if(is_admin()){
                $base_url = "?page=stars-smtpm-accounts&action=delete&id=".$item[$this->unique_id];
                $complete_url = wp_nonce_url( $base_url, 'delete-log_'.$item[$this->unique_id] );
                $actions = array(
                    'edit' => sprintf('<a href="?page=stars-smtpm-new-account&action=%s&id=%s">Edit</a>', 'edit', $item[$this->unique_id]),
                    'delete' => sprintf('<a href="'.$complete_url.'" class="confirm-delete" data-value="account" data-id="'.$item[$this->unique_id].'">Delete</a>', 'delete', $item[$this->unique_id]),
                );
            }else{
                $actions = array(
                    'edit' => sprintf('<a href="javascript:void(0);" class="tooltip-toggle" title="<p>This feature is available in PRO version!</p>">Edit</a>'),
                    'delete' => sprintf('<a href="javascript:void(0);" class="tooltip-toggle" title="<p>This feature is available in PRO version!</p>">Delete</a>'),
                );
            }
            return sprintf('%s %s', $item['from_email'], $this->row_actions($actions));            
        }
        else return $item['from_email'];
    }
    public function column_sub($item){
       if($this->table_name == STARS_SMTPM_EMAILS_LOG){            
            if(is_admin()){
                $base_url = "?page=stars-smtpm-email-log&action=delete&id=".$item[$this->unique_id];
                $complete_url = wp_nonce_url( $base_url, 'delete-log_'.$item[$this->unique_id] );
                $actions = array(
                    'view' => sprintf('<a href="#TB_inline?width=600&height=550&inlineId=my-content-%s" class="thickbox">View</a>', $item[$this->unique_id]),
                    'delete' => sprintf('<a href="'.$complete_url.'" class="confirm-delete" data-value="log">Delete</a>'),     
                );    
            }else{
                $actions = array(
                'view' => sprintf('<a href="javascript:void(0);" class="tooltip-toggle" title="<p>This feature is available in PRO version!</p>">View</a>'),
                'delete' => sprintf('<a href="javascript:void(0);" class="tooltip-toggle" title="<p>This feature is available in PRO version!</p>">Delete</a>'),     
            );
            }
            $actions['resend'] = sprintf('<a href="javascript:void(0);" class="tooltip-toggle" title="<p>This feature is available in PRO version!</p>">Resend</a>');
                           
            return sprintf('%s %s', stripslashes($item['sub']), $this->row_actions($actions));
        } 
        else return stripslashes($item['sub']);      
    }
 	public function prepare_items()
    {	
		$columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $handle_delete = $this->process_bulk_action();
        $data = $this->table_data();         
        $_j = 0;
        for($_i = 1; $_i <= count($data); $_i++){
            if($this->table_name == STARS_SMTPM_SMTP_SETTINGS){
                $status = ($data[$_j]['status'] == 0 ? "Activate" : "Deactivate");
                $class  = ($data[$_j]['status'] == 0 ? "stars-btn-green" : "stars-btn-red");
                unset($data[$_j]['status']);
                $data[$_j]['status'] = '<button type="button" id="'.$data[$_j]['id'].'" class="smtp-activation button stars-btn-width '.strtolower($status)." ".$class.' ">'.$status.'</button>';
                $auth = ($data[$_j]['auth'] == 0 ? "False" : "True");
                unset($data[$_j]['auth']);
                $data[$_j]['auth'] = $auth;
                $encryption = strtoupper($data[$_j]['encryption']);
                unset($data[$_j]['encryption']);
                $data[$_j]['encryption'] = $encryption;
            }
            else if($this->table_name == STARS_SMTPM_EMAILS_LOG){
                $status = '<p class="email-status"><span class="'.strtolower($data[$_j]['status']).'">'.ucwords(strtolower($data[$_j]['status'])).'</span>'.($data[$_j]['status'] == 'Unsent' ? '&nbsp;&nbsp;&nbsp;<span class="tooltip-toggle" title="<p>'.$data[$_j]['debug_op'].'</p>">!</span>' : "")."</p>";
                $body = $data[$_j]['mail_body']; 
                $from =  $data[$_j]['from_email'];
                $to   =  $data[$_j]['email_id'];                
                
                $Attachmetns = "";
                $attachmetns = maybe_unserialize($data[$_j]['attachment']);                
                if(is_array($attachmetns)){
                    foreach($attachmetns as $name => $url){
                        $Attachmetns .= '<a href="'.$url.'" target="_blank">'.$name.'</a><br>';       
                    }
                }
                
                unset($data[$_j]['status']);         
                $data[$_j]['status']    = $status;
                $data[$_j]['attachment']= $Attachmetns;               
                $data[$_j]['from']      = $from; 
                $data[$_j]['to']        = $to."<div style='display:none' id='my-content-".$data[$_j]['log_id']."'><div>$body</div></div>"; 
                $data[$_j]['body']      = $body;
                $data[$_j]['date_time'] = date(" D , d M Y h:i A", strtotime($data[$_j]['mail_date']));
                $data[$_j]['details']   = '<style>.email-details span{display:block}</style><p class="email-details">                
                '.(trim($data[$_j]['reply_to']) != "" ? '<span><strong>Reply To: </strong>'.$data[$_j]['reply_to'].'</span>' : "").'
                '.(trim($data[$_j]['cc']) != "" ? '<span><strong>CC: </strong>'.$data[$_j]['cc'].'</span>' : "").'
                '.(trim($data[$_j]['bcc']) != "" ? '<span><strong>BCC: </strong>'.$data[$_j]['bcc'].'</span>' : "")
                .($data[$_j]['mail_type'] == 'test' ? '<span class="test_email">Test Email</span>' : "").'</p>';
            }
            $_j++;   
        }
                
        usort( $data, array( &$this, 'sort_data' ) );

        $user = get_current_user_id();
        $screen = get_current_screen();
        if($this->table_name == STARS_SMTPM_SMTP_SETTINGS || $this->table_name == STARS_SMTPM_EMAILS_LOG){
            $option = $screen->get_option('per_page', 'option');
            $per_page = get_user_meta($user, $option, true);
            if ( empty ( $per_page) || $per_page < 1 ) {
                $per_page = $screen->get_option( 'per_page', 'default' );
            }            
        }else{
            $per_page = 50;
        }
        $perPage = $per_page;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );
        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }
    public function get_columns()
    {
    	$result = $this->get_table_columns();    	
    	$remove_col = $this->remove_columns;
    	$columns['cb'] = '<input type="checkbox" />';
        if($this->table_name == STARS_SMTPM_SMTP_SETTINGS){
    	   foreach ($result as $key => $value) {
        		if(isset($value['Field']) && !in_array($value['Field'] , $remove_col))
        			$columns[$value['Field']] = ucwords(str_replace("_"," ",$value['Field']));
        	}
    	}else if($this->table_name == STARS_SMTPM_EMAILS_LOG){
    	   $columns['sub']        = 'Title';
           $columns['from']       = 'From';  
           $columns['to']         = 'To'; 
           $columns['details']    = 'Email Headers';           
           $columns['date_time']  = 'Date Sent';           
           $columns['status']     = 'Status';
           $columns['attachment'] = 'Attachment';           
    	}
        return $columns;
    }
    public function get_hidden_columns()
    {
        return array();
    }
    public function get_sortable_columns()
    {
        $sort_columns = array();
        if($this->table_name == STARS_SMTPM_EMAILS_LOG){
            $sort_columns = array(
                'to' => array('email_id', false),
                'sub' => array('sub', false),
                'date_time' => array("mail_date", false),
                'status' => array("status", false)
            );
        }
        else if($this->table_name == STARS_SMTPM_SMTP_SETTINGS){
            $sort_columns = array('from_email' => array('from_email', false),'username' => array('username', false));
        }
        
        return $sort_columns;
    }
    private function table_data()
    {
        $table_data = $this->get_result();
        return $table_data;
    }
    public function column_default( $item, $column_name )
    {    	
        return $item[ $column_name ];
    }
    private function sort_data( $a, $b )
    {
        $orderby = ($this->table_name == STARS_SMTPM_EMAILS_LOG ? "log_id" : "id");
        $order = 'desc';
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }
        $result = strnatcmp ( $a[$orderby], $b[$orderby] );
        if($order === 'asc')
        {
            return $result;
        }
        return -$result;
    }
    public function get_bulk_actions() {
	    $actions = array(
	        'delete'    => 'Delete'
	    );
	    return $actions;
	}
    public function column_cb($item) {
        $admin = !is_admin();
	    return sprintf(
	        '<input type="checkbox" name="table_dlt_id[]" value="%s" />',
	        $item[$this->unique_id]
	    );
	}
    public function process_bulk_action() {
        global $wpdb;
    	if(isset($_POST['table_dlt_id']) && !empty($_POST['table_dlt_id']) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) && is_admin()){            
	    	$action = $this->current_action();
	    	switch ( $action ) {
	            case 'delete':
	            	foreach ($_POST['table_dlt_id'] as $key => $value) {
	            	    if($this->table_name == STARS_SMTPM_EMAILS_LOG){
                            $getRow = $wpdb->get_row("SELECT * FROM ".STARS_SMTPM_EMAILS_LOG." WHERE ".$this->unique_id." = ".$value);
                            if(isset($getRow->attachment) && $getRow->attachment != ""){
                                $attachment = maybe_unserialize($getRow->attachment);
                                if(is_array($attachment)){
                                    foreach($attachment as $att){
                                        $att = explode("/",$att);
                                        if(file_exists(stars_smtpm_get_upload_path()."/".$att[count($att)-1]))unlink(stars_smtpm_get_upload_path()."/".$att[count($att)-1]);
                                    }
                                }
                            }
                        }  
	                	$wpdb->delete($this->table_name,array($this->unique_id => $value));
	                }	                                    
	                break;
	            default:
	                return;
	                break;
	        }
	    }
    	return;
    }
    function extra_tablenav( $which ) {
        if($this->table_name == STARS_SMTPM_SMTP_SETTINGS) 
            return;
    	global $wpdb;
    	if ( $which == "top" ){
    	    $min = $wpdb->get_row("SELECT mail_date FROM ".$this->table_name." ORDER BY mail_date ASC LIMIT 1");
            $sdate = ( isset($_POST['sdate']) ? $_POST['sdate'] : (isset($min->mail_date) && $min->mail_date != "" ? date('d/m/Y',strtotime($min->mail_date)) : date('d/m/Y')) );
            $edate = ( isset($_POST['edate']) ? $_POST['edate'] : date('d/m/Y') );
	        ?>
	        <div class="alignleft actions">
                <input placeholder="Date From" name="sdate" type="text" value="<?php echo $sdate; ?>" class="stars_datepicker" id="sdate" /> 
                <input placeholder="Date To" name="edate" type="text" value="<?php echo $edate; ?>" class="stars_datepicker" id="edate" /> 
	            <input type="submit" name="filter_table_action" id="post-query-submit" class="button" value="Filter" />
	        </div>
	        <?php
	    }
	}
    public function get_result(){        
	    global $wpdb;         
        if(isset($_GET['action']) && $_GET['action'] == "delete" && isset($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], "delete-log_".$_GET['id']) && is_admin()){
            if($this->table_name == STARS_SMTPM_EMAILS_LOG){
                $getRow = $wpdb->get_row("SELECT * FROM ".STARS_SMTPM_EMAILS_LOG." WHERE ".$this->unique_id." = ".$_GET['id']);
                if(isset($getRow->attachment) && $getRow->attachment != ""){
                    $attachment = maybe_unserialize($getRow->attachment);
                    if(is_array($attachment)){
                        foreach($attachment as $att){
                            $att = explode("/",$att);
                            if(file_exists(stars_smtpm_get_upload_path()."/".$att[count($att)-1]))unlink(stars_smtpm_get_upload_path()."/".$att[count($att)-1]);
                        }
                    }
                }
            }
            $wpdb->delete($this->table_name,array($this->unique_id => $_GET['id']));
            ?><script>window.history.replaceState({}, "", '<?php echo admin_url("admin.php?page=".$_GET['page']) ?>');</script><?php
        }
        
        if(isset($_POST['sdate']) && isset($_POST['edate']) && $this->table_name == STARS_SMTPM_EMAILS_LOG){
            
            $date_tmp = str_replace("/","-",$_POST['sdate']);
            $sdate = date('Y-m-d',strtotime($date_tmp));
            
            $date_tmp = str_replace("/","-",$_POST['edate']);
            $edate = date('Y-m-d',strtotime($date_tmp));
            
            $cur_form_res = $wpdb->get_results("SELECT * FROM ".$this->table_name." WHERE (mail_date BETWEEN '$sdate 00:00:00' AND '$edate 23:59:59') LIMIT 200",ARRAY_A);         
        }        
        else if(isset($_GET['s']) && trim($_GET['s']) != "" && $this->table_name == STARS_SMTPM_EMAILS_LOG){ 
            $search = sanitize_text_field($_GET['s']);
            $cur_form_res = $wpdb->get_results("SELECT * FROM ".$this->table_name." WHERE 
            from_name LIKE '%".$search."%' OR 
            from_email LIKE '%".$search."%' OR 
            reply_to LIKE '%".$search."%' OR 
            email_id LIKE '%".$search."%' OR 
            cc LIKE '%".$search."%' OR 
            bcc LIKE '%".$search."%' OR 
            sub LIKE '%".$search."%' OR 
            mail_body LIKE '%".$search."%' OR 
            status LIKE '%".$search."%' OR 
            mail_type LIKE '%".$search."%'
             LIMIT 200",ARRAY_A);  
        }        	    				
		else $cur_form_res = $wpdb->get_results("SELECT * FROM ".$this->table_name." LIMIT ".($this->table_name == STARS_SMTPM_SMTP_SETTINGS ? '3' : '200'),ARRAY_A);        
	    return ($cur_form_res ? $cur_form_res : array());
	}
    
    public function get_table_columns(){
        global $wpdb; 
        $cur_form_res = $wpdb->get_results("SHOW COLUMNS FROM ".$this->table_name,ARRAY_A);        
        return ($cur_form_res ? $cur_form_res : array()); 
    }        
}