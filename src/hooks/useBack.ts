import { useCallback } from '@wordpress/element'

const useBack = () => {
  const back = useCallback(() => {
    const data: { name: string; value: string }[] = []

    data.push({
      name: 'action',
      value: 'ecommpay_break',
    })

    if (window.ECP.order_id > 0) {
      data.push({
        name: 'order_id',
        value: window.ECP.order_id,
      })
    }

    window.jQuery.ajax({
      type: 'POST',
      url: window.ECP.ajax_url + '?' + window.location.href.split('?')[1],
      data,
      dataType: 'json',
      success: function (result) {
        console.log(result)
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(jqXHR, textStatus, errorThrown)
      },
    })
  }, [])

  return { back }
}

export default useBack
