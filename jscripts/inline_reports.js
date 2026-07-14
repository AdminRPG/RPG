var inlineReports = {
	init: function()
	{
		inlineReports.cookieName = 'inlinereports';
		inlineReports.shared = new InlineSelection({
			prefix: 'reports',
			cookieName: inlineReports.cookieName,
			buttonSelector: '#inline_read',
			buttonText: mark_read_text
		});

		var inputs = $('input');

		if(!inputs.length)
		{
			return false;
		}

		var inlineIds = inlineReports.shared.getCookie(inlineReports.cookieName);
		var removedIds = inlineReports.shared.getCookie(inlineReports.cookieName+'_removed');
		var allChecked = true;

		$(inputs).each(function() {
			var element = $(this);
			if((element.attr('name') != 'allbox') && (element.attr('type') == 'checkbox') && (element.attr('id')) && (element.attr('id').split('_')[0] == 'reports'))
			{
				$(element).on('click', inlineReports.checkItem);
			}

			if(element.attr('id'))
			{
				var inlineCheck = element.attr('id').split('_');
				var id = inlineCheck[1];

				if(inlineCheck[0] == 'reports')
				{
					if(inlineIds.indexOf(id) != -1 || (inlineIds.indexOf('ALL') != -1 && removedIds.indexOf(id) == -1))
					{
						element.prop('checked', true);
						var report = element.parents('.inline_row');
						if(report.length)
						{
							report.addClass('trow_selected');
						}
					}
					else
					{
						element.prop('checked', false);
						var report = element.parents('.inline_row');
						if(report.length)
						{
							report.removeClass('trow_selected');
						}
					}
					allChecked = false;
				}
			}
		});

		inlineReports.shared.updateCookies(inlineIds, removedIds);

		if(inlineIds.indexOf('ALL') != -1 && removedIds.length == 0)
		{
			var allSelectedRow = $('#allSelectedrow');
			if(allSelectedRow)
			{
				allSelectedRow.show();
			}
		}
		else if(inlineIds.indexOf('ALL') == -1 && allChecked == true)
		{
			var selectRow = $('#selectAllrow');
			if(selectRow)
			{
				selectRow.show();
			}
		}
		return true;
	},

	checkItem: function()
	{
		var element = $(this);

		if(!element || !element.attr('id'))
		{
			return false;
		}

		var inlineCheck = element.attr('id').split('_');
		var id = inlineCheck[1];

		if(!id)
		{
			return false;
		}

		var inlineIds = inlineReports.shared.getCookie(inlineReports.cookieName);
		var removedIds = inlineReports.shared.getCookie(inlineReports.cookieName+'_removed');

		if(element.prop('checked') == true)
		{
			if(inlineIds.indexOf('ALL') == -1)
			{
				inlineIds = inlineReports.shared.addId(inlineIds, id);
			}
			else
			{
				removedIds = inlineReports.shared.removeId(removedIds, id);
				if(removedIds.length == 0)
				{
					var allSelectedRow = $('#allSelectedrow');
					if(allSelectedRow)
					{
						allSelectedRow.show();
					}
				}
			}
			var report = element.parents('.inline_row');
			if(report.length)
			{
				report.addClass('trow_selected');
			}
		}
		else
		{
			if(inlineIds.indexOf('ALL') == -1)
			{
				inlineIds = inlineReports.shared.removeId(inlineIds, id);
				var selectRow = $('#selectAllrow');
				if(selectRow)
				{
					selectRow.hide();
				}
			}
			else
			{
				removedIds = inlineReports.shared.addId(removedIds, id);
				var allSelectedRow = $('#allSelectedrow');
				if(allSelectedRow)
				{
					allSelectedRow.hide();
				}
			}
			var report = element.parents('.inline_row');
			if(report.length)
			{
				report.removeClass('trow_selected');
			}
		}

		inlineReports.shared.updateCookies(inlineIds, removedIds);

		return true;
	},

	clearChecked: function()
	{
		inlineReports.shared.clearChecked();
		return true;
	},

	checkAll: function(master)
	{
		inlineReports.shared.checkAll(master);
	},

	selectAll: function()
	{
		inlineReports.shared.selectAll();
	}
};

$(inlineReports.init);
