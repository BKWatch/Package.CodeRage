<?php

/**
 * Generates HTTP responses based on posted JSON data; to view documentation,
 * request /CodeRage/Tool/Test/mock-response.php?help
 *
 * File:        CodeRage/Tool/Test/__www__/mock-response.php
 * Date:        Thu Jan 18 22:53:59 UTC 2018
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

require __DIR__ . '/../../../../vendor/autoload.php';

use CodeRage\Tool\Test\MockResponse;
use CodeRage\Util\Array_;

/**
 * @var array
 */
const GENERIC_DOCUMENT_NAME =
    [
        'application/json' => 'json.json',
        'application/pdf' => 'pdf.pdf',
        'image/bmp' => 'bmp.bmp',
        'image/gif' => 'gif.gif',
        'image/jpeg' => 'jpeg.jpg',
        'image/png' => 'png.png',
        'image/vnd.microsoft.icon' => 'ico.ico',
        'text/css' => 'css.css',
        'text/html' => 'html5.html',
        'text/xml' => 'xml-1.0.xml'
    ];

/**
 * @var array
 */
const GENERIC_DOCUMENT_BASE_URL =
    'https://raw.githubusercontent.com/mathiasbynens/small/master/';

/**
 * @var array
 */
const SUPPORTED_INPUT_TYPES =
    [
        'text' => 1,
        'password' => 1,
        'checkbox' => 1,
        'radio' => 1,
        'submit' => 1,
        'hidden' => 1
    ];

/**
 * @var string
 */
const MATCH_JSON = '#^application/json\b#';

/**
 * @var string
 */
const MATCH_ATTRIBUTE = '/^[-.:_a-z0-9]+$/';

/**
 * @var array
 */
const HIDDEN_QUERY_PARAMETERS = ['XDEBUG_SESSION_START' => 1];

?>
<?php if ($_SERVER['REQUEST_METHOD'] == 'GET' && parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) == 'help') { ?>

<!DOCTYPE html>
<html>
  <head>
    <title>CodeRage\Tool\Robot Mock Page</title>
    <style type="text/css">
    body {
        font-family: monospace;
    }
    h1 {
        font-size: 24px;
    }
    h2 {
        font-size: 16px;
    }
    h3 {
        font-size: 14px;
        margin-top: 3px;
        margin-bottom: 3px;
    }
    p {
        font-size: 14px;
        margin-top: 10px;
        margin-bottom: 6px;
    }
    pre {
        border: 1px solid #000;
        margin-top: 0;
        margin-bottom: 10px;
        padding: 1em;
        background-color: #eee;

    }
    dt {
        margin-top: 10px;
        font-weight: bold;
    }
    dd {
        margin-top: 3px;
        margin-bottom: 10px;
    }
    </style>
  </head>
  <body>
    <h1>CodeRage\Tool\Robot Mock Response</h1>
    <h2>Description</h2>
	<p>Used to test CodeRage\Tool\Robot.</p>
	<p>
	  Generates custom HTTP responses based on input posted as JSON or encoded
	  as a query string using Bracket Object Notation. The input may consist of
	  an associative collection of options or an array of such
	  collections. When multiple collections of options are present, one of
	  them is selected based on the values of the <b>ifRequestedAfter</b> and
	  <b>ifRequestedBefore</b> options, as described below.
	</p>
	<h2>Options</h2>
	<dl>
	  <dt>statusCode</dt>
	  <dd>The HTTP status code (optional)</dd>

	  <dt>reasonPhrase</dt>
	  <dd>The HTTP reason phrase (optional)</dd>

	  <dt>contentType</dt>
	  <dd>The value of the Content-Type response header (optional)</dd>

	  <dt>headers</dt>
	  <dd>An associative collection of response headers (optional)</dd>

	  <dt>cookies</dt>
	  <dd>The collection of cookies contained in Set-Cookie headers, as an array
	    of name/value pairs (optional)</dd>

	  <dt>sleep</dt>
	  <dd>The number of milliseconds to wait before returning a response;
	    incompatible with the option <b>forms</b> (optional)</dd>

	  <dt>body</dt>
	  <dd>The HTTP response body; incompatible with the option <b>forms</b>
	    (optional)</dd>

	  <dt>forms</dt>
	  <dd>The collection of HTML forms to be included in the response body,
	    as an array of associative collections (optional). Refer to
	    <b>Examples</b> for additional details.</dd>

	  <dt>ifRequestedAfter</dt>
	  <dd>A UNIX timestamp to be compared with the timestamp of the request.
	    (optional). If the request timestamp is strictly less than this value,
	    the collection of options containing this value will be ignored.</dd>

	  <dt>ifRequestedBefore</dt>
	  <dd>A UNIX timestamp to be compared with the timestamp of the request
	    (optional). If the request timestamp is greater than or equal to this
	    value, the collection of options containing this value will be ignored.</dd>
	</dl>
    <h2>Examples</h2>
    <ol>
      <li>
        <p>Generic PDF</p>
        <h3>Request</h3>
          <pre>{
  "contentType" : "application/pdf"
}</pre>
      </li>
      <li>
        <p>Specific PDF</p>
        <h3>Request</h3>
	      <pre>{
  "contentType" : "application/pdf",
  "body" : "%PDF-1.1\n\xc2\xa5\xc2\xb1\xc3\xab  \n\n1 0 obj\n  << /Type /Catalog\n..."
}</pre>
      </li>
      <li>
        <p>HTTP Error</p>
        <h3>Request</h3>
	      <pre>{
  "statusCode" : "404",
  "reasonPhrase" : "Not Found",
  "contentType" : "text/html",
  "sleep" : 100
}</pre>
      </li>
      <li>
        <p>Single HTML form</p>
        <h3>Request</h3>
	      <pre>{
  "forms" : [
    {
      "name" : "login",
      "method" : "post",
      "inputs" : [
        {
          "type" : "text",
          "name" : "username",
          "value" : "John Smith",
          "label" : "Login"
        },
        {
          "type" : "password",
          "name" : "password",
          "label" : "Password"
        },
        {
          "type" : "submit",
          "name" : "submit",
          "value" : "Submit"
        }
      ]
    }
  ]
}</pre>
        <h3>Response</h3>
        <pre>&lt;!DOCTYPE html&gt;
&lt;html xmlns="http://www.w3.org/1999/xhtml"&gt;
  &lt;head&gt;
    &lt;title&gt;Mock Page&lt;/title&gt;
  &lt;/head&gt;
  &lt;body&gt;
    &lt;form name="login" method="post" enctype="application/x-www-form-urlencoded"&gt;
      &lt;label&gt;Username: &lt;input type="text" name="login" value="John Smith"/&gt;&lt;/label&gt;&lt;br/&gt;
      &lt;label&gt;Password: &lt;input type="password" name="password"/&gt;&lt;/label&gt;&lt;br/&gt;
      &lt;input type="submit" name="submit" value="Submit"/&gt;&lt;br/&gt;
    &lt;/form&gt;
  &lt;/body&gt;
&lt;/html&gt;</pre>
      <li>
        <p>Two HTML forms</p>
        <h3>Request</h3>
	      <pre>{
  "forms" : [
    {
      "name" : "login",
      "id" : "login",
      "method" : "post",
      "enctype" : "multipart/form-data",
      "inputs" : [
        {
          "type" : "text",
          "name" : "username",
          "id" : "login-user",
          "value" : "John Smith"
        },
        {
          "type" : "password",
          "name" : "password",
          "id" : "login-password"
        },
        {
          "type" : "checkbox",
          "name" : "rememberme",
          "id" : "login-rememberme",
          "attribues" : {
            "checked" : "checked"
          }
        },
        {
          "type" : "select",
          "name" : "names",
          "id" : "select_1",
          "multiple" : "true",
          "options" : [
            {
              "value" : "ben",
              "label" : "Ben"
            },
            {
              "value" : "carl",
              "label" : "Carl",
              "selected" : "true"
            }
          ]
        },
        {
          "type" : "submit",
          "name" : "submit",
          "value" : "Submit"
        },
        {
          "type" : "submit",
          "name" : "cancel",
          "value" : "Cancel"
        }
      ]
    },
    {
      "name" : "logout",
      "id" : "logout",
      "method" : "get",
      "enctype" : "application/x-www-form-urlencoded",
      "action" : "/logout.php",
      "inputs" : [
        {
          "type" : "file",
          "name" : "file_1",
          "label" : "Select File",
          "accept" : "image/*",
          "multiple" : "true"
        },
        {
          "type" : "submit",
          "name" : "logout",
          "value" : "Logout"
        }
      ]
    }
  ]
}</pre>
        <h3>Response</h3>
        <pre>&lt;!DOCTYPE html&gt;
&lt;html&gt;
  &lt;head&gt;
    &lt;title&gt;CodeRage\Tool\Robot Mock Response&lt;/title&gt;
  &lt;/head&gt;
  &lt;body&gt;
    &lt;form name="login" id="login" method="post" enctype="multipart/form-data"&gt;
      &lt;input type="text" name="username" id="login-user" value="John Smith"/&gt;&lt;br/&gt;
      &lt;input type="password" name="password" id="login-password"/&gt;&lt;br/&gt;
      &lt;input type="checkbox" name="rememberme" id="login-rememberme"/&gt;&lt;br/&gt;
      &lt;select name="names" id="select_1" multiple=""&gt;&lt;option value="ben"&gt;Ben&lt;/option&gt;&lt;option value="carl" selected=""&gt;Carl&lt;/option&gt;&lt;/select&gt;&lt;br/&gt;
      &lt;input type="submit" name="submit" value="Submit"/&gt;&lt;br/&gt;
      &lt;input type="submit" name="cancel" value="Cancel"/&gt;&lt;br/&gt;
    &lt;/form&gt;
    &lt;form name="logout" id="logout" method="get" enctype="application/x-www-form-urlencoded" action="/logout.php"&gt;
      &lt;label&gt;Select File: &lt;input type="file" name="file_1" accept="image/*" multiple=""/&gt;&lt;/label&gt;&lt;br/&gt;
      &lt;input type="submit" name="logout" value="Logout"/&gt;&lt;br/&gt;
    &lt;/form&gt;
  &lt;/body&gt;
&lt;/html&gt;</pre>
      <li>
        <p>Multiple objects containing HTML forms. Response will be genereated using the object which contains the current
        time in its interval defined by ifRequestedAfter and ifRequestedBefore.
        The interval formed by the two timestamps is regarded as haf-open i.e [ifRequestedAfter, ifRequestedBefore)</p>
        <h3>Request</h3>
	      <pre>[
    {
      "forms" : [
        {
          "name" : "logout",
          "id" : "logout",
          "method" : "get",
          "enctype" : "application/x-www-form-urlencoded",
          "action" : "/logout.php",
          "inputs" : [
            {
              "type" : "text",
              "name" : "username",
              "value" : "John Smith",
              "label" : "Login"
            },
            {
              "type" : "password",
              "name" : "password",
              "label" : "Password"
            },
            {
              "type" : "submit",
              "name" : "submit",
              "value" : "Submit"
            }
          ]
        }
      ],
      "ifRequestedAfter" : 1523259526,
      "ifRequestedBefore" : 1523259536
    },
    {
      "forms" : [
        {
          "name" : "logout",
          "id" : "logout",
          "method" : "get",
          "enctype" : "application/x-www-form-urlencoded",
          "action" : "/logout.php",
          "inputs" : [
            {
              "type" : "file",
              "name" : "file_1",
              "label" : "Select File",
              "accept" : "image/*",
              "multiple" : "true"
            },
            {
              "type" : "submit",
              "name" : "logout",
              "value" : "Logout"
            }
          ]
        }
      ],
      "ifRequestedAfter" : 1523259536
    }
]</pre>
        <h3>Response</h3>
        <pre>&lt;!DOCTYPE html&gt;
&lt;html&gt;
  &lt;head&gt;
    &lt;title&gt;CodeRage\Tool\Robot Mock Page&lt;/title&gt;
  &lt;/head&gt;
  &lt;body&gt;
    &lt;form name="logout" id="logout" method="get" enctype="application/x-www-form-urlencoded" action="/logout.php"&gt;
      &lt;label&gt;Select File: &lt;input type="file" name="file_2" accept="image/*" multiple=""/&gt;&lt;/label&gt;&lt;br/&gt;
      &lt;input type="submit" name="logout" value="Logout"/&gt;&lt;br/&gt;
    &lt;/form&gt;
  &lt;/body&gt;
&lt;/html&gt;
        </pre>
    </ol>
  </body>
</html>

<?php } else { ?>

<?php

function handleRequest()
{
    try {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == 'GET') {
            $url = $_SERVER['REQUEST_URI'];
            handleGet($url);
        } elseif ($method == 'POST') {
            if (!isset($_SERVER['HTTP_CONTENT_TYPE']))
                throw new Exception('Missing Content-Type header');
            $contentType = $_SERVER['HTTP_CONTENT_TYPE'];
            if (preg_match(MATCH_JSON, $contentType)) {
                $body = stream_get_contents(fopen('php://input', 'r'));
                handleJson($body);
            } else {
                throw new Exception("Unsupported content type: $contentType");
            }
        } else {
            httpError(
                400, 'Bad Request',
                "Invalid request method: expected GET or POST; found '$method'"
            );
        }
    } catch (Throwable $e) {
        httpError(500, 'Internal Server Error', $e->getMessage());
    }
}

function handleGet($url)
{
    $params = [];
    $query = parse_url($url, PHP_URL_QUERY);
    $parts = $query != '' ? explode('&', $query) : [];
    foreach ($parts as $p) {
        $n = $v = null;
        if (($pos = strpos($p, '=')) !== false) {
            $n = rawurldecode(substr($p, 0, $pos));
            $v = rawurldecode(substr($p, $pos + 1));
        } else {
            $n = rawurldecode($p);
            $v = '';
        }
        if (array_key_exists($n, HIDDEN_QUERY_PARAMETERS))
            continue;
        $params[] = [$n, $v];
    }
    $options = null;
    try {
        $options =
            \CodeRage\Util\BracketObjectNotation::decode(
                $params,
                ['objectsAsArrays' => true]
            );
    } catch (Throwable $e) {
        httpError(400, 'Bad Request', $e->getMessage());
    }
    handleJson(json_encode($options));
}

function handleJson($body)
{
    $options = null;
    try {
        $options = parseInput($body);
    } catch (Throwable $e) {
        httpError(400, 'Bad Request', $e->getMessage());
    }
    $body = generateBody($options);
    outputResponse($options, $body);
}

function parseInput($body)
{
    $options = json_decode($body, true);
    if ($options === null)
        throw new Exception('JSON decoding error');
    if (is_array($options) && Array_::isIndexed($options)) {

        // Process options of all time based requests
        foreach ($options as &$op)
            MockResponse::processOptions($op);

        // Sort time based requets and check if their intervals overlap
        usort(
            $options,
            function($op1, $op2)
            {
                return $op1['ifRequestedAfter'] - $op2['ifRequestedAfter'];
            }
        );
        for ($i = 0; $i < count($options) - 1; ++$i)
            if ($options[$i + 1]['ifRequestedAfter'] < $options[$i]['ifRequestedBefore'])
                httpError(400, 'Bad Request', 'Intervals of objects may not overlap');

        // Search for options arrqay corresponding to current time
        $time = CodeRage\Util\Time::real();
        foreach ($options as $opt) {
            if ( $time >= $opt['ifRequestedAfter'] &&
                 $time < $opt['ifRequestedBefore'] )
            {
                return $opt;
            }
        }

        // No suitable options array was found
        httpError(
            400, 'Bad Request',
            "No object with interval containing the current time: $time"
        );
    } else {
        MockResponse::processOptions($options);
        return $options;
    }
}

function generateBody(array $options)
{
    if (isset($options['body'])) {
        return $options['body'];
    } elseif (isset($options['forms'])) {
        return generateForms($options);
    } else {
        $contentType =
            preg_replace(
                '#$[-.a-z0-9]+/[-.a-z0-9]+#',
                '$1',
                $options['contentType']
            );
        if (array_key_exists($contentType, GENERIC_DOCUMENT_NAME)) {
            $url =
                GENERIC_DOCUMENT_BASE_URL .
                GENERIC_DOCUMENT_NAME[$contentType];
            return stream_get_contents(fopen($url, 'r'));
        } else {
            return '';
        }
    }
}

function generateForms($options)
{
    $html =
        "<!DOCTYPE html>\n" .
        "<html>\n" .
        "  <head>\n" .
        "    <title>CodeRage\Tool\Robot Mock Response</title>\n" .
        "  </head>\n" .
        "  <body>\n";
    foreach ($options['forms'] as $form)
        $html .= generateForm($form);
    $html .= "  </body>\n</html>\n";
    return $html;
}

function generateForm($form)
{
    $html = '    <form';
    foreach (['name', 'id', 'method', 'enctype', 'action'] as $attr)
        if (isset($form[$attr]))
            $html .= " $attr=\"" . htmlentities($form[$attr]) . "\"";
    $html .= ">\n";
    foreach ($form['inputs'] as $input)
        $html .= '      ' . generateInput($input);
    $html .= "    </form>\n";
    return $html;
}

function generateInput($input)
{
    $html = '';
    if (isset($input['label']))
        $html .= '<label>' . htmlentities($input['label']) . ': ';

    // Handle input type 'select'
    if ($input['type'] == 'select') {
        $html .= '<select';
        foreach (['name', 'id'] as $attr)
            if (isset($input[$attr]))
                $html .= " $attr=\"" . htmlentities($input[$attr]) . "\"";
        if ($input['multiple'])
            $html .= ' multiple=""';
        $html .= '>';
        foreach ($input['options'] as $option) {
            $html .=
                "<option value=\"" . htmlentities($option['value']) . "\"";
            if (isset($option['selected']))
                $html .= ' selected=""';
            $html .= ">" . htmlentities($option['label']) . "</option>";
        }
        $html .= '</select>';
    } else {

        // Handle other input types
        $html .= '<input';
        foreach (['type', 'name', 'id', 'value', 'accept'] as $attr)
            if (isset($input[$attr]))
                $html .= " $attr=\"" . htmlentities($input[$attr]) . "\"";
        foreach ($input['attributes'] as $n => $v)
            $html .= " $n=\"" . htmlentities($v) . "\"";
        if (isset($input['multiple']))
            $html .= ' multiple=""';
        $html .= '/>';
    }
    if (isset($input['label']))
        $html .= '</label>';
    $html .= "<br/>\n";
    return $html;
}

function outputResponse(array $options, $body)
{
    if (isset($options['sleep']))
        usleep($options['sleep'] * 1000);
    $status =
        $options['statusCode'] .
        ( isset($options['reasonPhrase']) ?
              " {$options['reasonPhrase']}" :
              "" );
    header("HTTP/1.1 $status");
    foreach ($options['headers'] as $n => $v)
        header("$n: $v");
    foreach ($options['cookies'] as $cookie)
        setcookie($cookie['name'], $cookie['value']);
    header("Content-Type: {$options['contentType']}");
    echo $body;
    exit;
}

function httpError($status, $title, $message)
{
    header("HTTP/1.1 $status $title");
    header('Content-Type: ' . MockResponse::TEXT_HTML_UTF8);

    // Output response
    $title = htmlentities($title);
    $message = htmlentities($message);
    $html =
        "<!DOCTYPE html>
         <html>
           <head><title>$status $title</title></head>
           <body>
             <h1>$status $title</h1>
             <p>$message</p>
           </body>
         </html>";
    echo $html;
    exit;
}

handleRequest();

?>

<?php  } ?>
