<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet  version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:op="http://www.coderage.com/2012/operation">
  <xsl:output method="xml"/>

  <xsl:param name="protocol"/>
  <xsl:param name="idekey"/>

  <xsl:template match="/">
    <xsl:for-each select="child::op:operation">
      <xsl:call-template name="process-operation"/>
    </xsl:for-each>
    <xsl:for-each select="child::op:operationList">
      <xsl:call-template name="process-operation-list"/>
    </xsl:for-each>
    <xsl:for-each select="child::op:scheduledOperationList">
      <xsl:call-template name="process-scheduled-operation-list"/>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="process-operation">
    <xsl:choose>
      <xsl:when test="op:properties/op:property[@name='skipProtocols' and contains(@value, $protocol)]">
        <xsl:call-template name="always-pass"/>
      </xsl:when>
      <xsl:otherwise>
        <op:operation xmlns="http://www.coderage.com/2012/operation">
          <op:description><xsl:value-of select="op:description"/></op:description>
          <xsl:for-each select="op:properties">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <xsl:for-each select="op:config">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <xsl:for-each select="op:nativeDataEncoding">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <xsl:for-each select="op:xmlEncoding">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <xsl:for-each select="op:dataMatching">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <xsl:for-each select="op:termination">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <xsl:for-each select="op:schedule">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <xsl:call-template name="process-instance"/>
          <xsl:for-each select="op:input">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <xsl:for-each select="op:output">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <xsl:for-each select="op:exception">
            <xsl:copy-of select="."/>
          </xsl:for-each>
        </op:operation>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="process-operation-list">
    <xsl:choose>
      <xsl:when test="op:properties/op:property[@name='skipProtocols' and contains(@value, $protocol)]">
        <xsl:call-template name="always-pass"/>
      </xsl:when>
      <xsl:otherwise>
        <op:operationList xmlns="http://www.coderage.com/2012/operation">
          <op:description><xsl:value-of select="op:description"/></op:description>
          <xsl:for-each select="op:properties">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <xsl:for-each select="op:config">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <op:operations>
            <xsl:for-each select="op:operations/op:operation">
              <xsl:call-template name="process-operation"/>
            </xsl:for-each>
          </op:operations>
        </op:operationList>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="process-scheduled-operation-list">
    <xsl:choose>
      <xsl:when test="op:properties/op:property[@name='skipProtocols' and contains(@value, $protocol)]">
        <xsl:call-template name="always-pass"/>
      </xsl:when>
      <xsl:otherwise>
        <op:scheduledOperationList xmlns="http://www.coderage.com/2012/operation">
          <op:description><xsl:value-of select="op:description"/></op:description>
          <xsl:for-each select="op:properties">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <xsl:for-each select="op:config">
            <xsl:copy-of select="."/>
          </xsl:for-each>
          <op:operations>
            <xsl:for-each select="op:operations/op:operation">
              <xsl:call-template name="process-operation"/>
            </xsl:for-each>
          </op:operations>
          <xsl:if test="op:repeatingOperations">
            <op:repeatingOperations>
              <xsl:for-each select="op:repeatingOperations/op:operation">
                <xsl:call-template name="process-operation"/>
              </xsl:for-each>
            </op:repeatingOperations>
          </xsl:if>
        </op:scheduledOperationList>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="process-instance">
    <xsl:choose>
      <xsl:when test="op:instance">
        <xsl:for-each select="op:instance">
          <op:name>execute</op:name>
          <op:instance class="CodeRage.WebService.OperationExecutor">
            <op:param name="method" value="{../op:name}"/>
            <op:param name="class" value="{@class}"/>
            <xsl:if test="@classPath">
              <op:param name="classPath" value="{@classPath}"/>
            </xsl:if>
            <xsl:for-each select="op:param">
              <op:param name="param.{@name}" value="{@value}"/>
            </xsl:for-each>
            <op:param name="protocol" value="{$protocol}"/>
            <xsl:if test="$idekey">
              <op:param name="idekey" value="{$idekey}"/>
            </xsl:if>
          </op:instance>
        </xsl:for-each>
      </xsl:when>
      <xsl:otherwise>
        <op:name><xsl:value-of select="op:name"/></op:name>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="always-pass">
    <op:operation>
      <op:description>Skipping protocol <xsl:value-of select="$protocol"/></op:description>
      <xsl:for-each select="op:properties">
        <xsl:copy-of select="."/>
      </xsl:for-each>
      <op:name>abs</op:name>
      <op:input><op:arg>1</op:arg></op:input>
      <op:output>1</op:output>
    </op:operation>
  </xsl:template>

</xsl:stylesheet>
