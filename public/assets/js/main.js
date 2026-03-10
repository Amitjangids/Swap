function chkFeatureForm() {

var valid = false;

if (document.getElementById('ttl').value == "") {
  alert("Title field can't be left blank");
  document.getElementById('ttl').focus();
}
else if (document.getElementById('img').value == "") {
  alert("Please select image!");
  document.getElementById('img').focus();
}
else if (document.getElementById('icon_image').value == "") {
  alert("Please select icon!");
  document.getElementById('icon_image').focus();
}
else if (document.getElementById("editor").value == "") {
  alert("Description field can't be left blank");
  document.getElementById("editor").focus();
}
else {
  valid = true;	
}
return valid;
}

function chkEditFeatureForm() {
var valid = false;

if (document.getElementById('ttl').value == "") {
  alert("Title field can't be left blank");
  document.getElementById('ttl').focus();
}
else if (document.getElementById("editor").value == "") {
  alert("Description field can't be left blank");
  document.getElementById("editor").focus();
}
else {
  valid = true;	
}
return valid;
}

function chkEventForm() {
 var valid = false;
 if (document.getElementById("ttl").value == "") {
    alert("Event title field can't be left blank");
	document.getElementById("ttl").focus();
 }
 else if (document.getElementById("sub_ttl").value == "") {
   alert("Event subtitle field can't be left blank");
   document.getElementById("sub_ttl").focus();	
 }
 else if (document.getElementById("evnt_image").value == "") {
   alert("Event image field can't be left blank");
   document.getElementById("evnt_image").focus();	
 }
 else if (document.getElementById("evnt_desc").value == "") {
   alert("Event description field can't be left blank");
   document.getElementById("evnt_desc").focus();	
 }
 else if (document.getElementById("evnt_date").value == "") {
   alert("Event start date field can't be left blank");
   document.getElementById("evnt_date").focus();   
 }
 else if (document.getElementById("evnt_end_date").value == "") {
   alert("Event end date field can't be left blank");
   document.getElementById("evnt_end_date").focus();   
 }
 else {
   var stDt = new Date(document.getElementById("evnt_date").value);
   var edDt = new Date(document.getElementById("evnt_end_date").value);
   if (edDt < stDt) {
	   alert("Invalid End Date!");
	   document.getElementById("evnt_end_date").focus();
	   valid = false;	
	}
   else {	
   valid = true;
   }
 }
 //alert("Valid: "+valid);
 return valid;
}

function chkEditEventForm() {
  var valid = false;
 if (document.getElementById("ttl").value == "") {
    alert("Event title field can't be left blank");
	document.getElementById("ttl").focus();
 }
 else if (document.getElementById("sub_ttl").value == "") {
   alert("Event subtitle field can't be left blank");
   document.getElementById("sub_ttl").focus();	
 }
 else if (document.getElementById("evnt_desc").value == "") {
   alert("Event description field can't be left blank");
   document.getElementById("evnt_desc").focus();	
 }
 else if (document.getElementById("evnt_date").value == "") {
   alert("Event start date field can't be left blank");
   document.getElementById("evnt_date").focus();   
 }
 else if (document.getElementById("evnt_end_date").value == "") {
   alert("Event end date field can't be left blank");
   document.getElementById("evnt_end_date").focus();   
 }
 else {
   var stDt = new Date(document.getElementById("evnt_date").value);
   var edDt = new Date(document.getElementById("evnt_end_date").value);
   if (edDt < stDt) {
	   alert("Invalid End Date!");
	   document.getElementById("evnt_end_date").focus();
	   valid = false;	
	}
   else {	
   valid = true;
   }	 
 }
 return valid;
}

function chkAddLocForm()
{
  var valid = false;
  if (document.getElementById("loc_name").value == "")
  {
	 alert("Location name field can't be left blank");
	 document.getElementById("loc_name").focus();
  }
  else if (document.getElementById("loc_image").value == "")
  {
	alert("Location image field can't be left blank");
	document.getElementById("loc_image").focus();	
  }
  else if (document.getElementById("loc_desc").value == "")
  {
	alert("Location description field can't be left blank");
	document.getElementById("loc_desc").focus();	
  }
  else if (document.getElementById("loc_addr").value == "")
  {
	alert("Location address field can't be left blank");
	document.getElementById("loc_addr").focus();	
  }
  else if (document.getElementById("loc_shrt_addr").value == "")
  {
	alert("Location short address field can't be left blank");
	document.getElementById("loc_shrt_addr").focus();	
  }
  else if (document.getElementById("loc_lat").value == "")
  {
	alert("Location latitude field can't be left blank");
	document.getElementById("loc_lat").focus();	
  }
  else if (document.getElementById("loc_long").value == "")
  {
	alert("Location longitude field can't be left blank");
	document.getElementById("loc_long").focus();	
  }
  else if (isNaN(document.getElementById("loc_lat").value))
  {
	alert("Invalid Location Latitude! Use number only.");
	document.getElementById("loc_lat").focus();	
  }
  else if (isNaN(document.getElementById("loc_long").value))
  {
	alert("Invalid Location Longitude! Use number only.");
	document.getElementById("loc_long").focus();  
  }
  else {
	 valid = true; 
  }
  return valid;
}

function chkEditLocationForm()
{
  var valid = false;
  if (document.getElementById("loc_name").value == "")
  {
	 alert("Location name field can't be left blank");
	 document.getElementById("loc_name").focus();
  }
  else if (document.getElementById("loc_desc").value == "")
  {
	alert("Location description field can't be left blank");
	document.getElementById("loc_desc").focus();	
  }
  else if (document.getElementById("loc_addr").value == "")
  {
	alert("Location address field can't be left blank");
	document.getElementById("loc_addr").focus();	
  }
  else if (document.getElementById("loc_shrt_addr").value == "")
  {
	alert("Location short address field can't be left blank");
	document.getElementById("loc_shrt_addr").focus();	
  }
  else if (document.getElementById("loc_lat").value == "")
  {
	alert("Location latitude field can't be left blank");
	document.getElementById("loc_lat").focus();	
  }
  else if (document.getElementById("loc_long").value == "")
  {
	alert("Location longitude field can't be left blank");
	document.getElementById("loc_long").focus();	
  }
  else if (isNaN(document.getElementById("loc_lat").value))
  {
	alert("Invalid Location Latitude! Use number only.");
	document.getElementById("loc_lat").focus();	
  }
  else if (isNaN(document.getElementById("loc_long").value))
  {
	alert("Invalid Location Longitude! Use number only.");
	document.getElementById("loc_long").focus();  
  }
  else {
	 valid = true; 
  }
  return valid;	
}
