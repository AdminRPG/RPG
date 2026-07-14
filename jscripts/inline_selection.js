var InlineSelection = function(config) {
	var self = this;

	self.prefix = config.prefix;
	self.cookieName = config.cookieName;
	self.buttonSelector = config.buttonSelector;
	self.buttonText = config.buttonText;

	self.getCookie = function(name) {
		var inlineCookie = Cookie.get(name);
		var ids = [];
		if (inlineCookie) {
			var inlineIds = inlineCookie.split('|');
			$.each(inlineIds, function(index, item) {
				if (item !== '' && item !== null && item !== undefined) {
					ids.push(item);
				}
			});
		}
		return ids;
	};

	self.setCookie = function(name, array) {
		if (array.length !== 0) {
			var data = '|' + array.join('|') + '|';
			Cookie.set(name, data, 60 * 60 * 1000);
		} else {
			Cookie.unset(name);
		}
	};

	self.addId = function(array, id) {
		if (array.indexOf(id) === -1) {
			array.push(id);
		}
		return array;
	};

	self.removeId = function(array, id) {
		var position = array.indexOf(id);
		if (position !== -1) {
			array.splice(position, 1);
		}
		return array;
	};

	self.updateCookies = function(inlineIds, removedIds) {
		var count;
		if (inlineIds.indexOf('ALL') !== -1) {
			count = all_text - removedIds.length;
		} else {
			count = inlineIds.length;
		}
		if (count < 0) {
			count = 0;
		}
		$(self.buttonSelector).val(self.buttonText + ' (' + count + ')');
		if (count === 0) {
			self.clearChecked();
		} else {
			self.setCookie(self.cookieName, inlineIds);
			self.setCookie(self.cookieName + '_removed', removedIds);
		}
		return count;
	};

	self.clearChecked = function() {
		$('#selectAllrow').hide();
		$('#allSelectedrow').hide();

		var inputs = $('input');
		if (!inputs.length) {
			return false;
		}

		$(inputs).each(function() {
			var element = $(this);
			if (!element.val()) return;
			if (element.attr('type') === 'checkbox' && ((element.attr('id') && element.attr('id').split('_')[0] === self.prefix) || element.attr('name') === 'allbox')) {
				element.prop('checked', false);
			}
		});

		$('.trow_selected').each(function() {
			$(this).removeClass('trow_selected');
		});

		$('fieldset.inline_selected').each(function() {
			$(this).removeClass('inline_selected');
		});

		Cookie.unset(self.cookieName);
		Cookie.unset(self.cookieName + '_removed');

		return true;
	};

	self.checkAll = function(master) {
		var inputs = $('input');
		master = $(master);

		if (!inputs.length) {
			return false;
		}

		var inlineIds = self.getCookie(self.cookieName);
		var removedIds = self.getCookie(self.cookieName + '_removed');

		$(inputs).each(function() {
			var element = $(this);
			if (!element.val() || !element.attr('id')) return;
			var inlineCheck = element.attr('id').split('_');
			if ((element.attr('name') !== 'allbox') && (element.attr('type') === 'checkbox') && (inlineCheck[0] === self.prefix)) {
				var id = inlineCheck[1];
				var changed = (element.prop('checked') !== master.prop('checked'));

				var thread = element.parents('.inline_row');
				if (thread.length) {
					if (master.prop('checked') === true) {
						thread.addClass('trow_selected');
					} else {
						thread.removeClass('trow_selected');
					}
				}

				if (changed) {
					element.trigger('click');

					if (master.prop('checked') === true) {
						if (inlineIds.indexOf('ALL') === -1) {
							inlineIds = self.addId(inlineIds, id);
						} else {
							removedIds = self.removeId(removedIds, id);
						}
					} else {
						if (inlineIds.indexOf('ALL') === -1) {
							inlineIds = self.removeId(inlineIds, id);
						} else {
							removedIds = self.addId(removedIds, id);
						}
					}
				}
			}
		});

		var count = self.updateCookies(inlineIds, removedIds);

		if (count < all_text) {
			var selectRow = $('#selectAllrow');
			if (selectRow.length) {
				if (master.prop('checked') === true) {
					selectRow.show();
				} else {
					selectRow.hide();
				}
			}
		}

		if (inlineIds.indexOf('ALL') === -1 || removedIds.length !== 0) {
			$('#allSelectedrow').hide();
		} else if (inlineIds.indexOf('ALL') !== -1 && removedIds.length === 0) {
			$('#allSelectedrow').show();
		}
	};

	self.selectAll = function() {
		self.updateCookies(['ALL'], []);
		$('#selectAllrow').hide();
		$('#allSelectedrow').show();
	};
};
