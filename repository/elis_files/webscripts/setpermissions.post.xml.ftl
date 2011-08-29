<?xml version="1.0" encoding="UTF-8" ?>
<permissions>
<#list permissions as permission>
    <permission name="${permission[2]}" for="${permission[1]}">${permission[0]}</permission>
</#list>
</permissions>
