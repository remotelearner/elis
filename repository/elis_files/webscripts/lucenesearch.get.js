var searchquery = args['searchquery'];
var nodes = search.luceneSearch(searchquery);

model.resultset = nodes;