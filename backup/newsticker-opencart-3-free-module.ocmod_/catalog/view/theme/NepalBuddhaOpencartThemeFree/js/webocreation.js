$(document).ready(function () {
	$('#searchicon').on('click', function (event) {
		$('#search').addClass('open');
		$('#search  > input[type="search"]').focus();
	});
	$('#search, #search button.close').on('click keyup', function (event) {
		if (event.target == this || event.target.className == 'close' || event.keyCode == 27) {
			$(this).removeClass('open');
		}
	});
	/* Search */
	$('#search input[name=\'search\']').parent().find('button').on('click', function () {
		var url = $('base').attr('href') + 'index.php?route=product/search';

		var value = $('#search input[name=\'search\']').val();

		if (value) {
			url += '&search=' + encodeURIComponent(value);
		}

		location = url;
	});

	$('#search input[name=\'search\']').on('keydown', function (e) {
		if (e.keyCode == 13) {
			$('#search input[name=\'search\']').parent().find('button').trigger('click');
		}
	});
});