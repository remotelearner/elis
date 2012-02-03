var userName = '';
var password = '';

if (args.length > 0 && args['username'] != undefined) {
	var userName = args['username'];
}

if (args.length > 0 && args['password'] != undefined) {
	var password = args['password'];
}

if (people.setPassword(userName, password) !== null) {
	model.status = 'true';
} else {
	model.status = 'false';
}
