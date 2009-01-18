var currentEdit = new Array();
var beforeText;
var editIcon;
var saveIcon;
var url = 'index.php?eID=tx_servicemgr_ajax';


function tx_servicemgr_pi3_editSermonName(element) {
	var textNode = element.firstChild;
	if (currentEdit[textNode.id] != 1) {
		currentEdit[textNode.id] = 1;
		editIcon = element.lastChild;
		initSaveIcon();
		var title = textNode.innerHTML;
		beforeText = title;
		textNode.innerHTML = '<input type="text" value="' + title + '" name="tx_servicemgr_pi3[sermonTitleEdit]" onblur="tx_servicemgr_pi3_saveSermonName(this.parentNode.parentNode)" />';
		element.replaceChild(saveIcon, element.lastChild);
	}
}

function tx_servicemgr_pi3_saveSermonName(element) {
	var textNode = element.firstChild;
	var inputBox = textNode.firstChild;
	if (currentEdit[textNode.id] == 1) {
		currentEdit[textNode.id] = 0;
		if (beforeText == inputBox.value) {
			textNode.innerHTML = inputBox.value;
			element.replaceChild(editIcon, element.lastChild);
		} else {
			var temp = textNode.id.split("-");
			var sermonid = temp[temp.length-1];
			new Ajax.Request(url, {
				method: 'post',
				parameters: {action: 'setsermontitle', sermonid: sermonid, sermontitle: inputBox.value},
				onSuccess: function(transport) {
					textNode.innerHTML = transport.responseText;
					element.replaceChild(editIcon, element.lastChild);
				}
			});
		}
	}
}

function initSaveIcon() {
	if (!saveIcon) {
		saveIcon = $('tx-servicemgr-pi3-saveicon').firstChild;
	}
}