<?xml version="1.0" encoding="UTF-8" ?>
<node>
    <uuid>${node.id}</uuid>
    <name>${node.properties.name?replace("&", "&amp;")?replace("<", "&lt;")?replace(">", "&gt;")?replace("'", "&apos;")?replace("\"", "&quot;")}</name>
</node>
