(function ($) {
	'use strict';

	$(function () {
		var frame;
		var $sets = $('#mks-badge-sets');
		var template = document.getElementById('mks-badge-set-template');

		if (!$sets.length || !template) {
			return;
		}

		var nextIndex = $sets.children('.mks-badge-set').length;

		function renumber() {
			$sets.children('.mks-badge-set').each(function (i) {
				$(this).find('.mks-badge-set-number').text(i + 1);
			});
		}

		function renderSelected($set) {
			var $selected = $set.find('.mks-badge-selected').empty();

			$set.find('.mks-badge-cats input[type="checkbox"]:checked').each(function () {
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

		function initSet($set) {
			$set.find('.mks-core-color-picker').wpColorPicker();
			renderSelected($set);
		}

		$sets.children('.mks-badge-set').each(function () {
			initSet($(this));
		});
		renumber();

		$sets.on('change', '.mks-badge-cats input[type="checkbox"]', function () {
			renderSelected($(this).closest('.mks-badge-set'));
		});

		$sets.on('input', '.mks-badge-cats-search', function () {
			var $set = $(this).closest('.mks-badge-set');
			var query = $.trim($(this).val().toLowerCase());
			var $items = $set.find('.mks-badge-cats li');

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

		$sets.on('click', '.mks-badge-pick', function (e) {
			e.preventDefault();

			var $set = $(this).closest('.mks-badge-set');

			if (!frame) {
				frame = wp.media({
					title: 'Выберите изображение бейджа',
					button: { text: 'Использовать' },
					multiple: false
				});
			}

			frame.off('select');
			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();

				$set.find('.mks-badge-attachment').val(attachment.id);
				$set.find('.mks-badge-preview').attr('src', attachment.url).show();
				$set.find('.mks-badge-remove').show();
			});

			frame.open();
		});

		$sets.on('click', '.mks-badge-remove', function (e) {
			e.preventDefault();

			var $set = $(this).closest('.mks-badge-set');

			$set.find('.mks-badge-attachment').val('');
			$set.find('.mks-badge-preview').attr('src', '').hide();
			$(this).hide();
		});

		$sets.on('click', '.mks-badge-set-remove', function (e) {
			e.preventDefault();

			$(this).closest('.mks-badge-set').remove();
			renumber();
		});

		$('#mks-badge-add').on('click', function (e) {
			e.preventDefault();

			var $set = $(template.innerHTML.replace(/__index__/g, nextIndex++));

			$sets.append($set);
			initSet($set);
			renumber();
		});
	});
})(jQuery);
