function main() {
	var nodeRef  = 'workspace://SpacesStore/' + url.templateArgs.uuid;
	var username = '';

	// Get the switch from the query string that controls whether we are enabling or disabling permission inheritance.
	if (args.length > 0 && args['username'] != undefined) {
		username = '' + args['username'];
	}

	// Get the actual node.
	var node = search.findNode(nodeRef);

	if (node == undefined) {
		status.code     = 400;
		status.message  = 'Could not find node: ' + url.templateArgs.uuid;
		status.redirect = true;
		return false;
	}
	
	if (people.getPerson(username) == null) {
		status.code     = 400;
		status.message  = 'Could not user: ' + username;
		status.redirect = true;
		return false;
	}

	node.setOwner(username);

	// Store the properties that the Freemarker template needs for the return data.
	model.uuid  = node.id;
	model.owner = username;
	
	return true;
}

main();
