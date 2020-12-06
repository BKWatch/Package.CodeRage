<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    exclude-result-prefixes="xs"
    version="1.0" xmlns:tns="http://www.test.com">

    <xsl:param name="testparam"/>

    <xsl:template match="/">
        <output>
            <one>
                <xsl:value-of select="$testparam"/>
            </one>
            <two>
                <xsl:value-of select="/tns:input/tns:one"/>
            </two>
        </output>
    </xsl:template>
</xsl:stylesheet>
