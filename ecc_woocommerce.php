<?php
    /*
   Plugin Name: eCommerceConnect
   Plugin URI: https://ecommerce.upc.ua/
   Description: One-Click Payment with eCommerceConnect
   Version: 0.13
   Author: UPC
   Author URI: https://upc.ua/
   License: GPL2
  */
  
  include_once "functions.php";
    
    if (!defined('ABSPATH')) exit; // Exit if accessed directly
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;
    
    add_action('plugins_loaded', 'woocommerce_ecc_init', 0);
    function woocommerce_ecc_init()
    {
        add_action('init', 'translations_ecc');
        function translations_ecc()
        {
            load_plugin_textdomain('ecc', FALSE, dirname(plugin_basename(__FILE__)) . '/languages/');
        }
    
        class WC_ecc extends WC_Payment_Gateway
        {
            var $action_url;
            var $notify_url;
            
            var $merchantID;
            var $terminalID;
            //var $redirect_page_id;  не удалять!
    
            function __construct()
            {
                global $woocommerce;
                
                $this->plugin_url = path2url(realpath(__DIR__));
                //$this->action_url = 'https://ecg.test.upc.ua/ecgtest/enter';
				$this->action_url = 'https://ecg.test.upc.ua/go/enter';          // changed in v0.16
                
                $this->id           = 'ecc';
                $this->method_title = 'eCommerceConnect';
               
                
                
                $this->init_form_fields();
                $this->init_settings();
				
                $this->title       = $this->settings['title'];
				$this->icon        = $this->plugin_url.'/images/visa_PNG14_2 x42.png';
                $this->description = $this->settings['description'];
				$this->has_fields  = false;
    
                $this->merchantID       = $this->settings['merchant_id'];
                $this->terminalID       = $this->settings['terminal_id'];
                //$this->redirect_page_id = $this->settings['redirect_page_id'];
    
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
                add_action('woocommerce_receipt_ecc', array(&$this, 'receipt_page'));
    
                add_action('woocommerce_api_wc_ecc', array(&$this, 'check_bank_response'));
                add_action('valid_request', array($this, 'process_gateway_response'));
                
                add_action('woocommerce_thankyou_ecc', array(&$this, 'thankyou_page'));
            }
    
            function init_form_fields(){
     
                $this -> form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable/Disable', 'ecc'),
                        'type' => 'checkbox',
                        'label' => __('Enable ECC Payment Plugin.', 'ecc'),
                        'default' => 'no'),
                    'title' => array(
                        'title' => __('Title:', 'ecc'),
                        'type'=> 'text',
                        'description' => __('This controls the title which the user sees during checkout.', 'ecc'),
                        'default' => __('eCommerceConnect', 'ecc')),
                    'description' => array(
                        'title' => __('Description:', 'ecc'),
                        'type' => 'textarea',
                        'description' => __('This controls the description which the user sees during checkout.', 'ecc'),
                        'default' => __('The credit cards, issued by all the banks of the world, including Visa Electron/ Maestro.', 'ecc')),
                    'merchant_id' => array(
                        'title' => __('Merchant ID', 'ecc'),
                        'type' => 'text',
                        'description' =>  __('Given to Merchant by ECC', 'ecc')),
                    'terminal_id' => array(
                        'title' => __('Terminal ID', 'ecc'),
                        'type' => 'text',
                        'description' =>  __('Given to Merchant by ECC', 'ecc'))/*,
                    'redirect_page_id' => array(
                        'title' => __('Return Page'),
                        'type' => 'select',
                        'options' => $this -> get_pages('Select Page'),
                        'description' => "URL of success page"
                    )
					*/
                );
            }
    
    
            function get_pages($title = false, $indent = true) {
                $wp_pages = get_pages('sort_column=menu_order');
                $page_list = array();
                if ($title) $page_list[] = $title;
                foreach ($wp_pages as $page) {
                    $prefix = '';
                    // show indented child pages?
                    if ($indent) {
                        $has_parent = $page->post_parent;
                        while($has_parent) {
                            $prefix .=  ' - ';
                            $next_page = get_page($has_parent);
                            $has_parent = $next_page->post_parent;
                        }
                    }
                    // add to page list array array
                    $page_list[$page->ID] = $prefix . $page->post_title;
                    
                    /*
                    
                    var_dump($page);
                    echo '<br><br>';
                    
                    Example of page info:
                  
                  object(WP_Post)#11447 (24) { 
                    ["ID"]=> int(151) 
                    ["post_author"]=> string(1) "1" 
                    ["post_date"]=> string(19) "2018-02-01 12:06:07" 
                    ["post_date_gmt"]=> string(19) "2018-02-01 12:06:07" 
                    ["post_content"]=> string(9) "qwdqwdqwd" 
                    ["post_title"]=> string(3) "TYP" 
                    ["post_excerpt"]=> string(0) "" 
                    ["post_status"]=> string(7) "publish" 
                    ["comment_status"]=> string(6) "closed" 
                    ["ping_status"]=> string(6) "closed" 
                    ["post_password"]=> string(0) "" 
                    ["post_name"]=> string(3) "typ" 
                    ["to_ping"]=> string(0) "" 
                    ["pinged"]=> string(0) "" 
                    ["post_modified"]=> string(19) "2018-02-01 12:06:07" 
                    ["post_modified_gmt"]=> string(19) "2018-02-01 12:06:07" 
                    ["post_content_filtered"]=> string(0) "" 
                    ["post_parent"]=> int(0) 
                    ["guid"]=> string(66) "http://rolf.in.ua/work/upc/plugins/dev/wp-woocommerce/?page_id=151" 
                    ["menu_order"]=> int(0) 
                    ["post_type"]=> string(4) "page" 
                    ["post_mime_type"]=> string(0) "" 
                    ["comment_count"]=> string(1) "0" 
                    ["filter"]=> string(3) "raw" 
                  }
                
                */
                    // echo 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'.$prefix . $page->post_title.'xxxxxxxxxxxxx   :   '.$page->ID.'<br>';
                    
                }
                
                
                
                
                
                return $page_list;
            }
            
            public function admin_options(){
                echo '<h3>'.__('ECC Payment Gateway', 'ecc').'</h3>';
                
				/*
				$plugin_dir_path = get_bloginfo("url");
				echo 
				"<img src=\"".$plugin_dir_path."/images/ecc_logo.gif\" /><p>Спеціалісту UPC необхідно передати наступне посилання для повернення до магазину після оплати:</p>
				<ul>
				<code>".get_bloginfo("url")."/wc-api/".get_class($this)."</code>
				</ul>
				<hr>";
				*/
				
				echo 
				"<img src=\"".$this->plugin_url."/images/ecc_logo.gif\" />
				<p>Спеціалісту UPC необхідно передати наступне посилання для повернення до магазину після оплати:</p>
				<ul>
				<code>".get_bloginfo("url")."/wc-api/".get_class($this)."</code>
				</ul>
				<hr>";
				
				
				
				
				
				
                echo '<table class="form-table">';
                // Generate the HTML For the settings form.
                $this -> generate_settings_html();
				
				
				/*
				load keys from plugin settings
				wp_enqueue_media();
                include('view/add_files_form.html');
                */
				
                echo '</table>';
         
            }
    
            function process_payment($order_id)
            {
                $order = new WC_Order($order_id);
                return array(
                    'result' => 'success',
                    'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
                );
            }
    
            function receipt_page($order)
            {
                echo '<p>' . __('Pay through your bank', 'ecc') . '</p>';
                echo $this->generate_bank_form($order);
            }
    
            function generate_bank_form($order_id)
            {
                global $woocommerce;
                $order = new WC_Order ($order_id);
    
                $merchantID = $this -> merchantID;
        		$terminalID = $this -> terminalID;
        		$purchaseTime = date("ymdHis");
        		$totalAmount = $order->get_total()*100;  // здесь нужно указать всю сумму В КОПЕЙКАХ!!!
        		
        		
        		$data = "$merchantID;$terminalID;$purchaseTime;$order_id;980;$totalAmount;aa;";
        		
        		
        		$pemFile = __DIR__ .'/keys/'.$merchantID.'.pem';
        		$fp = fopen($pemFile, "r");
        
        		$priv_key = fread($fp, 8192); 
        		fclose($fp); 
        		$pkeyid = openssl_get_privatekey($priv_key); 
        		openssl_sign( $data , $signature, $pkeyid); 
        		openssl_free_key($pkeyid); 
        		$b64sign = base64_encode($signature) ; //Подпись данных в формате base64
        	
        		//printLog((string)$order);
        		//printLog($order->get_total());
        		
        		$args = array(
        			  'Version'     => '1',
        			  'redirect'    => $this->VK_RETURN,
        			  'MerchantID'  => $merchantID,
        			  'TerminalID'  => $terminalID,
        			  'TotalAmount' => $totalAmount,   
        			  'Currency'    => '980',
        			  'locale'      => 'en',
        			  'SD' 			=> 'aa',
        			  'OrderID' 	=> $order_id,
        			  'PurchaseTime'=> $purchaseTime,
        			  'PurchaseDesc'=> 'test_description',
        			  'Signature' 	=> $b64sign
        			  );
        
        	    
        		
        		if(sizeof($order -> get_items()) > 0){
        			foreach($order -> get_items() as $item){ 
        				$descar[] = $item['name'].' '.sprintf( 'x %s', $item['qty'] );
        			} 
        			$desc = implode(", ", $descar);
        		}
        		else{$desc = '';}
        		if(count($order->get_used_coupons())>0){
        			$desc = 'Купоны: "'.implode("\", \"", $order->get_used_coupons()).'" (общая сумма скидки: '.$order->get_total_discount().'). '.$desc;
        		}
        			
        		if($order->customer_message!='') $desc .= '. Сообщение: '.$order->customer_message;
        		$args['LMI_PAYMENT_DESC'] = $desc;
        		$args_array = array();
        		foreach ($args as $key => $value){
        			$args_array[] = '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'" />';
        		}
        		$htm .= '<form action="'.esc_url($this -> action_url).'" method="POST" id="paymaster_form">'."\n".
        			implode("\n", $args_array).
        			'<span id="ppform">
        			<input type="submit" class="button btn btn-default" id="submit_paymaster_payment_form" value="'.__('Сплатити', 'woocommerce').'" style="display:inline-block" /> <a class="button btn alt btn-black" href="'.$order->get_cancel_order_url().'">'.__('Повернутися у кошик', 'woocommerce').'</a>
        			</span>'."\n".
        			'</form>
        			';
        		echo $htm;
            }
    
            function check_bank_response() {
                
                echo "check_bank_response";
                
                @ob_clean();
    
                if (!empty($_REQUEST)) {
                    header('HTTP/1.1 200 OK');
                    do_action("valid_request", $_REQUEST);
    
                } else {
                    wp_die("No response from the bank");
                }
            }
            
            function process_gateway_response($posted){
                
                process_transaction_code();
                
                process_signatures();
                
                
                // if all OK - finish order state
                
				global $woocommerce;
                 
                 
                $order_id = $_POST["OrderID"];
                echo "OrderID=$order_id";
                echo "<br />";       
                        
                $order = new WC_Order($order_id);
                
                $order -> payment_complete();
                $order -> add_order_note(' response from gateway : OK ');
                $woocommerce -> cart -> empty_cart();
                
                
                print_r($woocommerce);


                echo '<br>' ;
                echo '<br>' ;	
    
				
          
    
                /*
                $p = $this->bank_charset_param;
                $bankCharset = '';
                if ($p != '') {
                    $bankCharset = $macFieldsBefore[$p];
                }
    
                if ($bankCharset == '') {
                    $bankCharset = 'iso-8859-1';
                }
    
                $macFields = array();
                foreach ($macFieldsBefore as $f => $v) {
                    $macFields[$f] = $this->from_bank_ch($v, $bankCharset);
                }
    
                $key = openssl_pkey_get_public(file_get_contents($this->bank_certificate));
    
                if (!openssl_verify($this->generate_mac_strings($macFields), base64_decode($macFields['VK_MAC']), $key)) {
                    trigger_error("Invalid signature", E_USER_ERROR);
                }
    
                $order_id = str_replace('#', '', $macFields['VK_STAMP']);
                $order = $this->get_order($order_id);
    
                if ($macFields['VK_SERVICE'] == '1901') {
                    $macFields['message'] = __("Payment canceled!\n", 'banklink_swedbank');
                    $macFields['status'] = 'pending';
                    $macFields['return'] = $this->get_return_url($order);
                } elseif ($macFields['VK_SERVICE'] == '1902') {
                    $macFields['message'] = sprintf(__("Payment unsuccessful!\nBank returned error %s!", 'banklink_swedbank'), $macFields('VK_ERROR_CODE'));
                    $macFields['status'] = 'pending';
                    $macFields['return'] = $this->get_return_url($order);
                } elseif ($macFields['VK_SERVICE'] == '1101') {
                    if ($macFields['VK_REC_ID'] != $this->VK_SND_ID) {
                        $macFields['message'] = __("Payment unsuccessful!.\nBank returned unknown merchant ID!\n", 'banklink_swedbank');
                        $macFields['status'] = 'pending';
                        $macFields['return'] = $this->get_return_url($order);
    
                    } elseif ($macFields['VK_AMOUNT'] != $order->get_total()) {
                        $macFields['message'] = __("Payd ammount and order ammount do not match!\nOrder has been set to on-hold.\nPlease contact us: ", 'banklink_swedbank') . $this->my_support_email . "\n";
                        $macFields['status'] = "on-hold";
                        $macFields['return'] = $this->get_return_url($order);
                    } else {
                        $macFields['message'] = __("Payment recieved!\n", 'banklink_swedbank');
                        $macFields['status'] = "completed";
                        $macFields['return'] = $this->get_return_url($order);
                    }
                } else {
                    $macFields['message'] = __("Payment unsuccessful!\nUnknown reply: ", 'banklink_swedbank') . $macFields['VK_SERVICE'] . "\n";
                    $macFields['status'] = 'pending';
                    $macFields['return'] = $this->get_return_url($order);
                }
    
                if ($macFields['status'] == 'completed') {
                    $order->add_order_note($macFields['message']);
                    $order->payment_complete();
                } else {
                    $order->update_status($macFields['status'], sprintf(__('Payment on-hold: %s', 'banklink_swedbank'), $macFields['message']));
                    $order->add_order_note($macFields['message']);
    
                }
                */
                
                
                
                // Вариант 1)
                // redirect_page_id - нужно найти на page_id (например, у созданной Thank_you_page шв = 152);
                //$url = get_site_url().'/'.$this->redirect_page_id;
                
                
                
                // Вариант 2)
                // http://{host_name}/checkout/order-received/157/?key=wc_order_5a731ee1315b2
                $order = new WC_Order($order_id);
                // $order_id = 
                $order_key = $order->get_order_key();
                
                
                $url = get_site_url().'/'.'checkout'.'/'.'order-received'.'/'.$order_id.'/'.'?'.'key='.$order_key;
                
                
                
                wp_safe_redirect($url);
                // wp_safe_redirect('http://rolf.in.ua/work/upc/plugins/dev/wp-woocommerce/wp-content/plugins/woocommerce/templates/checkout/thankyou.php');
                
                // wp_safe_redirect('http://rolf.in.ua/work/upc/plugins/dev/wp-woocommerce/wp-content/plugins/swed/thankyou.php');
                // wp_safe_redirect('http://rolf.in.ua/work/upc/plugins/dev/wp-woocommerce/wp-content/plugins/swed/thankyou2.php');
                
            }

        }
    
        function woocommerce_add_ecc_gateway($methods)
        {
            $methods[] = 'WC_ecc';
            return $methods;
        }
    
        add_filter('woocommerce_payment_gateways', 'woocommerce_add_ecc_gateway');
 
    }
    
?>