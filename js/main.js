$(document).ready(function() {
	$('select').material_select();
	
	$('.notification').click(function() {
		$(this).hide("fast");
	});

	$('.shop-cart-add').click(cartAdd);
	$('.shop-cart-button-plus').click(cartIncrease);
	$('.shop-cart-button-minus').click(cartDecrease);
	$('.delete-button').click(cartRemove);
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

function cartAdd() {
	var id = this.getAttribute('eid');
	var xhr = new XMLHttpRequest();
	xhr.open('GET', 'cart.php?action=add&id=' + id);
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4) {
			if (xhr.status == 404) {
				console.error('Przedmiot o identyfikatorze ' + id + ' nie został odnaleziony.');
				Materialize.toast('Błąd: nie odnaleziono elementu.');
			}
			else if (xhr.status == 400) {
				console.error('Błędne zapytanie:');
				console.error(xhr.responseText);
				Materialize.toast('Wystąpił nieoczekiwany błąd.');
			}
			else {
				size++;
				Materialize.toast('Element został dodany do koszyka.', 3500);
				updateCartText();
			}
		}
	};
	xhr.send(null);
}

function cartIncrease() {
	var p = $(this).parent().find('.shop-cart-amount');
	if (p.text() == '100')
		return;

	var oldAmount = parseInt(p.text());
	p.text(oldAmount + 1);

	var id = $(this).parent().parent().attr('eid');
	if (id)
		sendRequest('add', id);
	else {
		console.error('Nie odnaleziono identyfikatora przedmiotu.');
		return;
	}

	size++;
	updatePrice(this, oldAmount, oldAmount + 1);
	updateTotal();
	updateCartText();
}

function cartDecrease() {
	var p = $(this).parent().find('.shop-cart-amount');
	if (p.text() == '1')
		return;

	var oldAmount = parseInt(p.text());
	p.text(oldAmount - 1);

	var id = $(this).parent().parent().attr('eid');
	if (id)
		sendRequest('remove', id);
	else {
		console.error('Nie odnaleziono identyfikatora przedmiotu.');
		return;
	}

	size--;
	updatePrice(this, oldAmount, oldAmount - 1)
	updateTotal();
	updateCartText();
}

function updatePrice(element, oldAmount, newAmount) {
	var e = $(element).parent().find('.shop-cart-total');
	var f = parseFloat(e.text());
	var t = (f / oldAmount * newAmount) + ' zł';
	e.text(t);
}

function updateTotal() {
	var total = 0;
	$('.shop-cart-total').each(function() {
		total += parseFloat(this.textContent);
	});

	$('.shop-cart-summary > p:first-child > span').text(total + ' zł');
}

function cartRemove() {
	var id = $(this).parent().parent().attr('eid');
	if (id)
		sendRequest('delete', id)
	else {
		console.error('Nie odnaleziono identyfikatora przedmiotu.');
		return;
	}

	var s = parseInt($(this).parent().find('.shop-cart-amount').text());
	size -= s;
	var p = $(this).parent().parent();
	if (p.next().hasClass('hr'))
		p.next().remove();
	p.remove();
	updateTotal();
	updateCartText();
}

function updateCartText() {
	var txt = undefined;
	if (size == 0)
		txt = '<i>koszyk jest pusty</i>';
	else if (size == 1)
		txt = '1 przedmiot';
	else if (size < 5 || (size > 21 && size % 10 > 1 && size % 10 < 5))
		txt = size + ' przedmioty';
	else
		txt = size + ' przedmotów';

	var e = $('#basket > a > p');
	e.html(txt);
}

function sendRequest(action, id) {
	var url = 'cart.php?action=' + action;
	if (id)
		url += '&id=' + id;

	var xhr = new XMLHttpRequest();
	xhr.open('GET', url);
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && xhr.status != 200) {
			console.error('Wystąpił błąd podczas wysyłania żądania "' + url + '": ' + xhr.status);
		}
	}
	xhr.send(null);
}