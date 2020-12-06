<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
  xmlns:ts="http://www.coderage.com/2007/testsuite">

  <!--

    Takes a document conforming to testSuite.xsd and produces
    an HTML report with embedded JavaScript and CSS

    The HTML report should have these features:

    - When first loaded in a browser, only the top-level test suite name and
      description, the names and descriptions of the second-level test cases
      and suites, and the pass/fail status of each of the second-level tests
      and suites should be visible.
    - Test suites and cases which failed should be displayed with a red
      background
    - The user should be able to toggle a test suite to display or hide its
      children, and to toggle a test case to display or hide its output
      text output should be displayed within a 'pre' element
    - The output must be easy to read and professional looking
      when displayed in a browser
    - The output must confrm to the schema
      http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd

  -->

</xsl:stylesheet>