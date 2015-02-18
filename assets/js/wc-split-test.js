jQuery(document).ready(function () {

	var $ = jQuery;

	$("#wc_title_test_list a.delete-title-test").click(function () {

		if (confirm("Are you sure you want to delete this item ?")) {

			var id = $(this).data("id");

			var that = this;

			var data = {
				'action': 'wc_title_test_delete',
				'id': id
			};

			$(this).hide().after($('<img src="/wp-admin/images/loading.gif">'));

			$.post(ajaxurl, data, function () {

				$(that).parent().parent().fadeOut(300, function () {
					$(this).remove();
				});

			});
		}

		return false;

	});


});