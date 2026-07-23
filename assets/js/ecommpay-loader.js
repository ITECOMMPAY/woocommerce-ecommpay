document.addEventListener('DOMContentLoaded', function() {
	jQuery(document.body).append(`
		<div id="ecommpay-overlay-loader" class="ecommpay-loader-overlay" style="display: none;">
			<div class="lds-ecommpay">
				<div></div>
				<div></div>
				<div></div>
			</div>
		</div>
	`)
})


if (window.ECP === undefined) {
	window.ECP = {}
}

window.ECP.loader = {
	show: function () {
		jQuery('#ecommpay-overlay-loader').show()
	},
	hide: function () {
		jQuery('#ecommpay-overlay-loader').hide()
	}
}
