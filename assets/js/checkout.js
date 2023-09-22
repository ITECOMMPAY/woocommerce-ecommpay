var WidgetInstance;
jQuery(document).ready(function () {

    // update embedded iframe when updating cart (taxes, delivery, etc)
    jQuery(document.body).on('updated_checkout', function () {
        resetEmbeddedIframe();
        loadEmbeddedIframe();
    });
    jQuery(document.body).append('<div id="ecommpay-overlay-loader" class="blockUI blockOverlay ecommpay-loader-overlay" style="display: none;"></div>');
    var isEmbeddedMode = false;
    var targetForm;
    var loader = jQuery('#ecommpay-loader');
    var isPaymentRunning = false;
    var paramsForEmbeddedPP = false;
    var clarificationRunning = false;


    function resetEmbeddedIframe() {
        paramsForEmbeddedPP = false;
        jQuery("#ecommpay-iframe-embedded").hide().empty();
        jQuery('#ecommpay-loader-embedded').show();
        getParamsForCreateEmbeddedPP();
    }

    function loadEmbeddedIframe() {
        var embeddedIframeDivOld = null;
        var intervalId = setInterval(function () {
            var embeddedIframeDiv = jQuery("#ecommpay-iframe-embedded");
            if (embeddedIframeDiv.length === 1 && embeddedIframeDiv.is(embeddedIframeDivOld) && paramsForEmbeddedPP) {
                isEmbeddedMode = true;
                loader = jQuery('#ecommpay-loader-embedded');
                showIFrame(paramsForEmbeddedPP);
                clearInterval(intervalId);
                jQuery('input[name="payment_method"]').change(function () {
                    if (isEcommpayCardPayment()) {
                        jQuery(window).trigger('resize');
                    }
                });
            }
            embeddedIframeDivOld = embeddedIframeDiv;
            }, 200);
    }

    if (window.location.href.includes('pay_for_order=true')) {
        loadEmbeddedIframe();
    }

    getParamsForCreateEmbeddedPP();

    function isEcommpayPayment() {
        return jQuery("input[name='payment_method']:checked").val().slice(0, 8) === 'ecommpay';
    }

    function isEcommpayCardPayment() {
        return jQuery("input[name='payment_method']:checked").val() === 'ecommpay-card';
    }

    function submit_error(error_message) {
        jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
        targetForm.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>');
        targetForm.removeClass('processing').unblock();
        targetForm.find('.input-text, select, input:checkbox').trigger('validate').blur();
        scroll_to_notices();
        jQuery(document.body).trigger('checkout_error');
    }

    function clear_error() {
        jQuery('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
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
                if (isEmbeddedMode && isEcommpayCardPayment()) {
                    processOrderWithEmbeddedIframe(result)
                    break;
                }
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
        targetForm = jQuery(e.target.form)
        e.preventDefault();
        if (isEmbeddedMode && isEcommpayCardPayment()) {
            startEmbeddedIframeFlow();
            return;
        }

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
        if (isEmbeddedMode) {
            url.onEnterKeyPressed = onEnterKeyPressed;
            url.onPaymentSent = onPaymentSent;
            url.onShowClarificationPage = onShowClarificationPage;
            url.onEmbeddedModeRedirect3dsParentPage = onEmbeddedModeRedirect3dsParentPage;
            url.onEmbeddedModeCheckValidationResponse = onEmbeddedModeCheckValidationResponse;
        }
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
        if (!isEmbeddedMode) {
            jQuery('#woocommerce_ecommpay_checkout_page').hide();
        }
        scroll_to_notices();
        show(url);
    }

    // Called sometime after postMessage from iFrame is called
    function processorIFrame(event) {
        var data = parseMessage(event.data);

        if (data.message === 'epframe.loaded') {
            loader.hide();
            if (isEmbeddedMode) {
                jQuery('#ecommpay-iframe-embedded').show();
            } else {
                jQuery('#ecommpay-iframe').show();
            }
        }

        if (data.message === 'epframe.exit' || data.message === 'epframe.destroy') {
            event.preventDefault();
            back();
        }
    }

    // Called sometime after postMessage from iFrame is called
    function processorPopup(event) {
        var d = parseMessage(event.data);

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
            success: function (result) {
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

    /* Embedded iFrame flow */

    // Step1 . On page load - init payment form with minimum params
    function getParamsForCreateEmbeddedPP() {
        var href = window.location.href.split('?');
        var data = [{
            'name': 'action',
            'value': 'get_data_for_payment_form'
        }];
        var query_string = href[1] === undefined ? '' : href[1];

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
            success: function (result) {
                paramsForEmbeddedPP = result;
            },
            error: function (jqXHR, textStatus, errorThrown) {
                submit_error('<div class="woocommerce-error">' + errorThrown + '</div>');
            }
        });
    }

    // Step 2. Button "Place order" - onclick, send message to iframe, call form validation
    function startEmbeddedIframeFlow() {
        isPaymentRunning = true;
        window.addEventListener("message", function (e) {
            var d = parseMessage(e.data);
            if (d.message === 'epframe.exit' || d.message === 'epframe.destroy') {
                e.preventDefault();
                back();
            }
        }, false);

        const data = [
            {'name': 'action', 'value': 'check_cart_amount'},
            {'name': 'amount', 'value': paramsForEmbeddedPP.payment_amount}
        ]
        jQuery.ajax({
            type: 'POST',
            url: ECP.ajax_url,
            data: data,
            dataType: 'json',
            success: function (result) {
                if (result.amount_is_equal) {
                    window.postMessage("{\"message\":\"epframe.embedded_mode.check_validation\",\"from_another_domain\":true}");
                } else {
                    window.location.reload();
                }
            },
            error: (jqXHR, textStatus, errorThrown) => {alert(textStatus);}
        });
    }

    // Step3. Listen Answer from Iframe about form validation
    function onEmbeddedModeCheckValidationResponse(data) {
        if (!isPaymentRunning) {
            return;
        }
        if (!!data && Object.keys(data).length > 0) {
            var errors = [];
            var errorText = '';
            jQuery.each(data, function (key, value) {
                errors.push(value);
            });
            var errorsUnique = [...new Set(errors)]; //remove duplicated
            jQuery.each(errorsUnique, function (key, value) {
                errorText += value + '<br>';
            });
            submit_error('<div class="woocommerce-error">' + errorText + '</div>');
            isPaymentRunning = false;
        } else {
            clear_error();
            if (clarificationRunning) {
                submitClarification();
            } else {
                createWoocommerceOrder();
            }
        }
    }

    // Step 4. Create Wocommerce Order
    function createWoocommerceOrder() {
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
        data.push({
            'name': 'payment_id',
            'value': paramsForEmbeddedPP.payment_id
        });
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
    }

    // Step 5 send payment request via post message
    function processOrderWithEmbeddedIframe(result) {
        var redirect = result.redirect;
        redirect.frame_mode = 'iframe';
        redirect.payment_id = paramsForEmbeddedPP.payment_id;
        window.addEventListener("message", function (e) {
            e.preventDefault();
            var d = parseMessage(e.data);
            switch (d.message) {
                case 'epframe.payment.success':
                case 'epframe.card.verify.success':
                    if (redirect.redirect_success_enabled) {
                        hideOverlayLoader();
                        window.location.replace(redirect.redirect_success_url);
                    }
                    break;
                case 'epframe.payment.fail':
                case 'epframe.card.verify.fail':
                    if (redirect.redirect_fail_enabled) {
                        hideOverlayLoader();
                        window.location.replace(redirect.redirect_fail_url);
                    }
                    break;
            }
        }, false);
        var billingFields = [
            "billing_address", "billing_city", "billing_country", "billing_postal", "customer_first_name",
            "customer_last_name", "customer_phone", "customer_zip", "customer_address", "customer_city",
            "customer_country", "customer_email"
        ];
        var fieldsObject = {};
        Object.keys(redirect).forEach(key => {
            var name = key;
            if (billingFields.includes(key)) {
                name = "BillingInfo[" + name + "]";
            }
            fieldsObject[name] = redirect[key];
            if (key === 'billing_country') {
                fieldsObject["BillingInfo[country]"] = redirect[key];
            }
        });

        var message = {"message": "epframe.embedded_mode.submit"};
        message.fields = fieldsObject;
        message.from_another_domain = true;
        window.postMessage(JSON.stringify(message));
    }

    function onEnterKeyPressed() {
        jQuery('#place_order').click();
    }

    function onEmbeddedModeRedirect3dsParentPage(data) {
        var form = document.createElement('form');
        form.setAttribute('method', data.method);
        form.setAttribute('action', data.url);
        form.setAttribute('style', 'display:none;');
        form.setAttribute('name', '3dsForm');
        for (let k in data.body) {
            const input = document.createElement('input');
            input.name = k;
            input.value = data.body[k];
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }

    function onPaymentSent() {
        jQuery('#ecommpay-overlay-loader').show();
    }

    function hideOverlayLoader() {
        jQuery('#ecommpay-overlay-loader').hide();
    }

    function parseMessage(message) {
        try {
            var parsed = JSON.parse(message);
            if (!!parsed.message && !!parsed.data) {
                return parsed;
            }
        } catch (e) {
        }
        return false;
    }

    function onShowClarificationPage() {
        clarificationRunning = true;
        hideOverlayLoader();
    }

    function submitClarification() {
        var message = {"message": "epframe.embedded_mode.submit"};
        message.fields = {};
        message.from_another_domain = true;
        window.postMessage(JSON.stringify(message));
        window.addEventListener("message", (e) => {
            var d = parseMessage(e.data);
            if (d.message === 'epframe.submit_clarification_form') {
                jQuery('#ecommpay-overlay-loader').show();
            }
        }, false);
    }
});
