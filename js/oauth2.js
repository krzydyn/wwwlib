function OAuth2(auth_server, client_id) {
	//this.ajax = new Ajax();
	this.auth_server = auth_server;
	this.client_id = client_id;
	this.fragment = window.location.hash; // hash is not sent to server, but can be acceses in webclient
}

// redirect: client app callback (redirected to from auth host)
OAuth2.prototype.auth = function(scope, redirect) {
	scopemap = {
		// https://developers.google.com/identity/protocols/oauth2/scopes?hl=pl
		'user_email' : 'https://www.googleapis.com/auth/userinfo.email',
		'user_profile' : 'https://www.googleapis.com/auth/userinfo.profile',
	};
	scope = 'https://www.googleapis.com/auth/userinfo.profile';
	var params = {'client_id': this.client_id,
                'redirect_uri': redirect,
                'response_type': 'token',	// access_token is returned in uri fragment(window.location.hash)
											// for javascript it is ok.
                //'response_type': 'code',	// code is passed in uri params (after ?)
											// another request is needed to get access_token
                'scope': scope,
                'include_granted_scopes': 'true',
                'state': 'random_value'};

	var endpoint = this.auth_server+'auth';
	var form = document.createElement('form');
	form.setAttribute('method', 'GET');
	form.setAttribute('action', endpoint);

	// Add form parameters as hidden input values.
	// brawser will encode all values before sending server
	for (var p in params) {
		var input = document.createElement('input');
		input.setAttribute('type', 'hidden');
		input.setAttribute('name', p);
		input.setAttribute('value', params[p]);
		form.appendChild(input);
	}
	var input = document.createElement('input');
	input.setAttribute('type', 'submit');
	input.setAttribute('value', 'SignIn');
	form.appendChild(input);
	var el = $('.g-signin2');
	el.appendChild(form);
}
OAuth2.prototype.signOut = function() {
	// revoke access_token
	var endpoint = this.auth_server+'revoke';
}
