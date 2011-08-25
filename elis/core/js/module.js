M.elis_core = {};

/**
 * Set the value of an element
 */
M.elis_core.set_value = function(itemid, value) {
    var item = document.getElementById(itemid);
    item.value = value;
    return false;
};

/**
 * Create and manage the gradebook idnumber selector popup.
 */
M.elis_core.init_gradebook_popup = function(Y, options) {
    var textelem = Y.one('#'+options.textelemid);

    // add the Browse button after the text element
    var parent = textelem.ancestor();
    parent.appendChild(document.createTextNode(" "));
    var button = Y.Node.create('<button type="button">'+M.str.editor.browse+'</button>');
    parent.appendChild(button);

    // create the panel helper class
    var GradebookPickerHelper = function(options) {
        GradebookPickerHelper.superclass.constructor.apply(this, arguments);
    };

    GradebookPickerHelper.NAME = "GradebookPickerHelper";
    GradebookPickerHelper.ATTRS = {
        options: {},
        lang: {}
    };

    Y.extend(GradebookPickerHelper, Y.Base, {
	api: M.cfg.wwwroot+'/elis/core/lib/form/gradebookidnumber_ajax.php',

	initializer: function(options) {
	    this.options = options;
	},

	destructor: function() { },

	/**
	 * IO handler to show the course dropdown after it has been retrieved
	 * via AJAX.
	 */
	show_course_list_handler: function(id, o, args) {
	    var pickerid = this.options.textelemid + '_picker';
	    var courseselectorcontainer = Y.one('#courseselector-'+pickerid);
	    courseselectorcontainer.set('innerHTML', '');
	    var courseselector = Y.Node.create(o.response);

	    courseselectorcontainer.appendChild(courseselector);
	    if (this.options.lockcourse) {
		courseselector.set('disabled', 1);
	    } else {
		var scope = this;
		courseselector.on('change', function() {
		    scope.get_gradebook_items(courseselector.get('value'));
		});
	    }

	},

	/**
	 * Show the activities and gradebook items for the specified course.
	 */
	get_gradebook_items: function(course) {
	    var pickerid = this.options.textelemid + '_picker';
	    var panel = Y.one('#panel-'+pickerid);
	    panel.set('innerHTML', '');

	    var tabView = new YAHOO.widget.TabView();
	    var common_params = '?textelemid=' + this.options.textelemid;
	    if (course) {
		common_params += '&course=' + course;
	    }
	    tabView.addTab(new YAHOO.widget.Tab({
		label: M.str.grades.activities,
		content: M.str.repository.loading,
		dataSrc: this.api + common_params + '&mode=activities',
		cacheData: true,
		active: true
	    }));
	    tabView.addTab(new YAHOO.widget.Tab({
		label: M.str.grades.gradeitems,
		content: M.str.repository.loading,
		dataSrc: this.api + common_params + '&mode=gradebook',
		cacheData: true
	    }));

	    tabView.appendTo('panel-'+pickerid);
	},

	/**
	 * Create and initialize the popup panel.
	 */
	render: function() {
	    var pickerid = this.options.textelemid + '_picker';
	    var pickernode = Y.Node.create('<div class="grade-picker" id="'+pickerid+'"></div>');
	    Y.one(document.body).appendChild(pickernode);
	    var panel = new YAHOO.widget.Panel(pickerid, {
		draggable: false,
		close: true,
		underlay: 'none',
		zindex: 9999990,
		monitorresize: false,
		xy: [button.getX(), button.getY()]
	    });
	    var layout = null;
	    var scope = this;
	    panel.beforeRenderEvent.subscribe(function() {
		YAHOO.util.Event.onAvailable('layout-'+pickerid, function() {
		    var layout = new YAHOO.widget.Layout('layout-'+pickerid, {
			height: 500, width: 500,
			units: [
			    {position: 'top', height: 32, resize: false,
			     body: '<div class="gradebookpicker-panel" id="courseselector-'+pickerid+'"></div>',
			     scroll: false,
			     gutter: '0 0 0 0' },
			    {position: 'center', body: '<div class="gradebookpicker-panel" id="panel-'+pickerid+'"></div>',
			     scroll: true,
			     gutter: '0 0 0 0' }
			]
		    });
		    layout.render();

		    // load the initial list of gradebook elements
		    scope.get_gradebook_items(scope.options.courseid);

		    // show the course list
		    var courseselectorcontainer = Y.one('#courseselector-'+pickerid);
		    courseselectorcontainer.set('innerHTML', M.str.repository.loading);
		    var cfg = {
			on: {
			    complete: scope.show_course_list_handler
			},
			context: scope
		    };
		    var uri = scope.api + '?mode=course&textelemid=' + scope.options.textelemid;
		    if (scope.options.courseid) {
			uri += '&course=' + scope.options.courseid;
		    }
		    Y.io(uri, cfg);
		});
	    });

	    panel.setHeader(M.str.editor.browse);
	    panel.setBody('<div id="layout-'+pickerid+'"></div>');
	    panel.render();
	    this.panel = panel;
	    this.rendered = true;
	},

	hide: function() {
	    this.panel.hide();
	},

	show: function() {
	    if (this.rendered) {
		this.panel.show();
	    } else {
		this.launch();
	    }
	},

	launch: function() {
	    this.render();
	}
    });

    // create the picker panel
    var panel = new GradebookPickerHelper(options);

    // show the panel when the button is clicked
    button.on('click', function(e) {
	panel.show();
    });
};
