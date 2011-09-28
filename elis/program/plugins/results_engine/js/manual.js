function process(source, pmclass) {

	var manualProcessor = new Y.DataSource.Get({
		source: source
	});
	
	manualProcessor.sendRequest({
	    request: 'id='+pmclass,
	    callback: {
	        success: function(e){
	        },
	        failure: function(e){
	        }
	    }
	});
}