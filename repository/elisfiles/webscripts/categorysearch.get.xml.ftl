<?xml version="1.0" encoding="UTF-8"?>
<nodes>
<#list resultset as node>
  <node>
    <title>${node.name?replace("&", "&amp;")?replace("<", "&lt;")?replace(">", "&gt;")?replace("'", "&apos;")?replace("\"", "&quot;")}</title>
    <link rel="alternate" href="${absurl(url.serviceContext)}/api/node/content/${node.nodeRef.storeRef.protocol}/${node.nodeRef.storeRef.identifier}/${node.nodeRef.id}/${node.name?url}"/>
    <icon>${absurl(url.context)}${node.icon16}</icon>
    <uuid>${node.id}</uuid>
    <updated>${xmldate(node.properties.modified)}</updated>
    <summary>${node.properties.description!""}</summary>
    <author> 
      <name>${node.properties.creator}</name>
    </author> 
  </node>
</#list>
</nodes>
