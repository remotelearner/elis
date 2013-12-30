
var nodeRef = 'workspace://SpacesStore/' + url.templateArgs.uuid;
var enabled = '';

// Get the switch from the query string that controls whether we are enabling or disabling permission inheritance.
if (args.length > 0 && args['enabled'] != undefined) {
	enabled = '' + args['enabled'];
}

// Get the actual node.
var node = search.findNode(nodeRef);

if (node != undefined) {
	if (enabled == 'true' || enabled == '1') {
		node.setInheritsPermissions(true);
	} else if (enabled == 'false' || enabled == '0') {
		node.setInheritsPermissions(false);
	}
}

// Store the properties that the Freemarker template needs for the return data.
model.uuid = node.id;

if (node.inheritsPermissions()) {
	model.enabled = 'true';
} else {
	model.enabled = 'false';
}
