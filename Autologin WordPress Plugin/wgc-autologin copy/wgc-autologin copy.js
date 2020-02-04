// Store this file in <WordPress site>/plugins/wgc-autologin.
jQuery(document).ready(function ($) {
    // This function sssumes ALL tags with FooBox class are for autologin.
    var key = "";
    var chars =
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < 16; i++) {
        key += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    key = key + new Date().getTime();
    $('.foobox').each(function () {
        var currentURL = $(this).attr('href');
        var targetDomain = (currentURL.split('?'))[0];
        let paramsString = (currentURL.split('?'))[1];
        var searchParams = new URLSearchParams(paramsString);
        var niche = searchParams.get("niche");
        // This code is setting "com" for site on webgraphicscreator!
        var hostname = window.location.hostname;
        console.log("window.location.hostname: " + hostname);
        var hostArray = (hostname.split("."));
        console.log(hostArray.toString());
        var site = hostArray[hostArray.length - 2];
        console.log("==========================================");
        console.log("site: " + site);
        console.log("key: " + key);
        console.log("currentURL: " + currentURL);
        console.log("targetDomain: " + targetDomain);
        console.log("niche: " + niche);
        if (niche == "" || niche == undefined) {
            console.log("No niche parameter: ignored.");
        } else {
            var newURL = targetDomain + "?";
            newURL = newURL + "niche=" + niche;
            newURL = newURL + "&" + "site=" + site;
            newURL = newURL + "&" + "key=" + key;
            console.log("--- Pushing href to foobox instance ---");
            ($(this).attr('href', newURL));
            ($(this).attr('onclick', 'handleAutoLoginClick(event)'));
        }
    });
})

function handleAutoLoginClick(e) {
    var target = (e.target) ? e.target : e.srcElement;
    var foobox = target.parentElement;
    var currentURL = jQuery(foobox).attr('href');
    let paramsString = currentURL.split('?')[1];
    var searchParams = new URLSearchParams(paramsString);

    console.log("--- Calling AJAX with key ---");
    var key = searchParams.get("key");

    // Getting some Autologin docs with no DAP session error.
    // After DAP login, user is immediatedly routed to
    // the app launcher page. Speculate that DAP session
    // instantiation can be delayed by an asynchronous process
    // that causes brief delay.
    var autologinDelay = 2000; 
    setTimeout(() => {
        jQuery.ajax({
            url: autologin_ajax_obj.ajaxurl,
            data: {
                'action': 'autologin_ajax_request',
                'fruit': key,
                'security': autologin_ajax_obj.ajax_nonce
            },
            method: 'POST',
            success: function (response) {
                console.log("AJAX success:");
                console.log(response);
            },
            error: function (errorThrown) {
                console.log("AJAX error:")
                console.log(errorThrown);
            }
        });
    }, autologinDelay);
}