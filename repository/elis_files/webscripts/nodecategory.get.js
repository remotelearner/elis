var categories = new Array();

if (args["nodeRef"] != null) {
	var nodeRef = args["nodeRef"];
	node = search.findNode(nodeRef);

	if (node != null) {
		if (node.properties["cm:categories"] != null) {
			var cats = node.properties["cm:categories"];

			if (cats.constructor.toString().indexOf("Array") != -1) {
				for (var i = 0; i < cats.length; i++) {
					categories[i] = new Array();
					categories[i]['uuid'] = cats[i].id;
					categories[i]['name'] = cats[i].properties.name;
				}
			}
		}
	}
}

// store node onto model
model.categories = categories;
