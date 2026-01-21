<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WooCommerce' ) ) {
	die( esc_html( __( 'Local delivery drivers for WooCommerce is a WooCommerce add-on, you must activate a WooCommerce on your site.', 'lddfw' ) ) );
}

/**
 * Get WordPress query_var.
 */
$lddfw_screen       = ( '' !== get_query_var( 'lddfw_screen' ) ) ? get_query_var( 'lddfw_screen' ) : 'dashboard';
$lddfw_order_id     = get_query_var( 'lddfw_orderid' );
$lddfw_reset_key    = get_query_var( 'lddfw_reset_key' );
$lddfw_page         = get_query_var( 'lddfw_page' );
$lddfw_reset_login  = get_query_var( 'lddfw_reset_login' );
$lddfw_dates		= get_query_var( 'lddfw_dates' );

/**
 * Set global variables.
*/
$lddfw_driver    				= new LDDFW_Driver();
$lddfw_screens    				= new LDDFW_Screens();
$lddfw_content   				= '';
$lddfw_driver_id 				= '';
$lddfw_wpnonce 					= wp_create_nonce( 'lddfw-nonce' );
$lddfw_drivers_tracking_timing 	= '';
$lddfw_tracking_status 			= '';

/**
 * Log out delivery driver.
*/
if ( 'logout' === $lddfw_screen ) {
	LDDFW_Login::lddfw_logout();
}

/**
 * Check if user is logged in.
*/
if ( ! is_user_logged_in() ) {
	$lddfw_content = $lddfw_screens->lddfw_home();
} else {
	// Check if user is a delivery driver.
	$lddfw_user = wp_get_current_user();
	$lddfw_driver_id = $lddfw_user->ID;

	//Get user on network.
	$lddfw_user = new WP_User( $lddfw_driver_id, '', get_current_blog_id() );

	$lddfw_driver_account = get_user_meta( $lddfw_driver_id, 'lddfw_driver_account', true );
	if ( ! in_array( 'driver', (array) $lddfw_user->roles, true ) || '1' !== $lddfw_driver_account ) {
		LDDFW_Login::lddfw_logout();
		// User is not a delivery driver.
		$lddfw_user_is_driver = 0;
		$lddfw_content        = $lddfw_screens->lddfw_home();
	} else {
		/**
		 * User is a delivery driver.
		 */

		// Set global variables.
		$lddfw_user_is_driver      = 1;
		$lddfw_driver_name         = $lddfw_user->display_name;
		$lddfw_driver_availability = get_user_meta( $lddfw_driver_id, 'lddfw_driver_availability', true );
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				$lddfw_drivers_tracking_timing = get_option( 'lddfw_drivers_tracking_timing' );
				$lddfw_tracking_status = get_user_meta( $lddfw_driver_id, 'lddfw_tracking_status', true );
			}
		}


		// Get the number of orders in each status.
		$lddfw_orders                   = new LDDFW_Orders();
		$lddfw_array                    = $lddfw_orders->lddfw_orders_count_query( $lddfw_driver_id );
		$lddfw_out_for_delivery_counter = 0;
		$lddfw_failed_attempt_counter   = 0;
		$lddfw_delivered_counter        = 0;
		$lddfw_assign_to_driver_counter = 0;
		$lddfw_claim_orders_counter     = 0;

		/**
		 * Set current status names
		 */
		$lddfw_driver_assigned_status_name  = esc_html( __( 'Driver assigned', 'lddfw' ) );
		$lddfw_out_for_delivery_status_name = esc_html( __( 'Out for delivery', 'lddfw' ) );
		$lddfw_failed_attempt_status_name   = esc_html( __( 'Failed delivery', 'lddfw' ) );
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$result = wc_get_order_statuses();
			if ( ! empty( $result ) ) {
				foreach ( $result as $key => $status ) {
					 switch ( $key ) {
					 	case get_option( 'lddfw_out_for_delivery_status' ):
							if ( $status !== $lddfw_out_for_delivery_status_name ){
								$lddfw_out_for_delivery_status_name = $status;
							}
							break;
						case get_option( 'lddfw_failed_attempt_status' ):
							if ( $status !== esc_html( __( 'Failed Delivery Attempt', 'lddfw' ) ) ){
								$lddfw_failed_attempt_status_name = $status;
							}
							break;
						case get_option( 'lddfw_driver_assigned_status' ):
							if ( $status !== $lddfw_driver_assigned_status_name ){
								$lddfw_driver_assigned_status_name = $status;
							}
							break;
					}
				}
			}
		}


		foreach ( $lddfw_array as $row ) {

			switch ( $row->post_status ) {
				case get_option( 'lddfw_out_for_delivery_status' ):
					$lddfw_out_for_delivery_counter = $row->orders;
					break;
				case get_option( 'lddfw_failed_attempt_status' ):
					$lddfw_failed_attempt_counter = $row->orders;
					break;
				case get_option( 'lddfw_delivered_status' ):
					$lddfw_delivered_counter = $row->orders;
					break;
				case get_option( 'lddfw_driver_assigned_status' ):
					$lddfw_assign_to_driver_counter = $row->orders;
					break;
			}
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				if ( '1' === get_option( 'lddfw_self_assign_delivery_drivers', '' ) ){
					$lddfw_claim_orders_counter = $lddfw_orders->lddfw_claim_orders_counts__premium_only();
				}
			}
		}

		/**
		 * Drivers screens.
		*/
		if ( 'dashboard' === $lddfw_screen ) {
			$lddfw_content = $lddfw_screens->lddfw_dashboard_screen( $lddfw_driver_id );
		}
		if ( 'out_for_delivery' === $lddfw_screen ) {
			$lddfw_content = $lddfw_screens->lddfw_out_for_delivery_screen( $lddfw_driver_id );
		}
		if ( 'failed_delivery' === $lddfw_screen ) {
			$lddfw_content = $lddfw_screens->lddfw_failed_delivery_screen( $lddfw_driver_id );
		}
		if ( 'delivered' === $lddfw_screen ) {
			$lddfw_content = $lddfw_screens->lddfw_delivered_screen( $lddfw_driver_id );
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				if ( 'claim_orders' === $lddfw_screen ) {
					$lddfw_content = $lddfw_screens->lddfw_claim_orders_screen__premium_only( $lddfw_driver_id );
				}

			}
		}

		if ( 'settings' === $lddfw_screen && '' !== $lddfw_driver_id ) {
			$lddfw_content = $lddfw_screens->lddfw_driver_settings_screen( $lddfw_driver_id );
		}

		if ( 'assign_to_driver' === $lddfw_screen ) {
			$lddfw_content = $lddfw_screens->lddfw_assign_to_driver_screen( $lddfw_driver_id );
		}

		if ( 'order' === $lddfw_screen && '' !== $lddfw_order_id ) {
			$lddfw_content = $lddfw_screens->lddfw_order_screen( $lddfw_driver_id );
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				if ( 'info' === $lddfw_screen ) {
					$lddfw_content = $lddfw_screens->lddfw_info_screen__premium_only();
				}

				// Screen Filter.
				if ( has_filter( 'lddfw_driver_screen' ) ){
					$lddfw_content = apply_filters('lddfw_driver_screen', $lddfw_content );
				}

			}
		}

	}
}
	/**
	 * Register scripts and css files
	 */
	wp_register_script( 'lddfw-jquery-validate', plugin_dir_url( __FILE__ ) . 'public/js/jquery.validate.min.js', array( 'jquery', 'jquery-ui-core' ), LDDFW_VERSION, true );
	wp_register_script( 'lddfw-bootstrap', plugin_dir_url( __FILE__ ) . 'public/js/bootstrap.min.js', array(), LDDFW_VERSION, false );
	if ( lddfw_fs()->is__premium_only() ) {
		if ( lddfw_fs()->is_plan( 'premium', true ) ) {
			wp_register_script( 'lddfw-signature', plugin_dir_url( __FILE__ ) . 'public/js/signature_pad.min.js', array(), LDDFW_VERSION, false );
		}
	}
	wp_register_script( 'lddfw-public', plugin_dir_url( __FILE__ ) . 'public/js/lddfw-public.js', array(), LDDFW_VERSION, false );

	wp_register_style( 'lddfw-bootstrap', plugin_dir_url( __FILE__ ) . 'public/css/bootstrap.min.css', array(), LDDFW_VERSION, 'all' );
	wp_register_style( 'lddfw-fonts', 'https://fonts.googleapis.com/css?family=Open+Sans|Roboto&display=swap', array(), LDDFW_VERSION, 'all' );
	wp_register_style( 'lddfw-public', plugin_dir_url( __FILE__ ) . 'public/css/lddfw-public.css', array(), LDDFW_VERSION, 'all' );

?>
<!DOCTYPE html>
<html>
<head>
<?php
	echo '<title>' . esc_js( __( 'Delivery Driver', 'lddfw' ) ) . '</title>';
?>
<meta name="robots" content="noindex" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="icon" href="<?php echo get_site_icon_url( 32, esc_url( plugin_dir_url( __FILE__ ) . 'public/images/favicon-32x32.png?ver=' . LDDFW_VERSION ) ); ?>" >
<?php
	wp_print_styles( [ 'lddfw-fonts', 'lddfw-bootstrap', 'lddfw-public' ] );

	if ( is_rtl() === true ) {
		wp_register_style( 'lddfw-public-rtl', plugin_dir_url( __FILE__ ) . 'public/css/lddfw-public-rtl.css', array(), LDDFW_VERSION, 'all' );
		wp_print_styles( [ 'lddfw-public-rtl' ] );
	}

	wp_print_scripts( [ 'lddfw-jquery-validate' ] );
?>
<?php
echo '<script>
	var lddfw_driver_id = "' . esc_js( $lddfw_driver_id ) . '";
	var lddfw_ajax_url = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";
	var lddfw_confirm_text = "' . esc_js( __( 'Are you sure?', 'lddfw' ) ) . '";
	var lddfw_nonce = {"nonce":"' . esc_js(  $lddfw_wpnonce ) . '"};
	var lddfw_hour_text = "' . esc_js( __( 'hour', 'lddfw' ) ) . '";
	var lddfw_hours_text = "' . esc_js( __( 'hours', 'lddfw' ) ) . '";
	var lddfw_mins_text = "' . esc_js( __( 'mins', 'lddfw' ) ) . '";
	var lddfw_dates = "' . esc_js( $lddfw_dates ) . '";
	var lddfw_tracking_status = "' . esc_js( $lddfw_tracking_status ) . '";
</script>';

if ( lddfw_fs()->is__premium_only() ) {
	if ( lddfw_fs()->is_plan( 'premium', true ) ) {
		/**
		 * Branding
		 */
		$lddfw_branding_background 		  = esc_attr( get_option( 'lddfw_branding_background', '' ) );
		$lddfw_branding_text_color		  = esc_attr( get_option( 'lddfw_branding_text_color', '' ) );
		$lddfw_branding_button_color	  = esc_attr( get_option( 'lddfw_branding_button_color', '' ) );
		$lddfw_branding_button_background = esc_attr( get_option( 'lddfw_branding_button_background', '' ) );
		echo '<style>';
		if ( '' !== $lddfw_branding_background ){ 
			echo '#lddfw_home .lddfw_cover , #lddfw_home { background-color:' . $lddfw_branding_background . '; }';
		}
		if ( '' !== $lddfw_branding_text_color ){ 
			echo '#lddfw_home h1 , #lddfw_home {  color:' . $lddfw_branding_text_color . '; }';
		}
		if ( '' !== $lddfw_branding_button_color ){ 
			echo '#lddfw_start { color:' . $lddfw_branding_button_color . '; }';
		}
		if ( '' !== $lddfw_branding_button_background ){ 
			echo '#lddfw_start { border-color: ' . $lddfw_branding_button_background . '; background-color:' . $lddfw_branding_button_background . '; }';
		}
		echo '</style>';
	}
}
?>


</head>
<body>
	<div id="lddfw_page" class="<?php echo $lddfw_screen;?>" ><?php echo $lddfw_content; ?></div>
<?php
	//Print scripts.
	if ( lddfw_is_free() ){
		wp_print_scripts( [ 'lddfw-bootstrap', 'lddfw-public' ] );
	} else {
		wp_print_scripts( [ 'lddfw-bootstrap', 'lddfw-signature', 'lddfw-public' ] );
	}

	if ( lddfw_fs()->is__premium_only() ) {
		if ( lddfw_fs()->is_plan( 'premium', true ) ) {
			//Print Tracking script.
			if ( false !== $lddfw_drivers_tracking_timing && '' !== $lddfw_drivers_tracking_timing ){
				$tracking = new LDDFW_Tracking();
				echo $tracking->lddfw_drivers_panel_script();
			}
		}
	}
?>
</body>
</html>
