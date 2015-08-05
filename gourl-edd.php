<?php
/*
Plugin Name: 		GoUrl Easy Digital Downloads (EDD) - Bitcoin Altcoin Payment Gateway
Plugin URI: 		https://gourl.io/bitcoin-easy-digital-downloads-edd.html
Description:		Provides a <a href="https://gourl.io">GoUrl.io</a> Bitcoin/Altcoin Payment Gateway for <a href="https://wordpress.org/plugins/easy-digital-downloads/">Easy Digital Downloads 2.4.2+</a>. Direct Integration on your website, no external payment pages opens (as other payment gateways offer). Accept Bitcoin, Litecoin, Paycoin, Dogecoin, Dash, Speedcoin, Reddcoin, Potcoin, Feathercoin, Vertcoin, Vericoin, Peercoin, MonetaryUnit payments online. You will see the bitcoin/altcoin payment statistics in one common table on your website. No Chargebacks, Global, Secure. All in automatic mode.
Version: 			1.0
Author: 			GoUrl.io
Author URI: 		https://gourl.io
License: 			GPLv2
License URI: 		http://www.gnu.org/licenses/gpl-2.0.html
GitHub Plugin URI: 	https://github.com/cryptoapi/Bitcoin-Easy-Digital-Downloads
*/


if (!defined( 'ABSPATH' )) exit; // Exit if accessed directly

if (!function_exists('gourl_edd_gateway_load') && !function_exists('gourl_edd_action_links')) // Exit if duplicate
{
	
	DEFINE('GOURLEDD', 'gourl-edd');
	
	add_action( 'plugins_loaded', 		'gourl_edd_gateway_load', 20 );
	add_filter( 'plugin_action_links', 	'gourl_edd_action_links', 10, 2 );
	add_action( 'plugins_loaded', 		'gourl_edd_load_textdomain' );


	/*
	 *	1. Localisation
	*/
	function gourl_edd_load_textdomain() 
	{
		load_plugin_textdomain( GOURLEDD, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	
	
	/*
	 *	2. Plugins Page - links
	*/
	function gourl_edd_action_links($links, $file)
	{
		static $this_plugin;
		
		if (!class_exists('Easy_Digital_Downloads')) return $links;
	
		if (false === isset($this_plugin) || true === empty($this_plugin)) {
			$this_plugin = plugin_basename(__FILE__);
		}
	
		if ($file == $this_plugin) {
			$settings_link = '<a href="'.admin_url('edit.php?post_type=download&page=edd-settings&tab=gateways#gourls').'">'.__( 'Settings', GOURLEDD ).'</a>';
			array_unshift($links, $settings_link);
				
			if (defined('GOURL'))
			{
				$unrecognised_link = '<a href="'.admin_url('admin.php?page='.GOURL.'payments&s=unrecognised').'">'.__( 'Unrecognised', GOURLEDD ).'</a>';
				array_unshift($links, $unrecognised_link);
				$payments_link = '<a href="'.admin_url('admin.php?page='.GOURL.'payments&s=gourledd').'">'.__( 'Payments', GOURLEDD ).'</a>';
				array_unshift($links, $payments_link);
			}
		}
	
		return $links;
	}
	
	
	
	
	
 /*
  *	 Load Plugin
  */
 function gourl_edd_gateway_load() 
 {
	
	// Easy Digital Downloads required
	if (!class_exists('Easy_Digital_Downloads') || class_exists('Edd_Gateway_GoUrl')) return;
	

	/*
	 *	3. GoUrl Payment Gateway Edd Class
	 */
	class Edd_Gateway_GoUrl 
	{
		
		private $payments 			= array();
		private $languages 			= array();
		private $coin_names			= array('BTC' => 'bitcoin', 'LTC' => 'litecoin', 'XPY' => 'paycoin', 'DOGE' => 'dogecoin', 'DASH' => 'dash', 'SPD' => 'speedcoin', 'RDD' => 'reddcoin', 'POT' => 'potcoin', 'FTC' => 'feathercoin', 'VTC' => 'vertcoin', 'VRC' => 'vericoin', 'PPC' => 'peercoin', 'MUE' => 'monetaryunit');
		private $mainplugin_url		= '';
		private $url				= '';
		private $url2				= '';
		private $url3				= '';
		private $cointxt			= '';
		
		private $title				= '';
		private $emultiplier		= '';
		private $deflang			= '';
		private $defcoin			= '';
		private $iconwidth			= '';
		
		
		
		/*
		 * 3.1
		*/
	    public function __construct() 
	    {
	    	global $gourl;

	    	
			$this->mainplugin_url 		= admin_url("plugin-install.php?tab=search&type=term&s=GoUrl+Bitcoin+Payment+Gateway+Downloads");
			$this->method_title       	= __( 'GoUrl Bitcoin/Altcoins', GOURLEDD );
			$this->method_description  = "<a target='_blank' href='https://gourl.io/bitcoin-easy-digital-downloads-edd.html'>".__( 'Plugin Homepage', GOURLEDD )."</a> &#160;&amp;&#160; <a target='_blank' href='https://gourl.io/bitcoin-easy-digital-downloads-edd.html#screenshot'>".__( 'screenshots', GOURLEDD )." &#187;</a><br/>";
			$this->method_description  .= "<a target='_blank' href='https://github.com/cryptoapi/Bitcoin-Easy-Digital-Downloads'>".__( 'Plugin on Github - 100% Free Open Source', GOURLEDD )." &#187;</a><br/><br/>";
			$this->has_fields         	= false;

			if (class_exists('gourlclass') && defined('GOURL') && defined('GOURL_ADMIN') && is_object($gourl))
			{ 
				if (true === version_compare(GOURL_VERSION, '1.3.4', '<'))
				{
					$this->method_description .= '<div class="error"><p><b>' .sprintf(__( "Your GoUrl Bitcoin Gateway <a href='%s'>Main Plugin</a> version is too old. Requires 1.3.4 or higher version. Please <a href='%s'>update</a> to latest version.", GOURLEDD ), GOURL_ADMIN.GOURL, $this->mainplugin_url)."</b> &#160; &#160; &#160; &#160; " . 
							  __( 'Information', GOURLEDD ) . ": &#160; <a href='https://gourl.io/bitcoin-wordpress-plugin.html'>".__( 'Main Plugin Homepage', GOURLEDD )."</a> &#160; &#160; &#160; " . 
							  "<a href='https://wordpress.org/plugins/gourl-bitcoin-payment-gateway-paid-downloads-membership/'>".__( 'WordPress.org Plugin Page', GOURLEDD )."</a></p></div>";
				}
				elseif (true === version_compare(EDD_VERSION, '2.4.2', '<'))
				{
					$this->method_description .= '<div class="error"><p><b>' .sprintf(__( "Your Easy Digital Downloads version is too old. The GoUrl payment plugin requires Easy Digital Downloads 2.4.2 or higher to function. Please update to <a href='%s'>latest version</a>.", GOURLEDD ), admin_url('plugin-install.php?tab=search&type=term&s=edd')).'</b></p></div>';
				}
				else 
				{
					$this->payments 			= $gourl->payments(); 		// Activated Payments
					$this->coin_names			= $gourl->coin_names(); 	// All Coins
					$this->languages			= $gourl->languages(); 		// All Languages
				}
				
				$this->url		= GOURL_ADMIN.GOURL."settings";
				$this->url2		= GOURL_ADMIN.GOURL."payments&s=gourledd";
				$this->url3		= GOURL_ADMIN.GOURL;
				$this->cointxt 	= (implode(", ", $this->payments)) ? implode(", ", $this->payments) : __( '- Please setup -', GOURLEDD );
			}
			else
			{
				$this->method_description .= '<div class="error"><p><b>' . 
								sprintf(__( "You need to install GoUrl Bitcoin Gateway Main Plugin also. Go to - <a href='%s'>Automatic installation</a> or <a href='%s'>Manual</a>.", GOURLEDD ), $this->mainplugin_url, "https://gourl.io/bitcoin-wordpress-plugin.html") . "</b> &#160; &#160; &#160; &#160; " .
								__( 'Information', GOURLEDD ) . ": &#160; &#160;<a href='https://gourl.io/bitcoin-wordpress-plugin.html'>".__( 'Main Plugin Homepage', GOURLEDD )."</a> &#160; &#160; &#160; <a href='https://wordpress.org/plugins/gourl-bitcoin-payment-gateway-paid-downloads-membership/'>" .
								__( 'WordPress.org Plugin Page', GOURLEDD ) . "</a></p></div>";
				
				$this->url		= $this->mainplugin_url;
				$this->url2		= $this->url;
				$this->url3		= $this->url;
				$this->cointxt 	= '<b style="color:red">'.__( 'Please install GoUrl Bitcoin Gateway WP Plugin', GOURLEDD ).' &#187;</b>';
				
			}

			$this->method_description  .= "<b>" . __( "Secure payments with virtual currency. <a target='_blank' href='https://bitcoin.org/'>What is Bitcoin?</a>", GOURLEDD ) . '</b><br/><br/>';
			$this->method_description  .= sprintf(__( 'Accept %s payments online in Easy Digital Downloads.', GOURLEDD ), __( ucwords(implode(", ", $this->coin_names)), GOURLEDD )).'<br/>';
			$this->method_description  .= sprintf(__( "If you use multiple stores/sites online, please create separate <a target='_blank' href='%s'>GoUrl Payment Box</a> (with unique payment box public/private keys) for each of your stores/websites. Do not use the same GoUrl Payment Box with the same public/private keys on your different websites/stores.", GOURLEDD ), "https://gourl.io/editrecord/coin_boxes/0") . '<br/><br/>';

			
			// Init Settings
			$this->gourl_init_settings();
			
			// Gateway Filters
			add_filter( 'edd_settings_gateways', array( $this, 'init_form_fields' ), 1 );
			add_filter( 'edd_payment_gateways', array( $this, 'gourl_register' ) );
			add_filter( 'edd_accepted_payment_icons', array( $this, 'register_payment_icon' ), 10, 1 );
			add_action( 'edd_gourl_cc_form', '__return_false' );
			add_action( 'edd_gateway_gourl',  array( $this, 'process_payment' ) );
			add_action( 'edd_payment_receipt_before',  array( $this, 'cryptocoin_payment' ) );
			
			// Currency Filters
			add_filter( 'edd_currencies', array( $this, 'currencies' ), 20 );
			add_filter( 'edd_btc_currency_filter_before', array( $this, 'currency_symbol' ), 20 );
			add_filter( 'edd_btc_currency_filter_after', array( $this, 'currency_symbol' ), 20 );
			add_filter( 'edd_currency_decimal_count', array( $this, 'currency_decimals' ), 20 );
				
			
			if (isset($_GET["page"]) && isset($_GET["tab"]) && $_GET["page"] == "edd-settings" && $_GET["tab"] == "gateways") add_action( 'admin_footer_text', array(&$this, 'admin_footer_text'), 25);

			
			return true;
	    }

	    
	    
	    /*
	     * 3.2 Initialize Values
	    */
	    public function gourl_init_settings()
	    {
	    	global $edd_options;
	    	
	    	// Define user set variables
	    	$arr = array('gourl_title', 'gourl_emultiplier', 'gourl_deflang', 'gourl_defcoin', 'gourl_iconwidth');
	    	
	    	foreach ($arr as $v)
	    	{
	    		$k = str_replace('gourl_', '', $v);  
	    		$this->$k = (isset($edd_options[$v])) ? $edd_options[$v] : ''; 
	    	}
	    	
	    	$this->emultiplier  = trim(str_replace(array("%", ","), array("", "."), $this->emultiplier));
	    	$this->iconwidth  	= trim(str_replace("px", "", $this->iconwidth));

	    	
	    	// Re-check
	    	if (!$this->title)								{ $this->title = __('GoUrl Bitcoin/Altcoins', GOURLEDD); edd_update_option( 'gourl_title', $this->title); }
	    	if (!isset($this->languages[$this->deflang])) 	{ $this->deflang = 'en'; edd_update_option( 'gourl_deflang', $this->deflang); }
	    		
	    	if (!$this->emultiplier || !is_numeric($this->emultiplier) || $this->emultiplier < 0.01) { $this->emultiplier = 1; edd_update_option( 'gourl_emultiplier', $this->emultiplier); }
	    	if (!is_numeric($this->iconwidth) || $this->iconwidth < 30 || $this->iconwidth > 250) 	 { $this->iconwidth = 60; edd_update_option( 'gourl_iconwidth', $this->iconwidth); }
	    	
	    	if ($this->defcoin && $this->payments && !isset($this->payments[$this->defcoin])) { $this->defcoin = key($this->payments); edd_update_option( 'gourl_defcoin', $this->defcoin); }
	    	elseif (!$this->payments)						{ $this->defcoin = ''; edd_update_option( 'gourl_defcoin', $this->defcoin); }
	    	elseif (!$this->defcoin)						{ $this->defcoin = key($this->payments); edd_update_option( 'gourl_defcoin', $this->defcoin); }
	    	
	    	return true;
	    }
	    
	    
	    
	    /*
	     * 3.3 Settings Page
	    */
	   	public function init_form_fields( $settings ) 
	    {

	    	$gourl_settings = array(
    				'gourl' => array(
    						'id'   => 'gourl',
    						'name' => '<a id="gourls"></a><strong>' . __( 'GoUrl Bitcoin/Altcoin', GOURLEDD ) . '</strong>',
    						'desc' => __( 'Configure the GoUrl Bitcoin/Altcoin Settings', GOURLEDD ),
    						'type' => 'header',
    				),
    				'gourl_intro' => array(
    						'id'   => 'gourl_intro',
    						'name' => "<a target='_blank' href='https://gourl.io/'><img border='0' src='".plugins_url('/images/gourl.png', __FILE__)."'></a>",
    						'desc' => $this->method_description,
    						'type' => 'descriptive_text',
    				),
    				'gourl_title' => array(
    						'id'   => 'gourl_title',
    						'name' => __( 'Title', GOURLEDD ),
    						'desc' => __( 'Payment method title that the customer will see on your checkout', GOURLEDD ),
    						'type' => 'text',
    						'size' => 'regular',
    						'std' => $this->title
    				),
    				'gourl_emultiplier' => array(
    						'id' => 'gourl_emultiplier',
    						'name' => __('Exchange Rate Multiplier', GOURLEDD ),
    						'desc' => '<br/>'.__('The system uses the multiplier rate with today LIVE cryptocurrency exchange rates (which are updated every 30 minutes) when the transaction is calculating from a fiat currency (e.g. USD, EUR, etc) to cryptocurrency. <br/> Example: <b>1.05</b> - will add an extra 5% to the total price in bitcoin/altcoins, <b>0.85</b> - will be a 15% discount for the price in bitcoin/altcoins. Default: 1.00', GOURLEDD ),
    						'type' => 'text',
    						'size' => 'medium',
    						'std' => $this->emultiplier
    				),
    				'gourl_deflang' => array(
    						'id' => 'gourl_deflang',
    						'name' => __('PaymentBox Language', GOURLEDD ),
    						'desc' => __("Default Crypto Payment Box Localisation", GOURLEDD),
    						'type' => 'select',
    						'options' => $this->languages,
    						'chosen' => true,
    						'placeholder' => __( 'Select a language', GOURLEDD ),
    						'std' => $this->deflang
    				),
    				'gourl_defcoin' => array(
    						'id' => 'gourl_defcoin',
    						'name' => __('PaymentBox Default Coin', GOURLEDD ),
    						'desc' => sprintf(__( "Default Coin in Crypto Payment Box. Activated Payments : <a href='%s'>%s</a>", GOURLEDD ), $this->url, $this->cointxt),
    						'type' => 'select',
    						'options' => $this->payments,
    						'chosen' => true,
    						'placeholder' => __( 'Select a coin', GOURLEDD ),
    						'std' => $this->defcoin
    				),
    				'gourl_iconwidth' => array(
    						'id' => 'gourl_iconwidth',
    						'name' => __( 'Icons Size', GOURLEDD ),
    						'desc' => 'px<br/>'.__( "Cryptocoin icons size in 'Select Payment Method' that the customer will see on your checkout. Default 60px. Allowed: 30..250px", GOURLEDD ),
    						'type' => 'text',
    						'size' => 'medium',
    						'std' => $this->iconwidth
    				),
    				'gourl_boxstyle' => array(
    						'id'   => 'gourl_boxstyle',
    						'name' => __( 'Payment Box Style', GOURLEDD ),
    						'desc' => sprintf(__( "Payment Box <a href='%s'>sizes</a> and border <a href='%s'>shadow</a> you can change <a href='%s'>here &#187;</a>", GOURLEDD ), "https://gourl.io/images/global/sizes.png", "https://gourl.io/images/global/styles.png", $this->url."#gourlmonetaryunitprivate_key"),
    						'type' => 'descriptive_text',
    				),
    				'gourl_langstyle' => array(
    						'id'   => 'gourl_langstyle',
    						'name' => __( 'Languages', GOURLEDD ),
    						'desc' => sprintf(__( "If you want to use GoUrl Bitcoin Gateway plugin in a language other than English, see the page <a href='%s'>Languages and Translations</a>", GOURLEDD ), "https://gourl.io/languages.html") . '<br/><br/><br/>',
    						'type' => 'descriptive_text',
    				),
    				 
    		);
    	
    		$gateway_settings        = array_merge( $settings, $gourl_settings );
    	
    		return $gateway_settings;
    	
    	}
	    	
	    
    	
	   	/*
	   	 * 3.4
	   	*/
	    public function register_payment_icon( $payment_icons ) 
		{
	   		$payment_icons[plugins_url('/images/gourl.png', __FILE__)] = __( 'GoUrl Bitcoin/Altcoin', GOURLEDD );
	    	
	   		return $payment_icons;
		}
	    	 
	
		
	   	/*
	   	 * 3.5 Register GoUrl Gateway
	   	*/
	    public function gourl_register( $gateways ) {
		    
	    	$gateways['gourl'] = array(
	    			'admin_label'       => __( 'GoUrl Bitcoin/Altcoin', GOURLEDD ),
	    			'checkout_label'    => $this->title
	    	);
		    
	    	return $gateways;
		    
	    }

    
   
	    /*
	     *	3.6 Add Cryptocurrency in Currencies List; Users can set prices in Bitcoin/Altcoin directly
	    */
	    public function currencies ( $currencies )
	    {
	    	global $gourl;
	    
	    	if (class_exists('gourlclass') && defined('GOURL') && defined('GOURL_ADMIN') && is_object($gourl))
	    	{
	    		$arr = $gourl->coin_names();
	    
	    		foreach ($arr as $k => $v)
	    			$currencies[$k] = __( 'Cryptocurrency', GOURLEDD ) . " - " . __( ucfirst($v), GOURLEDD ) . " (".($k=="BTC"?"&#3647;":$k).")";
	    		
	    		asort($currencies);
	    		
	    		__( 'Bitcoin', GOURLEDD );  // use in translation
	    		
	    	}
	    
	    	return $currencies;
	    }
	     

	    
	    /*
	     *	3.7
	    */
	    public function currency_symbol( $formated ) 
	    {
			$formated = str_replace( 'BTC', '&#3647;', $formated );
				
	    	return $formated;
	    }
	     
	    
	    
	    /*
	     *	3.8
	    */
	    public function currency_decimals( $decimals ) 
	    {
	    	global $edd_options, $gourl;
	    
	    	$currency = isset( $edd_options['currency'] ) ? $edd_options['currency'] : 'USD';
	    	
	    	if (class_exists('gourlclass') && defined('GOURL') && defined('GOURL_ADMIN') && is_object($gourl))
	    	{
	    		$arr = $gourl->coin_names();
	    		if (isset($arr[$currency])) 
	    		{
	    			if ($currency == "BTC") 	$decimals = 4;
	    			elseif (in_array($currency, array("LTC", "XPY", "DASH"))) $decimals = 3;
	    			else $decimals = 0;
	    		}
	    	}
	    	
	    	return $decimals;
	    }
	     
	    
	    
		/*
	     * 3.9
	     */
		public function admin_footer_text()
	    {
		    	return sprintf( __( "If you like <b>Bitcoin Gateway for Easy Digital Downloads</b> please leave us a %s rating on %s. A huge thank you from GoUrl in advance!", GOURLEDD ), "<a href='https://wordpress.org/support/view/plugin-reviews/gourl-bitcoin-easy-digital-downloads-edd?filter=5#postform' target='_blank'>&#9733;&#9733;&#9733;&#9733;&#9733;</a>", "<a href='https://wordpress.org/support/view/plugin-reviews/gourl-bitcoin-easy-digital-downloads-edd?filter=5#postform' target='_blank'>WordPress.org</a>");
	    }
		    
	    
	    
	    /*
	     * 3.10 Forward to Payment Page
	    */
	    public function process_payment( $purchase_data )
	    {
	    
	    	if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'edd-gateway' ) ) {
	    		wp_die( __( 'Nonce verification has failed', GOURLEDD ), __( 'Error', GOURLEDD ), array( 'response' => 403 ) );
	    	}
	    		
	    	$payment_data = array(
	    			'price' 		=> $purchase_data['price'],
	    			'date' 			=> $purchase_data['date'],
	    			'user_email' 	=> $purchase_data['user_email'],
	    			'purchase_key' 	=> $purchase_data['purchase_key'],
	    			'currency' 		=> edd_get_currency(),
	    			'downloads' 	=> $purchase_data['downloads'],
	    			'user_info' 	=> $purchase_data['user_info'],
	    			'cart_details' 	=> $purchase_data['cart_details'],
	    			'status' 		=> 'pending'
	    	);
	    		
	    	// Record the pending payment
	    	$payment_id = edd_insert_payment( $payment_data );
	    		
	    	if ( $payment_id )
	    	{
    			// Save Log
	    		$userID		= edd_get_payment_user_id( $payment_id );
	    		$user 		= (!$userID) ? __('Guest', GOURLEDD) : "<a href='".admin_url("user-edit.php?user_id=".$userID)."'>user".$userID."</a>";
	    		edd_insert_payment_note( $payment_id, sprintf(__('Order Created by %s. <br/>Awaiting cryptocurrency payment ...', GOURLEDD ), $user) . ' <br/>');
	    		
	    		// Forward to payment page
	    		edd_empty_cart();
	    		edd_send_to_success_page();
	    	}
	    	else
	    	{
	    		edd_record_gateway_error( __( 'Payment Error', GOURLEDD ), sprintf( __( 'Payment creation failed while processing Bitcoin/Altcoin purchase. Payment data: %s', GOURLEDD ), json_encode( $payment_data ) ), $payment );
	    		// If errors are present, send the user back to the purchase page so they can be corrected
	    		edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
	    	}
	    		
	    	return true;
	    }
	     
	    
	    
	    /*
	     * 3.11 Payment Page
	     */
	    public function cryptocoin_payment( $payment )
		{
			global $gourl;
			
			
			if (!is_object($payment) || edd_get_payment_gateway($payment->ID) != "gourl") return true;
			
			// Current Order Details
			$status			= $payment->post_status;
			$amount 		= edd_get_payment_amount( $payment->ID );
			$currency 		= edd_get_payment_currency_code ($payment->ID );
			$userID			= edd_get_payment_user_id( $payment->ID );
			$orderID		= "order" . $payment->ID;
			
			
			// file shortcode-receipt.php
			// filter 'edd_payment_receipt_before' inside <table>
			echo '</thead></table>';
			
			
			if (!$payment || !$payment->ID)
			{
				echo '<h3>' . __( 'Information', GOURLEDD ) . '</h3>' . PHP_EOL;
				echo "<div class='edd-alert edd-alert-error'>". __( 'The GoUrl payment plugin was called to process a payment but could not retrieve the order details. Cannot continue!', GOURLEDD )."</div>";
			}
			elseif (!in_array($status, array("pending", "publish")))
			{
				echo '<h3>' . __( 'Information', GOURLEDD ) . '</h3>' . PHP_EOL;
				echo "<div class='edd-alert edd-alert-error'>". sprintf(__( "This order's status is '%s' - it cannot be paid for. Please contact us if you need assistance.", GOURLEDD ), $status)."</div>";
			}
			elseif (!class_exists('gourlclass') || !defined('GOURL') || !is_object($gourl))
			{
				echo '<h3>' . __( 'Information', GOURLEDD ) . '</h3>' . PHP_EOL;
				echo "<div class='edd-alert edd-alert-error'>".sprintf(__( "Please try a different payment method. Admin need to install and activate wordpress plugin <a href='%s'>GoUrl Bitcoin Gateway for Wordpress</a> to accept Bitcoin/Altcoin Payments online.", GOURLEDD), "https://gourl.io/bitcoin-wordpress-plugin.html")."</div>";
			}
			elseif (!$this->payments || !$this->defcoin || true === version_compare(EDD_VERSION, '2.4.2', '<') || true === version_compare(GOURL_VERSION, '1.3.4', '<') || 
					(array_key_exists($currency, $this->coin_names) && !array_key_exists($currency, $this->payments)))
			{
				echo '<h3>' . __( 'Information', GOURLEDD ) . '</h3>' . PHP_EOL;
				echo  "<div class='edd-alert edd-alert-error'>".sprintf(__( 'Sorry, but there was an error processing your order. Please try a different payment method or contact us if you need assistance (GoUrl Bitcoin Plugin not configured / %s not activated).', GOURLEDD ),(!$this->payments || !$this->defcoin || !isset($this->coin_names[$currency])? $this->title : $this->coin_names[$currency]))."</div>";
			}
			else 
			{ 	
				
				$plugin			= "gourledd";
				$period			= "NOEXPIRY";
				$language		= $this->deflang;
				$coin 			= $this->coin_names[$this->defcoin];
				$affiliate_key 	= 'gourl';
				$crypto			= array_key_exists($currency, $this->coin_names);
				
				if (!$userID) $userID = "guest"; // allow guests to make checkout (payments)
				
	
				
				if (!$userID) 
				{
					echo '<h3>' . __( 'Information', GOURLEDD ) . '</h3>' . PHP_EOL;
					echo "<div align='center'><a href='".wp_login_url(get_permalink())."'>
							<img style='border:none;box-shadow:none;' title='".__('You need first to login or register on the website to make Bitcoin/Altcoin Payments', GOURLEDD )."' vspace='10'
							src='".$gourl->box_image()."' border='0'></a></div>";
				}
				elseif ($amount <= 0)
				{
					echo '<h3>' . __( 'Information', GOURLEDD ) . '</h3>' . PHP_EOL;
					echo "<div class='edd-alert edd-alert-error'>". sprintf(__( "This order's amount is %s - it cannot be paid for. Please contact us if you need assistance.", GOURLEDD ), $amount ." " . $currency)."</div>";
				}
				else
				{
	
					// Exchange (optional)
					// --------------------
					if ($currency != "USD" && !$crypto)
					{
						$amount = gourl_convert_currency($currency, "USD", $amount);
							
						if ($amount <= 0)
						{
							echo '<h3>' . __( 'Information', GOURLEDD ) . '</h3>' . PHP_EOL;
							echo "<div class='edd-alert edd-alert-error'>".sprintf(__( 'Sorry, but there was an error processing your order. Please try later or use a different payment method. System cannot receive exchange rates for %s/USD from Google Finance', GOURLEDD ), $currency)."</div>";
						}
						else $currency = "USD";
					}
						
					if (!$crypto) $amount = $amount * $this->emultiplier;
						
					
						
					// Payment Box
					// ------------------
					if ($amount > 0)
					{
						// crypto payment gateway
						$result = $gourl->cryptopayments ($plugin, $amount, $currency, $orderID, $period, $language, $coin, $affiliate_key, $userID, $this->iconwidth);
						
						if (!isset($result["is_paid"]) || !$result["is_paid"]) echo '<h3>' . __( 'Pay Now -', GOURLEDD ) . '</h3>' . PHP_EOL;
						
						if ($result["error"]) echo "<div class='edd-alert edd-alert-error'>".__( "Sorry, but there was an error processing your order. Please try a different payment method.", GOURLEDD )."<br/>".$result["error"]."</div>";
						else
						{
							// display payment box or successful payment result
							echo $result["html_payment_box"];
							
							// payment received
							if ($result["is_paid"]) 
							{	
								if (false) echo "<div align='center'>" . sprintf( __('%s Payment ID: #%s', GOURLEDD), ucfirst($result["coinname"]), $result["paymentID"]) . "</div>";
								echo "<br/>";
								
								if ($status == 'pending') header('Location: '.$_SERVER['REQUEST_URI']);
							}
						}
					}	
				}
		    }
	
		    echo '<br/><br/><table id="edd_purchase_receipt"><thead>';
		    	    
		    return true;
		}
		    
		
	    
	}
	// end class Edd_Gateway_GoUrl




	
	
	/*
	 *  4. Instant Payment Notification Function - pluginname."_gourlcallback"
	 *  
	 *  This function will appear every time by GoUrl Bitcoin Gateway when a new payment from any user is received successfully. 
	 *  Function gets user_ID - user who made payment, current order_ID (the same value as you provided to bitcoin payment gateway), 
	 *  payment details as array and box status.
	 *  
	 *  The function will automatically appear for each new payment usually two times :  
	 *  a) when a new payment is received, with values: $box_status = cryptobox_newrecord, $payment_details[is_confirmed] = 0
	 *  b) and a second time when existing payment is confirmed (6+ confirmations) with values: $box_status = cryptobox_updated, $payment_details[is_confirmed] = 1.
	 *
	 *  But sometimes if the payment notification is delayed for 20-30min, the payment/transaction will already be confirmed and the function will
	 *  appear once with values: $box_status = cryptobox_newrecord, $payment_details[is_confirmed] = 1
	 *  
	 *  Read more - https://gourl.io/affiliate-bitcoin-wordpress-plugins.html
	 */ 
	function gourledd_gourlcallback ($user_id, $order_id, $payment_details, $box_status)
	{
		
    	if (!in_array($box_status, array("cryptobox_newrecord", "cryptobox_updated"))) 	return false;
		if (strpos($order_id, "order") === 0) $payment_id = substr($order_id, 5); else 	return false;
		if (!$user_id || $payment_details["status"] != "payment_received") 				return false;
		$payment = get_post( $payment_id ); if ( !$payment || !$payment->post_status ) 	return false;
		
		
		$coinName 	= ucfirst($payment_details["coinname"]);
		$amount		= $payment_details["amount"] . " " . $payment_details["coinlabel"] . "&#160; ( $" . $payment_details["amountusd"] . " )";
		$payID		= $payment_details["paymentID"];
		$confirmed	= ($payment_details["is_confirmed"]) ? __('Yes', GOURLEDD) : __('No', GOURLEDD);
		
		
		// a. New Payment Received - Awaiting Transaction Confirmation...
		if ($box_status == "cryptobox_newrecord") 
		{	
			// Save Log
			edd_insert_payment_note( $payment_id, sprintf(__("<b>%s</b> Payment Received <br/>%s <br/><a href='%s'>Payment ID: %s</a>. <br/>Awaiting network confirmation...", GOURLEDD), __($coinName, GOURLEDD), $amount, GOURL_ADMIN.GOURL."payments&s=payment_".$payID, $payID) . ' <br/>');
			edd_set_payment_transaction_id( $payment_id, $payment_details["tx"] );
		}
		
			
		// b. Existing Payment Confirmed (6+ transaction confirmations)
		if ($payment_details["is_confirmed"])
		{
			// Save Log
			edd_insert_payment_note( $payment_id, sprintf(__("%s Payment ID: <a href='%s'>%s</a> - <b>Confirmed</b>", GOURLEDD), __($coinName, GOURLEDD), GOURL_ADMIN.GOURL."payments&s=payment_".$payID, $payID) . ' <br/>');
		}
		 
		
		// c. Update Status to Completed
		if ($payment->post_status != 'publish')
		{ 
			edd_update_payment_status( $payment_id, 'publish' );
		}
		
		
		return true;
	}

	
	
	new Edd_Gateway_GoUrl();
	

	
 }
 // end gourl_edd_gateway_load()


}