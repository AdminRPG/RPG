var inlineModeration = {
	init: function()
	{
		$(function(){
			if($("#inlinemoderation_options_selector").length !== 0) {
				$("#inlinemoderation_options_selector").on('change', function() {
					$("#inlinemoderation_options").trigger('submit');
				});

				$("#inlinemoderation_options").on('submit', function(){
					if($("#inlinemoderation_options_selector").val() == "") {
						$.jGrowl(lang.select_tool, {theme:'jgrowl_error'});
						return false;
					} else if($('input[name^="inlinemod_"]:checked').length === 0) {
						$.jGrowl(lang.selected_nil, {theme:'jgrowl_error'});
						return false;
					}
				});
			}
		});
		
		if(!inlineType || !inlineId)
		{
			return false;
		}

		inlineModeration.cookieName = 'inlinemod_'+inlineType+inlineId;
		inlineModeration.shared = new InlineSelection({
			prefix: 'inlinemod',
			cookieName: inlineModeration.cookieName,
			buttonSelector: '#inline_go',
			buttonText: go_text
		});

		var inputs = $('input');

		if(!inputs.length)
		{
			return false;
		}

		var inlineIds = inlineModeration.shared.getCookie(inlineModeration.cookieName);
		var removedIds = inlineModeration.shared.getCookie(inlineModeration.cookieName+'_removed');
		var allChecked = true;

		$(inputs).each(function() {
			var element = $(this);
			if((element.attr('name') != 'allbox') && (element.attr('type') == 'checkbox') && (element.attr('id')) && (element.attr('id').split('_')[0] == 'inlinemod'))
			{
				$(element).on('click', inlineModeration.checkItem);
			}

			if(element.attr('id'))
			{
				var inlineCheck = element.attr('id').split('_');
				var id = inlineCheck[1];

				if(inlineCheck[0] == 'inlinemod')
				{
					if(inlineIds.indexOf(id) != -1 || (inlineIds.indexOf('ALL') != -1 && removedIds.indexOf(id) == -1))
					{
						element.prop('checked', true);
						var post = element.parents('.post');
						var thread = element.parents('.inline_row');
						var fieldset = element.parents('fieldset');
						if(post.length)
						{
							post.addClass('trow_selected');
						}
						else if(thread.length)
						{
							thread.addClass('trow_selected');
						}
						
						if(fieldset.length)
						{
							fieldset.addClass('inline_selected');
						}

					}
					else
					{
						element.prop('checked', false);
						var post = element.parents('.post');
						var thread = element.parents('.inline_row');
						if(post.length)
						{
							post.removeClass('trow_selected');
						}
						else if(thread.length)
						{
							thread.removeClass('trow_selected');
						}
					}
					allChecked = false;
				}
			}
		});

		inlineModeration.shared.updateCookies(inlineIds, removedIds);

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

		var inlineIds = inlineModeration.shared.getCookie(inlineModeration.cookieName);
		var removedIds = inlineModeration.shared.getCookie(inlineModeration.cookieName+'_removed');

		if(element.prop('checked') == true)
		{
			if(inlineIds.indexOf('ALL') == -1)
			{
				inlineIds = inlineModeration.shared.addId(inlineIds, id);
			}
			else
			{
				removedIds = inlineModeration.shared.removeId(removedIds, id);
				if(removedIds.length == 0)
				{
					var allSelectedRow = $('#allSelectedrow');
					if(allSelectedRow)
					{
						allSelectedRow.show();
					}
				}
			}
			var post = element.parents('.post');
			var thread = element.parents('.inline_row');
			if(post.length)
			{
				post.addClass('trow_selected');
			}
			else if(thread.length)
			{
				thread.addClass('trow_selected');
			}
		}
		else
		{
			if(inlineIds.indexOf('ALL') == -1)
			{
				inlineIds = inlineModeration.shared.removeId(inlineIds, id);
				var selectRow = $('#selectAllrow');
				if(selectRow)
				{
					selectRow.hide();
				}
			}
			else
			{
				removedIds = inlineModeration.shared.addId(removedIds, id);
				var allSelectedRow = $('#allSelectedrow');
				if(allSelectedRow)
				{
					allSelectedRow.hide();
				}
			}
			var post = element.parents('.post');
			var thread = element.parents('.inline_row');
			if(post.length)
			{
				post.removeClass('trow_selected');
			}
			else if(thread.length)
			{
				thread.removeClass('trow_selected');
			}
		}

		inlineModeration.shared.updateCookies(inlineIds, removedIds);

		return true;
	},

	clearChecked: function()
	{
		inlineModeration.shared.clearChecked();
		return true;
	},

	checkAll: function(master)
	{
		inlineModeration.shared.checkAll(master);
	},

	selectAll: function()
	{
		inlineModeration.shared.selectAll();
	}
};

$(inlineModeration.init);
