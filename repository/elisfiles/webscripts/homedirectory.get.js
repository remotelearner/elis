// Just make sure that we actually have the full user information.	

if (args.length > 0 && args['username'] != undefined) {
	var testuser = people.getPerson(args['username']);
}

model.homedir = '';

if (testuser == null) {
	status.code     = 400;
	status.message  = 'Could not find the specified user: ' + args['username'];
	status.redirect = true;
} else {
	// Make the UUID value availavble to the Freemarker template.
	model.homedir = testuser.properties['homeFolder'].id;
}
