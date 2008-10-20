
function sermonshowplayer(uid) {
	
	new Ajax.Request('index.php?eID=tx_servicemgr_ajax&action=showinlineplayer&playerid='+uid,
	{
    	method:'get',
		onSuccess: function(transport) {
			setsermonplayerinline(uid, transport.responseText || "no response text");
		},
		onFailure: function(){ alert('Something went wrong...') }
	}
	);
	
}

function setsermonplayerinline(uid, response) {
	var wrapperElement = 'tx-servicemgr-sa-sermon-player'+uid;
	
	Element.extend(document.getElementById(wrapperElement));
	$(wrapperElement).innerHTML = response;
	
	var jsCo = $(wrapperElement).getElementsByTagName('script');
	//AudioPlayer.embed("audioplayer_1", {soundFile: "fileadmin/predigten/1-20080613-0.mp3"});
	eval(jsCo[0].innerHTML);
	//var all = document.getElementsByClassName('tx-servicemgr-sa-sermon-player');
	//for (i=0; i<count(all); i++) {
	//	all[i].hide()
	//}
	
	//new Effect.Appear(wrapperElement, {duration: .3, from: 0, to: 1});
	$(wrapperElement).show();

}