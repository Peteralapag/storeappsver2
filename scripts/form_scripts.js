function set_session(value,params)
{
	$.post("./actions/set_session_process.php", { params: params, value: value },
	function(data) {
		$("#"+sessionStorage.navcount).click();
	});
}
$(function()
{
	$('#kilo_used').keyup(function()
	{
		calculate();	
	});
	$('#category').change(function()
	{
		$('#item_id').val('');
		$('#itemname').val('');
		
	});
	$('#itemname').change(function()
	{	
		var valvas = $("#items option:selected").text();
		var mode = 'getiteminfo';
		
		let input = $(this).val();

		let option = $('#items option').filter(function () {
			return this.value === input;
		});

		let item_id = option.data('id') || '';
		
		if(item_id == '')
		{ 
			$('#itemid').val(''); 
			$('.btnnew,.btnupdate').prop('disabled', true); 
			return false;
		} else { 
			$('.btnnew,.btnupdate').prop('disabled', false); 
		} 
		$.post("./actions/actions.php", { mode: mode, item_id: item_id },
		function(data) {
			$('.results').html(data);
			calculate();
		});
	});
});
function GetItems()
{
	var mode = 'getitems';
	var category = $('#category').val();
	var item_id = $('#item_id').val();
	
	$.post("./actions/actions.php", { mode: mode, category: category, item_id: item_id },
	function(data) {
		rms_reloaderOff();
		$('#items').html(data);
	});
}