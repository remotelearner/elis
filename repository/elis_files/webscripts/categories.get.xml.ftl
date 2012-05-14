<#macro childCategories node>
<#compress>
    <category>
        <uuid>${node.id}</uuid>
        <name>${node.properties.name?replace("&", "&amp;")?replace("<", "&lt;")?replace(">", "&gt;")?replace("'", "&apos;")?replace("\"", "&quot;")}</name>
        <#if 0 &lt; node.immediateSubCategories?size>
            <categories>
            <#list node.immediateSubCategories as childNode>
                <@childCategories node=childNode/>
            </#list>
            </categories>
        </#if>
    </category>
</#compress>
</#macro>
<?xml version="1.0" encoding="UTF-8" ?>
<#compress>
<#list companyhome.childrenByLuceneSearch["PATH:\"/cm:generalclassifiable\""] as child>
<categories>
    <#list classification.getRootCategories("cm:generalclassifiable") as rootnode>
        <@childCategories node=rootnode/>
    </#list>
</categories>
</#list>
</#compress>
