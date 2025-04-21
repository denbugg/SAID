<?php

include 'donorApi.php';
include 'userApi.php';
include 'operatorApi.php';
include 'adminApi.php';

if (!empty($_COOKIE['sid'])) {
    // check session id in cookies
    session_id($_COOKIE['sid']);
}

session_start();

$api = array (
    "user"=>"userAjaxRequest",
    "donor"=>"donorAjaxRequest",
    "operator"=>"operatorAjaxRequest",
    "admin"=>"adminAjaxRequest",
);

//$ajaxRequest = new musicJudgeAjaxRequest($_REQUEST);
$apiName = $api[$_REQUEST['api']];
$ajaxRequest = new $apiName($_REQUEST);
$ajaxRequest->showResponse();