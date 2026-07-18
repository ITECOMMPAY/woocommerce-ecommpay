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

window.ecpLoader = {
	show: function () {
		jQuery('#ecommpay-overlay-loader').show()
	},
	hide: function () {
		jQuery('#ecommpay-overlay-loader').hide()
	}
}
