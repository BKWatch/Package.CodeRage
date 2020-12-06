<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet  version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:op="http://www.coderage.com/2012/operation">
  <xsl:output method="xml" indent="yes"/>
  
  <xsl:template match="/">
    <scheduledOperationList xmlns="http://www.coderage.com/2012/operation" xmlns:xi="http://www.w3.org/2001/XInclude">
      <description><xsl:value-of select="op:executionPlan/op:description"/></description>
      <operations xmlns="http://www.coderage.com/2012/operation">
        <operation>
          <description>Clears the history of events</description>
          <properties>
            <property name="cost" value="0.00"/>
          </properties>
          <schedule time="2010-01-01T00:00:00+00:00"></schedule>            
          <name>clearHistory</name>
          <instance class="CodeRage.Test.Operations.Test.Event"/>
          <input/>
          <output>1</output>
        </operation> 
        <xsl:for-each select="op:executionPlan/op:operations/op:operation[op:schedule/@time]">
          <xsl:call-template name="process-operation"/>
        </xsl:for-each> 
        <operation xmlns="http://www.coderage.com/2012/operation">
          <description>Verify the history of events</description>
          <properties>
            <property name="cost" value="0.00"/>
          </properties>
          <xmlEncoding>
            <listElement name="history" itemName="step"/>
          </xmlEncoding>
          <schedule time="2050-01-01T00:00:00+00:00"></schedule>          
          <name>verifyHistory</name>
          <instance class="CodeRage.Test.Operations.Test.Event"/>          
          <input>
            <arg>
              <history>
                <xsl:for-each select="op:executionPlan/op:steps/op:step">
                  <step><xsl:value-of select="@date"/></step>
                </xsl:for-each>                   
              </history>
              <tolerance>120</tolerance>
            </arg>
          </input>
          <output>1</output>
        </operation>
      </operations>
      <repeatingOperations xmlns="http://www.coderage.com/2012/operation">
        <xsl:for-each select="op:executionPlan/op:operations/op:operation[op:schedule/@from]">
          <xsl:call-template name="process-operation"/>
        </xsl:for-each>   
      </repeatingOperations>
    </scheduledOperationList>
  </xsl:template>
  
  <xsl:template name="process-operation">
    <operation xmlns="http://www.coderage.com/2012/operation">
      <description><xsl:value-of select="@label"/></description>
      <properties>
        <property name="cost" value="0.00"/>
      </properties>
      <xsl:if test="op:schedule/@time">
        <schedule time="{op:schedule/@time}"></schedule>  
      </xsl:if>      
      <xsl:if test="op:schedule/@from">
        <schedule from="{op:schedule/@from}" to="{op:schedule/@to}" repeat="{op:schedule/@repeat}"></schedule>
      </xsl:if>
      <name>trigger</name>
      <instance class="CodeRage.Test.Operations.Test.Event"/>
      <input/>
      <output>1</output>
    </operation>
  </xsl:template>
  
</xsl:stylesheet>
