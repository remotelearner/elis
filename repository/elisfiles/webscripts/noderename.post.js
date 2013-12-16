
var nodeRef = 'workspace://SpacesStore/' + url.templateArgs.uuid;

var node    = search.findNode(nodeRef);
var newname = '';

for each (field in formdata.fields) {
	if (field.name.toLowerCase() == 'name') {
    	newname = field.value;
	}
}

if (node != undefined && newname != '') {
	status = node.properties.name = newname;
	node.save();
}

model.node = node;
