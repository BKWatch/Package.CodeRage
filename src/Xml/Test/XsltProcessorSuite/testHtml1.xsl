<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns="http://www.w3.org/1999/xhtml"
  version="3.0">
  <xsl:output method="xhtml"/>

  <xsl:template match="/">
    <html>
      <head>
       <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <title><xsl:value-of select="testCase/title"/></title>
      </head>
      <body>
        <p><xsl:value-of select="testCase/body"/></p>
      </body>
    </html>
  </xsl:template>

</xsl:stylesheet>
