(function ($) {
	'use strict';

	$(function () {
		var frame;
		var $input = $('#mks-core-badge-attachment');
		var $preview = $('#mks-core-badge-preview');
		var $remove = $('#mks-core-badge-remove');

		$('.mks-core-color-picker').wpColorPicker();
		$('#mks-core-badge-select').on('click', function (e) {
			e.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: 'Выберите изображение бейджа',
				button: { text: 'Использовать' },
				multiple: false
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();

				$input.val(attachment.id);
				$preview.attr('src', attachment.url).show();
				$remove.show();
			});

			frame.open();
		});

		$remove.on('click', function (e) {
			e.preventDefault();

			$input.val('');
			$preview.attr('src', '').hide();
			$remove.hide();
		});

		var $search = $('#mks-core-badge-cats-search');
		var $cats = $('#mks-core-badge-cats');
		var $selected = $('#mks-core-badge-selected');

		function renderSelected() {
			$selected.empty();

			$cats.find('input[type="checkbox"]:checked').each(function () {
				var $checkbox = $(this);
				var $chip = $('<span class="mks-badge-chip"></span>').text($checkbox.parent('label').text().trim());

				$('<button type="button" aria-label="Убрать">&times;</button>')
					.on('click', function () {
						$checkbox.prop('checked', false).trigger('change');
					})
					.appendTo($chip);

				$selected.append($chip);
			});
		}

		$cats.on('change', 'input[type="checkbox"]', renderSelected);
		renderSelected();

		$search.on('input', function () {
			var query = $.trim($(this).val().toLowerCase());
			var $items = $cats.find('li');

			if (!query) {
				$items.show();
				return;
			}

			$items.hide();
			$items.each(function () {
				var $item = $(this);

				if ($item.children('label').text().toLowerCase().indexOf(query) !== -1) {
					$item.show().parents('li').show();
				}
			});
		});
	});
})(jQuery);
