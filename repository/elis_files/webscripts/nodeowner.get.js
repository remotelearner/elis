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

	// If no(empty) username parameter then just return current owner
	// RL EDIT: ELIS-5750 - Alfresco 3.2 not returning owner with node info

	if (!username.length) {
		model.owner = node.getOwner();
		model.uuid  = node.id;
		return true;
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
