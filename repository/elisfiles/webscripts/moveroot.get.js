
var fromNodeRef = 'workspace://SpacesStore/' + url.templateArgs.from;
var toNodeRef   = 'workspace://SpacesStore/' + url.templateArgs.to;

var status = false;

var fromNode = search.findNode(fromNodeRef);
var toNode   = search.findNode(toNodeRef);

if (fromNode != undefined && toNode != undefined) {
	status = true;
	
	var destChildren = toNode.children;
	
/// Move all of the children in the origin root space to the new root space.
	for (i in fromNode.children) {
		var found = false;

	/// Make sure that the folder we want to move isn't in the destination.
		for (j in toNode.children) {
			if (found == true) {
				continue;
			}

			if (fromNode.children[i].properties.title == toNode.children[j].properties.title) {
				found = true;
			}
		}

	/// If we didn't find the node, attempt to move it now.
		if (found == false) {
			status = status && fromNode.children[i].move(toNode);
		}
	}
}

// Store status for FTL template
if (status === true) {
	model.status = 'true';
} else {
	model.status = 'false';
}
