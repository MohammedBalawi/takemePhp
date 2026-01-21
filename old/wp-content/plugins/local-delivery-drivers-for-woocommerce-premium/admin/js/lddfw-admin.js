jQuery(document).ready(
    function($) {

        $("body").on("click", ".lddfw_premium_close", function() {
            $(this).parent().hide();
            return false;
        });
        $("body").on("click", ".lddfw_star_button", function() {
            if ($(this).next().is(":visible")) {
                $(this).next().hide();
            } else {
                $(".lddfw_premium_feature_note").hide();
                $(this).next().show();
            }
            return false;
        });

        function lddfw_dates_range() {
            var $lddfw_this = $("#lddfw_dates_range");
            if ($lddfw_this.val() == "custom") {
                $("#lddfw_dates_custom_range").show();
            } else {
                var lddfw_fromdate = $('option:selected', $lddfw_this).attr('fromdate');
                var lddfw_todate = $('option:selected', $lddfw_this).attr('todate');
                $("#lddfw_dates_custom_range").hide();
                $("#lddfw_dates_range_from").val(lddfw_fromdate);
                $("#lddfw_dates_range_to").val(lddfw_todate);
            }
        }

        $("#lddfw_dates_range").change(
            function() {
                lddfw_dates_range()
            }
        );

        if ($("#lddfw_dates_range").length) {
            lddfw_dates_range();
        }

        /* <fs_premium_only> */

        function lddfw_driver_commission_type() {
            $("#lddfw_driver_commission_symbol_currency").hide();
            $("#lddfw_driver_commission_symbol_percentage").hide();
            var $lddfw_val = $("#lddfw_driver_commission_type").val();
            if ($lddfw_val == "") {
                $("#lddfw_driver_commission_value_wrap").hide();
                $("#lddfw_driver_commission_value").val("");
            } else {
                $("#lddfw_driver_commission_value_wrap").show();
                if ($lddfw_val == "fixed" || $lddfw_val == "distance") {
                    $("#lddfw_driver_commission_symbol_currency").show();
                } else {
                    $("#lddfw_driver_commission_symbol_percentage").show();
                }
            }
        }

        $("#lddfw_driver_commission_type").change(
            function() {
                lddfw_driver_commission_type()
            }
        );

        if ($("#lddfw_driver_commission_type").length) {
            lddfw_driver_commission_type();
        }

        $(".lddfw_media_delete").click(
            function() {
                var lddfw_object_id = $(this).attr("data");
                $("#" + lddfw_object_id).val("");
                $("#" + lddfw_object_id + "_preview").html("");
            }
        );

        $('.lddfw_media_manager').click(
            function(e) {
                var lddfw_object_id = $(this).attr("data");
                e.preventDefault();
                var lddfw_image_frame;
                if (lddfw_image_frame) {
                    lddfw_image_frame.open();
                }
                // Define image_frame as wp.media object
                lddfw_image_frame = wp.media({
                    title: 'Select Media',
                    multiple: false,
                    library: {
                        type: 'image',
                    }
                });

                lddfw_image_frame.on(
                    'close',
                    function() {
                        var lddfw_selection = lddfw_image_frame.state().get('selection');
                        var lddfw_gallery_ids = new Array();
                        var lddfw_index = 0;
                        lddfw_selection.each(
                            function(attachment) {
                                lddfw_gallery_ids[lddfw_index] = attachment['id'];
                                lddfw_index++;
                            }
                        );
                        var lddfw_ids = lddfw_gallery_ids.join(",");
                        jQuery('input#' + lddfw_object_id).val(lddfw_ids);
                        lddfw_refresh_image(lddfw_ids, lddfw_object_id);
                    }
                );

                lddfw_image_frame.on(
                    'open',
                    function() {
                        var lddfw_selection = lddfw_image_frame.state().get('selection');
                        var lddfw_ids = jQuery('input#' + lddfw_object_id).val().split(',');
                        lddfw_ids.forEach(
                            function(id) {
                                var lddfw_attachment = wp.media.attachment(id);
                                lddfw_attachment.fetch();
                                lddfw_selection.add(lddfw_attachment ? [lddfw_attachment] : []);
                            }
                        );

                    }
                );

                lddfw_image_frame.open();
            }
        );

        if ($(".lddfw-color-picker").length) {
            $(".lddfw-color-picker").wpColorPicker();
        }
        if ($(".lddfw-datepicker").length) {
            $(".lddfw-datepicker").datepicker({ dateFormat: "yy-mm-dd" });
        }

        $(".lddfw_account_icon").click(
            function() {
                var lddfw_driver_id = $(this).attr("driver_id");
                if ($(this).hasClass("lddfw_active")) {
                    $(this).removeClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-off'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_account_status',
                            lddfw_account_status: "0",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                } else {
                    $(this).addClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-on'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_account_status',
                            lddfw_account_status: "1",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                }
                return false;
            }
        );
        $(".lddfw_availability_icon").click(
            function() {
                var lddfw_driver_id = $(this).attr("driver_id");
                if ($(this).hasClass("lddfw_active")) {
                    $(this).removeClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-off'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_availability',
                            lddfw_availability: "0",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                } else {
                    $(this).addClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-on'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_availability',
                            lddfw_availability: "1",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                }
                lddfw_counters();
                return false;
            }
        );
        $(".lddfw_claim_icon").click(
            function() {
                var lddfw_driver_id = $(this).attr("driver_id");
                if ($(this).hasClass("lddfw_active")) {
                    $(this).removeClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-off'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_claim_permission',
                            lddfw_claim: "0",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                } else {
                    $(this).addClass("lddfw_active");
                    $(this).html("<i class='lddfw-toggle-on'></i>");
                    jQuery.post(
                        lddfw_ajax.ajaxurl, {
                            action: 'lddfw_ajax',
                            lddfw_service: 'lddfw_claim_permission',
                            lddfw_claim: "1",
                            lddfw_driver_id: lddfw_driver_id,
                            lddfw_wpnonce: lddfw_nonce.nonce,
                            lddfw_data_type: 'html'
                        }
                    );
                }
                lddfw_counters();
                return false;
            }
        );

        /* </fs_premium_only> */

        function checkbox_toggle(element) {
            if (!element.is(':checked')) {
                element.parent().next().hide();
            } else {
                element.parent().next().show();
            }

        }

        $(".checkbox_toggle input").click(
            function() {
                checkbox_toggle($(this))

            }
        );
        $(".checkbox_toggle input").each(
            function() {
                checkbox_toggle($(this))
            }
        );

        $(".lddfw_copy_template_to_textarea").click(
            function() {
                var textarea_id = $(this).parent().parent().find("textarea").attr("id");

                var text = $(this).attr("data");
                $("#" + textarea_id).val(text);

                return false;
            }
        );

        $(".lddfw_copy_tags_to_textarea a").click(
            function() {
                var textarea_id = $(this).parent().attr("data-textarea");
                var text = $("#" + textarea_id).val() + $(this).attr("data");
                $("#" + textarea_id).val(text);

                return false;
            }
        );

        /* <fs_premium_only> */
        $(".post-type-shop_order #bulk-action-selector-top").change(
            function() {

                if ($(this).val() == "assign_a_driver") {
                    var $this = $(this);
                    if ($("#lddfw_driverid_lddfw_action").length) {
                        $("#lddfw_driverid_lddfw_action").show();
                    } else {
                        $.post(
                            lddfw_ajax.ajaxurl, {
                                action: 'lddfw_ajax',
                                lddfw_service: 'lddfw_get_drivers_list',
                                lddfw_obj_id: 'lddfw_action',
                                lddfw_wpnonce: lddfw_nonce.nonce,
                            },
                            function(data) {
                                $(data).insertAfter($this);
                            }
                        );
                    }
                } else {
                    $("#lddfw_driverid_lddfw_action").hide();
                }
            }
        );

        $(".post-type-shop_order #bulk-action-selector-bottom").change(
            function() {
                if ($(this).val() == "assign_a_driver") {
                    var $this = $(this);
                    if ($("#lddfw_driverid_lddfw_action2").length) {
                        $("#lddfw_driverid_lddfw_action2").show();
                    } else {
                        $.post(
                            lddfw_ajax.ajaxurl, {
                                action: 'lddfw_ajax',
                                lddfw_service: 'lddfw_get_drivers_list',
                                lddfw_obj_id: 'lddfw_action2',
                                lddfw_wpnonce: lddfw_nonce.nonce,
                            },
                            function(data) {
                                $(data).insertAfter($this);
                            }
                        );
                    }
                } else {
                    $("#lddfw_driverid_lddfw_action2").hide();
                }
            }
        );
        $("#lddfw_custom_fields_new").click(
            function() {
                $("#lddfw_custom_fields_raw").clone().appendTo("#lddfw_custom_fields_table");
                return false;
            }
        );
        /* </fs_premium_only> */

    }
);

/* <fs_premium_only> */
function lddfw_counters() {
    var lddfw_unavailable_counter = jQuery(".lddfw_availability_icon").not('.lddfw_active').length;
    var lddfw_available_counter = jQuery(".lddfw_availability_icon.lddfw_active").length;
    var lddfw_unclaim_counter = jQuery(".lddfw_claim_icon").not('.lddfw_active').length;
    var lddfw_claim_counter = jQuery(".lddfw_claim_icon.lddfw_active").length;

    jQuery("#lddfw_available_counter").html(lddfw_available_counter);
    jQuery("#lddfw_claim_counter").html(lddfw_claim_counter);

    jQuery("#lddfw_unavailable_counter").html(lddfw_unavailable_counter);
    jQuery("#lddfw_unclaim_counter").html(lddfw_unclaim_counter);
}

// Ajax request to refresh the image preview
function lddfw_refresh_image(the_id, div_id) {
    var data = {
        action: 'lddfw_ajax',
        lddfw_service: 'lddfw_set_image',
        lddfw_image_id: the_id,
        lddfw_wpnonce: lddfw_nonce.nonce,
    };
    jQuery.post(
        ajaxurl,
        data,
        function(response) {

            if (response.success === true) {
                jQuery('#' + div_id + '_preview').html(response.data.image);
            }
        }
    );
}
/* </fs_premium_only> */