$(document).ready(function() {
	$('select').material_select();
	
	$('.notification').click(function() {
		$(this).hide("fast");
	});
});

function deleteCategory() {
	return confirm('Czy na pewno chcesz usunąć tę kategorię? Tej operacji nie można cofnąć.');
}

function deleteItem(itemId) {
	if (confirm('Czy na pewno chcesz usunąć towar o identyfikatorze ' + itemId + '?')) {
		$('#item_delete_id').val(itemId);
		$('#item_delete_submit').submit();
	}
}