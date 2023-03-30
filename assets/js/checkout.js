jQuery(document).ready(function () {

    var targetForm;
    var loader = jQuery('#ecommpay-loader');

    function isEcommpayPayment() {
        return jQuery("input[name='payment_method']:checked").val().slice(0, 8) === 'ecommpay';
    }

    function submit_error(error_message) {
        jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
        targetForm.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>');
        targetForm.removeClass('processing').unblock();
        targetForm.find('.input-text, select, input:checkbox').trigger('validate').blur();
        scroll_to_notices();
        jQuery(document.body).trigger('checkout_error');
    }

    function scroll_to_notices() {
        var scrollElement = jQuery('.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout'),
            isSmoothScrollSupported = 'scrollBehavior' in document.documentElement.style;

        if (!scrollElement.length) {
            scrollElement = loader;
        }

        if (scrollElement.length) {
            if (isSmoothScrollSupported) {
                scrollElement[0].scrollIntoView({
                    behavior: 'smooth'
                });
            } else {
                jQuery('html, body').animate({
                    scrollTop: (scrollElement.offset().top - 100)
                }, 1000);
            }
        }
    }

    function show_error(result, message) {
        console.error(message);

        if (true === result.reload) {
            window.location.reload();
            return;
        }

        if (true === result.refresh) {
            jQuery(document.body).trigger('update_checkout');
        }

        if (result.messages) {
            submit_error(result.messages);
        } else {
            submit_error('<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error + '</div>');
        }
    }

    function success(result) {
        switch (result.result) {
            case 'success':
                ECP.order_id = result.order_id;
                switch (result.redirect.frame_mode) {
                    case 'iframe':
                        showIFrame(result.redirect);
                        break;
                    case 'popup':
                        showPopup(result.redirect);
                        break;
                    default:
                        redirect(result.redirect);
                        break;
                }
                break;
            case 'failure':
                show_error(result, 'Result failure');
                break;
            default:
                show_error(result, 'Invalid response');
        }
    }

    // Create order via AJAX in case of "popup" or "iframe" mode
    jQuery('body').on('click', '#place_order', function (e) {
        if (!isEcommpayPayment()) {
            return;
        }

        e.preventDefault();
        targetForm = jQuery(e.target.form)

        var href = window.location.href.split('?');
        var data = targetForm.serializeArray();
        var query_string = href[1] === undefined ? '' : href[1];

        data.push({
            'name': 'action',
            'value': 'ecommpay_process'
        });

        if (ECP.order_id > 0) {
            data.push({
                'name': 'order_id',
                'value': ECP.order_id
            });
        }

        jQuery.ajax({
            type: 'POST',
            url: ECP.ajax_url + '?' + query_string,
            data: data,
            dataType: 'json',
            success: success,
            error: function (jqXHR, textStatus, errorThrown) {
                submit_error('<div class="woocommerce-error">' + errorThrown + '</div>');
            }
        });
    });

    function show(url) {
        EPayWidget.run(url, 'post');
    }

    function showPopup(url) {
        window.addEventListener("message", processorPopup, false);
        show(url)
    }

    function showIFrame(url) {
        window.addEventListener("message", processorIFrame, false);

        // EPayWidget.registerPostListener = frameLoaded;
        loader.show();
        jQuery('#woocommerce_ecommpay_checkout_page').hide();
        scroll_to_notices();
        show(url);
    }

    // Called sometime after postMessage from iFrame is called
    function processorIFrame(event) {
        // Do we trust the sender of this message?
        // if (event.origin !== ECP.origin_url)
        //     return;

        var data = JSON.parse(event.data);

        if (data.message === 'epframe.loaded') {
            loader.hide();
            jQuery('#ecommpay-iframe').show();
        }

        if (data.message === 'epframe.exit' || data.message === 'epframe.destroy') {
            event.preventDefault();
            back();
        }
    }

    // Called sometime after postMessage from iFrame is called
    function processorPopup(event) {
        // Do we trust the sender of this message?
        // if (event.origin !== ECP.origin_url)
        //     return;

        var d = JSON.parse(event.data);

        if (d.message === 'epframe.exit' || d.message === 'epframe.destroy') {
            event.preventDefault();
            back();
        }
    }

    function back() {
        var href = window.location.href.split('?');
        var query_string = href[1] === undefined ? '' : href[1];
        var data = [];

        data.push({
            'name': 'action',
            'value': 'ecommpay_break'
        });

        if (ECP.order_id > 0) {
            data.push({
                'name': 'order_id',
                'value': ECP.order_id
            });
        }

        jQuery.ajax({
            type: 'POST',
            url: ECP.ajax_url + '?' + query_string,
            data: data,
            dataType: 'json',
            success: function(result) {
                window.location.replace(result.redirect);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                submit_error('<div class="woocommerce-error">' + errorThrown + '</div>');
            }
        });
    }

    function redirect(url) {
        let form = jQuery('<form/>', {
            method: 'post',
            action: url.baseUrl + '/payment',
            style: {
                display: 'none',
            }
        });

        jQuery.each(url, function (key, value) {
            form.append(jQuery('<input/>', {
                type: 'hidden',
                name: key,
                value: value
            }));
        });

        jQuery(form).appendTo('body').submit();
    }
});