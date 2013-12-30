<?xml version="1.0" encoding="UTF-8" ?>
<feed>
  <generator version="${server.version}">Alfresco (${server.edition})</generator>
  <title>Alfresco Lucene Search</title>
  <updated>${xmldate(date)}</updated>
  <icon>${absurl(url.context)}/images/logo/AlfrescoLogo16.ico</icon>
  <author>
    <name><#if person??>${person.properties.userName}<#else>unknown</#if></name>
  </author>
  <#list resultset as row>
    <entry>
      <title>${row.name?xml}</title>
      <updated>${xmldate(row.properties.modified)}</updated>
      <uuid>${row.id}</uuid>
      <noderef>${row.nodeRef}</noderef>
      <updated>${xmldate(row.properties.modified)}</updated>
      <summary>${(row.properties.description!"")?html}</summary>
      <author> 
        <name>${row.properties.creator}</name>
      </author>
    </entry>
  </#list>
</feed>