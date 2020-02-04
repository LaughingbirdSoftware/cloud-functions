// Store this file in <WordPress site>/plugins/wgc-autologin.
jQuery(document).ready(function ($) {
    // This function assumes ALL tags with FooBox class that have a "niche" parameter are for autologin.
    $('.foobox').each(function () {
        var key = "";
        var chars =
            "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        for (var i = 0; i < 16; i++) {
            key += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        key = key + new Date().getTime();
        var currentURL = $(this).attr('href');
        var targetDomain = (currentURL.split('?'))[0];
        let paramsString = (currentURL.split('?'))[1];
        var searchParams = new URLSearchParams(paramsString);
        var niche = searchParams.get("niche");
        // This code is setting "com" for site on webgraphicscreator!
        var hostname = window.location.hostname;

        var hostArray = (hostname.split("."));
 
        var site = hostArray[hostArray.length - 2];

        if (niche == "" || niche == undefined) {
            
        } else {
            var newURL = targetDomain + "?";
            newURL = newURL + "niche=" + niche;
            newURL = newURL + "&" + "site=" + site;
            newURL = newURL + "&" + "key=" + key;
 
            ($(this).attr('href', newURL));
            ($(this).attr('onclick', 'handleAutoLoginClick(event)'));
        }
    });
})

var wgcKey;
var dapOK;
var maxTries;
var tryNum;

function handleAutoLoginClick(e) {
    dapOK = false;
    maxTries = 5;
    tryNum = 1;
    var target = (e.target) ? e.target : e.srcElement;
    var foobox = target.parentElement;
    var currentURL = jQuery(foobox).attr('href');
    let paramsString = currentURL.split('?')[1];
    var searchParams = new URLSearchParams(paramsString);
    wgcKey = searchParams.get("key");

    var loginResult = login();
    console.log(loginResult);


}


function login() {
    return jQuery.ajax({
        url: autologin_ajax_obj.ajaxurl,
        datatype: "json",
        data: {
            'action': 'autologin_ajax_request',
            'fruit': wgcKey,
            'security': autologin_ajax_obj.ajax_nonce
        },
        method: 'POST',
        success: function (response) {
            dapOK = response;
            console.log("AJAX response received.")
            console.log(response);
            //callback();
        },
        error: function (errorThrown) {
            console.log("AJAX error:")
            console.log(errorThrown);
        }
    });
}

    function myCallback(result) {
        if (result) {
            console.log("myCallback: dap is ok");
        } else {
            tryNum++;
            if (tryNum <= maxTries) {
                console.log("callback: dapOK is false");
                setTimeout(() => {
                    console.log("try again: " + tryNum);
                    login();
                }, 2000);
            }
        }
    }