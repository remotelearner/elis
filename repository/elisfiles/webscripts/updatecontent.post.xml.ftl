<xml>
   <node>
     <uuid>${node.id}</uuid>
     <filename>${node.name?replace("&", "&amp;")?replace("<", "&lt;")?replace(">", "&gt;")?replace("'", "&apos;")?replace("\"", "&quot;")}</filename>
   </node>
</xml>
