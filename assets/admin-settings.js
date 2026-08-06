(function ($) {
	'use strict';

	function initTaxonomySelect() {
		var $tax = $('#mks-core-publisher-taxonomy');
		if (!$tax.length || typeof $tax.selectWoo !== 'function') {
			return $tax;
		}

		$tax.selectWoo({
			width: '320px',
			placeholder: mksCoreSettings.i18n.placeholder,
			allowClear: true,
			language: {
				noResults: function () {
					return mksCoreSettings.i18n.noResults;
				},
				searching: function () {
					return mksCoreSettings.i18n.search;
				}
			}
		});

		return $tax;
	}

	function initTermSelect() {
		var $term = $('#mks-core-publisher-term');
		if (!$term.length || typeof $term.selectWoo !== 'function') {
			return $term;
		}

		$term.selectWoo({
			width: '320px',
			placeholder: mksCoreSettings.i18n.placeholder,
			allowClear: true,
			ajax: {
				url: mksCoreSettings.ajaxUrl,
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return {
						action: 'mks_core_attribute_terms',
						nonce: mksCoreSettings.nonce,
						taxonomy: $('#mks-core-publisher-taxonomy').val() || $term.data('taxonomy'),
						q: params.term || ''
					};
				},
				processResults: function (data) {
					return {
						results: (data && data.results) ? data.results : []
					};
				},
				cache: true
			},
			minimumInputLength: 0,
			language: {
				noResults: function () {
					return mksCoreSettings.i18n.noResults;
				},
				searching: function () {
					return mksCoreSettings.i18n.search;
				},
				inputTooShort: function () {
					return mksCoreSettings.i18n.loadingTerms;
				}
			}
		});

		return $term;
	}

	$(function () {
		var $tax = initTaxonomySelect();
		var $term = initTermSelect();

		$tax.on('change', function () {
			var taxonomy = $(this).val() || '';
			$term.data('taxonomy', taxonomy);
			$term.val(null).trigger('change');
		});
	});
})(jQuery);
