function sleep(milliseconds) {
   return new Promise(resolve => setTimeout(resolve, milliseconds));
}

function playAudio(url) {
  var a = new Audio('sound/' +  url);
  a.play();
}

function toggle_visibility( id ) {

	var login = document.getElementById( 'login' );
	var register = document.getElementById( 'register' );

	if ( id == 'register' ) {
		login.style.display = 'none';
		register.style.display = 'block';
	} else {
		register.style.display = 'none';
		login.style.display = 'block';
	}
}


