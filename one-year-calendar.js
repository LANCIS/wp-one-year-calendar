jQuery(function($) {
	$('.lancis_calendar_date').click(function () {
		$('#lancis_calendar_item_date').val($(this).data('date'))
		var details = $(this).data('details') ? $(this).data('details') : ''
		$('#lancis_calendar_item_value').val(details).focus()
	})
})

