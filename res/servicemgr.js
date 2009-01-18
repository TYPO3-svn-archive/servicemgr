var url = 'index.php?eID=tx_servicemgr_ajax';

function addSeries(pid) {
	var title = prompt('Bitte geben sie den Titel der neuen Serie ein:');
	if (title != '' && title != null) {
		new Ajax.Request(url, {
			method: 'post',
			parameters: {action: 'addseries', seriestitle: title, pid: pid},
			onSuccess: function(transport) {
				var sBox = $('frm-series');
				for (var i = 0; i < sBox.length; i++) {
					sBox.options[i].selected = false;
				}
				sBox.options[sBox.length] = new Option(title, transport.responseText, false, true);
			}
		});
	}
}

function addTag(cbArrayElement, name, pid) {
	var title = prompt('Bitte geben sie den Titel des neuen Tags ein:');
	if (title != '' && title != null) {
		new Ajax.Request(url, {
			method: 'post',
			parameters: {action: 'addtag', tagtitle: title, pid: pid},
			onSuccess: function(transport) {
				var id = transport.responseText;
				cbArrayElement.innerHTML += '<input id="frm-tags-' + id + '" type="checkbox" value="' + id + '" name="' + name + '[]"/><label for="frm-tags-' + id + '">' + title + '</label><br/>';
			}
		});
	}
}

var expanded = new Array();

function expandSermonElement(divElement) {
	if (expanded[divElement] === 1) {
		return true;
	} else {
		divElementObject = $(divElement);
		Effect.BlindDown(divElement);
		expanded[divElement]=1;
		return false;
	}
}

function collapseSermonElement(divElement) {
	if (expanded[divElement] !== 1) return false;
	divElementObject = $(divElement);
	new Effect.BlindUp(divElement);
	expanded[divElement]=0;
	return false;
}