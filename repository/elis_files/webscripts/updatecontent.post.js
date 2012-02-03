/**
 * Update the content in a node method
 * 
 * @method POST
 * @param uuid {string}
 *        filedata {file}
 */

function main()
{
   var filename = null;
   var content = null;
   var uuid = null;
   
   // locate file attributes
   for each (field in formdata.fields)
   {
      if (field.name == "filedata" && field.isFile)
      {
         filename = field.filename;
         content = field.content;
      }
      else if (field.name == "uuid")
      {
         uuid = field.value;
      }
   }
   
   // ensure all mandatory attributes have been located
   if (filename == undefined || content == undefined)
   {
      status.code = 400;
      status.message = "Uploaded file cannot be located in request";
      status.redirect = true;
      return;
   }
   if (uuid == null || uuid.length == 0)
   {
      status.code = 500;
      status.message = "UUID parameter not supplied.";
      status.redirect = true;
      return;
   }

   var nodeRef = 'workspace://SpacesStore/' + uuid;
   var node = search.findNode(nodeRef);

   // ensure we found a valid user and that it is the current user or we are an admin
   if (node == null)
   {
      status.code = 500;
      status.message = "Failed to locate node with UUID '" + uuid + "'";
      status.redirect = true;
      return;
   }

   node.properties.content.write(content);
   node.properties.content.guessMimetype(filename);
   node.properties.content.encoding = "UTF-8";
   node.save()
   
   // save ref to be returned
   model.node = node;
}

main();