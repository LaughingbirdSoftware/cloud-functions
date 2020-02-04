<?php
/*
Plugin Name: Web Graphics Creator Autologin
Plugin URI:
description: Automatic login to the Web Graphics Creator web app.
Version: 1.0.0
Author: Gerald W. Chastain, Jr.
Author URI:
License: Copyright 2018 Laughingbird Software, LLC,  All Rights Reserved.
 */
// ini_set('display_errors', 1); // Only for debugging!
// error_reporting(E_ALL); // Only for debugging!

include_once "https://webgraphicscreator.com/dap/dap-config.php";

function autologin_ajax_enqueue()
{
    wp_enqueue_script(
        'autologin-ajax-script',
        plugins_url('wgc-autologin.js', __FILE__),
        array('jquery'),
        "1.0",
        true
    );
    // Output the ajax_url path for script to use.
    $params = array(
        'ajaxurl' => network_admin_url('admin-ajax.php', $protocol),
        'ajax_nonce' => wp_create_nonce('akjhuh767gGgGJ8uUfdl78sfdsd'),
    );
    wp_localize_script(
        'autologin-ajax-script',
        'autologin_ajax_obj',
        $params);
}
add_action('wp_enqueue_scripts', 'autologin_ajax_enqueue', 1);

function autologin_ajax_request()
{
    $user_info = get_userdata($wpUserID);
    $role = ($user_info->roles)[0];
    $subscriber = 'subscriber';
    $administrator = 'administrator';
    if (strcmp($role, $subscriber) || strcmp($role, $administrator)) {
        // User is subscriber or administrator.
        $fruit = $_REQUEST['fruit'];
        $secret = 'hgdryKfs3jj234jfJGjsjjdfjjdj-qpzTksh7yg555sggPegdHhjhkjhs-88yysyOhjnLdnklWkkekrXjlwke';
        $isUserLoggedIn = false;
        if (Dap_Session::isLoggedIn()) {
            $session = Dap_Session::getSession();
            $user = $session->getUser();
            $user = Dap_User::loadUserById($user->getId());
            if (isset($user)) {
                $isUserLoggedIn = true;
                $userid = $user->getId();
                $dapEmail = $user->getEmail();
                $wpUser = get_user_by('email', $dapEmail);
                $wpUserID = $wpUser->ID;
                // echo 'DAP: ' . $userid . '  ' . 'WP: ' . $wpUserID; // For debug only.
                // break;
            }
        }

        if ($isUserLoggedIn) {
            $user_info = get_userdata($wpUserID);
            $firstName = $user_info->first_name;
            $role = ($user_info->roles)[0];
            $data = array(
                "secret" => "$secret",
                "key" => "$fruit",
                "wpName" => "$firstName",
                "wpRole" => "$role",
                "wpID" => $wpUserID,
                "dapEmail" => $dapEmail,
            );
            $data_string = json_encode($data);
            curlToCloudFunction($data_string);
            //echo 'Curl executed with user info.';
        } else {
            $data = array(
                "secret" => "$secret",
                "key" => "$fruit",
                "wpName" => '',
                "wpRole" => '',
                "wpID" => '',
                "dapEmail" => 'No DAP session.',
            );
            $data_string = json_encode($data);
            curlToCloudFunction($data_string);
            //echo 'Curl executed with --No DAP session--';
        }
        //wp_die();
    }
}
function curlToCloudFunction($data_string)
{
    $ch;
    if (strpos($_SERVER['HTTP_HOST'], 'videoadmachine.com') !== false) {
        // Staging.
        $ch = curl_init('https://us-central1-tgc8-ae5bf.cloudfunctions.net/autologin');
    } else {
        // Production.
        $ch = curl_init('https://us-central1-webgraphicscreator.cloudfunctions.net/autologin');
    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $result = curl_exec($ch);
    curl_close($ch);
}
add_action('wp_ajax_autologin_ajax_request', 'autologin_ajax_request', 1);

// For non-logged in users (in a theme for example):
add_action('wp_ajax_nopriv_autologin_ajax_request', 'autologin_ajax_request', 1);
