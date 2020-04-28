<?php

use \Firebase\JWT\JWT;
use \Firebase\JWT\JWK;
use \Phpfastcache\CacheManager;

/*
Plugin Name: share.c3d.io 
Plugin URI: http://localhost:8080/
Description: customization for share.c3d.io
Version: 0.1
Author: jmadar
Author URI: http://github.com/env3d
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

$InstanceCache = null;
define('JWK_PATH', 'https://cognito-idp.us-west-2.amazonaws.com/us-west-2_MZfapDhzH/.well-known/jwks.json');

// A helper function to return the latest jwks from cognito
function getKeysFromCognito() {
    global $InstanceCache;
    $InstanceCache = $InstanceCache ?: CacheManager::getInstance('files');    
    $keys = $InstanceCache->getItem('JWK_PATH');
    if (is_null($keys->get())) {
        $jwkSet = json_decode(file_get_contents(JWK_PATH), true);
        // re-download keys every 24 hours
        $keys->set($jwkSet)->expiresAfter(60*24);
        $InstanceCache->save($keys);
    }
    return $keys->get();
}

// add access-control-allow-origin headers for all, to allow CORS
yourls_add_action( 'content_type_header', 'c3d_cors' );
function c3d_cors($type) {
    if( !headers_sent() ) {
        header("Access-Control-Allow-Origin: *");
        return true;
    }
    return false;
}


yourls_add_filter( 'shunt_is_valid_user', 'c3d_bypass_security' );
function c3d_bypass_security() {
    // all api format defaults to json
    $_REQUEST['format'] = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'json';    

    if (defined('C3D_BYPASS_SECURITY')) return true;
    
    // pass to default security if username, password, or signature is specified
    if (isset($_REQUEST['username']) && isset($_REQUEST['password'])) return null;
    if (isset($_REQUEST['signature'])) return null;    
    
    // we only protect shorturl action
    if (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'shorturl') return null;
    
    // We now starts to check for JWT            
    $headers = getallheaders();
    if (!isset($headers['Authorization']) || substr($headers['Authorization'], 0, 6 ) !== "Bearer") {
        return null;
    }

    $jwt = substr($headers['Authorization'], 7);    

    try {
        $keys = getKeysFromCognito();
        $decoded = JWT::decode($jwt, JWK::parseKeySet($keys), array('RS256'));
        $username = ($decoded && property_exists($decoded,'email')) ? $decoded->{'email'} : null;
    } catch (Exception $e) {
        $errorMessage = 'JWT ERROR: '.$e->getMessage();
        error_log($errorMessage);        
        return yourls__($errorMessage);
    }

    if ($username) {
        $_REQUEST['owner'] = $username;
        return true;
    } else {
        return null;
    }    
}

yourls_add_action( 'pre_load_template', 'c3d_REST_actions');
// create a more 'REST' friendly endpints
function c3d_REST_actions( $request ) {
    [ $searchPath ] = $request;
    $parts = explode("/", $searchPath, 2);    
    error_log("intercepting url $searchPath");

    switch($parts[0]) {
        
    case 'owner':
        $_REQUEST['action'] = $parts[0];
        $_REQUEST['owner'] = sizeOf($parts) > 1 ? $parts[1] : null;
        define('C3D_BYPASS_SECURITY', true);
        require_once( YOURLS_ABSPATH.'/yourls-api.php' );        
        break;
        
    case 'shorturl':
        $_REQUEST['action'] = $parts[0];
        error_log(print_r($_REQUEST, true));
        require_once( YOURLS_ABSPATH.'/yourls-api.php' );
        break;
        
    }
    
}

// add characters to allow email address in the keyword
// field, useful for identifying owner of an url
yourls_add_filter('get_shorturl_charset', 'c3d_owner_charset');
function c3d_owner_charset($charset) {
    return $charset."/@.";
}

// we append the owner to the keyword
yourls_add_filter('custom_keyword', 'c3d_add_owner');
yourls_add_filter('random_keyword', 'c3d_add_owner');
function c3d_add_owner( $args ) {
    [ $keyword, $url, $title ] = $args;
    error_log(print_r($args,true));
    $owner = isset($_REQUEST['owner']) ? $_REQUEST['owner']."/" : "";

    return "$owner$args";
}


// Force rendering with javascript, as the default
// Location http header will not be large enough to hold big files
yourls_add_action( 'pre_redirect', 'redirect_with_javascript' );
function redirect_with_javascript( $args ) {
    $url = $args[0];
    $code = $args[1];
    echo "<p>redirecting $code via javascript, should be automatic</p>";
}



// --------------------------------------------------------------- 
// check header when using api

#yourls_add_action( 'api', 'check_jwt_header' );
function check_jwt_header( $args ) {
    $action = $args[0];
    // all api format defaults to json
    $_REQUEST['format'] = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'json';    

    // we only protect shorturl action
    if ($action !== 'shorturl') return;
    
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        print("Must provide valid id_token\n");
        die();        
    }
    
    if (substr($headers['Authorization'], 0, 6 ) !== "Bearer") {
        http_response_code(401);
        print("Must use Bearer token\n");
        die();                
    }

    $jwt = substr($headers['Authorization'], 7);
    // special token to bypass security

    if ($jwt === '12345') {
        $username = 'debug';        
    } else {
        // Just decode it for now, in production we will have to validate it as well
        $decoded = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $jwt)[1]))));
        //$decoded = JWT::decode($jwt, $key, array('HS256'));
        $username = $decoded->{'email'};        
    }

    $_REQUEST['owner'] = $username;
    
}

// ---------------------------------------------------------------

// adding an new action type
yourls_add_filter( 'api_actions', 'c3d_actions_filter' );
function c3d_actions_filter( $actions ) {
    $actions['c3d'] = 'c3d_version';
    $actions['owner'] = 'c3d_owner';
    return $actions;
}
function c3d_version() {
    return array(
        'version' => 'share.c3d.io custom'
    );
}

// get from the database all links with a particular title
function c3d_owner() {
    $owner = isset($_REQUEST['owner']) ? $_REQUEST['owner'] : null;
    
    if (!$owner) return array(
        'errorCode' => 400,
        'message' => 'must provide an owner',
        'simple' => 'must provide an owner'
    );

    global $ydb;
    $table_url = YOURLS_DB_TABLE_URL;
    $sql = "SELECT keyword, title, timestamp FROM $table_url WHERE keyword like '$owner%'";
    $resultSet = $ydb->fetchObjects($sql);
    return array(
        'result' => $resultSet
    );
}

