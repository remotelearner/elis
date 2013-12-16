
var fromNodeRef = 'workspace://SpacesStore/' + url.templateArgs.uuid;
var toNodeRef   = 'workspace://SpacesStore/' + url.templateArgs.to;

var status = false;

var fromNode = search.findNode(fromNodeRef);
var toNode   = search.findNode(toNodeRef);

if (fromNode != undefined && toNode != undefined && toNode.isContainer == true) {
	status = fromNode.move(toNode);
}

// Store status for FTL template
if (status === true) {
	model.status = 'true';
} else {
	model.status = 'false';
}
