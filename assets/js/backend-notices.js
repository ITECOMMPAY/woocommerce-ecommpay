;(function ($, config) {
  $(function () {
    $(document.body).on("click", ".wc-ecp-notice .notice-dismiss", function () {
      $.post(config.flush)
    })
  })
})(jQuery, window.wcEcpBackendNotices || {})
