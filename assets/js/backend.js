;(function ($) {
  "use strict"

  ECP.prototype.init = function () {
    this.infoBox.on("click", "[data-action]", $.proxy(this.callAction, this))
  }

  ECP.prototype.callAction = function (e) {
    e.preventDefault()
    let target = $(e.target)
    let action = target.attr("data-action")

    if (typeof this[action] !== "undefined") {
      let message =
        target.attr("data-confirm") || "Are you sure you want to continue?"
      if (confirm(message)) {
        this[action]()
      }
    }
  }

  ECP.prototype.refresh = function () {
    this.request({
      ecommpay_action: "refresh",
    })
  }

  ECP.prototype.refund = function () {
    this.request({
      ecommpay_action: "refund",
    })
  }

  ECP.prototype.request = function (dataObject) {
    let that = this
    return $.ajax({
      type: "POST",
      url: ajaxurl,
      dataType: "json",
      data: $.extend(
        {},
        {
          action: "ecommpay_manual_transaction_actions",
          post: this.postID.val(),
        },
        dataObject
      ),
      beforeSend: $.proxy(this.showLoader, this, true),
      success: function () {
        $.get(window.location.href, function (data) {
          let newData = $(data)
            .find("#" + that.actionBox.attr("id") + " .inside")
            .html()
          that.actionBox.find(".inside").html(newData)
          newData = $(data)
            .find("#" + that.infoBox.attr("id") + " .inside")
            .html()
          that.infoBox.find(".inside").html(newData)
          that.showLoader(false)
        })
      },
      error: function (jqXHR) {
        alert(jqXHR.responseText)
        that.showLoader(false)
      },
    })
  }

  ECP.prototype.showLoader = function (e, show) {
    if (show) {
      this.actionBox.append(this.loaderBox)
      this.infoBox.append(this.loaderBox)
    } else {
      this.actionBox.find(this.loaderBox).remove()
      this.infoBox.find(this.loaderBox).remove()
    }
  }

  // DOM ready
  $(function () {
    new ECP().init()

    function ecpInsertAjaxResponseMessage(response) {
      if (response.hasOwnProperty("status") && response.status === "success") {
        let message = $(
          '<div id="message" class="updated"><p>' +
            response.message +
            "</p></div>"
        )
        message.hide()
        message.insertBefore($("#wc-ecp_wiki"), null)
        message.fadeIn("fast", function () {
          setTimeout(function () {
            message.fadeOut("fast", function () {
              message.remove()
            })
          }, 5000)
        })
      }
    }

    let emptyLogsButton = $("#wc-ecp_logs_clear")
    emptyLogsButton.on("click", function (e) {
      e.preventDefault()
      emptyLogsButton.prop("disabled", true)
      $.getJSON(
        ajaxurl,
        { action: "ecommpay_empty_logs" },
        function (response) {
          ecpInsertAjaxResponseMessage(response)
          emptyLogsButton.prop("disabled", false)
        }
      )
    })

    let flushCacheButton = $("#wc-ecp_flush_cache")
    flushCacheButton.on("click", function (e) {
      e.preventDefault()
      flushCacheButton.prop("disabled", true)
      $.getJSON(
        ajaxurl,
        { action: "ecommpay_flush_cache" },
        function (response) {
          ecpInsertAjaxResponseMessage(response)
          flushCacheButton.prop("disabled", false)
        }
      )
    })
  })

  function ECP() {
    this.actionBox = $("#ecommpay-payment-actions")
    this.infoBox = $("#ecommpay-payment-info")
    this.postID = $("#post_ID")
    this.loaderBox = $(
      '<div class="blockUI blockOverlay" style="z-index: 1000; border: medium none; margin: 0; padding: 0; width: 100%; height: 100%; top: 0; left: 0; background: rgb(255, 255, 255) none repeat scroll 0 0; opacity: 0.6; cursor: wait; position: absolute;"></div>'
    )
  }
})(jQuery)
