<?php
/**
 * Route page.
 *
 * All the route functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */

/**
 * Route page.
 *
 * All the route functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class LDDFW_Route {

	/**
	 * Google_api_key variable.
	 *
	 * @var string
	 */
	private $lddfw_google_api_key;
	/**
	 * Google_api_key variable.
	 *
	 * @var string
	 */
	private $lddfw_google_api_key_server;

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->lddfw_google_api_key        = get_option( 'lddfw_google_api_key', '' );
		$this->lddfw_google_api_key_server = get_option( 'lddfw_google_api_key_server', '' );
	}

	/**
	 * Set driver route.
	 *
	 * @param int $driver_id driver user id.
	 * @param int $route_array route array.
	 * @return int
	 */
	public function lddfw_set_route__premium_only( $driver_id, $route_array ) {
		update_user_meta( $driver_id, 'lddfw_route', $route_array );
		// Set first order origin.
		$wc_query = $this->lddfw_route_query__premium_only( $driver_id );
		while ( $wc_query->have_posts() ) {
			$wc_query->the_post();
			$orderid = get_the_ID();
			update_post_meta( $orderid, 'lddfw_order_origin', $route_array['origin_map_address'] );
			break;
		}
		return 1;
	}

	/**
	 * Delete driver route.
	 *
	 * @param int $driver_id driver user id.
	 * @return int
	 */
	public function lddfw_delete_route__premium_only( $driver_id ) {
		delete_user_meta( $driver_id, 'lddfw_route' );
		return 1;
	}


	/**
	 * Route alerts
	 *
	 * @return html
	 */
	public function lddfw_route_alerts__premium_only() {
		$html = '';
		if ( '' !== $this->lddfw_google_api_key ) {
			$plain_route_note_info = __( 'The route has been optimized by distance, if you want to make changes you can drag and drop orders manually.', 'lddfw' );
			$plain_route_note_wait = __( 'Optimize route, please wait...', 'lddfw' );
		} else {
			$plain_route_note_info = __( 'The route is ready for optimization, you can make changes by drag and drop orders manually.', 'lddfw' );
			$plain_route_note_wait = __( 'Please wait...', 'lddfw' );
		}

		$html .= '
			<div class="lddfw_plain_route_wrap">
					<div class="row" id="lddfw_plain_route_row">
					<div class="col-9">
					<a id="lddfw_plainroute_btn" data_start =\'<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="edit" class="svg-inline--fa fa-edit fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M402.6 83.2l90.2 90.2c3.8 3.8 3.8 10 0 13.8L274.4 405.6l-92.8 10.3c-12.4 1.4-22.9-9.1-21.5-21.5l10.3-92.8L388.8 83.2c3.8-3.8 10-3.8 13.8 0zm162-22.9l-48.8-48.8c-15.2-15.2-39.9-15.2-55.2 0l-35.4 35.4c-3.8 3.8-3.8 10 0 13.8l90.2 90.2c3.8 3.8 10 3.8 13.8 0l35.4-35.4c15.2-15.3 15.2-40 0-55.2zM384 346.2V448H64V128h229.8c3.2 0 6.2-1.3 8.5-3.5l40-40c7.6-7.6 2.2-20.5-8.5-20.5H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V306.2c0-10.7-12.9-16-20.5-8.5l-40 40c-2.2 2.3-3.5 5.3-3.5 8.5z"></path></svg> ' . esc_attr( __( 'Plan your route', 'lddfw' ) ) . '\' data_finish =\'<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="lock" class="svg-inline--fa fa-lock fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M400 224h-24v-72C376 68.2 307.8 0 224 0S72 68.2 72 152v72H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V272c0-26.5-21.5-48-48-48zm-104 0H152v-72c0-39.7 32.3-72 72-72s72 32.3 72 72v72z"></path></svg> ' . esc_attr( __( 'Finish planning route', 'lddfw' ) ) . '\' class=" btn btn-secondary  btn-block" href="#">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="route" class="svg-inline--fa fa-route fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M416 320h-96c-17.6 0-32-14.4-32-32s14.4-32 32-32h96s96-107 96-160-43-96-96-96-96 43-96 96c0 25.5 22.2 63.4 45.3 96H320c-52.9 0-96 43.1-96 96s43.1 96 96 96h96c17.6 0 32 14.4 32 32s-14.4 32-32 32H185.5c-16 24.8-33.8 47.7-47.3 64H416c52.9 0 96-43.1 96-96s-43.1-96-96-96zm0-256c17.7 0 32 14.3 32 32s-14.3 32-32 32-32-14.3-32-32 14.3-32 32-32zM96 256c-53 0-96 43-96 96s96 160 96 160 96-107 96-160-43-96-96-96zm0 128c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32z"></path></svg>
					 ' . esc_html( __( 'Optimize route', 'lddfw' ) ) . '
					</a>
					<button style="display:none" class="lddfw_loading_btn btn btn-primary btn-block" type="button" disabled>
						<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
					</button>
					<button style="display:none" class="lddfw_done_btn btn btn-primary btn-block" type="button" disabled>
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check" class="svg-inline--fa fa-check fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"></path></svg>
					' . esc_html( __( 'Done', 'lddfw' ) ) . '
					</button>
					</div>
					<div class="col-3">
					<button id="lddfw_sortroute_btn" class=" btn btn-secondary btn-block" data-finish=\'<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="times" class="svg-inline--fa fa-times fa-w-11" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512"><path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path></svg>\' data-start=\'<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sort" class="svg-inline--fa fa-sort fa-w-10" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M41 288h238c21.4 0 32.1 25.9 17 41L177 448c-9.4 9.4-24.6 9.4-33.9 0L24 329c-15.1-15.1-4.4-41 17-41zm255-105L177 64c-9.4-9.4-24.6-9.4-33.9 0L24 183c-15.1 15.1-4.4 41 17 41h238c21.4 0 32.1-25.9 17-41z"></path></svg>\'>
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sort" class="svg-inline--fa fa-sort fa-w-10" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M41 288h238c21.4 0 32.1 25.9 17 41L177 448c-9.4 9.4-24.6 9.4-33.9 0L24 329c-15.1-15.1-4.4-41 17-41zm255-105L177 64c-9.4-9.4-24.6-9.4-33.9 0L24 183c-15.1 15.1-4.4 41 17 41h238c21.4 0 32.1-25.9 17-41z"></path></svg>
					</button>
					</div>
					</div>
					<div id="lddfw_plain_route_note_wait" style="display:none;margin-top:17px">
						<div class="alert alert-primary">' . $plain_route_note_wait . '</div>
					</div>
					<div id="lddfw_plain_route_note_info" style="display:none;margin-top:17px">
						<div class="alert alert-primary" id="lddfw_plain_route_note_alert">' . $plain_route_note_info . ' <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a></div>
					</div>
			</div>';
		return $html;
	}

	/**
	 * All Routes query.
	 *
	 * @since 1.4.0
	 * @return object
	 */
	public function lddfw_all_routes_query__premium_only() {

		$array = array(
			'driver_clause' => array(
				'key'     => 'lddfw_driverid',
				'compare' => 'EXISTS',
			),
		);

		$sort_array = array(
			'driver_clause'            => 'ASC',
			'sort_meta_not_exist'      => 'ASC',
			'sort_city_meta_not_exist' => 'ASC',
		);

		$relation_array = array(
			'relation' => 'or',
			array(
				'sort_city_meta_not_exist' => array(
					'key'     => '_shipping_city',
					'compare' => 'NOT EXISTS',
				),
			),
			array(
				'sort_city_meta_exist' => array(
					'key'     => '_shipping_city',
					'compare' => 'EXISTS',
				),
			),
			array(
				'sort_meta_exist' => array(
					'key'     => 'lddfw_order_sort',
					'compare' => 'EXISTS',
					'type'    => 'NUMERIC',
				),
			),
			array(
				'sort_meta_not_exist' => array(
					'key'     => 'lddfw_order_sort',
					'compare' => 'NOT EXISTS',
					'type'    => 'NUMERIC',
				),
			),
		);

		$params = array(
			'posts_per_page' => -1,
			'post_status'    => get_option( 'lddfw_out_for_delivery_status', '' ),
			'post_type'      => 'shop_order',
			'meta_query'     => array(
				'relation' => 'AND',
				$relation_array,
				$array,
			),
			'orderby'        => $sort_array,
		);
		return new WP_Query( $params );
	}

	/**
	 * Route query.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return object
	 */
	public function lddfw_route_query__premium_only( $driver_id ) {

		$array = array(
			'key'     => 'lddfw_driverid',
			'value'   => $driver_id,
			'compare' => '=',
		);

		$params = array(
			'posts_per_page' => -1,
			'post_status'    => get_option( 'lddfw_out_for_delivery_status', '' ),
			'post_type'      => 'shop_order',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'relation' => 'or',
					array(
						'sort_city_meta_not_exist' => array(
							'key'     => '_shipping_city',
							'compare' => 'NOT EXISTS',
						),
					),
					array(
						'sort_city_meta_exist' => array(
							'key'     => '_shipping_city',
							'compare' => 'EXISTS',
						),
					),
					array(
						'sort_meta_exist' => array(
							'key'     => 'lddfw_order_sort',
							'compare' => 'EXISTS',
							'type'    => 'NUMERIC',
						),
					),
					array(
						'sort_meta_not_exist' => array(
							'key'     => 'lddfw_order_sort',
							'compare' => 'NOT EXISTS',
							'type'    => 'NUMERIC',
						),
					),
				),
				$array,
			),
			'orderby'        => array(
				'sort_meta_not_exist'      => 'ASC',
				'sort_city_meta_not_exist' => 'ASC',
			),
		);

		return new WP_Query( $params );
	}

	/**
	 * Next delivery.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return json
	 */
	public function lddfw_get_next_delivery__premium_only( $driver_id ) {
		$wc_query        = $this->lddfw_route_query__premium_only( $driver_id );
		$orderid         = 0;
		$shipping_address = __( 'Your delivery route is completed', 'lddfw' );
		if ( $wc_query->have_posts() ) {
			$lddfw_order = new LDDFW_Order();
			while ( $wc_query->have_posts() ) {
				$wc_query->the_post();
				$orderid = get_the_ID();
				$order   = wc_get_order( $orderid );
				// Get and fromat shipping address.
				$shipping_array   = $lddfw_order->lddfw_order_address( 'shipping', $order, $orderid );
				$shipping_address = lddfw_format_address( 'address_line', $shipping_array );
				$shipping_address = filter_var( $shipping_address, FILTER_SANITIZE_SPECIAL_CHARS );
				$shipping_address = __( 'Your next delivery', 'lddfw' ) . ':<br><b>' . $shipping_address . "</b><br><a class='btn btn-block btn-lg btn-primary' href='" . lddfw_drivers_page_url( 'lddfw_screen=order&lddfw_orderid=' . $orderid ) . "'>" . esc_html( __( 'Drive to the next delivery', 'lddfw' ) ) . '</a>';
				break;
			}
		} else {
			// Delete driver route.
			$this->lddfw_delete_route__premium_only( $driver_id );
		}
		return "{\"order_id\":\"$orderid\",\"shipping_address\":\"$shipping_address\"}";
	}





	/**
	 * Set order distance.
	 *
	 * @since 1.0.0
	 * @param object $order order object.
	 * @param int    $order_id order id.
	 * @param int    $driver_id driver user id.
	 * @param int    $origin_distance origin distance.
	 * @return json
	 */
	public function lddfw_distancematrix( $order, $order_id, $driver_id, $origin_distance ) {
		$result         = 0;
		$error          = '';
		$distance_array = array();

		$lddfw_google_api_key = $this->lddfw_google_api_key_server;
		if ( '' === $lddfw_google_api_key ) {
			$lddfw_google_api_key = $this->lddfw_google_api_key;
		}

		if ( '' !== $lddfw_google_api_key ) {
			$lddfw_order = new LDDFW_Order();
			$store       = new LDDFW_Store();
			$unit_system = $store->lddfw_country_unit_system__premium_only();
			$seller_id   = $store->lddfw_order_seller( $order );
			$origin      = rawurlencode( $store->lddfw_pickup_address( 'map_address', $order, $seller_id ) );

			if ( false === $origin_distance ) {
				$order_origin = get_post_meta( $order_id, 'lddfw_order_origin', true );
				$origin       = ( '' !== $order_origin ) ? $order_origin : $origin;
			}

			// Get and fromat shipping address.
			$shipping_array       = $lddfw_order->lddfw_order_address( 'shipping', $order, $order_id );
			$shipping_map_address = lddfw_format_address( 'map_address', $shipping_array );
			// Set address by coordinates.
			$coordinates = $lddfw_order->lddfw_order_shipping_address_coordinates( $order );
			if ( '' !== $coordinates ) {
				$shipping_map_address = $coordinates;
			}
			$destination          = rawurlencode( $shipping_map_address );

			if ( '' !== $destination ) {
				$travel_mode = LDDFW_Driver::get_driver_driving_mode( $driver_id, 'lowercase' );

				// Get distancematrix service.
				$url      = 'https://maps.googleapis.com/maps/api/distancematrix/json?mode=' . $travel_mode . '&origins=' . $origin . '&destinations=' . $destination . '&key=' . $lddfw_google_api_key . '&units=' . $unit_system;
				$response = wp_remote_get( $url );

				if ( is_wp_error( $response ) ) {
					$error = __( 'An unexpected error has occurred.', 'lddfw' );
				} else {
					$body = wp_remote_retrieve_body( $response );
					$obj  = json_decode( $body );
				}

				// Get order route index.
				$route       = get_post_meta( $order_id, 'lddfw_order_route', true );
				$route_index = 0;
				if ( ! empty( $route ) ) {
					if ( ! empty( $route['index'] ) ) {
						$route_index = $route['index'];
					}
				}

				if ( 'OK' === $obj->status ) {

					// Get distance and duration.
					$distance_text  = ( ! empty( $obj->rows[0]->elements[0]->distance->text ) ) ? $obj->rows[0]->elements[0]->distance->text : '';
					$distance_value = ( ! empty( $obj->rows[0]->elements[0]->distance->value ) ) ? $obj->rows[0]->elements[0]->distance->value : '';
					$duration_text  = ( ! empty( $obj->rows[0]->elements[0]->duration->text ) ) ? $obj->rows[0]->elements[0]->duration->text : '';
					$duration_value = ( ! empty( $obj->rows[0]->elements[0]->duration->value ) ) ? $obj->rows[0]->elements[0]->duration->value : '';

					// Set order distance and duration.
					$distance_array = array(
						'index'          => $route_index,
						'distance_text'  => $distance_text,
						'distance_value' => $distance_value,
						'duration_text'  => $duration_text,
						'duration_value' => $duration_value,
						'date_created'   => date_i18n( 'Y-m-d H:i:s' ),
					);
					if ( false === $origin_distance ) {
						update_post_meta( $order_id, 'lddfw_order_route', $distance_array );
					} else {
						update_post_meta( $order_id, '_lddfw_origin_distance', $distance_array );
					}
					$result = 1;
				} else {
					$error = __( 'Distance service returned an error status.', 'lddfw' );
					if ( ! empty( $obj->error_message ) ) {
						$order->add_order_note( $error . ' ' . esc_html( $obj->error_message ) );
					}
				}
			}
		} else {
			$error = __( 'Google API key not exists.', 'lddfw' );
		}
		return array( $result, $error, $distance_array );
	}

	/**
	 * Start delivery.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @param int $order_id order number.
	 * @return json
	 */
	public function lddfw_start_delivery__premium_only( $driver_id, $order_id ) {
			$result                  = 0;
			$error                   = '';
			$duration_text           = '';
			$duration_value          = '';
			$distance_text           = '';
			$distance_value          = '';
			$user                    = wp_get_current_user();
			$order                   = wc_get_order( $order_id );
			$order_driverid          = $order->get_meta( 'lddfw_driverid' );
			$out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
			$current_order_status    = 'wc-' . $order->get_status();

			// Check if order belongs to driver and status is processing.
		if ( intval( $order_driverid ) === intval( $driver_id ) && $current_order_status === $out_for_delivery_status ) {

			$start_delivery_date = date_i18n( 'Y-m-d H:i:s' );

			// Update start delivery date and time.
			update_post_meta( $order_id, '_lddfw_order_delivery_start', $start_delivery_date );

			// Add note to order.
			/* translators: %1$s: driver name. %2$s: delivery date.*/
			$driver_note = sprintf( __( 'The driver %1$s started delivery on %2$s', 'lddfw' ), esc_html( $user->display_name ), esc_html( $start_delivery_date ) );
			$order->add_order_note( $driver_note );

			// Get estimate arrival time.
			$result_array = $this->lddfw_distancematrix( $order, $order_id, $driver_id, false );

			if ( ! empty( $result_array ) ) {
				$distance_array = $result_array[2];
				if ( ! empty( $distance_array ) ) {
					$duration_text  = $distance_array['duration_text'];
					$duration_value = $distance_array['duration_value'];
					$distance_text  = $distance_array['distance_text'];
					$distance_value = $distance_array['distance_value'];
				}
			}

			// Send email to customer.
			WC_Emails::instance();
			do_action( 'lddfw_start_delivery_email_notification', $order_id );

			// Send whatsapp to customer.
			$lddfw_whatsapp_start_delivery = get_option( 'lddfw_whatsapp_start_delivery', '' );
			if ( '1' === $lddfw_whatsapp_start_delivery ) {
				// Send whatsapp to cusomer.
				$whatsapp = new LDDFW_WHATSAPP();
				$result = $whatsapp->lddfw_send_whatsapp_to_customer__premium_only( $order_id, $order, 'start_delivery' );
				$order->add_order_note( $result[1] );
			}

			// Send SMS to customer.
			$lddfw_sms_start_delivery = get_option( 'lddfw_sms_start_delivery', '' );
			if ( '1' === $lddfw_sms_start_delivery ) {
				// Send sms to cusomer.
				$sms    = new LDDFW_SMS();
				$result = $sms->lddfw_send_sms_to_customer__premium_only( $order_id, $order, 'start_delivery' );
				$order->add_order_note( $result[1] );
			}

			$result = 1;
		}
			return "{\"result\":\"$result\",\"error\":\"$error\",\"duration_text\":\"$duration_text\",\"duration_value\":\"$duration_value\",\"distance_value\":\"$distance_value\",\"distance_text\":\"$distance_text\"}";
	}


	/**
	 * Sort delivery.
	 *
	 * @since 1.0.0
	 * @param int   $driver_id driver user id.
	 * @param array $origin origin.
	 * @return json
	 */
	public function lddfw_sort_delivery__premium_only( $driver_id, $origin ) {
		$result = 0;
		if ( isset( $_POST['lddfw_wpnonce'] ) ) {
			 
				$orders_list = ( isset( $_POST['lddfw_orders_list'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_orders_list'] ) ) : '';
				if ( '' !== $orders_list ) {
					$orders_list_array = explode( ',', $orders_list );
					$counter           = 1;
					foreach ( $orders_list_array  as $order ) {
						if ( '' !== $order ) {
							lddfw_update_post_meta( $order, 'lddfw_order_sort', $counter );
							++$counter;
						}
					}
					$result = 1;
				}
			 
		}
		$this->lddfw_set_delivery_origin__premium_only( $driver_id, $origin );
		return $result;
	}

	/**
	 * Set delivery origin.
	 *
	 * @since 1.0.0
	 * @param int   $driver_id driver user id.
	 * @param array $origin origin.
	 */
	public function lddfw_set_delivery_origin__premium_only( $driver_id, $origin ) {
		$wc_query    = $this->lddfw_route_query__premium_only( $driver_id );
		$store       = new LDDFW_Store();
		$lddfw_order = new LDDFW_Order();
		while ( $wc_query->have_posts() ) {
			$wc_query->the_post();
			$orderid   = get_the_ID();
			$order     = wc_get_order( $orderid );
			$seller_id = $store->lddfw_order_seller( $order );

			if ( '' === $origin ) {
				$origin = $store->lddfw_pickup_address( 'map_address', $order, $seller_id );
			}

			// Get and fromat shipping address.
			$shipping_array       = $lddfw_order->lddfw_order_address( 'shipping', $order, $orderid );
			$shipping_map_address = lddfw_format_address( 'map_address', $shipping_array );

			// Set address by coordinates.
			$coordinates = $lddfw_order->lddfw_order_shipping_address_coordinates( $order );
			if ( '' !== $coordinates ) {
				$shipping_map_address = $coordinates;
			}

			// set delivery origin.
			update_post_meta( $orderid, 'lddfw_order_origin', $origin );
			$origin = $shipping_map_address;
		}
	}
	/**
	 * Plain route.
	 *
	 * @since 1.0.0
	 * @param int   $driver_id driver user id.
	 * @param array $origin origin.
	 * @param array $destination destination.
	 * @return json
	 */
	public function lddfw_plain_route__premium_only( $driver_id, $origin, $destination ) {

		$alert = '';
		// Get google API key.
		$lddfw_google_api_key = $this->lddfw_google_api_key_server;
		if ( '' === $lddfw_google_api_key ) {
			$lddfw_google_api_key = $this->lddfw_google_api_key;
		}

		if ( '' !== $lddfw_google_api_key ) {

			$store       = new LDDFW_Store();
			$lddfw_order = new LDDFW_Order();
			$unit_system = $store->lddfw_country_unit_system__premium_only();

			$store_address     = '';
			$wc_query          = $this->lddfw_route_query__premium_only( $driver_id );
			$order_destination = '';
			$counter           = 0;
			$orders_array      = array();
			$travel_mode       = LDDFW_Driver::get_driver_driving_mode( $driver_id, 'lowercase' );

			while ( $wc_query->have_posts() ) {

				$wc_query->the_post();
				$orderid = get_the_ID();
				$order   = wc_get_order( $orderid );

				// Get start pick-up address.
				$seller_id = $store->lddfw_order_seller( $order );
				if ( '' === $store_address ) {
					$store_address = $store->lddfw_pickup_address( 'map_address', $order, $seller_id );
				}

				// Get and fromat shipping address.
				$shipping_array       = $lddfw_order->lddfw_order_address( 'shipping', $order, $orderid );
				$shipping_map_address = lddfw_format_address( 'map_address', $shipping_array );

				// Set address by coordinates.
				$coordinates = $lddfw_order->lddfw_order_shipping_address_coordinates( $order );
				if ( '' !== $coordinates ) {
					$shipping_map_address = $coordinates;
				}

				if ( $counter > 0 ) {
					$order_destination .= '|';
				}
				$order_destination .= rawurlencode( $shipping_map_address );
				$orders_array[]     = array(
					'orderid'          => $orderid,
					'shipping_address' => $shipping_map_address,
				);
				$counter++;
			}

			// Set origin.
			$origin        = ( '' !== $origin ) ? $origin : $store_address;
			$origin_encode = rawurlencode( $origin );

			// Get farest address from origin.
			if ( '' !== $order_destination ) {

				$url      = 'https://maps.googleapis.com/maps/api/distancematrix/json?mode=' . $travel_mode . '&origins=' . $origin_encode . '&destinations=' . $order_destination . '&key=' . $lddfw_google_api_key . '&units=' . $unit_system;
				$response = wp_remote_get( $url );

				if ( is_wp_error( $response ) ) {
					return '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . __( 'An unexpected error has occurred, please try again.', 'lddfw' ) . '</div>';
				} else {
					$body = wp_remote_retrieve_body( $response );
					$obj  = json_decode( $body );
				}

				$farthest_distance = 0;
				$farthest_index    = 0;
				$counter           = 0;
				$array             = array();

				if ( 'OK' === $obj->status ) {

					$row = $obj->rows[0];
					foreach ( $row->elements  as $element ) {

						$distance_text  = '';
						$distance_value = '';
						$duration_text  = '';
						$duration_value = '';

						if ( 'ZERO_RESULTS' === $element->status ) {
							if ( ! empty( $orders_array[ $counter ]['orderid'] ) ) {
								$alert .= '<div>' . __( 'Order #', 'lddfw' ) . $orders_array[ $counter ]['orderid'] . ': ' . __( 'no route could be found between the origin and address.', 'lddfw' ) . '</div>';
							}
						}

						if ( 'NOT_FOUND' === $element->status ) {
							if ( ! empty( $orders_array[ $counter ]['orderid'] ) ) {
								$alert .= '<div>' . __( 'Order #', 'lddfw' ) . $orders_array[ $counter ]['orderid'] . ': ' . __( 'origin and/or address could not be geocoded.', 'lddfw' ) . '</div>';
							}
						}

						if ( 'OK' === $element->status ) {

							$distance_text  = ( ! empty( $element->distance->text ) ) ? $element->distance->text : '';
							$distance_value = ( ! empty( $element->distance->value ) ) ? $element->distance->value : '';
							$duration_text  = ( ! empty( $element->duration->text ) ) ? $element->duration->text : '';
							$duration_value = ( ! empty( $element->duration->value ) ) ? $element->duration->value : '';

							// Get farthest order.
							if ( $distance_value > $farthest_distance ) {
								$farthest_distance = $distance_value;
								$farthest_index    = $counter;
							}
						}

						// Set array of the results.
						$array[] = array(
							'index'          => $counter,
							'distance_text'  => $distance_text,
							'distance_value' => $distance_value,
							'duration_text'  => $duration_text,
							'duration_value' => $duration_value,
							'date_created'   => date_i18n( 'Y-m-d H:i:s' ),
						);
						$counter++;
					}

					/**
					 * Sort route by distance.
					 *
					 * @param array $a distance_value.
					 * @param array $b distance_value.
					 * @return html
					 */
					function sort_count( $a, $b ) {
						if ( $a['distance_value'] === $b['distance_value'] ) {
							return 0;
						} else {
							return ( $a['distance_value'] > $b['distance_value'] ? 1 : -1 );
						}
					}
					$sorted_array = uasort( $array, 'sort_count' );
					$counter      = 0;

					// Save route info in each order.
					foreach ( $array as $value ) {
						$order_count = 0;
						foreach ( $orders_array as $order ) {
							if ( $order_count === $value['index'] ) {
								update_post_meta( $order['orderid'], 'lddfw_order_route', $value );
								update_post_meta( $order['orderid'], '_lddfw_origin_distance', $value );
								break;
							}
							$order_count++;
						}
						$counter++;
					}

					// Set waypoint and destination.
					$waypoint             = '';
					$counter              = 0;
					$destination_order_id = 0;

					if ( '' === $destination || 'last_address_on_route' === $destination ) {
						// Get last order address as destination.
						$destination_index    = end( $array )['index'];
						$destination          = $orders_array[ $destination_index ]['shipping_address'];
						$destination_order_id = $orders_array[ $destination_index ]['orderid'];
					}
					$destination = rawurlencode( $destination );

					$waypoint_array = array();
					foreach ( $orders_array as $order ) {
						if ( $order['orderid'] !== $destination_order_id ) {
							if ( $counter > 0 ) {
								$waypoint .= '|';
							}
							$waypoint_array[] = array(
								'orderid' => $order['orderid'],
							);
							$waypoint        .= rawurlencode( $order['shipping_address'] );
							$counter++;
						}
					}

					// Save orders sort index by direction waypoints.
					$url      = 'https://maps.googleapis.com/maps/api/directions/json?mode=' . $travel_mode . '&origin=' . $origin_encode . '&destination=' . $destination . '&waypoints=optimize:true|' . $waypoint . '&key=' . $lddfw_google_api_key;
					$response = wp_remote_get( $url );
					if ( is_wp_error( $response ) ) {
						return '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . __( 'An unexpected error has occurred, please try again.', 'lddfw' ) . '</div>';
					} else {
						$body = wp_remote_retrieve_body( $response );
						$obj  = json_decode( $body );
					}

					if ( 'OK' === $obj->status ) {
						$array   = $obj->routes[0]->waypoint_order;
						$counter = 0;
						foreach ( $array as $value ) {
							$waypoint_count = 0;
							foreach ( $waypoint_array as $waypoint ) {
								if ( $waypoint_count === $value ) {
									lddfw_update_post_meta( $waypoint['orderid'], 'lddfw_order_sort', $counter );
								}
								$waypoint_count++;
							}
							$counter++;
						}

						if ( $destination_order_id > 0 ) {
							// Set last order sort number if destination is order address.
							lddfw_update_post_meta( $destination_order_id, 'lddfw_order_sort', $counter );
						}
					}

					// Set delivery origin for each order.
					$this->lddfw_set_delivery_origin__premium_only( $driver_id, $origin );
				}
			}
		}

		// show route orders.
		$orders = new LDDFW_Orders();
		$alert  = ( '' !== $alert ) ? '<div class="alert alert-danger alert-dismissible fade show" role="alert"><a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>' . $alert . '</div>' : '';
		return $alert . $orders->lddfw_out_for_delivery( $driver_id );
	}
	/**
	 * Route script.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function lddfw_route_script__premium_only() {
		global $lddfw_driver_id;
		$html = '';
		if ( '' !== $this->lddfw_google_api_key ) {
			$store                    = new LDDFW_Store();
			$store_address            = $store->lddfw_store_address( 'map_address' );
			$lddfw_driver_travel_mode = LDDFW_Driver::get_driver_driving_mode( $lddfw_driver_id, '' );

			$html .= '
			<script>
				var lddfw_optimizeWaypoints_flag = false;
				var lddfw_google_api_key         =  "' . $this->lddfw_google_api_key . '";
				var lddfw_google_api_origin 	 =  "' . esc_attr( $store_address ) . '";
				var lddfw_driver_travel_mode 	 =  "' . esc_attr( $lddfw_driver_travel_mode ) . '";
			</script>
			';
		}
		return $html;
	}

	/**
	 * Route button.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function lddfw_route_button__premium_only() {
		$html = '';
		if ( '' !== $this->lddfw_google_api_key ) {
			$html .= '
			<div class="lddfw_footer_buttons">
				<div class="container">
					<div class="row">
						<div class="col-12"><a href="#" id="lddfw_route_btn" class="btn btn-lg btn-block btn-success">
						<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marked-alt" class="svg-inline--fa fa-map-marked-alt fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M288 0c-69.59 0-126 56.41-126 126 0 56.26 82.35 158.8 113.9 196.02 6.39 7.54 17.82 7.54 24.2 0C331.65 284.8 414 182.26 414 126 414 56.41 357.59 0 288 0zm0 168c-23.2 0-42-18.8-42-42s18.8-42 42-42 42 18.8 42 42-18.8 42-42 42zM20.12 215.95A32.006 32.006 0 0 0 0 245.66v250.32c0 11.32 11.43 19.06 21.94 14.86L160 448V214.92c-8.84-15.98-16.07-31.54-21.25-46.42L20.12 215.95zM288 359.67c-14.07 0-27.38-6.18-36.51-16.96-19.66-23.2-40.57-49.62-59.49-76.72v182l192 64V266c-18.92 27.09-39.82 53.52-59.49 76.72-9.13 10.77-22.44 16.95-36.51 16.95zm266.06-198.51L416 224v288l139.88-55.95A31.996 31.996 0 0 0 576 426.34V176.02c0-11.32-11.43-19.06-21.94-14.86z"></path></svg></i> ' . esc_html( __( 'View Route', 'lddfw' ) ) . '</a></div>
					</div>
				</div>
			</div>';
		}
		return $html;
	}
	/**
	 * Route screen.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function lddfw_route_screen__premium_only() {
		$html = '';
		if ( '' !== $this->lddfw_google_api_key ) {
			$html .= '<div id="lddfw_directions" class="lddfw_lightbox" style="display:none">
					<div class="lddfw_lightbox_wrap">
						<div id="lddfw_hide_map" class="container">
						<div class="row">
						<div class="col-2">
						<a href="#" id="lddfw_hide_map_btn">
						<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="arrow-left" class="svg-inline--fa fa-arrow-left fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M257.5 445.1l-22.2 22.2c-9.4 9.4-24.6 9.4-33.9 0L7 273c-9.4-9.4-9.4-24.6 0-33.9L201.4 44.7c9.4-9.4 24.6-9.4 33.9 0l22.2 22.2c9.5 9.5 9.3 25-.4 34.3L136.6 216H424c13.3 0 24 10.7 24 24v32c0 13.3-10.7 24-24 24H136.6l120.5 114.8c9.8 9.3 10 24.8.4 34.3z"></path></svg></a>
						</div>
						<div class="col-8 text-center lddfw_header_title">
						 ' . esc_html( __( 'Route', 'lddfw' ) ) . '
						 </div>
						 <div class="col-2"></div>
						</div>
						</div>
						</div>
						<div id="lddfw_map123" class="lddfw_map-main-outer"></div>
								<div id="lddfw_directions-panel-listing" class="container"></div>
					</div>
		  		</div>';
		}
		return $html;
	}




	/**
	 * Drivers routes
	 *
	 * @param int $seller_id seller user id.
	 * @since 1.4.0
	 * @return json
	 */
	public function lddfw_drivers_routes__premium_only( $seller_id ) {

		$wc_query = $this->lddfw_all_routes_query__premium_only();

		$route_array         = '';
		$store               = new LDDFW_Store();
		$lddfw_order = new LDDFW_Order();
		$store_address       = $store->lddfw_store_address( 'map_address' );
		$drivers_counter     = 0;
		$last_lddfw_driverid = 0;

		/**
		 * Random color part.
		 *
		 * @since 1.7.4
		 * @return string
		 */
		function lddfw_random_color_part() {
			return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT );
		}

		/**
		 * Random color.
		 *
		 * @since 1.7.4
		 * @return string
		 */
		function lddfw_random_color() {
			return lddfw_random_color_part() . lddfw_random_color_part() . lddfw_random_color_part();
		}

		$drivers_colors = array(
			'#86b8ff',
			'#008000',
			'#800080',
			'#000080',
			'#FF00FF',
			'#cb4b4b',
			'#3377aa',
			'#9440ed',
			'#800000',
			'#bd9b33',
			'#808080',
			'#808000',
		);

		if ( $wc_query->have_posts() ) {
				$route_array = '{ "data": [{"route": [';
			while ( $wc_query->have_posts() ) {

				$wc_query->the_post();
				$orderid          = get_the_ID();
				$order            = wc_get_order( $orderid );
				$order_seller_id  = $store->lddfw_order_seller( $order );
				$lddfw_driverid   = $order->get_meta( 'lddfw_driverid' );
				$driver_seller_id = $store->lddfw_get_driver_seller( $lddfw_driverid );

				if ( 0 === $seller_id || ( 0 < $seller_id && $order_seller_id === $seller_id && $driver_seller_id === $seller_id ) ) {

					// Get and fromat shipping address.
					$shipping_array       = $lddfw_order->lddfw_order_address( 'shipping', $order, $orderid );
					$shipping_map_address = lddfw_format_address( 'map_address', $shipping_array );
					// Set address by coordinates.
					$coordinates = $lddfw_order->lddfw_order_shipping_address_coordinates( $order );
					if ( '' !== $coordinates ) {
						$shipping_map_address = $coordinates;
					}

					// route array.
					if ( $last_lddfw_driverid !== $lddfw_driverid ) {

						if ( $drivers_counter > 0 ) {
							$route_array  = substr( $route_array, 0, -1 );
							$route_array .= '] ,"destination": "' . esc_attr( $shipping_map_address ) . '"},';
						};
						$user			   = get_userdata( $lddfw_driverid );
						$lddfw_driver_name = ( ! empty ( $user ) ) ? $user->display_name : '';
						$image_id		   = get_user_meta( $lddfw_driverid, 'lddfw_driver_image', true );
						$lddfw_driver_travel_mode = LDDFW_Driver::get_driver_driving_mode( $lddfw_driverid, '' );

						$image = '';
						if ( intval( $image_id ) > 0 ) {
							$image = wp_get_attachment_image_src( $image_id, 'medium' )[0];
						}

						$origin = get_post_meta( $orderid, 'lddfw_order_origin', true );
						if ( '' === $origin ) {
							$origin = $store->lddfw_pickup_address( 'map_address', $order, $order_seller_id );
						}

						$driver_color = '#' . lddfw_random_color();
						if ( $drivers_counter < 12 ) {
							$driver_color = $drivers_colors[ $drivers_counter ];
						}
						$route_array        .= '{"driver": [{"id": "' . $lddfw_driverid . '","name": "' . esc_attr( $lddfw_driver_name ) . '","Image": "' . esc_attr( $image ) . '","color": "' . $driver_color . '", "travel_mode":"' . $lddfw_driver_travel_mode . '"}],"origin": "' . esc_attr( $origin ) . '","waypoints": [';
						++$drivers_counter;
						$last_lddfw_driverid = $lddfw_driverid;
					}
					$route_array .= '{"order": "' . $orderid . '","address": "' . esc_attr( $shipping_map_address ) . '","status": "waiting","color": "#800000"},';
					?>
					<?php
				}
			}
			if ( $drivers_counter > 0 ) {
				$route_array  = substr( $route_array, 0, -1 );
				$route_array .= '] ,"destination": "' . esc_attr( $shipping_map_address ) . '"}]}]	}';
			} else {
				$route_array = '{}';
			}
		} else {
			$route_array = '{}';
		}
		return $route_array;
	}


	/**
	 * Admin routes screen.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function lddfw_admin_routes_screen() {
		echo '<div class="wrap">
		<h1 class="wp-heading-inline">' . esc_html( __( 'Drivers Routes', 'lddfw' ) ) . '</h1>';
		echo LDDFW_Admin::lddfw_admin_plugin_bar();
		echo '<hr class="wp-header-end">';
		echo '<div id="lddfw_routes_notice" class="update notice" style="display:none"><p>' . esc_html( __( 'There are no routes for drivers.', 'lddfw' ) ) . '</p></div>';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				echo '<div id="lddfw_routes" style="display:none">
				<div id="lddfw_map123"></div>
				<div id="driver-panel"></div>
				</div>
			</div>';

				if ( '' === $this->lddfw_google_api_key ) {
					echo '<div class="error notice" ><p>' . esc_html( __( 'Google maps not showing – missing key.', 'lddfw' ) ) . '</p></div>';
				}
				?>

		<script>
			var geocoder;
			var infowindow;
			var driverMarker = [];
			var lddfw_waypts_array = [];
			var lddfw_map;
				<?php
				echo '
				var lddfw_ajax_url = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";
				var lddfw_hour_text = "' . esc_js( __( 'hour', 'lddfw' ) ) . '";
				var lddfw_hours_text = "' . esc_js( __( 'hours', 'lddfw' ) ) . '";
				var lddfw_mins_text = "' . esc_js( __( 'mins', 'lddfw' ) ) . '";
				'
				?>
		</script>

				<?php
				$route = new LDDFW_Route();
				echo $route->lddfw_route_script__premium_only();
				$lddfw_drivers_tracking_timing = get_option( 'lddfw_drivers_tracking_timing' );

				?>
				<script>
				var lddfw_json='';
				function lddfw_get_routes_json()
				{
					return jQuery.ajax({
							type: "POST",
							url: lddfw_ajax_url,
							dataType: "json",
							 data: {
								action: 'lddfw_ajax',
								lddfw_service: 'lddfw_drivers_routes',
								lddfw_wpnonce: lddfw_nonce.nonce,
								lddfw_data_type: 'json'
							},
							success:function(data){
								lddfw_json = data;
							},
							error: function(request, status, error) {
								console.log(error);
							}
						})
				}

				function lddfw_drivers()
				{
					jQuery("#driver-panel").html("");
					if( typeof lddfw_json['data'] != 'undefined' ){
						jQuery.each(lddfw_json['data'], function(i, data) {
						if(data['route'] != "") {
							<?php if ( false !== $lddfw_drivers_tracking_timing && '' !== $lddfw_drivers_tracking_timing ) { ?>
								jQuery("#driver-panel").append("<div class='lddfw_track_drivers'><button class='button button-primary' id='lddfw_track_drivers_btn' type='button'><?php echo esc_attr( __( 'Track drivers', 'lddfw' ) ); ?></buttob></div>");
							<?php } ?>

							jQuery.each(data['route'], function(i, route) {
							   var lddfw_driver_id   = route['driver'][0]['id'];
							   var lddfw_driver_name 		= route['driver'][0]['name'];
							   var lddfw_driver_image		= route['driver'][0]['Image'];
							   var lddfw_driver_color		= route['driver'][0]['color'];
								var lddfw_driver_img = '';
							   if ( lddfw_driver_image != ""){
								lddfw_driver_img = '<img src="'+lddfw_driver_image+'">';
							   } else
							   {
								lddfw_driver_img = '<img src="<?php echo esc_attr( plugins_url() . '/' . LDDFW_FOLDER . '/public/images/user.png?ver=' . LDDFW_VERSION ); ?>">';
							   }
							   //style='background-image:url("+lddfw_driver_image+")'
							   jQuery("#driver-panel").append("<div class='lddfw_driver_box active' data='"+lddfw_driver_id+"' id='driver_"+lddfw_driver_id+"'>" + lddfw_driver_img + "<div class='lddfw_driver_name'>" + lddfw_driver_name+ "</div><div class='lddfw_tracking'></div><div class='lddfw_button' title='<?php echo esc_attr( __( 'Route on map', 'lddfw' ) ); ?>'></div> <div class='lddfw_handle'></div> <div class='lddfw_line' style='background-color:"+lddfw_driver_color+";'></div> <div class='lddfw_directions-panel-listing'></div></div>");

							})
						}
					});

					}
				}

				jQuery("body").on("click", ".lddfw_driver_box .lddfw_handle", function(){
						var lddfw_driver_box = jQuery(this).parent();
						if ( lddfw_driver_box.hasClass("open") )  {
							lddfw_driver_box.removeClass("open");
						} else {
							lddfw_driver_box.addClass("open");
						}
						 return false;
					});

					jQuery("body").on("click", ".lddfw_driver_box .lddfw_button", function(){
						var lddfw_driver_box = jQuery(this).parent();
						if ( lddfw_driver_box.hasClass("active") )  {
							lddfw_driver_box.removeClass("active");
						} else {
							lddfw_driver_box.addClass("active");
						}
						lddfw_initMap();
						return false;
					});

					<?php
					// Track drivers.
					if ( false !== $lddfw_drivers_tracking_timing && '' !== $lddfw_drivers_tracking_timing ) {
						?>
						jQuery("body").on("click", "#lddfw_track_drivers_btn", function(){
							var lddfw_track_drivers_btn = jQuery(this);
							lddfw_track_drivers_btn.prop('disabled', true);
							lddfw_track_drivers_btn.html("<?php echo esc_attr( __( 'Done, please wait for next track.', 'lddfw' ) ); ?>");

							drivers_tracking();
							setInterval(function(){
								lddfw_track_drivers_btn.prop('disabled', false)
								lddfw_track_drivers_btn.html("<?php echo esc_attr( __( 'Track drivers', 'lddfw' ) ); ?>");
							}, '<?php echo $lddfw_drivers_tracking_timing; ?>' );
							return false;
						});
						<?php
					}
					?>

				function lddfw_computeTotalDistance(lddfw_driverid,result) {
					var lddfw_totalDist = 0;
					var lddfw_totalTime = 0;
					var lddfw_distance_text = '';
					var lddfw_distance_array = '';
					var lddfw_distance_type = '';

					var lddfw_myroute = result.routes[0];
					for (i = 0; i < lddfw_myroute.legs.length; i++) {
						lddfw_totalTime += lddfw_myroute.legs[i].duration.value;
						lddfw_distance_text = lddfw_myroute.legs[i].distance.text;
						lddfw_distance_array = lddfw_distance_text.split(" ");
						lddfw_totalDist += parseFloat(lddfw_distance_array[0]);
						lddfw_distance_type = lddfw_distance_array[1];
					}
					lddfw_totalTime = (lddfw_totalTime / 60).toFixed(0);
					lddfw_TotalTimeText = lddfw_timeConvert(lddfw_totalTime);

					jQuery("#driver_" + lddfw_driverid ).find(".lddfw_total_route").html( "<b>" + lddfw_TotalTimeText + "</b> <span>(" + (lddfw_totalDist).toFixed(1) + " " + lddfw_distance_type + ")</span> " );
				}
				function lddfw_timeConvert(n) {
					var lddfw_num = n;
					var lddfw_hours = (lddfw_num / 60);
					var lddfw_rhours = Math.floor(lddfw_hours);
					var lddfw_minutes = (lddfw_hours - lddfw_rhours) * 60;
					var lddfw_rminutes = Math.round(lddfw_minutes);
					var lddfw_result = '';
					if (lddfw_rhours > 1) {
						lddfw_result = lddfw_rhours + " " + lddfw_hours_text + " ";
					}
					if (lddfw_rhours == 1) {
						lddfw_result = lddfw_rhours + " " + lddfw_hour_text + " ";
					}
					if (lddfw_rminutes > 0) {
						lddfw_result += lddfw_rminutes + " " + lddfw_mins_text;
					}
					return lddfw_result;
				}

				function lddfw_numtoletter(lddfw_num) {
						var lddfw_s = '',
							lddfw_t;

						while (lddfw_num > 0) {
							lddfw_t = (lddfw_num - 1) % 26;
							lddfw_s = String.fromCharCode(65 + lddfw_t) + lddfw_s;
							lddfw_num = (lddfw_num - lddfw_t) / 26 | 0;
						}
						return lddfw_s || undefined;
					}
				function lddfw_initMap() {

				if( typeof lddfw_json['data'] != 'undefined' ){

				//Create map
				var rendererOptions = {
					draggable: false,
					suppressMarkers: true,
				};
				var lddfw_directionsService = new google.maps.DirectionsService();
				var lddfw_directionsRenderer = new google.maps.DirectionsRenderer(rendererOptions);
				var lddfw_map = new google.maps.Map(
					document.getElementById('lddfw_map123'), {
						zoom: 6,
						center: { lat: 41.85, lng: -87.65 }
					}
				);
				lddfw_directionsRenderer.setMap(lddfw_map);

				var infowindow = new google.maps.InfoWindow();
					//Set route
					jQuery.each(lddfw_json['data'], function(i, data) {

						if(data['route'] != "") {
							jQuery.each(data['route'], function(i, route) {

								var lddfw_origin 		= route['origin'];
								var lddfw_destination 	= route['destination'];
								var lddfw_waypts_array  = [] ;
								var lddfw_color 		= route['driver'][0]['color'];
								var lddfw_driverid 		= route['driver'][0]['id'];
								var lddfw_drivername 	= route['driver'][0]['name'];
								var lddfw_travel_mode 	= route['driver'][0]['travel_mode'];


								jQuery.each(route['waypoints'], function(i, waypoints) {
									if ( i + 1 < route['waypoints'].length )
									{
										lddfw_waypts_array.push( waypoints["address"] );
									}
									else
									{
										lddfw_destination = waypoints["address"];
									}
								});

								 if ( jQuery( "#driver_" + lddfw_driverid ).hasClass("active") ) {

									<?php
									if ( false !== $lddfw_drivers_tracking_timing && '' !== $lddfw_drivers_tracking_timing ) {
										?>
										// Add drivers icon
										var icon = {
											path: "M751.25,280C751.25,130.156,629.844,8.75,480,8.75S208.75,130.156,208.75,280c0,149.844,121.406,271.25,271.25,271.25 S751.25,429.844,751.25,280z M261.25,280c0-120.859,97.891-218.75,218.75-218.75c120.859,0,218.75,97.891,218.75,218.75 c0,120.858-97.891,218.75-218.75,218.75C359.141,498.75,261.25,400.86,261.25,280z" + "M516.563,206.875c12.115,0,21.938-9.822,21.938-21.938S528.678,163,516.563,163c-12.114,0-21.938,9.821-21.938,21.938 S504.447,206.875,516.563,206.875z M514.734,262.176c2.593,2.077,5.816,3.205,9.141,3.199h29.25c8.077,0,14.625-6.548,14.625-14.625 s-6.548-14.625-14.625-14.625h-24.122l-32.55-26.051c-5.48-4.401-13.32-4.271-18.651,0.311l-51.188,43.875 c-6.132,5.258-6.841,14.49-1.583,20.622c0.871,1.016,1.877,1.908,2.991,2.65l37.353,24.916v50.677 c0,8.075,6.547,14.625,14.625,14.625c8.077,0,14.625-6.55,14.625-14.625v-58.5c-0.001-4.891-2.445-9.455-6.513-12.166 l-18.903-12.602l26.623-22.813L514.734,262.176z M567.75,280c-32.309,0-58.5,26.19-58.5,58.5c0,32.309,26.191,58.5,58.5,58.5 s58.5-26.191,58.5-58.5C626.25,306.191,600.059,280,567.75,280z M567.75,367.75c-16.155,0-29.25-13.097-29.25-29.25 c0-16.155,13.095-29.25,29.25-29.25S597,322.345,597,338.5C597,354.655,583.905,367.75,567.75,367.75z M392.25,280 c-32.309,0-58.5,26.19-58.5,58.5c0,32.309,26.191,58.5,58.5,58.5c32.309,0,58.5-26.191,58.5-58.5 C450.75,306.191,424.559,280,392.25,280z M392.25,367.75c-16.154,0-29.25-13.097-29.25-29.25c0-16.155,13.096-29.25,29.25-29.25 s29.25,13.095,29.25,29.25C421.5,354.655,408.404,367.75,392.25,367.75z" ,
											fillColor: lddfw_color ,
											fillOpacity: 1,
											//strokeColor: '#000',
											//strokeWeight: 2,
											anchor: new google.maps.Point(0,0),
											scale: 0.04
										}

										if ( lddfw_travel_mode == 'bicycling' ){
											// bicycle / bike
											  var icon = 'data:image/svg+xml,<svg focusable="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" x="0px" y="0px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xml:space="preserve"> <path fill="'+  encodeURIComponent( lddfw_color ) +'" d="M751.25,280C751.25,130.156,629.844,8.75,480,8.75S208.75,130.156,208.75,280c0,149.844,121.406,271.25,271.25,271.25 S751.25,429.844,751.25,280z M261.25,280c0-120.859,97.891-218.75,218.75-218.75c120.859,0,218.75,97.891,218.75,218.75 c0,120.858-97.891,218.75-218.75,218.75C359.141,498.75,261.25,400.86,261.25,280z"/><path d="M506.976,189.965c14.908,0,27.005-12.096,27.005-27.005c0-14.908-12.097-27.004-27.004-27.004  c-14.909,0-27.005,12.096-27.005,27.004C479.971,177.869,492.066,189.965,506.976,189.965z M560.141,273.847l-13.108-6.639  l-5.456-16.54c-8.271-25.092-31.337-42.646-57.497-42.7c-20.254-0.057-31.449,5.682-52.489,14.177  c-12.153,4.896-22.11,14.178-27.961,25.992l-3.77,7.65c-4.388,8.89-0.844,19.69,7.989,24.136c8.776,4.442,19.466,0.844,23.911-8.045  l3.77-7.651c1.97-3.938,5.231-7.032,9.282-8.664l15.078-6.076l-8.552,34.149c-2.925,11.701,0.226,24.135,8.382,33.08l33.7,36.793  c4.05,4.443,6.92,9.789,8.383,15.584l10.295,41.237c2.42,9.62,12.209,15.527,21.829,13.108s15.527-12.209,13.107-21.829  l-12.489-50.069c-1.462-5.796-4.331-11.195-8.383-15.585l-25.599-27.961l9.678-38.648l3.095,9.282  c2.981,9.058,9.396,16.541,17.834,20.815l13.107,6.638c8.775,4.444,19.466,0.845,23.909-8.045  C572.518,289.205,568.973,278.291,560.141,273.847L560.141,273.847z M431.363,353.003c-1.8,4.557-4.501,8.663-7.989,12.096  l-28.129,28.187c-7.033,7.032-7.033,18.452,0,25.484c7.032,7.032,18.396,7.032,25.429,0l33.417-33.417  c3.433-3.434,6.133-7.539,7.99-12.096l7.595-19.018c-31.111-33.924-21.772-23.516-26.667-30.211L431.363,353.003z"/> </svg>';
										}
										else if ( lddfw_travel_mode == 'walking' ){
											 // walking.
											 var icon = 'data:image/svg+xml,<svg focusable="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" x="0px" y="0px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xml:space="preserve"> <path fill="'+  encodeURIComponent( lddfw_color ) +'" d="M751.25,280C751.25,130.156,629.844,8.75,480,8.75S208.75,130.156,208.75,280c0,149.844,121.406,271.25,271.25,271.25 S751.25,429.844,751.25,280z M261.25,280c0-120.859,97.891-218.75,218.75-218.75c120.859,0,218.75,97.891,218.75,218.75 c0,120.858-97.891,218.75-218.75,218.75C359.141,498.75,261.25,400.86,261.25,280z"/><path d="M506.976,189.965c14.908,0,27.005-12.096,27.005-27.005c0-14.908-12.097-27.004-27.004-27.004 c-14.909,0-27.005,12.096-27.005,27.004C479.971,177.869,492.066,189.965,506.976,189.965z M560.141,273.847l-13.108-6.639 l-5.456-16.54c-8.271-25.092-31.337-42.646-57.497-42.7c-20.254-0.057-31.449,5.682-52.489,14.177 c-12.153,4.896-22.11,14.178-27.961,25.992l-3.77,7.65c-4.388,8.89-0.844,19.69,7.989,24.136c8.776,4.442,19.466,0.844,23.911-8.045 l3.77-7.651c1.97-3.938,5.231-7.032,9.282-8.664l15.078-6.076l-8.552,34.149c-2.925,11.701,0.226,24.135,8.382,33.08l33.7,36.793 c4.05,4.443,6.92,9.789,8.383,15.584l10.295,41.237c2.42,9.62,12.209,15.527,21.829,13.108s15.527-12.209,13.107-21.829 l-12.489-50.069c-1.462-5.796-4.331-11.195-8.383-15.585l-25.599-27.961l9.678-38.648l3.095,9.282 c2.981,9.058,9.396,16.541,17.834,20.815l13.107,6.638c8.775,4.444,19.466,0.845,23.909-8.045 C572.518,289.205,568.973,278.291,560.141,273.847L560.141,273.847z M431.363,353.003c-1.8,4.557-4.501,8.663-7.989,12.096 l-28.129,28.187c-7.033,7.032-7.033,18.452,0,25.484c7.032,7.032,18.396,7.032,25.429,0l33.417-33.417 c3.433-3.434,6.133-7.539,7.99-12.096l7.595-19.018c-31.111-33.924-21.772-23.516-26.667-30.211L431.363,353.003z"/></svg>';
										}
										else {
											// driving
											var icon = 'data:image/svg+xml,<svg focusable="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" x="0px" y="0px" width="26px" height="26px" viewBox="208.75 8.75 542.5 542.5" enable-background="new 208.75 8.75 542.5 542.5" xml:space="preserve"> <path fill="'+  encodeURIComponent( lddfw_color ) +'" d="M751.25,280C751.25,130.156,629.844,8.75,480,8.75S208.75,130.156,208.75,280c0,149.844,121.406,271.25,271.25,271.25 S751.25,429.844,751.25,280z M261.25,280c0-120.859,97.891-218.75,218.75-218.75c120.859,0,218.75,97.891,218.75,218.75 c0,120.858-97.891,218.75-218.75,218.75C359.141,498.75,261.25,400.86,261.25,280z"/><path d="M612.159,236.667h-32.431l-9.013-22.533C561.454,190.966,539.349,176,514.394,176h-68.792 c-24.949,0-47.06,14.966-56.328,38.133l-9.014,22.533h-32.424c-4.23,0-7.334,3.976-6.305,8.076l3.25,13 c0.72,2.893,3.32,4.924,6.305,4.924h10.872c-7.274,6.354-11.96,15.589-11.96,26v26c0,8.73,3.336,16.611,8.667,22.712v29.288  c0,9.569,7.762,17.332,17.333,17.332h17.333c9.571,0,17.333-7.763,17.333-17.332v-17.334h138.667v17.334 c0,9.569,7.763,17.332,17.334,17.332h17.333c9.571,0,17.333-7.763,17.333-17.332v-29.288c5.33-6.095,8.667-13.977,8.667-22.712v-26 c0-10.412-4.686-19.647-11.954-26h10.871c2.984,0,5.584-2.031,6.305-4.924l3.25-13C619.494,240.643,616.39,236.667,612.159,236.667z M421.46,227.009c3.949-9.869,13.509-16.342,24.142-16.342h68.792c10.635,0,20.194,6.473,24.144,16.342L549.331,254H410.665 L421.46,227.009z M393.332,314.559c-10.4,0-17.333-6.912-17.333-17.279s6.933-17.278,17.333-17.278s26,15.551,26,25.919 C419.332,316.285,403.731,314.559,393.332,314.559L393.332,314.559z M566.665,314.559c-10.4,0-26,1.728-26-8.641 c0-10.367,15.6-25.918,26-25.918c10.399,0,17.333,6.911,17.333,17.278C583.998,307.647,577.064,314.559,566.665,314.559z"/></svg>';
										}

										driverMarker[lddfw_driverid] = new google.maps.Marker({
										map: lddfw_map,
										icon : icon,
										});

										// Click on the driver icon
										google.maps.event.addListener(driverMarker[lddfw_driverid], 'click', function () {
											infowindow.setContent( "<div style='margin:5px'>" + lddfw_drivername + "</div>" );
											infowindow.open(lddfw_map, driverMarker[lddfw_driverid]);
											lddfw_map.setZoom(16);
											lddfw_map.panTo(driverMarker[lddfw_driverid].position);
										});
										<?php
									}
									?>

									lddfw_calculateAndDisplayRoute(lddfw_travel_mode,lddfw_driverid,lddfw_color,lddfw_map,lddfw_directionsService,lddfw_destination,lddfw_origin,lddfw_waypts_array);
								 }
							});
						}
					});

							<?php
							if ( false !== $lddfw_drivers_tracking_timing && '' !== $lddfw_drivers_tracking_timing ) {
								?>
							//Click on driver location icon
							jQuery("body").on("click",".lddfw_driver_box .lddfw_tracking a.active",function(){
								var lddfw_driver_id = jQuery(this).parent().parent().attr("data");
								if (  driverMarker[lddfw_driver_id] ){
									new google.maps.event.trigger( driverMarker[lddfw_driver_id], 'click' );
								}
							return false;
							});
								<?php
							}
							?>

				}
			}

				function lddfw_calculateAndDisplayRoute(lddfw_travel_mode,lddfw_driverid,color,lddfw_map,directionsService , lddfw_destination_address,lddfw_google_api_origin,lddfw_waypts_array) {
					var lddfw_waypts = [];
					lddfw_waypts_array.forEach(function (item, index) {
						lddfw_waypts.push({
							location: item,
							stopover: true
						});
					});
						setdirectionsService(lddfw_travel_mode,lddfw_driverid,color,lddfw_map,directionsService,lddfw_waypts,lddfw_destination_address,lddfw_google_api_origin);
				}
					<?php
					if ( false !== $lddfw_drivers_tracking_timing && '' !== $lddfw_drivers_tracking_timing ) {
						?>
				function drivers_tracking(){
					jQuery.ajax({
							type: "POST",
							url: lddfw_ajax_url,
							dataType: "json",
							data: {
								action: 'lddfw_ajax',
								lddfw_service: 'lddfw_drivers_locations',
								lddfw_wpnonce: lddfw_nonce.nonce,
								lddfw_data_type: 'json'
							},
							success: function(data) {
								var lddfw_counter = 0;
								jQuery.each( data, function( key, val ) {
									var driver_id = val.driver;
									var latv = val.lat ;
									var lonv = val.long ;
									lddfw_counter = lddfw_counter + 1;
									var tracking_status = val.tracking ;
									if ( '1' == tracking_status ) {
										jQuery("#driver_" + driver_id + ' .lddfw_tracking').html("<a href='#' title='<?php echo esc_attr( __( 'Tracking is on', 'lddfw' ) ); ?>' class='active'><svg style='color:green;cursor:pointer' aria-hidden='true' focusable='false' data-prefix='fas' data-icon='location-arrow' class='svg-inline--fa fa-location-arrow fa-w-16' role='img' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><path fill='currentColor' d='M444.52 3.52L28.74 195.42c-47.97 22.39-31.98 92.75 19.19 92.75h175.91v175.91c0 51.17 70.36 67.17 92.75 19.19l191.9-415.78c15.99-38.39-25.59-79.97-63.97-63.97z'></path></svg></a>");
									}
									else
									{
										jQuery("#driver_" + driver_id + ' .lddfw_tracking').html("<a href='#' title='<?php echo esc_attr( __( 'Tracking is off', 'lddfw' ) ); ?>'><svg style='color:silver' aria-hidden='true' focusable='false' data-prefix='fas' data-icon='location-arrow' class='svg-inline--fa fa-location-arrow fa-w-16' role='img' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><path fill='currentColor' d='M444.52 3.52L28.74 195.42c-47.97 22.39-31.98 92.75 19.19 92.75h175.91v175.91c0 51.17 70.36 67.17 92.75 19.19l191.9-415.78c15.99-38.39-25.59-79.97-63.97-63.97z'></path></svg></a>");
									}

									if ( latv != '' && lonv != '' ) {
										var latlng = new google.maps.LatLng(latv,lonv);
										if ( driverMarker[driver_id] )  {
											driverMarker[driver_id].setPosition(latlng);
										}
									}
								});
								/*
									if ( lddfw_counter < jQuery("#driver-panel .lddfw_driver_box").length )
									{
										lddfw_refresh_screen();
									}
								*/
							},
							error: function(request, status, error) {}
						})
				}
						<?php
					}
					?>
				function setdirectionsService(lddfw_travel_mode,lddfw_driverid,color,lddfw_map,directionsService,lddfw_waypts,lddfw_destination_address,lddfw_google_api_origin){
					directionsService.route({
						origin: lddfw_google_api_origin,
						destination: lddfw_destination_address,
						waypoints: lddfw_waypts,
						optimizeWaypoints: false,
						travelMode: lddfw_travel_mode,
						transitOptions: {
						modes: ['SUBWAY', 'RAIL', 'TRAM', 'BUS', 'TRAIN'],routingPreference: 'LESS_WALKING'},
					},
					function(response, status) {

						if (status === 'OK') {

							var directionsRenderer = new google.maps.DirectionsRenderer(
							{ 	polylineOptions: { strokeColor: color, strokeWeight: 6 } }
							);
							directionsRenderer.setMap(lddfw_map);
							directionsRenderer.setDirections(response);
							var lddfw_route = response.routes[0];
							var lddfw_summaryPanel = jQuery( "#driver_" + lddfw_driverid ).find( ".lddfw_directions-panel-listing" );
							lddfw_summaryPanel.html('<div class="lddfw_total_route"></div>') ;
							var lddfw_last_address = '';
							// For each route, display summary information.
							for (var i = 0; i < lddfw_route.legs.length; i++) {
								var lddfw_routeSegment = i + 1;
								if (lddfw_last_address != lddfw_route.legs[i].start_address) {
									lddfw_summaryPanel.append('<div class="row lddfw_address"><div class="col-2 text-center" ><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker" class="svg-inline--fa fa-map-marker fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"></path></svg><span class="lddfw_point">' + lddfw_numtoletter(lddfw_routeSegment) + '</span></div><div class="col-10">' + lddfw_route.legs[i].start_address + '</div></div>');
								}
								lddfw_summaryPanel.append( '<div class="row lddfw_drive"><div class="col-2 text-center"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-v" style="width: 6px;" class="svg-inline--fa fa-ellipsis-v up fa-w-6" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path fill="currentColor" d="M96 184c39.8 0 72 32.2 72 72s-32.2 72-72 72-72-32.2-72-72 32.2-72 72-72zM24 80c0 39.8 32.2 72 72 72s72-32.2 72-72S135.8 8 96 8 24 40.2 24 80zm0 352c0 39.8 32.2 72 72 72s72-32.2 72-72-32.2-72-72-72-72 32.2-72 72z"></path></svg><br><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="car" class="svg-inline--fa fa-car fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M499.99 176h-59.87l-16.64-41.6C406.38 91.63 365.57 64 319.5 64h-127c-46.06 0-86.88 27.63-103.99 70.4L71.87 176H12.01C4.2 176-1.53 183.34.37 190.91l6 24C7.7 220.25 12.5 224 18.01 224h20.07C24.65 235.73 16 252.78 16 272v48c0 16.12 6.16 30.67 16 41.93V416c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32v-32h256v32c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32v-54.07c9.84-11.25 16-25.8 16-41.93v-48c0-19.22-8.65-36.27-22.07-48H494c5.51 0 10.31-3.75 11.64-9.09l6-24c1.89-7.57-3.84-14.91-11.65-14.91zm-352.06-17.83c7.29-18.22 24.94-30.17 44.57-30.17h127c19.63 0 37.28 11.95 44.57 30.17L384 208H128l19.93-49.83zM96 319.8c-19.2 0-32-12.76-32-31.9S76.8 256 96 256s48 28.71 48 47.85-28.8 15.95-48 15.95zm320 0c-19.2 0-48 3.19-48-15.95S396.8 256 416 256s32 12.76 32 31.9-12.8 31.9-32 31.9z"></path></svg><br><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-v" style="width: 6px;" class="svg-inline--fa down fa-ellipsis-v fa-w-6" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path fill="currentColor" d="M96 184c39.8 0 72 32.2 72 72s-32.2 72-72 72-72-32.2-72-72 32.2-72 72-72zM24 80c0 39.8 32.2 72 72 72s72-32.2 72-72S135.8 8 96 8 24 40.2 24 80zm0 352c0 39.8 32.2 72 72 72s72-32.2 72-72-32.2-72-72-72-72 32.2-72 72z"></path></svg> </div><div class="col-10 middle"  ><b>' + lddfw_route.legs[i].duration.text + "</b><br>" + lddfw_route.legs[i].distance.text + '</div></div></div>' );
								lddfw_summaryPanel.append( '<div class="row lddfw_address"><div class="col-2 text-center"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker" class="svg-inline--fa fa-map-marker fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"></path></svg><span class="lddfw_point">' + lddfw_numtoletter((lddfw_routeSegment + 1) * 1) + '</span></div><div class="col-10">' + lddfw_route.legs[i].end_address + '</div></div>' );
								lddfw_last_address = lddfw_route.legs[i].end_address;
							}
							lddfw_computeTotalDistance(lddfw_driverid,response);

						} else {
							var lddfw_summaryPanel = jQuery( "#driver_" + lddfw_driverid ).find( ".lddfw_directions-panel-listing" );
							lddfw_summaryPanel.html('<div class="lddfw_total_route"></div>') ;
							lddfw_summaryPanel.append('Directions request failed due to ' + status);
						}
					}
				);
				}

				function lddfw_refresh_screen(){
					jQuery.when( lddfw_get_routes_json () ).done(function( data ){
						var lddfw_json = data;
						if( typeof lddfw_json['data'] != 'undefined' ){
							jQuery( "#lddfw_routes_notice").hide();
							jQuery( "#lddfw_map123" ).show();

							//Create drivers
							lddfw_drivers();

							//Create map
							lddfw_initMap();

						} else {

							jQuery( "#lddfw_routes_notice").show();
							jQuery( "#lddfw_map123" ).hide();
							jQuery( "#driver-panel" ).hide("");
						}
					});
				}


				function lddfw_screen(){
					//Load routes
					jQuery.when( lddfw_get_routes_json () ).done(function( data ){
					var lddfw_json = data;
					if( typeof lddfw_json['data'] != 'undefined' ){
						jQuery( "#lddfw_routes_notice").hide();
						var head = document.getElementsByTagName('head')[0];
						var script = document.createElement('script');
						script.type = 'text/javascript';
						script.onload = function() {
							//Create drivers
							lddfw_drivers();

							//Create map
							lddfw_initMap();
							<?php
							if ( false !== $lddfw_drivers_tracking_timing && '' !== $lddfw_drivers_tracking_timing ) {
								?>
							//Track drivers
							drivers_tracking();
								<?php
							}
							?>

							jQuery( "#lddfw_routes").show();
						}
						script.src = "https://maps.googleapis.com/maps/api/js?v=3&key=<?php echo esc_attr( $this->lddfw_google_api_key ); ?>";
						head.appendChild(script);
						} else
						{
							jQuery( "#lddfw_routes_notice").show();
						}
					});
				}
					<?php
					if ( '' !== $this->lddfw_google_api_key ) {
						?>
				lddfw_screen();
						<?php
					}
					?>
				</script>
					<?php
			}
		}
	}
}
