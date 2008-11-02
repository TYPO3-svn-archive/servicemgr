function getBox(element) {
	var el = $(element);
	var newChild = $(element+'-new').cloneNode(true);
	newChild.id = element + '-' + (el.childNodes.length + 1);
	el.appendChild(newChild);
	return false;
}

function removeBox(element) {
	var	pEl = element.parentNode;
	pEl.removeChild(element);
	return false;
}