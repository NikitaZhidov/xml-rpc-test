<?php

function return_xmlrpc_error($errno,$errstr,$errfile=NULL,$errline=NULL
       ,$errcontext=NULL){
    global $xmlrpc_server;
    if(!$xmlrpc_server)die("Error: $errstr in '$errfile', line '$errline'");

    header("Content-type: text/xml; charset=UTF-8");
    print(xmlrpc_encode(array(
        'faultCode'=>$errno
        ,'faultString'=>"Remote XMLRPC Error from
          ".$_SERVER['HTTP_HOST'].": $errstr in at $errfile:$errline"
    )));
    die();
} 
set_error_handler('return_xmlrpc_error');

/*
 Использование функции без параметра: curl -d "<methodCall><methodName>echo</methodName></methodCall>" http://kappa.cs.karelia.ru/~kulakov/xmlrpc/test.php
 Использование функции с параметром: curl -d "<methodCall><methodName>echo</methodName><params><param><struct><member><name>param</name><value>Some input</value></member></struct></param></params></methodCall>" http://kappa.cs.karelia.ru/~kulakov/xmlrpc/test.php
 */
function echo_func($method_name, $params, $app_data)
{
    // if (is_array($params[0]))
    // {
	// $ret = array("Found parameter", $params[0]["param"]);
    // } else {
	$ret = "No params. ";
    // }

    if (is_array($ret))
        array_push($ret, "Some results returns");
    else
	$ret .= "Some results returns";

    return $ret;
}

function _get_elements_by_xpath($xpath)
{
    $xml_content = file_get_contents('./xml-questions.xml');
    $xml = new SimpleXMLElement($xml_content);

    return $xml->xpath($xpath);
}

#1
function get_topics_func($method_name, $params, $app_data)
{
    $topics = _get_elements_by_xpath("//quiz/@name");

    $result = [];

    foreach ($topics as $topic) {
        $result[] = "$topic";
    }

    return $result;
}

function get_random_question_by_topic_func($method_name, $params, $app_data)
{
    $question_number = random_int(1, 5);
    $topic = $params[0]["topic"];

    $question = _get_elements_by_xpath("//quiz[@name='$topic']/question[$question_number]")[0];

    $question_text = (string) $question->{"question-text"};
    $answer_options = $question->{"answer-options"};

    return ['question' => $question_text, 'answer-options' => $answer_options, 'question-number' => $question_number];
}

function check_question_answer_func($method_name, $params, $app_data)
{
    $topic = $params[0]["topic"];
    $question_number = $params[0]["question-number"];
    $user_answer = (int) $params[0]["answer"];

    $question = _get_elements_by_xpath("//quiz[@name='$topic']/question[$question_number]")[0];

    $answer = (int) $question->{"correct-answer"}[0];

    return $answer == $user_answer;
}


/* create server */
$xmlrpc_server = xmlrpc_server_create();

/* register methods */
xmlrpc_server_register_method($xmlrpc_server, "echo", "echo_func");

// #1
xmlrpc_server_register_method($xmlrpc_server, "get_topics", "get_topics_func");

// #2
xmlrpc_server_register_method($xmlrpc_server, "get_random_question_by_topic", "get_random_question_by_topic_func");

// #3
xmlrpc_server_register_method($xmlrpc_server, "check_question_answer", "check_question_answer_func");

/* process request */
$request_xml = file_get_contents("php://input");//$HTTP_RAW_POST_DATA;

$response = xmlrpc_server_call_method($xmlrpc_server, $request_xml, '');

header('Content-Type: text/xml');
echo $response;

xmlrpc_server_destroy($xmlrpc_server);
?>