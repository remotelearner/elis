var permissions = new Array();

var username   = '';  // Username to assign permission for.
var permName   = '';  // The permission name to use.
var capability = '';  // The capability (ALLOWED or DENIED)

var uuid = url.templateArgs.uuid;  // Get the node UUID from the URL

// Search for the specified node in the repository.
var node = search.findNode('node', new Array('workspace', 'SpacesStore', uuid));

// Do some sanity checking.
if (node == null) {
	status.code     = 400;
	status.message  = 'Could not find specified node: "' + uuid + '"';
	status.redirect = true;
} else if (formdata == null) {
	status.code     = 400;
	status.message  = 'No form data was supplied to this script';
	status.redirect = true;
} else {
	for each (field in formdata.fields) {
		if (field.name.toLowerCase() == 'username') {
    		username = new String(field.value);
		} else if (field.name.toLowerCase() == 'name') {
    		permName = new String(field.value);
		} else if (field.name.toLowerCase() == 'capability') {
    		capability = new String(field.value);
		}
	}

	// Make sure that we have received all required values and they are correct.
	if (username == '') {
		status.code     = 400;
		status.message  = 'username property is required';
		status.redirect = true;
	} else if (permName == '') {
		status.code     = 400;
		status.message  = 'name property is required';
		status.redirect = true;
	} else if (capability == '') {
		status.code     = 400;
		status.message  = 'capability property is required';
		status.redirect = true;
	} else if ((permName != 'Coordinator') && (permName != 'Collaborator') &&
               (permName != 'Contributor') && (permName != 'Editor') && (permName != 'Consumer')) {

		status.code     = 400;
		status.message  = 'Invalid permission name specified: "' + permName + '"';
		status.redirect = true;
	} else if ((capability != 'ALLOWED') && (capability != 'DENIED')) {
		status.code     = 400;
		status.message  = 'Invalid permission name specified: "' + capability + '"';
		status.redirect = true;
	} else {
		// Assign or remove the specified capability as asked.
		if (capability == 'ALLOWED') {
			node.setPermission(permName, username);
		} else if (capability == 'DENIED') {
			node.removePermission(permName, username);
		}

		// Get the current permissions on this node for returning.
		i = 0;

		for each (nodePerm in node.getPermissions()) {
			var parts = nodePerm.split(';', 3);

			// Only return permissions for this username.
			if (username == null || parts[1] == username) {
				permissions[i++] = parts;
			}
		}
	}
}

model.permissions = permissions;
