var permissions = new Array();

var uuid = url.templateArgs.uuid;

// If a username was supplied, verify it is valid by fetching the user object for that value.
if (args.length > 0 && args['username'] != undefined) {
	var userName = args['username'];
}

// Search for the specified node in the repository.
var node = search.findNode('node', new Array('workspace', 'SpacesStore', uuid));

// Do some sanity checking.
if (node == null) {
	status.code     = 400;
	status.message  = 'Could not find specified node: "' + uuid + '"';
	status.redirect = true;
} else {
	i = 0;
	
	for each (nodePerm in node.getPermissions()) {
		var parts = nodePerm.split(';', 3);
	
		// If a username filter was supplied, search for that username explicitly.
		if (userName == null || parts[1] == userName) {
			permissions[i++] = parts;
		}
	}
}

model.permissions = permissions;
