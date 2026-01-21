<?php
/**
 * Update post meta.
 *
 * @return void
 */
function lddfw_update_post_meta( $order_id, $key, $value ){
	update_post_meta( $order_id, $key, $value );
	lddfw_update_sync_order( $order_id, $key, $value );
}

/**
 * Delete post meta.
 *
 * @return void
 */
function lddfw_delete_post_meta( $order_id, $key ){
	delete_post_meta( $order_id, $key );
	lddfw_update_sync_order( $order_id, $key, '0' );
}

/**
 * Update a order row from sync table when a order is updated.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_update_sync_order( $order_id, $key, $value ) {
    global $wpdb;

	$column ='';
	switch ( $key ) {
		case 'lddfw_order_sort':
			$column = 'order_sort';
			break;
		case 'lddfw_delivered_date':
			$column = 'delivered_date';
			break;
		case 'lddfw_driverid':
			$column = 'driver_id';
			break;
		case 'lddfw_driver_commission':
			$column = 'driver_commission';
			break;
		case 'order_refund_amount':
			$column = 'order_refund_amount';
			break;
	}

	if ( '' !== $column ){

		if ( ! lddfw_is_order_already_exists( $order_id ) ) {
			lddfw_insert_orderid_to_sync_order( $order_id );
		}

		$table_name  = $wpdb->prefix."lddfw_orders";
		$wpdb->query( $wpdb->prepare("UPDATE $table_name
			SET $column = %s
			WHERE order_id = %s",$value, $order_id)
		);
	}
}


/**
 * Update order row in sync table.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_update_all_sync_order( $order ){
	global $wpdb;
	$table_name = $wpdb->prefix . 'lddfw_orders';
	$store      = new LDDFW_Store();
	$seller_id  = $store->lddfw_order_seller( $order );
	$city 		= ( ! empty ( $order->get_shipping_city() ) ) ? $order->get_shipping_city() : $order->get_billing_city();
	$refund 	= $order->get_total_refunded();
	$wpdb->query( $wpdb->prepare("UPDATE $table_name
	 SET
			driver_id   = %d,
			seller_id   = %d,
			order_total = %f,
			driver_commission = %f,
			delivered_date = %s,
			order_sort = %d,
			order_refund_amount = %f,
			order_shipping_amount = %f,
			order_shipping_city = %s
	 WHERE order_id = %s",
	 		$order->get_meta('lddfw_driverid'),
			$seller_id,
		 	$order->get_total(),
		 	$order->get_meta('lddfw_driver_commission'),
		 	$order->get_meta('lddfw_delivered_date'),
		 	$order->get_meta('lddfw_order_sort'),
		 	$refund,
		 	$order->get_shipping_total(),
		 	$city,
			$order->get_id() )
		);

}


 /**
     * Delete  orders and from lddfw sync table when a order is deleted
     *
     * @param int $post_id
     */
     function lddfw_admin_on_delete_order( $post_id ) {
        $post = get_post( $post_id );

        if ( 'shop_order' == $post->post_type ) {
            lddfw_delete_sync_order( $post_id );

            $sub_orders = get_children(
                array(
					'post_parent' => $post_id,
					'post_type'   => 'shop_order',
                )
            );
            if ( $sub_orders ) {
                foreach ( $sub_orders as $order_post ) {
					lddfw_delete_sync_order( $order_post->ID );
                }
            }
        }
    }


/**
 * Delete a order row from sync table when a order is deleted from WooCommerce.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_delete_sync_order( $order_id ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'lddfw_orders', array( 'order_id' => $order_id ) );
}

/**
 * Insert new order to sync table.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_insert_sync_order_by_id( $order_id ) {
	global $wpdb;
	$order = wc_get_order( $order_id );

	if ( lddfw_is_order_already_exists( $order_id ) ) {
		lddfw_update_all_sync_order( $order );
        return;
    }

	lddfw_insert_sync_order( $order );
}


/**
 * Check if an order with same id is exists in database
 *
 * @param  int order_id
 *
 * @return boolean
 */
function lddfw_is_order_already_exists( $id ) {
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) ) {
        return false;
    }

    $order_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM {$wpdb->prefix}lddfw_orders WHERE order_id=%d LIMIT 1", $id ) );

    return $order_id ? true : false;
}


/**
 * Insert a order row to sync table.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_insert_orderid_to_sync_order( $order_id ){
	global $wpdb;
	$table_name = $wpdb->prefix . 'lddfw_orders';
	$wpdb->insert( $table_name, [ 'order_id' => $order_id, ], [ '%d' ]);
}

/**
 * Insert a order row to sync table.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_insert_sync_order( $order ){
	global $wpdb;
	$table_name = $wpdb->prefix . 'lddfw_orders';
	$store      = new LDDFW_Store();
	$seller_id = $store->lddfw_order_seller( $order );
	$city 		= ( ! empty ( $order->get_shipping_city() ) ) ? $order->get_shipping_city() : $order->get_billing_city();
	$order_date = ( ! empty ( $order->get_date_created() ) ) ? $order->get_date_created()->format( 'Y-m-d H:i:s' ) : '';
	$order_status = $order->get_status();
	// make sure order status contains "wc-" prefix
	if ( stripos( $order_status, 'wc-' ) === false ) {
		$order_status = 'wc-' . $order_status;
	}

	// Delete duplicate orders.
	lddfw_delete_sync_order( $order->get_id() );

	$wpdb->insert(
		$table_name,
		[
			'order_id'     => $order->get_id(),
			'driver_id'    => $order->get_meta('lddfw_driverid'),
			'seller_id'    => $seller_id,
			'order_total'  => $order->get_total(),
			'driver_commission' => $order->get_meta('lddfw_driver_commission'),
			'delivered_date' => $order->get_meta('lddfw_delivered_date'),
			'order_sort' => $order->get_meta('lddfw_order_sort'),
			'order_refund_amount' => $order->get_total_refunded(),
			'order_shipping_amount' => $order->get_shipping_total(),
			'order_shipping_city'=> $city,
		],
		[
			'%d',
			'%d',
			'%d',
			'%f',
			'%f',
			'%s',
			'%d',
			'%f',
			'%f',
			'%s',
		]
	);
}

	/**
     * Create order sync table
     *
     * @return void
     */
    function lddfw_create_sync_table() {
        global $wpdb;
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}lddfw_orders` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `order_id` bigint(20) DEFAULT 0,
          `driver_id` bigint(20) DEFAULT 0,
		  `seller_id` bigint(20) DEFAULT 0,
		  `order_total` decimal(19,4) DEFAULT 0,
		  `order_refund_amount` decimal(19,4) DEFAULT 0,
		  `order_sort` bigint(20) DEFAULT 0,
		  `order_shipping_amount` decimal(19,4) DEFAULT 0,
		  `order_shipping_city` varchar(200) DEFAULT NULL,
		  `driver_commission` decimal(19,4) DEFAULT 0,
		  `delivered_date` varchar(50) DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `order_id` (`order_id`),
          KEY `driver_id` (`driver_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        dbDelta( $sql );
    }

	/**
     * Check plugin db
     *
     * @return void
     */
	function lddfw_update_db_check(){
		if ( '2' !== get_option( 'lddfw_sync_table', '' ) ) {
			lddfw_create_sync_table();
			lddfw_sync_table();
		}
	}

	/**
     * Sync table
     *
     * @return void
     */
	function lddfw_sync_table() {
		global $wpdb;

			// If plugin has been upgraded we sync table once.
			if ( '2' !== get_option( 'lddfw_sync_table', '' ) ) {
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}lddfw_orders" );

				// Sync plugin data.
				$wpdb->query( '
				insert into ' .  $wpdb->prefix . 'lddfw_orders (
					order_id,
					driver_id,
					delivered_date,
					driver_commission,
					order_sort
				)
				select p.ID,
				pm.meta_value,
				pm2.meta_value,
				IFNULL ( pm3.meta_value , 0 ),
				IFNULL ( pm4.meta_value , 0)
				from ' . $wpdb->prefix . 'posts p
				inner join ' . $wpdb->prefix . 'postmeta pm on p.ID = pm.post_id and pm.meta_key = \'lddfw_driverid\' and pm.meta_value <> \'\'
				left join ' . $wpdb->prefix . 'postmeta pm2 on p.ID = pm2.post_id and pm2.meta_key = \'lddfw_delivered_date\'
				left join ' . $wpdb->prefix . 'postmeta pm3 on p.ID = pm3.post_id and pm3.meta_key = \'lddfw_driver_commission\'
				left join ' . $wpdb->prefix . 'postmeta pm4 on p.ID = pm4.post_id and pm4.meta_key = \'lddfw_order_sort\'
				group by p.ID');


				//Remove duplicate orders.
				$wpdb->query( 'delete t1 from ' . $wpdb->prefix . 'lddfw_orders t1 
				INNER JOIN ' . $wpdb->prefix . 'lddfw_orders t2
				WHERE
    			t1.id < t2.id AND
    			t1.order_id = t2.order_id');

				// Sync order data.
				$wpdb->query( 'UPDATE ' .  $wpdb->prefix . 'lddfw_orders o
				left join ' . $wpdb->prefix . 'postmeta pm4 on o.order_id = pm4.post_id and pm4.meta_key = \'_order_total\'
				left join ' . $wpdb->prefix . 'postmeta pm5 on o.order_id = pm5.post_id and pm5.meta_key = \'_order_shipping\'
				left join ' . $wpdb->prefix . 'posts p2 on o.order_id=p2.post_parent and p2.post_type = \'shop_order_refund\'
				left join ' . $wpdb->prefix . 'postmeta pm6 on p2.id=pm6.post_id and pm6.meta_key = \'_refund_amount\'
				SET
				o.order_total           = IFNULL ( pm4.meta_value , 0),
				o.order_shipping_amount = IFNULL ( pm5.meta_value , 0),
				o.order_refund_amount   = IFNULL ( pm6.meta_value , 0)
				');


				// Sync order shipping cities.
				$wpdb->query( 'UPDATE ' .  $wpdb->prefix . 'lddfw_orders o
				left join ' . $wpdb->prefix . 'postmeta pm4 on o.order_id = pm4.post_id and pm4.meta_key = \'_shipping_city\'
				left join ' . $wpdb->prefix . 'postmeta pm5 on o.order_id = pm5.post_id and pm5.meta_key = \'_billing_city\'
				SET
				o.order_shipping_city = CASE WHEN pm4.meta_value = \'\' Or pm4.meta_value IS NULL THEN pm5.meta_value else pm4.meta_value END
				');

				// Sync seller
				switch ( LDDFW_MULTIVENDOR ) {
					case 'dokan':
						$wpdb->query( 'UPDATE ' .  $wpdb->prefix . 'lddfw_orders o
						INNER JOIN ' . $wpdb->prefix . 'postmeta pm ON pm.post_iD = o.order_id and pm.meta_key = \'_dokan_vendor_id\'
						SET o.seller_id = IFNULL ( pm.meta_value , 0 )
						');
						break;
					case 'wcmp':
						$wpdb->query( 'UPDATE ' .  $wpdb->prefix . 'lddfw_orders o
						INNER JOIN ' . $wpdb->prefix . 'postmeta pm ON pm.post_iD = o.order_id and pm.meta_key = \'_vendor_id\'
						SET o.seller_id = IFNULL ( pm.meta_value , 0 )
						');
						break;
					case 'wcfm':
						$wpdb->query( 'UPDATE ' .  $wpdb->prefix . 'lddfw_orders o
						INNER JOIN ' . $wpdb->prefix . 'wcfm_marketplace_orders pm ON pm.order_id = o.order_id
						SET o.seller_id = IFNULL ( pm.vendor_id , 0 )
						');
						break;
				}

				// Add option that sync table has been synced
				update_option( 'lddfw_sync_table', '2' );
			}
	}

	/**
     * Update refund in sync table.
     *
     * @return void
     */
	function lddfw_woocommerce_order_refunded( $order_id, $refund_id ){

		// Insert order_id to sync table if not exist.
		if ( ! lddfw_is_order_already_exists( $order_id ) ) {
			lddfw_insert_orderid_to_sync_order( $order_id );
		}

		// Update order on sync table.
		$order = wc_get_order( $order_id );
		lddfw_update_all_sync_order( $order );
	}

	/**
	 * Premium feature.
	 *
	 * @param string $value text.
	 * @return html
	 */
	function lddfw_admin_premium_feature( $value ) {
		$result = $value;
		if ( lddfw_is_free() ) {
			$result = '<div class="lddfw_premium_feature">
						<a class="lddfw_star_button" href="#"><svg style="color:#ffc106" width=20 aria-hidden="true" focusable="false" data-prefix="fas" data-icon="star" class=" lddfw_premium_iconsvg-inline--fa fa-star fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"> <title>' . esc_attr__( 'Premium Feature', 'lddfw' ) . '</title><path fill="currentColor" d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"></path></svg></a>
					  	<div class="lddfw_premium_feature_note" style="display:none">
						  <a href="#" class="lddfw_premium_close">
						  <svg aria-hidden="true"  width=10 focusable="false" data-prefix="fas" data-icon="times" class="svg-inline--fa fa-times fa-w-11" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512"><path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path></svg></a>
						  <h2>' . esc_html( __( 'Premium Feature', 'lddfw' ) ) . '</h2>
						  <p>' . esc_html( __( 'You Discovered a Premium Feature!', 'lddfw' ) ) . '</p>
						  <p>' . esc_html( __( 'Upgrading to Premium will unlock it.', 'lddfw' ) ) . '</p>
						  <a target="_blank" href="https://powerfulwp.com/local-delivery-drivers-for-woocommerce-premium#pricing" class="lddfw_premium_buynow">' . esc_html( __( 'UNLOCK PREMIUM', 'lddfw' ) ) . '</a>
						  </div>
					  </div>';
		}
		return $result;
	}

	/**
	 * International_phone_number
	 *
	 * @param string $country_code country code.
	 * @param string $phone phone number.
	 * @return string
	 */
	 function lddfw_get_international_phone_number( $country_code, $phone ) {
		$phone = preg_replace( '/[^0-9+]*/', '', $phone );

		// if phone number diesnt include + we format the number by country calling code.
		if ( strpos( $phone, '+' ) === false && '' !== $country_code ) {
			$calling_code      = WC()->countries->get_country_calling_code( $country_code );
			$calling_code      = is_array( $calling_code ) ? $calling_code[0] : $calling_code;
			$preg_calling_code = str_replace( '+', '', $calling_code );
			$preg              = '/^(?:\+?' . $preg_calling_code . '|0)?/';
			$phone             = preg_replace( $preg, $calling_code, $phone );
			$phone             = str_replace( $calling_code . '0', $calling_code, $phone );
		}
		return $phone;
	}

	/**
	 * Replace_tags
	 *
	 * @param string $content tags.
	 * @param int    $order_id the order number.
	 * @param object $order Order object.
	 * @param int    $driver_id user id number.
	 * @return array
	 */
	 function lddfw_replace_tags( $content, $order_id, $order, $driver_id ) {
		$date_format                = lddfw_date_format( 'date' );
		$time_format                = lddfw_date_format( 'time' );
		$store                      = new LDDFW_Store();
		$seller_id                  = $store->lddfw_order_seller( $order );
		$store_name                 = $store->lddfw_store_name__premium_only( $order, $seller_id );
		$delivery_driver_first_name = get_user_meta( $driver_id, 'first_name', true );
		$delivery_driver_last_name  = get_user_meta( $driver_id, 'last_name', true );
		$delivery_driver_page       = lddfw_drivers_page_url( '' );

		$order_status = wc_get_order_status_name( $order->get_status() );
		$date_created = $order->get_date_created()->format( $date_format );
		$total        = $order->get_total();
		$currency     = get_woocommerce_currency();

		$billing_first_name = $order->get_billing_first_name();
		$billing_last_name  = $order->get_billing_last_name();
		$billing_company    = $order->get_billing_company();
		$billing_address_1  = $order->get_billing_address_1();
		$billing_address_2  = $order->get_billing_address_2();
		$billing_city       = $order->get_billing_city();
		$billing_country    = $order->get_billing_country();
		$billing_state = LDDFW_Order::lddfw_states( $billing_country, $order->get_billing_state() );
		if ( '' !== $billing_country ) {
			$billing_country = WC()->countries->countries[ $billing_country ];
		}

		$billing_postcode = $order->get_billing_postcode();
		$billing_phone    = $order->get_billing_phone();

		$shipping_first_name = $order->get_shipping_first_name();
		$shipping_last_name  = $order->get_shipping_last_name();
		$shipping_company    = $order->get_shipping_company();
		$shipping_address_1  = $order->get_shipping_address_1();
		$shipping_address_2  = $order->get_shipping_address_2();
		$shipping_city       = $order->get_shipping_city();
		$shipping_postcode   = $order->get_shipping_postcode();

		$shipping_country = $order->get_shipping_country();
		$shipping_state = LDDFW_Order::lddfw_states( $shipping_country, $order->get_shipping_state() );
		if ( '' !== $shipping_country ) {
			$shipping_country = WC()->countries->countries[ $shipping_country ];
		}

		if ( in_array( 'woocommerce-extra-checkout-fields-for-brazil', LDDFW_PLUGINS, true ) ) {
			// Add shipping number to address.
			$shipping_number = get_post_meta( $order_id, '_shipping_number', true );
			if ( '' !== $shipping_number && false !== $shipping_number ) {
				$shipping_address_1 .= ' ' . $shipping_number;
			}

			// Add shipping number to address.
			$billing_number = get_post_meta( $order_id, '_billing_number', true );
			if ( '' !== $billing_number && false !== $billing_number ) {
				$billing_address_1 .= ' ' . $billing_number;
			}
		}

		if ( '' === $shipping_address_1 ) {
			$shipping_address_1 = $billing_address_1;
			$shipping_address_2 = $billing_address_2;
			$shipping_city      = $billing_city;
			$shipping_state     = $billing_state;
			$shipping_postcode  = $billing_postcode;
			$shipping_country   = $billing_country;
		}

		$payment_method  = $order->get_payment_method();
		$shipping_method = $order->get_shipping_method();

		// ETA.
		$estimated_time_of_arrival = '';
		$route                     = get_post_meta( $order_id, 'lddfw_order_route', true );
		if ( ! empty( $route ) ) {
			if ( isset( $route['distance_text'] ) ) {
				$duration_text = $route['distance_text'];
				if ( '' !== $duration_text ) {
					$estimated_time_of_arrival = esc_html( __( 'Estimated time of arrival', 'lddfw' ) ) . ': ' . esc_html( $route['duration_text'] );
				}
			}
		}

		$find = array(
			'[estimated_time_of_arrival]',
			'[delivery_driver_first_name]',
			'[delivery_driver_last_name]',
			'[delivery_driver_page]',
			'[store_name]',
			'[order_id]',
			'[order_create_date]',
			'[order_status]',
			'[order_amount]',
			'[order_currency]',
			'[shipping_method]',
			'[payment_method]',
			'[billing_first_name]',
			'[billing_last_name]',
			'[billing_company]',
			'[billing_address_1]',
			'[billing_address_2]',
			'[billing_city]',
			'[billing_state]',
			'[billing_postcode]',
			'[billing_country]',
			'[billing_phone]',
			'[shipping_first_name]',
			'[shipping_last_name]',
			'[shipping_company]',
			'[shipping_address_1]',
			'[shipping_address_2]',
			'[shipping_city]',
			'[shipping_state]',
			'[shipping_postcode]',
			'[shipping_country]',

		);

		$replace = array(
			$estimated_time_of_arrival,
			$delivery_driver_first_name,
			$delivery_driver_last_name,
			$delivery_driver_page,
			$store_name,
			$order_id,
			$date_created,
			$order_status,
			$total,
			$currency,
			$shipping_method,
			$payment_method,
			$billing_first_name,
			$billing_last_name,
			$billing_company,
			$billing_address_1,
			$billing_address_2,
			$billing_city,
			$billing_state,
			$billing_postcode,
			$billing_country,
			$billing_phone,
			$shipping_first_name,
			$shipping_last_name,
			$shipping_company,
			$shipping_address_1,
			$shipping_address_2,
			$shipping_city,
			$shipping_state,
			$shipping_postcode,
			$shipping_country,
		);

		$content = str_replace( $find, $replace, $content );
		return $content;
	}

	function lddfw_allowed_html() {

		$allowed_tags = array(
			
			'abbr' => array(),
			'b' => array(),
			'blockquote' =>array(),
			'cite' => array(),
			'code' => array(),
			'del' => array(),
			'dd' => array(),
			'div' => array(),
			'dl' => array(),
			'dt' => array(),
			'em' => array(),
			'h1' => array(),
			'h2' => array(),
			'h3' => array(),
			'h4' => array(),
			'h5' => array(),
			'h6' => array(),
			'i' => array(),
			'img' => array(
				'alt'    => array(),
				'class'  => array(),
				'height' => array(),
				'src'    => array(),
				'width'  => array(),
			),
			'li' =>array(),
			'ol' => array(),
			'p' => array(),
			'q' =>array(),
			'span' => array(),
			'strike' => array(),
			'strong' => array(),
			'ul' => array(),
		);
		
		return $allowed_tags;
	}

?>