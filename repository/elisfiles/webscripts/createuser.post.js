var username     = '';
var title        = '';
var firstName    = '';
var lastName     = '';
var organization = '';
var jobTitle     = '';
var email        = '';
var password     = '';
var quota        = '';

// Process form data submitted in a "multipart/form-data" Content-Type.
for each (field in formdata.fields) {
	if (field.name.toLowerCase() == 'username') {
    	username = field.value;
	} else if (field.name.toLowerCase() == 'title') {
    	title = field.value;
	} else if (field.name.toLowerCase() == 'firstname') {
    	firstName = field.value;
	} else if (field.name.toLowerCase() == 'lastname') {
    	lastName = field.value;
	} else if (field.name.toLowerCase() == 'organization') {
    	organization = field.value;
	} else if (field.name.toLowerCase() == 'jobtitle') {
    	jobTitle = field.value;
	} else if (field.name.toLowerCase() == 'email') {
    	email = field.value;
	} else if (field.name.toLowerCase() == 'password') {
    	password = field.value;
	} else if (field.name.toLowerCase() == 'quota') {
		quota = '' + field.value;  // Make sure that it's a string value.
	}
}

var user = null;

// Perform some sanity checking to ensure required values are specified.
if (username == '') {
	status.code     = 400;
	status.message  = 'username property is required';
	status.redirect = true;
} else if (firstName == '') {
	status.code     = 400;
	status.message  = 'firstname property is required';
	status.redirect = true;
} else if (lastName == '') {
	status.code     = 400;
	status.message  = 'lastname property is required';
	status.redirect = true;
} else if ((user = people.getPerson(username)) != null) {
	user.properties['firstName'] = firstName;
	user.properties['lastName']  = lastName;

	if (email != '') {
		user.properties['cm:email'] = email;
	}
	if (title != '') {
		user.properties['cm:title'] = title;
	}
	if (organization != '') {
		user.properties['cm:organization'] = organization;
	}
	if (jobTitle != '') {
		user.properties['cm:jobtitle'] = jobTitle;
	}

	user.save();

	if (password != '') {
		people.setPassword(username, password);
	}

	if (quota != '') {
		people.setQuota(user, quota);
	}

	// Just make sure that we actually have the full user information.	
	user = people.getPerson(username);

	model.newuser = user;
	
	model.jobTitle = '';

	if (user.properties['jobTitle'] != null) {
		model.jobTitle = user.properties['jobTitle'];
	} else if (user.properties['jobtitle'] != null) {
		model.jobTitle = user.properties['jobtitle'];
	}

} else {
	var newuser = people.createPerson(username, firstName, lastName, email, password, true);

	// Assign optional values to the new user's properties
	newuser.properties['cm:title']        = title;
	newuser.properties['cm:organization'] = organization;
	newuser.properties['cm:jobtitle']     = jobTitle;
	newuser.save();

	if (quota != '') {
		people.setQuota(newuser, quota);
	}

	// Just make sure that we actually have the full user information.	
	newuser = people.getPerson(username);

	// Make user data available to the Freemarker template
	model.newuser = newuser;
	
	model.jobTitle = '';

	if (newuser.properties['jobTitle'] != null) {
		model.jobTitle = newuser.properties['jobTitle'];
	} else if (newuser.properties['jobtitle'] != null) {
		model.jobTitle = newuser.properties['jobtitle'];
	}
}
