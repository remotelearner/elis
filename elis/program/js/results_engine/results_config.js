function delete_row(rid,delbutton)
{
	num_rows=parseInt($('#rowcount').val());
	
	//update rows
		for (i=rid;i<=num_rows;i=i+1)
		{
			if (i!=num_rows) {
				$('input[name="textgroup_'+i+'[mininput]"]').val($('input[name="textgroup_'+(i+1)+'[mininput]"]').val());
				$('input[name="textgroup_'+i+'[maxinput]"]').val($('input[name="textgroup_'+(i+1)+'[maxinput]"]').val());
				$('input[name="textgroup_'+i+'[nameinput]"]').val($('input[name="textgroup_'+(i+1)+'[nameinput]"]').val());
			} else {
				//clear values, hide group
				$('input[name="textgroup_'+i+'[mininput]"]').val('');
				$('input[name="textgroup_'+i+'[maxinput]"]').val('');
				$('input[name="textgroup_'+i+'[nameinput]"]').val('');
				$('input[name="textgroup_'+i+'[nameinput]"]').parents('.fitem').hide();
			}
		}
	
	//update rowcount
		$('#rowcount').val(num_rows-1);
}