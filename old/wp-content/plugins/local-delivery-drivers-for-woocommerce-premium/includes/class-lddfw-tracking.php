<?php
/**
 * Plugin Tracking.
 *
 * All the screens functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */

/**
 * Plugin Tracking.
 *
 * All the Tracking functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class LDDFW_Tracking {

	/**
	 * Admin tracking screen.
	 *
	 * @since 1.4.0
	 * @return json
	 */
	public function lddfw_drivers_locations() {
			$route               = new LDDFW_Route();
			$wc_query            = $route->lddfw_all_routes_query__premium_only();
			$json                = '';
			$last_lddfw_driverid = 0;
		if ( $wc_query->have_posts() ) {
			$json = '[';
			while ( $wc_query->have_posts() ) {
				$wc_query->the_post();
				$orderid        = get_the_ID();
				$order          = wc_get_order( $orderid );
				$lddfw_driverid = $order->get_meta( 'lddfw_driverid' );
				if ( $last_lddfw_driverid !== $lddfw_driverid ) {
					$lddfw_tracking_latitude  = get_user_meta( $lddfw_driverid, 'lddfw_tracking_latitude', true );
					$lddfw_tracking_longitude = get_user_meta( $lddfw_driverid, 'lddfw_tracking_longitude', true );
					$lddfw_tracking_speed     = get_user_meta( $lddfw_driverid, 'lddfw_tracking_speed', true );
					$lddfw_tracking_status    = get_user_meta( $lddfw_driverid, 'lddfw_tracking_status', true );
					if ( 0 !== $last_lddfw_driverid ) {
						$json .= ','; }
					$json               .= '{"driver":"' . $lddfw_driverid . '","tracking":"' . $lddfw_tracking_status . '","lat":"' . $lddfw_tracking_latitude . '","long":"' . $lddfw_tracking_longitude . '" , "speed" : "' . $lddfw_tracking_speed . '" }';
					$last_lddfw_driverid = $lddfw_driverid;
				}
				?>
				<?php
			}
				$json .= ']';
		}
		return $json;
	}

	/**
	 * Set driver tracking position.
	 *
	 * @param int    $driver_id driver user id.
	 * @param string $lddfw_latitude latitude.
	 * @param string $lddfw_longitude longitude.
	 * @param string $lddfw_speed speed.
	 * @since 1.4.0
	 * @return int
	 */
	public function lddfw_set_driver_tracking_position( $driver_id, $lddfw_latitude, $lddfw_longitude, $lddfw_speed ) {
		update_user_meta( $driver_id, 'lddfw_tracking_latitude', $lddfw_latitude );
		update_user_meta( $driver_id, 'lddfw_tracking_longitude', $lddfw_longitude );
		update_user_meta( $driver_id, 'lddfw_tracking_speed', $lddfw_speed );
		return 1;
	}

	/**
	 * Set driver tracking status.
	 *
	 * @param int    $driver_id driver user id.
	 * @param string $tracking_status tracking status.
	 * @since 1.4.0
	 * @return int
	 */
	public function lddfw_driver_tracking_status( $driver_id, $tracking_status ) {
		update_user_meta( $driver_id, 'lddfw_tracking_status', $tracking_status );
		return 1;
	}

	/**
	 * Admin tracking screen.
	 *
	 * @since 1.4.0
	 */
	public function lddfw_drivers_panel_script() {
		global $lddfw_out_for_delivery_counter, $lddfw_drivers_tracking_timing;
		?>
<script>
function geolocation_success(position) {
	lddfw_switch_tracking_icon("1");
	lddfw_driver_tracking_status("1");
	lddfw_watch_position_end();
	lddfw_watch_position_start();
}

function geolocation_error(err) {
	jQuery("#tracking_alert").html("<div style='margin:0px' class='alert alert-danger'>" + err.message + "</div>");
}

function lddfw_switch_tracking_icon(status) {
	if (status == "1") {
		jQuery("#lddfw_trackme .lddfw_trackme_off").hide();
		jQuery("#lddfw_trackme .lddfw_trackme_on").show();
		lddfw_tracking_status = "1";
	} else {
		jQuery("#lddfw_trackme .lddfw_trackme_on").hide();
		jQuery("#lddfw_trackme .lddfw_trackme_off").show();
		lddfw_tracking_status = "0";
	}
}

jQuery("#lddfw_trackme").click(function() {
	if (jQuery("#lddfw_trackme .lddfw_trackme_on").is(":visible")) {
		lddfw_switch_tracking_icon("0");
		lddfw_driver_tracking_status("0");
		lddfw_watch_position_end();
	} else {
		if (!navigator.geolocation) {
			jQuery("#tracking_alert").html("Geolocation is not supported by your browser");
		} else {
			navigator.geolocation.getCurrentPosition(geolocation_success, geolocation_error);
		}
	}
	return false;
});

function lddfw_driver_tracking_status(lddfw_status) {
	jQuery.ajax({
		type: "POST",
		url: lddfw_ajax_url,
		data: {
			action: 'lddfw_ajax',
			lddfw_service: 'lddfw_driver_tracking_status',
			lddfw_status: lddfw_status,
			lddfw_driver_id: lddfw_driver_id,
			lddfw_wpnonce: lddfw_nonce.nonce,
			lddfw_data_type: 'html'
		},
		success: function(data) {},
		error: function(request, status, error) {}
	});
}

if (jQuery("#lddfw_trackme").length) {
	if (lddfw_tracking_status == "1") {
		lddfw_switch_tracking_icon("1");
	} else {
		lddfw_switch_tracking_icon("0");
	}
}

var lddfw_watch_position_id, lddfw_last_latitude, lddfw_last_longitude;

function lddfw_watch_position_success(pos) {
	var coordination = pos.coords;
	//if (lddfw_last_latitude != coordination.latitude && coordination.longitude != lddfw_last_longitude) {

		jQuery.ajax({
			type: "POST",
			url: lddfw_ajax_url,
			data: {
				action: 'lddfw_ajax',
				lddfw_service: 'lddfw_driver_tracking_position',
				lddfw_latitude: coordination.latitude,
				lddfw_longitude: coordination.longitude,
				lddfw_speed: coordination.speed,
				lddfw_driver_id: lddfw_driver_id,
				lddfw_wpnonce: lddfw_nonce.nonce,
				lddfw_data_type: 'html'
			},
			success: function(data) {},
			error: function(request, status, error) {}
		});
		lddfw_last_latitude = coordination.latitude;
		lddfw_last_longitude = coordination.longitude;
	//}

}

function lddfw_watch_position_error(err) {
console.log(err);

}

function lddfw_watch_position_start() {
		<?php
		if ( false !== $lddfw_drivers_tracking_timing && '' !== $lddfw_drivers_tracking_timing ) {
			if ( intval( $lddfw_drivers_tracking_timing ) > 4999 ) {
				?>
				lddfw_watch_position_id = setInterval(lddfw_watch_position, <?php echo get_option( 'lddfw_drivers_tracking_timing' ); ?> );
				<?php
			}
		}
		?>
}

function lddfw_watch_position() {
	navigator.geolocation.getCurrentPosition(lddfw_watch_position_success, lddfw_watch_position_error, { enableHighAccuracy: true });
}

function lddfw_watch_position_end() {
	clearInterval(lddfw_watch_position_id);
}
		<?php if ( 0 < $lddfw_out_for_delivery_counter ) { ?>
	if (lddfw_tracking_status == "1" ) {
		lddfw_watch_position_start();
	}
		<?php } ?>
</script>
		<?php
	}

}
