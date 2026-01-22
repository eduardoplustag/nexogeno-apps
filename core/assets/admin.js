(function($) {
	'use strict';

	function initSelector($root) {
		var $search = $root.find('.nexogeno-apps-search');
		var $results = $root.find('.nexogeno-apps-results');
		var $tags = $root.find('.nexogeno-apps-tags');
		var appId = $root.data('app-id');
		var timer = null;
		var requestId = 0;

		function addTag(item) {
			var id = parseInt(item.id, 10);
			if (!id) {
				return;
			}

			if ($tags.find('.nexogeno-apps-tag[data-product-id="' + id + '"]').length) {
				return;
			}

			var $tag = $('<span/>', {
				'class': 'nexogeno-apps-tag',
				'data-product-id': id
			});
			var $label = $('<span/>', {
				'class': 'nexogeno-apps-tag-label',
				'text': item.text
			});
			var $remove = $('<button/>', {
				'type': 'button',
				'class': 'nexogeno-apps-remove',
				'aria-label': NexogenoApps.removeLabel,
				'text': '\u00d7'
			});
			var $input = $('<input/>', {
				'type': 'hidden',
				'name': 'nexogeno_apps_products[' + appId + '][]',
				'value': id
			});

			$tag.append($label, $remove, $input);
			$tags.append($tag);
		}

		function renderResults(items) {
			$results.empty();

			if (!items.length) {
				$results.append(
					$('<li/>', {
						'class': 'nexogeno-apps-empty',
						'text': NexogenoApps.messages.empty
					})
				);
				$results.show();
				return;
			}

			items.forEach(function(item) {
				var $button = $('<button/>', {
					'type': 'button',
					'class': 'nexogeno-apps-result',
					'text': item.text
				});

				$button.data('id', item.id);
				$button.data('text', item.text);

				$results.append($('<li/>').append($button));
			});

			$results.show();
		}

		function search(term) {
			requestId += 1;
			var currentRequest = requestId;

			$results
				.empty()
				.append(
					$('<li/>', {
						'class': 'nexogeno-apps-loading',
						'text': NexogenoApps.messages.loading
					})
				)
				.show();

			$.ajax({
				url: NexogenoApps.ajaxUrl,
				method: 'POST',
				dataType: 'json',
				data: {
					action: 'nexogeno_apps_product_search',
					term: term,
					nonce: NexogenoApps.nonce
				}
			}).done(function(response) {
				if (currentRequest !== requestId) {
					return;
				}

				if (!response || !response.success) {
					renderResults([]);
					return;
				}

				renderResults(response.data || []);
			}).fail(function() {
				if (currentRequest !== requestId) {
					return;
				}

				$results
					.empty()
					.append(
						$('<li/>', {
							'class': 'nexogeno-apps-empty',
							'text': NexogenoApps.messages.error
						})
					)
					.show();
			});
		}

		$search.on('input', function() {
			var term = $.trim($search.val());

			if (term.length < NexogenoApps.minLength) {
				$results.empty().hide();
				return;
			}

			clearTimeout(timer);
			timer = setTimeout(function() {
				search(term);
			}, 250);
		});

		$results.on('click', '.nexogeno-apps-result', function() {
			var $button = $(this);
			addTag({
				id: $button.data('id'),
				text: $button.data('text')
			});
			$search.val('');
			$results.empty().hide();
		});

		$tags.on('click', '.nexogeno-apps-remove', function() {
			$(this).closest('.nexogeno-apps-tag').remove();
		});
	}

	$(function() {
		$('.nexogeno-apps-selector').each(function() {
			initSelector($(this));
		});
	});
})(jQuery);
