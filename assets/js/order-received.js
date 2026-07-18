function getStatus() {
	jQuery.ajax({
		type: 'POST',
		url: ecpOrderReceivedData.adminAjaxUrl + window.location.search,
		data: [{ name: 'action', value: 'get_payment_status' }, { name: 'nonce', value: ecpOrderReceivedData.nonce}],
		dataType: 'json',
		success: function(response) {
			if (response.callbackReceived) {
				if (response.isSuccessStatus ^ ecpOrderReceivedData.isCurrentPageFailedStatus) {
					window.ecpLoader.hide()
					return
				} else {
					location.reload()
				}
			}
			window.ecpLoader.show()
			setTimeout(getStatus, 400)
		},
		error: function() {
			console.log('Error while getting order complete status')
		},
	})
}

document.addEventListener('DOMContentLoaded', function() {
	if (typeof ecpOrderReceivedData !== 'undefined') {
		getStatus()
	}
});
