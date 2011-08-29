<?xml version="1.0" encoding="UTF-8" ?>
<categories>
<#list categories as category>
    <category>
        <uuid>${category['uuid']}</uuid>
        <name>${category['name']?replace("&", "&amp;")?replace("<", "&lt;")?replace(">", "&gt;")?replace("'", "&apos;")?replace("\"", "&quot;")}</name>
    </category>
</#list>
</categories>
