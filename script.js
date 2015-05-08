var wpNotification = window.Notification || window.mozNotification || window.webkitNotification;
var wpNotifiedIDs = [];
var wpNotifySound = new Audio( dnStrings.mp3_url );
var wpNotifyCurrentDate = new Date().toUTCString();




function _wpNOTIFY( title, text, icon, link ){
	var instance = new wpNotification(
		title, {
			body: text,
			icon: icon
		}
	);
	
	instance.onclick = function ( event ) {
		location.href = link;
	};
	
	instance.onerror = function ( event ) {
		
	};

	instance.onshow = function ( event ) {
		wpNotifySound.play();
	};

	instance.onclose = function ( event ) {
		
	};

	return false;
}

wpNotification.requestPermission( function( permission ){
	window.setInterval( function(){
		jQuery.post(
			dnStrings.ajax_url,
			{
				'action':'dn-query',
				'since':wpNotifyCurrentDate
			},
			function( response ){
				response = JSON.parse( response );
				var msg_send = false;
				for( var i = 0; i < response.length; i++ ){
					if( -1 == jQuery.inArray( response[i].type + '-' +response[i].id, wpNotifiedIDs ) && !msg_send){
						msg_send = true;
						wpNotifiedIDs.push( response[i].type + '-' + response[i].id );
						window[dnStrings.callback]( response[i].title, response[i].content, response[i].avatar, response[i].link );
						
					}
				}
			}
		);
	}, dnStrings.interval );
});

