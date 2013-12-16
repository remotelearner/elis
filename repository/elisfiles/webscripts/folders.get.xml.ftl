<#macro childFolders node>
<#compress>
	<folder>
		<uuid>${node.id}</uuid>
		<name>${node.properties.name?replace("&", "&amp;")?replace("<", "&lt;")?replace(">", "&gt;")?replace("'", "&apos;")?replace("\"", "&quot;")}</name>
		<#if 0 &lt; node.children?size>
			<folders>
			<#list node.children as childNode>
				<#if childNode.isContainer>
					<@childFolders node=childNode/>
				</#if>
			</#list>
			</folders>
		</#if>
	</folder>
</#compress>
</#macro>
<?xml version="1.0" encoding="UTF-8" ?>
<#compress>
<#list companyhome.childrenByLuceneSearch["PATH:\"/app:company_home\""] as child>
<folders>
	<#list child.children as rootnode>
		<#if rootnode.isContainer>
			<@childFolders node=rootnode/>
		</#if>
	</#list>
</folders>
</#list>
</#compress>
