(function() {
    "use strict";

    /* <fs_premium_only> */
    function lddfw_get_signature() {
        var lddfw_signature = "";
        if (typeof lddfw_signaturePad === "undefined") {} else {
            if (!lddfw_signaturePad.isEmpty()) {
                var today = new Date();
                var date = today.getFullYear() + '-' + ('0' + (today.getMonth() + 1)).slice(-2) + '-' + ('0' + today.getDate()).slice(-2);
                var time = ('0' + today.getHours()).slice(-2) + ":" + ('0' + today.getMinutes()).slice(-2) + ":" + ('0' + today.getSeconds()).slice(-2);
                var dateTime = date + ' ' + time;
                var lddfw_canvas = document.getElementById('signature-pad');
                var context = lddfw_canvas.getContext('2d');
                var input = document.getElementById('signature_name');
                context.font = '16px arial';
                context.strokeText(dateTime + " " + input.value, 10, 25);
                lddfw_signature = lddfw_signaturePad.toDataURL();
            }
        }
        return lddfw_signature;
    }

    function lddfw_next_delivery_service() {
        jQuery.ajax({
            type: "POST",
            url: lddfw_ajax_url,
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_next_delivery',
                lddfw_driver_id: lddfw_driver_id,
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_data_type: 'json'
            },
            success: function(data) {
                var lddfw_json = JSON.parse(data);
                if (lddfw_json["result"] == "0") {
                    jQuery("#lddfw_next_delivery").html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\"><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" + lddfw_json["error"] + "</div>");
                } else {
                    jQuery("#lddfw_next_delivery").html(lddfw_json["shipping_address"]);
                }
            },
            error: function(request, status, error) {}
        })
    }

    jQuery("#signature-done").click(function() {
        jQuery("#signature-image").html("");

        if (typeof lddfw_signaturePad === "undefined") {} else {
            if (!lddfw_signaturePad.isEmpty()) {
                var lddfw_signature = lddfw_signaturePad.toDataURL();
                lddfw_resizeImage(lddfw_signature, 640, 640).then((result) => {
                    jQuery('#signature-image').html("<span class='lddfw_helper'></span><img src='" + lddfw_signature + "'>");
                });
            }
        }
        jQuery(".signature-wrapper").hide();
    });

    jQuery("#lddfw_claim_orders_button").click(
        function() {

            jQuery("#lddfw_claim_orders_button").hide();
            jQuery("#lddfw_claim_orders_button_loading").show();

            var lddfw_order_list = '';
            jQuery("#lddfw_alert").html();
            jQuery('.lddfw_multi_checkbox .custom-control-input').each(
                function(index, item) {
                    if (jQuery(this).prop("checked") == true) {

                        if (lddfw_order_list != "") {
                            lddfw_order_list = lddfw_order_list + ",";
                        }
                        lddfw_order_list = lddfw_order_list + jQuery(this).val();
                    }

                }
            );

            jQuery.ajax({
                url: lddfw_ajax_url,
                type: 'POST',
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_claim_orders',
                    lddfw_orders_list: lddfw_order_list,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                }
            }).done(
                function(data) {

                    jQuery("#lddfw_alert").show();
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        jQuery("#lddfw_alert").html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\"><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" + lddfw_json["error"] + "</div>");
                    }
                    if (lddfw_json["result"] == "1") {
                        jQuery(this).parents(".lddfw_multi_checkbox").hide();
                        jQuery('.lddfw_multi_checkbox .custom-control-input').each(
                            function(index, item) {
                                if (jQuery(this).prop("checked") == true) {
                                    jQuery(this).parents(".lddfw_multi_checkbox").replaceWith("");
                                }
                            }
                        );
                        jQuery("#lddfw_alert").html(lddfw_json["error"]);
                        if (jQuery('.lddfw_multi_checkbox').length == 0) {
                            jQuery(".lddfw_footer_buttons").hide();
                        }
                    }

                    jQuery("#lddfw_claim_orders_button").show();
                    jQuery("#lddfw_claim_orders_button_loading").hide();
                }
            );
            return false;
        }
    );


    function lddfw_sortroute_on() {
        var lddfw_sortroute_btn = jQuery("#lddfw_sortroute_btn");
        lddfw_sortroute_btn.addClass("lddfw_active");
        lddfw_sortroute_btn.addClass("btn-primary");
        lddfw_sortroute_btn.removeClass("btn-secondary");
        lddfw_sortroute_btn.html(lddfw_sortroute_btn.attr("data-finish"));
        jQuery(".lddfw_handle_column").show();
        jQuery("#route_origin_label").hide();
        jQuery("#route_origin_div").show();

        jQuery("#route_destination_label").hide();
        jQuery("#route_destination_div").show();


    }

    function lddfw_sortroute_off() {
        var lddfw_sortroute_btn = jQuery("#lddfw_sortroute_btn");
        lddfw_sortroute_btn.removeClass("lddfw_active");
        lddfw_sortroute_btn.removeClass("btn-primary");
        lddfw_sortroute_btn.addClass("btn-secondary");
        lddfw_sortroute_btn.html(lddfw_sortroute_btn.attr("data-start"));
        jQuery(".lddfw_handle_column").hide();
        jQuery("#route_origin_label").show();
        jQuery("#route_origin_div").hide();
        jQuery("#route_destination_label").show();
        jQuery("#route_destination_div").hide();
    }


    jQuery("#lddfw_sortroute_btn").click(
        function() {
            jQuery("#lddfw_plain_route_note_info").hide();
            if (jQuery(this).hasClass("lddfw_active")) {
                lddfw_sortroute_off()
            } else {
                lddfw_sortroute_on()
            }
        });

    jQuery("body").on("change", "#route_origin,#route_destination", function() {
        var origin_map_address = jQuery("#route_origin").val();
        var origin_address = jQuery("#route_origin").find('option:selected').text();
        var destination_map_address = jQuery("#route_destination").val();
        var destination_address = jQuery("#route_destination").find('option:selected').text();

        jQuery.ajax({
            url: lddfw_ajax_url,
            type: 'POST',
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_set_route',
                lddfw_origin_map_address: origin_map_address,
                lddfw_origin_address: origin_address,
                lddfw_destination_map_address: destination_map_address,
                lddfw_destination_address: destination_address,
                lddfw_driver_id: lddfw_driver_id,
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_data_type: 'html'
            }
        }).done(
            function(data) {
                if (data == '1') {
                    jQuery("#route_origin_label").html(origin_address);
                    jQuery("#route_destination_label").html(destination_address);
                }
            });
    });

    var route_timer;
    jQuery("#lddfw_plainroute_btn").click(
        function() {
            clearTimeout(route_timer);
            var lddfw_origin = jQuery("#route_origin").val();
            var lddfw_destination = jQuery("#route_destination").val();
            var lddfw_plainroute_btn = jQuery(this);
            var lddfw_loading_btn = jQuery("#lddfw_plain_route_row .lddfw_loading_btn");
            var lddfw_done_btn = jQuery("#lddfw_plain_route_row .lddfw_done_btn");
            var lddfw_plain_route_note_info = jQuery("#lddfw_plain_route_note_info");
            lddfw_sortroute_off();
            lddfw_done_btn.hide();
            lddfw_plainroute_btn.hide();
            lddfw_loading_btn.show();
            lddfw_plain_route_note_info.hide();
            jQuery(this).addClass("lddfw_active");
            jQuery("#lddfw_plain_route_note_wait").show();
            jQuery.ajax({
                url: lddfw_ajax_url,
                type: 'POST',
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_plain_route',
                    lddfw_origin: lddfw_origin,
                    lddfw_destination: lddfw_destination,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'html'
                }
            }).done(
                function(data) {
                    jQuery("#lddfw_plain_route_note_wait").hide();
                    jQuery("#lddfw_plain_route_container").html(data);
                    jQuery(this).removeClass("lddfw_active");
                    lddfw_sortroute_off();
                    lddfw_loading_btn.hide();
                    lddfw_done_btn.show();
                    lddfw_plain_route_note_info.show().delay(8000).hide(0);
                    route_timer = setTimeout(function() {
                        lddfw_done_btn.hide();
                        lddfw_plainroute_btn.show();
                    }, 3000);
                }
            );

            return false;
        }
    );

    jQuery("#lddfw_application_frm").validate({
        submitHandler: function(form) {

            var lddfw_form = jQuery(form);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn")
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn")
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");


            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_application',
                    lddfw_application_fullname: jQuery("#lddfw_application_fullname").val(),
                    lddfw_application_phone: jQuery("#lddfw_application_phone").val(),
                    lddfw_application_email: jQuery("#lddfw_application_email").val(),
                    lddfw_application_message: jQuery("#lddfw_application_message").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                },
                success: function(data) {
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                    if (lddfw_json["result"] == "1") {
                        jQuery(".lddfw_page").hide();
                        jQuery("#lddfw_application_thankyou").show();

                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                },
                error: function(request, status, error) {
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });
            return false;
        }
    });

    jQuery("#lddfw_route_btn").click(
        function() {
            lddfw_sortroute_off();
            if (jQuery("#lddfw_google_map_script").length == 0) {
                jQuery("body").append("<script id='lddfw_google_map_script' async defer src='https://maps.googleapis.com/maps/api/js?key=" + lddfw_google_api_key + "&callback=lddfw_initMap'></script>");
            } else {
                lddfw_initMap();
            }
            jQuery(".lddfw_page_content").hide();
            jQuery("#lddfw_directions").show();
            return false;
        }
    );
    jQuery("#lddfw_hide_map_btn").click(
        function() {
            jQuery(".lddfw_page_content").show();
            jQuery("#lddfw_directions").hide();
            return false;

        }
    );

    function lddfw_route_update() {

        var lddfw_couter = 1;
        var lddfw_order_list = "";
        var lddfw_origin = jQuery("#route_origin").val();
        jQuery("#lddfw_orders_table .lddfw_index").each(
            function() {
                jQuery(this).html(lddfw_couter);
                lddfw_couter = lddfw_couter + 1;
                lddfw_order_list = lddfw_order_list + jQuery(this).parent().find(".lddfw_address_chk").attr("orderid") + ",";
            }
        );
        jQuery.ajax({
            url: lddfw_ajax_url,
            type: 'POST',
            data: {
                action: 'lddfw_ajax',
                lddfw_service: 'lddfw_sort_orders',
                lddfw_orders_list: lddfw_order_list,
                lddfw_origin: lddfw_origin,
                lddfw_driver_id: lddfw_driver_id,
                lddfw_wpnonce: lddfw_nonce.nonce,
                lddfw_data_type: 'html'
            },
            success: function(data) {
                if (data != "1") {
                    jQuery("#lddfw_plain_route_container").html(data);
                }
            }
        });

    }

    function lddfw_route_handle() {
        jQuery("#lddfw_plain_route_container .lddfw_handle_column a").show();
    }

    jQuery("body").on("click", "#lddfw_plain_route_container .lddfw_sort-up", function() {
        clearTimeout(route_timer);
        jQuery("#lddfw_orders_table .lddfw_box").removeClass("lddfw_active");
        var lddfw_elem = jQuery(this).closest(".lddfw_box");
        lddfw_elem.prev().before(lddfw_elem)
        lddfw_elem.addClass("lddfw_active");
        route_timer = setTimeout(function() {
            lddfw_elem.removeClass("lddfw_active");
        }, 2000);
        lddfw_route_update();
        lddfw_route_handle();
    });

    jQuery("body").on("click", "#lddfw_plain_route_container .lddfw_sort-down", function() {
        clearTimeout(route_timer);
        jQuery("#lddfw_orders_table .lddfw_box").removeClass("lddfw_active");
        var lddfw_elem = jQuery(this).closest(".lddfw_box");
        lddfw_elem.next().after(lddfw_elem)
        lddfw_elem.addClass("lddfw_active");
        route_timer = setTimeout(function() {
            lddfw_elem.removeClass("lddfw_active");
        }, 2000);
        lddfw_route_update();
        lddfw_route_handle();
    });


    jQuery(".lddfw_product_line").click(function() {
        jQuery(this).parent().find(".lddfw_lightbox").show();
    });

    /* </fs_premium_only> */
    jQuery(".lddfw_premium-feature button").click(function() {
        jQuery(this).parent().find(".lddfw_lightbox").show();
    });

    jQuery("#lddfw_out_for_delivery_button").click(
        function() {
            jQuery("#lddfw_out_for_delivery_button").hide();
            jQuery("#lddfw_out_for_delivery_button_loading").show();

            var lddfw_order_list = '';
            jQuery("#lddfw_alert").html();
            jQuery('.lddfw_multi_checkbox .custom-control-input').each(
                function(index, item) {
                    if (jQuery(this).prop("checked") == true) {
                        if (lddfw_order_list != "") {
                            lddfw_order_list = lddfw_order_list + ",";
                        }
                        lddfw_order_list = lddfw_order_list + jQuery(this).val();
                    }
                }
            );
            jQuery.ajax({
                url: lddfw_ajax_url,
                type: 'POST',
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_out_for_delivery',
                    lddfw_orders_list: lddfw_order_list,
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                }
            }).done(
                function(data) {

                    jQuery("#lddfw_out_for_delivery_button").show();
                    jQuery("#lddfw_out_for_delivery_button_loading").hide();

                    jQuery("#lddfw_alert").show();
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        jQuery("#lddfw_alert").html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\"><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" + lddfw_json["error"] + "</div>");
                    }
                    if (lddfw_json["result"] == "1") {

                        jQuery('.lddfw_multi_checkbox .custom-control-input').each(
                            function(index, item) {
                                if (jQuery(this).prop("checked") == true) {
                                    jQuery(this).parents(".lddfw_multi_checkbox").replaceWith("");
                                }
                            }
                        );
                        jQuery("#lddfw_alert").html(lddfw_json["error"]);
                        if (jQuery('.lddfw_multi_checkbox').length == 0) {
                            jQuery(".lddfw_footer_buttons").hide();
                        }
                    }
                }
            );
            return false;
        }
    );

    jQuery(".lddfw_multi_checkbox .lddfw_wrap").click(
        function() {
            var lddfw_chk = jQuery(this).find(".custom-control-input");
            if (lddfw_chk.prop("checked") == true) {
                jQuery(this).parents(".lddfw_multi_checkbox").removeClass("lddfw_active");
                lddfw_chk.prop("checked", false);
            } else {
                jQuery(this).parents(".lddfw_multi_checkbox").addClass("lddfw_active");
                lddfw_chk.prop("checked", true);
            }
        }
    );

    jQuery("#lddfw_start").click(
        function() {
            jQuery("#lddfw_home").hide();
            jQuery("#lddfw_login").show();
        }
    );

    jQuery("#lddfw_login_button").click(
        function() {
            // hide the sign up button
            jQuery("#lddfw_signup_button").hide();
            // show the login form
            jQuery("#lddfw_login_wrap").toggle();
            return false;
        }
    );

    jQuery("#lddfw_availability").click(
        function() {
            if (jQuery(this).hasClass("lddfw_active")) {
                jQuery(this).removeClass("lddfw_active");
                jQuery(this).html('<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-off" class="svg-inline--fa fa-toggle-off fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C85.961 64 0 149.961 0 256s85.961 192 192 192h192c106.039 0 192-85.961 192-192S490.039 64 384 64zM64 256c0-70.741 57.249-128 128-128 70.741 0 128 57.249 128 128 0 70.741-57.249 128-128 128-70.741 0-128-57.249-128-128zm320 128h-48.905c65.217-72.858 65.236-183.12 0-256H384c70.741 0 128 57.249 128 128 0 70.74-57.249 128-128 128z"></path></svg>');
                jQuery("#lddfw_availability_status").html(jQuery("#lddfw_availability_status").attr("unavailable"));
                jQuery("#lddfw_menu .lddfw_availability").removeClass("text-success");
                jQuery("#lddfw_menu .lddfw_availability").addClass("text-danger");
                jQuery.post(
                    lddfw_ajax_url, {
                        action: 'lddfw_ajax',
                        lddfw_service: 'lddfw_availability',
                        lddfw_availability: "0",
                        lddfw_driver_id: lddfw_driver_id,
                        lddfw_wpnonce: lddfw_nonce.nonce,
                        lddfw_data_type: 'html'
                    }
                );
            } else {
                jQuery(this).addClass("lddfw_active");
                jQuery("#lddfw_availability_status").html(jQuery("#lddfw_availability_status").attr("available"));
                jQuery(this).html('<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="toggle-on" class="svg-inline--fa fa-toggle-on fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M384 64H192C86 64 0 150 0 256s86 192 192 192h192c106 0 192-86 192-192S490 64 384 64zm0 320c-70.8 0-128-57.3-128-128 0-70.8 57.3-128 128-128 70.8 0 128 57.3 128 128 0 70.8-57.3 128-128 128z"></path></svg>');
                jQuery("#lddfw_menu .lddfw_availability").removeClass("text-danger");
                jQuery("#lddfw_menu .lddfw_availability").addClass("text-success");
                jQuery.post(
                    lddfw_ajax_url, {
                        action: 'lddfw_ajax',
                        lddfw_service: 'lddfw_availability',
                        lddfw_availability: "1",
                        lddfw_driver_id: lddfw_driver_id,
                        lddfw_wpnonce: lddfw_nonce.nonce,
                        lddfw_data_type: 'html'
                    }
                );
            }
            return false;
        }
    );

    jQuery("#lddfw_dates_range").change(
        function() {
            var lddfw_location = jQuery(this).attr("data") + '&lddfw_dates=' + this.value;
            window.location.replace(lddfw_location);
            return false;
        }
    );

    if (lddfw_dates != "") {
        jQuery("#lddfw_dates_range").val(lddfw_dates);
    }

    function lddfw_delivered_screen_open() {
        jQuery("#lddfw_driver_complete_btn").show();
        jQuery(".lddfw_page_content").hide();
        jQuery("#lddfw_delivery_signature").hide();
        jQuery("#lddfw_delivery_photo").hide();
        jQuery("#lddfw_delivered_form").hide();
        jQuery("#lddfw_failed_delivery_form").hide();
        jQuery(".delivery_proof_bar a").removeClass("active");
        jQuery(".delivery_proof_bar a").eq(0).addClass("active");
    }

    jQuery("#lddfw_delivered_screen_btn").click(
        function() {
            jQuery("#lddfw_driver_complete_btn").attr("delivery", "success");
            jQuery(".delivery_proof_notes").attr("href", "lddfw_delivered_form");
            lddfw_delivered_screen_open();
            jQuery("#lddfw_delivered_form").show();
            jQuery("#lddfw_delivery_screen").show();
            return false;
        }
    );

    jQuery("#lddfw_failed_delivered_screen_btn").click(
        function() {
            jQuery("#lddfw_driver_complete_btn").attr("delivery", "failed");
            jQuery(".delivery_proof_notes").attr("href", "lddfw_failed_delivery_form");
            lddfw_delivered_screen_open();
            jQuery("#lddfw_failed_delivery_form").show();
            jQuery("#lddfw_delivery_screen").show();
            return false;
        }
    );

    jQuery(".lddfw_dashboard .lddfw_box a").click(function() {
        jQuery(this).parent().addClass("lddfw_active");
    });

    jQuery(".lddfw_confirmation .lddfw_cancel").click(
        function() {
            jQuery(".lddfw_page_content").show();
            jQuery(this).parents(".lddfw_lightbox").hide();
            return false;
        }
    );

    jQuery("#lddfw_delivered_confirmation .lddfw_ok").click(
        function() {

            var lddfw_reason = jQuery('input[name=lddfw_delivery_dropoff_location]:checked', '#lddfw_delivered_form');
            if (lddfw_reason.attr("id") != "lddfw_delivery_dropoff_other") {
                jQuery("#lddfw_driver_delivered_note").val(lddfw_reason.val());
            }
            jQuery("#lddfw_delivered").hide();
            jQuery("#lddfw_thankyou").show();

            var lddfw_orderid = jQuery("#lddfw_driver_complete_btn").attr("order_id");
            var lddfw_signature = '';
            var lddfw_delivery_image = '';
            /* <fs_premium_only> */
            lddfw_signature = lddfw_get_signature();
            lddfw_resizeImage(lddfw_signature, 640, 640).then((result) => {
                lddfw_signature = result;
            });
            lddfw_delivery_image = jQuery('#delivery_image').val();
            /* </fs_premium_only> */

            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_status',
                    lddfw_order_id: lddfw_orderid,
                    lddfw_order_status: jQuery("#lddfw_driver_complete_btn").attr("delivered_status"),
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_note: jQuery("#lddfw_driver_delivered_note").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'html',
                    lddfw_signature: lddfw_signature,
                    lddfw_delivery_image: lddfw_delivery_image


                },
                success: function(data) {
                    /* <fs_premium_only> */
                    lddfw_next_delivery_service();
                    /* </fs_premium_only> */
                },
                error: function(request, status, error) {}
            });

            return false;
        }
    );

    if (jQuery("#lddfw_delivered_form .custom-control.custom-radio").length == 1) {
        jQuery("#lddfw_delivered_form .custom-control.custom-radio").hide();
    }
    if (jQuery("#lddfw_failed_delivery_form .custom-control.custom-radio").length == 1) {
        jQuery("#lddfw_failed_delivery_form .custom-control.custom-radio").hide();
    }

    jQuery("#lddfw_driver_complete_btn").click(
        function() {
            jQuery("#lddfw_delivery_screen").hide();
            if (jQuery(this).attr("delivery") == "success") {
                jQuery("#lddfw_delivered_confirmation").show();
            } else {
                jQuery("#lddfw_failed_delivery_confirmation").show();
            }
            return false;
        }
    );
    jQuery("#lddfw_failed_delivery_confirmation .lddfw_ok").click(
        function() {

            var lddfw_reason = jQuery('input[name=lddfw_delivery_failed_reason]:checked', '#lddfw_failed_delivery_form');
            if (lddfw_reason.attr("id") != "lddfw_delivery_failed_6") {
                jQuery("#lddfw_driver_note").val(lddfw_reason.val());
            }

            jQuery("#lddfw_failed_delivery").hide();
            jQuery("#lddfw_thankyou").show();

            var lddfw_orderid = jQuery("#lddfw_driver_complete_btn").attr("order_id");

            var lddfw_signature = '';
            var lddfw_delivery_image = '';
            /* <fs_premium_only> */
            lddfw_signature = lddfw_get_signature();
            lddfw_resizeImage(lddfw_signature, 640, 640).then((result) => {
                lddfw_signature = result;
            });
            lddfw_delivery_image = jQuery('#delivery_image').val();
            /* </fs_premium_only> */

            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_status',
                    lddfw_order_id: lddfw_orderid,
                    lddfw_order_status: jQuery("#lddfw_driver_complete_btn").attr("failed_status"),
                    lddfw_driver_id: lddfw_driver_id,
                    lddfw_note: jQuery("#lddfw_driver_note").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'html',
                    lddfw_signature: lddfw_signature,
                    lddfw_delivery_image: lddfw_delivery_image
                },
                success: function(data) {
                    /* <fs_premium_only> */
                    lddfw_next_delivery_service();
                    /* </fs_premium_only> */
                },
                error: function(request, status, error) {}
            });

            return false;
        }
    );

    jQuery("#lddfw_delivered_form input[type=radio]").click(
        function() {
            jQuery("#lddfw_driver_delivered_note").val("");
            if (jQuery(this).attr("id") == "lddfw_delivery_dropoff_other") {
                jQuery("#lddfw_driver_delivered_note_wrap").show();
            } else {
                jQuery("#lddfw_driver_delivered_note_wrap").hide();
            }
        }
    );

    jQuery("#lddfw_failed_delivery_form input[type=radio]").click(
        function() {
            jQuery("#lddfw_driver_note").val("");
            if (jQuery(this).attr("id") == "lddfw_delivery_failed_6") {
                jQuery("#lddfw_driver_note_wrap").show();
            } else {
                jQuery("#lddfw_driver_note_wrap").hide();
            }
        }
    );

    jQuery(".lddfw_lightbox_close,#lddfw_driver_cancel_btn").click(
        function() {
            jQuery(".lddfw_page_content").show();
            jQuery(this).parents(".lddfw_lightbox").hide();
            return false;
        }
    );

    jQuery("#lddfw_login_frm").submit(
        function(e) {
            e.preventDefault();

            var lddfw_form = jQuery(this);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn")
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn")
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");

            var lddfw_nextpage = lddfw_form.attr('nextpage');

            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");

            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_login',
                    lddfw_login_email: jQuery("#lddfw_login_email").val(),
                    lddfw_login_password: jQuery("#lddfw_login_password").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                },
                success: function(data) {
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                    if (lddfw_json["result"] == "1") {
                        window.location.replace(lddfw_nextpage);
                    }
                },
                error: function(request, status, error) {
                    lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + status + ' ' + error + "</div>");
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("#lddfw_back_to_forgot_password_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery("#lddfw_login_button").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_login").show();
        }
    );
    jQuery("#lddfw_new_password_login_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_login").show();
        }
    );
    jQuery("#lddfw_new_password_reset_link").click(
        function() {
            jQuery("#lddfw_create_new_password").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery("#lddfw_forgot_password_link").click(
        function() {
            jQuery("#lddfw_login").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery(".lddfw_back_to_login_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_login").show();

        }
    );
    jQuery("#lddfw_resend_button").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_forgot_password").show();
        }
    );
    jQuery("#lddfw_application_link").click(
        function() {
            jQuery(".lddfw_page").hide();
            jQuery("#lddfw_application").show();
        }
    );

    jQuery("#lddfw_forgot_password_frm").submit(
        function(e) {
            e.preventDefault();

            var lddfw_form = jQuery(this);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn");
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn");
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");

            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");


            var lddfw_nextpage = lddfw_form.attr('nextpage');
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_forgot_password',
                    lddfw_user_email: jQuery("#lddfw_user_email").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'

                },
                success: function(data) {
                    var lddfw_json = JSON.parse(data);

                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                    if (lddfw_json["result"] == "1") {
                        jQuery(".lddfw_page").hide();
                        jQuery("#lddfw_forgot_password_email_sent").show();

                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                },
                error: function(request, status, error) {
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("#lddfw_new_password_frm").submit(
        function(e) {
            e.preventDefault();

            var lddfw_form = jQuery(this);
            var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn");
            var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn");
            var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");

            lddfw_submit_btn.hide();
            lddfw_loading_btn.show();
            lddfw_alert_wrap.html("");


            var lddfw_nextpage = lddfw_form.attr('nextpage');
            jQuery.ajax({
                type: "POST",
                url: lddfw_ajax_url,
                data: {
                    action: 'lddfw_ajax',
                    lddfw_service: 'lddfw_newpassword',
                    lddfw_new_password: jQuery("#lddfw_new_password").val(),
                    lddfw_confirm_password: jQuery("#lddfw_confirm_password").val(),
                    lddfw_reset_key: jQuery("#lddfw_reset_key").val(),
                    lddfw_reset_login: jQuery("#lddfw_reset_login").val(),
                    lddfw_wpnonce: lddfw_nonce.nonce,
                    lddfw_data_type: 'json'
                },

                success: function(data) {
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                    }
                    if (lddfw_json["result"] == "1") {
                        jQuery(".lddfw_page").hide();
                        jQuery("#lddfw_new_password_created").show();

                    }
                },
                error: function(request, status, error) {
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                }
            });
            return false;
        }
    );

    jQuery("body").on("click", "#lddfw_orders_table .lddfw_box a", function() {
        jQuery(this).closest(".lddfw_box").addClass("lddfw_active");
    });
    /* <fs_premium_only> */
    jQuery("body").on("click", "#delivery-image-clear", function() {
        jQuery("#delivery_image_wrap").html("");
        jQuery("#delivery_image").val("");
        return false;
    });
    /* </fs_premium_only> */

})(jQuery);

function lddfw_openNav() {
    jQuery(".lddfw_page_content").hide();
    document.getElementById("lddfw_mySidenav").style.width = "100%";
}

function lddfw_closeNav() {
    jQuery(".lddfw_page_content").show();
    document.getElementById("lddfw_mySidenav").style.width = "0";
}

/* <fs_premium_only> */
function lddfw_initMap() {
    var lddfw_directionsService = new google.maps.DirectionsService();
    var lddfw_directionsRenderer = new google.maps.DirectionsRenderer();
    var lddfw_map = new google.maps.Map(
        document.getElementById('lddfw_map123'), {
            zoom: 6,
            center: { lat: 41.85, lng: -87.65 }
        }
    );
    lddfw_directionsRenderer.setMap(lddfw_map);
    lddfw_calculateAndDisplayRoute(lddfw_directionsService, lddfw_directionsRenderer);
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

function lddfw_computeTotalDistance(result) {
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
    document.getElementById("lddfw_total_route").innerHTML = "<b>" + lddfw_TotalTimeText + "</b> <span>(" + (lddfw_totalDist).toFixed(1) + " " + lddfw_distance_type + ")</span> ";
}

function lddfw_calculateAndDisplayRoute(directionsService, directionsRenderer) {
    var lddfw_waypts = [];
    var lddfw_orders_count = jQuery('.lddfw_address_chk').length;
    var lddfw_last_waypoint = 0;

    var lddfw_destination_address = jQuery("#route_destination").val();
    if (lddfw_destination_address == '' || lddfw_destination_address == 'last_address_on_route') {
        lddfw_destination_address = jQuery('.lddfw_address_chk').eq(jQuery('.lddfw_address_chk').length - 1).val();
    }

    var lddfw_origin_address = jQuery('#route_origin').val();
    if (lddfw_origin_address != '') {
        lddfw_google_api_origin = lddfw_origin_address;
    }
    jQuery('.lddfw_address_chk').each(
        function(index, item) {
            if (jQuery(this).val() != lddfw_destination_address) {
                lddfw_waypts.push({
                    location: jQuery(this).val(),
                    stopover: true
                });
            }
        }
    );
    directionsService.route({
            origin: lddfw_google_api_origin,
            destination: lddfw_destination_address,
            waypoints: lddfw_waypts,
            optimizeWaypoints: lddfw_optimizeWaypoints_flag,
            travelMode: lddfw_driver_travel_mode,
            transitOptions: { modes: ['SUBWAY', 'RAIL', 'TRAM', 'BUS', 'TRAIN'], routingPreference: 'LESS_WALKING' },
        },
        function(response, status) {
            if (status === 'OK') {
                directionsRenderer.setDirections(response);
                var lddfw_route = response.routes[0];
                var lddfw_summaryPanel = document.getElementById('lddfw_directions-panel-listing');
                lddfw_summaryPanel.innerHTML = '<div id="lddfw_total_route"></div>';
                var lddfw_last_address = '';
                // For each route, display summary information.
                for (var i = 0; i < lddfw_route.legs.length; i++) {
                    var lddfw_routeSegment = i + 1;
                    if (lddfw_last_address != lddfw_route.legs[i].start_address) {
                        lddfw_summaryPanel.innerHTML += '<div class="row lddfw_address"><div class="col-2 text-center" ><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker" class="svg-inline--fa fa-map-marker fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"></path></svg><span class="lddfw_point">' + lddfw_numtoletter(lddfw_routeSegment) + '</span></div><div class="col-10">' + lddfw_route.legs[i].start_address + '</div></div>';
                    }
                    lddfw_summaryPanel.innerHTML += '<div class="row lddfw_drive"><div class="col-2 text-center"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-v" style="width: 6px;" class="svg-inline--fa fa-ellipsis-v up fa-w-6" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path fill="currentColor" d="M96 184c39.8 0 72 32.2 72 72s-32.2 72-72 72-72-32.2-72-72 32.2-72 72-72zM24 80c0 39.8 32.2 72 72 72s72-32.2 72-72S135.8 8 96 8 24 40.2 24 80zm0 352c0 39.8 32.2 72 72 72s72-32.2 72-72-32.2-72-72-72-72 32.2-72 72z"></path></svg><br><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="car" class="svg-inline--fa fa-car fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M499.99 176h-59.87l-16.64-41.6C406.38 91.63 365.57 64 319.5 64h-127c-46.06 0-86.88 27.63-103.99 70.4L71.87 176H12.01C4.2 176-1.53 183.34.37 190.91l6 24C7.7 220.25 12.5 224 18.01 224h20.07C24.65 235.73 16 252.78 16 272v48c0 16.12 6.16 30.67 16 41.93V416c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32v-32h256v32c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32v-54.07c9.84-11.25 16-25.8 16-41.93v-48c0-19.22-8.65-36.27-22.07-48H494c5.51 0 10.31-3.75 11.64-9.09l6-24c1.89-7.57-3.84-14.91-11.65-14.91zm-352.06-17.83c7.29-18.22 24.94-30.17 44.57-30.17h127c19.63 0 37.28 11.95 44.57 30.17L384 208H128l19.93-49.83zM96 319.8c-19.2 0-32-12.76-32-31.9S76.8 256 96 256s48 28.71 48 47.85-28.8 15.95-48 15.95zm320 0c-19.2 0-48 3.19-48-15.95S396.8 256 416 256s32 12.76 32 31.9-12.8 31.9-32 31.9z"></path></svg><br><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-v" style="width: 6px;" class="svg-inline--fa down fa-ellipsis-v fa-w-6" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path fill="currentColor" d="M96 184c39.8 0 72 32.2 72 72s-32.2 72-72 72-72-32.2-72-72 32.2-72 72-72zM24 80c0 39.8 32.2 72 72 72s72-32.2 72-72S135.8 8 96 8 24 40.2 24 80zm0 352c0 39.8 32.2 72 72 72s72-32.2 72-72-32.2-72-72-72-72 32.2-72 72z"></path></svg> </div><div class="col-10"  ><b>' + lddfw_route.legs[i].duration.text + "</b><br>" + lddfw_route.legs[i].distance.text + '</div></div></div>';
                    lddfw_summaryPanel.innerHTML += '<div class="row lddfw_address"><div class="col-2 text-center"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker" class="svg-inline--fa fa-map-marker fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"></path></svg><span class="lddfw_point">' + lddfw_numtoletter((lddfw_routeSegment + 1) * 1) + '</span></div><div class="col-10">' + lddfw_route.legs[i].end_address + '</div></div>';
                    lddfw_last_address = lddfw_route.legs[i].end_address;
                }
                lddfw_computeTotalDistance(response);
            } else {
                window.alert('Directions request failed due to ' + status);
            }
        }
    );
}

function lddfw_readURL(input) {
    if (input.files && input.files[0]) {
        var lddfw_reader = new FileReader();
        lddfw_reader.onload = function(e) {
            lddfw_resizeImage(e.target.result, 640, 640).then((result) => {
                jQuery('#delivery_image_wrap').html("<span class='lddfw_helper'></span><img src='" + result + "'>");
                jQuery('#delivery_image').val(result);
            });
        }
        lddfw_reader.readAsDataURL(input.files[0]); // convert to base64 string
    }
}

function lddfw_resizeImage(base64Str, maxWidth = 1000, maxHeight = 1000) {
    return new Promise((resolve) => {
        let lddfw_img = new Image()
        lddfw_img.src = base64Str
        lddfw_img.onload = () => {
            let lddfw_image_canvas = document.createElement('canvas')
            const MAX_WIDTH = maxWidth
            const MAX_HEIGHT = maxHeight
            let lddfw_width = lddfw_img.width
            let lddfw_height = lddfw_img.height

            if (lddfw_width > lddfw_height) {
                if (lddfw_width > MAX_WIDTH) {
                    lddfw_height *= MAX_WIDTH / lddfw_width
                    lddfw_width = MAX_WIDTH
                }
            } else {
                if (lddfw_height > MAX_HEIGHT) {
                    lddfw_width *= MAX_HEIGHT / lddfw_height
                    lddfw_height = MAX_HEIGHT
                }
            }
            lddfw_image_canvas.width = lddfw_width
            lddfw_image_canvas.height = lddfw_height
            let lddfw_ctx = lddfw_image_canvas.getContext('2d')
            lddfw_ctx.drawImage(lddfw_img, 0, 0, lddfw_width, lddfw_height)
            resolve(lddfw_image_canvas.toDataURL())
        }
    })
}

function lddfw_resizeCanvas() {
    if (typeof lddfw_signaturePad === "undefined") {} else {
        if (lddfw_signaturePad.isEmpty()) {
            var lddfw_ratio = Math.max(window.devicePixelRatio || 1, 1);
            lddfw_canvas.width = lddfw_canvas.offsetWidth;
            lddfw_canvas.height = lddfw_canvas.offsetHeight;
            lddfw_canvas.getContext("2d").scale(1, 1);
            lddfw_signaturePad.clear();
        }
    }
}

if (jQuery("#signature-pad").length) {
    var lddfw_canvas = document.getElementById('signature-pad');
    var lddfw_signaturePad = new SignaturePad(lddfw_canvas, {
        backgroundColor: '#ffffff'
    });

    jQuery(".signature-clear").click(function() {
        jQuery("#signature-image").html("");
        lddfw_signaturePad.clear();
        lddfw_resizeCanvas();
        return false;
    });

    window.onresize = lddfw_resizeCanvas;
    lddfw_resizeCanvas();
}

jQuery("#upload_image").change(function() {
    lddfw_readURL(this);
});


jQuery(".lddfw_upload_image").change(function(e) {
    var $this = jQuery(this);
    if (this.files && this.files[0]) {
        var lddfw_reader = new FileReader();
        lddfw_reader.onload = function(e) {
            lddfw_resizeImage(e.target.result, 640, 640).then((result) => {
                $this.parents(".upload_image_form").find(".upload_image_wrap").html("<span class='lddfw_helper'></span><img src='" + result + "'>");
                $this.parent().find(".lddfw_image_input").val(result);
            });
        }
        lddfw_reader.readAsDataURL(this.files[0]);
    }


});

jQuery("#lddfw_start_delivery_btn").on("click", function() {
    var lddfw_start_delivery_loading_btn = jQuery("#lddfw_start_delivery_loading_btn");
    jQuery("#lddfw_start_delivery_btn").hide();
    lddfw_start_delivery_loading_btn.show();
    var lddfw_orderid = jQuery(this).attr("order_id");
    jQuery.ajax({
        type: "POST",
        url: lddfw_ajax_url,
        data: {
            action: 'lddfw_ajax',
            lddfw_service: 'lddfw_start_delivery',
            lddfw_driver_id: lddfw_driver_id,
            lddfw_orderid: lddfw_orderid,
            lddfw_wpnonce: lddfw_nonce.nonce,
            lddfw_data_type: 'json'
        },
        success: function(data) {
            var lddfw_json = JSON.parse(data);
            if (lddfw_json["result"] == "1") {
                var duration_text = lddfw_json["duration_text"];
                var distance_text = lddfw_json["distance_text"];
                jQuery("#driver_duration_section").show();
                jQuery("#driver_duration").html(duration_text);
                jQuery("#lddfw_start_delivery_notice_duration").html(duration_text);
                jQuery("#lddfw_start_delivery_notice_distance").html(distance_text);
            }
        },
        error: function(request, status, error) {}
    }).done(function() {
        jQuery(".lddfw_delivery_start_button").hide();
        lddfw_start_delivery_loading_btn.hide();
        jQuery("#lddfw_start_delivery_notice").show();
        jQuery("#lddfw_start_delivery_notice").delay(4000).hide(0);
        jQuery(".lddfw_order_status_buttons").show(0);
    });


    // jQuery(".lddfw_order_status_buttons").show();
    return false;
});


/* </fs_premium_only> */


jQuery("#cancel_password_button").on("click", function() {
    jQuery("#lddfw_password_holder").hide();
    jQuery("#lddfw_password").val("");
});

jQuery("#new_password_button").on("click", function() {
    jQuery("#lddfw_password_holder").show();
    jQuery("#lddfw_password").val(Math.random().toString(36).slice(2));
});

jQuery("#billing_state_select").on("change", function() {
    jQuery("#billing_state_input").val(jQuery(this).val());
});
jQuery("#billing_country").on("change", function() {
    if (jQuery(this).val() == "US") {
        jQuery("#billing_state_select").show();
        jQuery("#billing_state_input").hide();
    } else {
        jQuery("#billing_state_input").show();
        jQuery("#billing_state_select").hide();
    }
});
if (jQuery("#billing_country").length) {
    jQuery("#billing_country").trigger("change");
}

function scrolltoelement(element) {
    jQuery('html, body').animate({
        scrollTop: element.offset().top - 100
    }, 1000);
}

jQuery(".lddfw_form").validate({
    submitHandler: function(form) {
        var lddfw_form = jQuery(form);
        var lddfw_loading_btn = lddfw_form.find(".lddfw_loading_btn")
        var lddfw_submit_btn = lddfw_form.find(".lddfw_submit_btn")
        var lddfw_alert_wrap = lddfw_form.find(".lddfw_alert_wrap");
        var lddfw_service = lddfw_form.attr("service");
        lddfw_submit_btn.hide();
        lddfw_loading_btn.show();
        lddfw_alert_wrap.html("");
        jQuery.ajax({
            type: "POST",
            url: lddfw_ajax_url,
            data: lddfw_form.serialize() + '&action=lddfw_ajax&lddfw_service=' + lddfw_service + '&lddfw_data_type=json',
            success: function(data) {
                try {
                    var lddfw_json = JSON.parse(data);
                    if (lddfw_json["result"] == "0") {
                        lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                        scrolltoelement(lddfw_alert_wrap);
                    }
                    if (lddfw_json["result"] == "1") {
                        var lddfw_hide_on_success = lddfw_form.find(".lddfw_hide_on_success");
                        if (lddfw_hide_on_success.length) {
                            lddfw_hide_on_success.replaceWith("");
                        }
                        lddfw_alert_wrap.html("<div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">" + lddfw_json["error"] + "</div>");
                        lddfw_submit_btn.show();
                        lddfw_loading_btn.hide();
                        if (lddfw_json["nonce"] != "") {
                            lddfw_form.find("#lddfw_wpnonce").val(lddfw_json["nonce"]);
                            lddfw_nonce = { "nonce": lddfw_json["nonce"] };
                        }
                        scrolltoelement(lddfw_alert_wrap);
                    }

                } catch (e) {
                    lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + e + "</div>");
                    lddfw_submit_btn.show();
                    lddfw_loading_btn.hide();
                    scrolltoelement(lddfw_alert_wrap);
                }
            },
            error: function(request, status, error) {
                lddfw_alert_wrap.html("<div class=\"alert alert-warning alert-dismissible fade show\" role=\"alert\">" + e + "</div>");
                lddfw_submit_btn.show();
                lddfw_loading_btn.hide();
                scrolltoelement(lddfw_alert_wrap);
            }
        });

        return false;
    }
});


jQuery("#lddfw_driver_add_signature_btn").click(function() {

    jQuery(".signature-wrapper").show();
    /* <fs_premium_only> */
    lddfw_resizeCanvas();
    /* </fs_premium_only> */
});

jQuery(".delivery_proof_bar a").click(function() {

    var $lddfw_this = jQuery(this);
    var $lddfw_screen_class = $lddfw_this.attr("href")
    $lddfw_this.parents(".delivery_proof_bar").find("a").removeClass("active");
    $lddfw_this.addClass("active");
    $lddfw_this.parents(".lddfw_lightbox").find(".screen_wrap").hide();
    $lddfw_this.parents(".lddfw_lightbox").find("." + $lddfw_screen_class).show();

    /* <fs_premium_only> */
    lddfw_resizeCanvas();
    /* </fs_premium_only> */
    return false;
});

//switch lazyload
jQuery("img.lazyload").each(function() {
    var $lddfw_src = jQuery(this).attr("data-src");
    jQuery(this).attr("src", $lddfw_src);
});
jQuery("iframe.lazyload").each(function() {
    var $lddfw_src = jQuery(this).attr("data-src");
    jQuery(this).attr("src", $lddfw_src);
});

/* <fs_premium_only> */
jQuery(".submenu-item").click(function() {
    var lddfw_submenu = '#' + jQuery(this).attr("data");
    if (jQuery(lddfw_submenu).hasClass("active")) {
        jQuery(this).removeClass("active");
        jQuery(lddfw_submenu).removeClass("active");
    } else {
        jQuery(lddfw_submenu).addClass("active");
        jQuery(this).addClass("active");
    }
});
/* </fs_premium_only> */