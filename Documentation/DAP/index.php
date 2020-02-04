<?php
	include_once "dap-config.php";
	header('Content-type: text/html; charset=UTF-8') ;
	$user = null;

	echo gettype(Dap_Session);
	echo Dap_Session;

	if( !Dap_Session::isLoggedIn() ) { 
		//send viewer to login page
		header("Location:" . Dap_Config::get("LOGIN_URL"));
		exit;
	}
	else if( Dap_Session::isLoggedIn() ) { 
		//get userid
		$session = Dap_Session::getSession();
		$user = $session->getUser();
		$user = Dap_User::loadUserById($user->getId()); //reload User object
		if(!isset($user)) {
			//send viewer to login page
			header("Location:".Dap_Config::get("LOGIN_URL"));
			exit;
		} else {
			$userProducts = Dap_UsersProducts::loadProducts($user->getId());
		}
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo $user->getFirst_name(); ?>'s Home Page</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="inc/content/dap.css" rel="stylesheet" type="text/css">
<?php getCss(); ?>
<script language="javascript" type="text/javascript" src="javascript/ajaxWrapper.js"></script>
<script language="javascript" type="text/javascript" src="javascript/common.js"></script>
<script language="javascript" type="text/javascript" src="/dap/javascript/UserProfile.js"></script>
<script language="javascript" type="text/javascript" src="/dap/javascript/jsstrings.php"></script>
<script language="javascript">

var affId = <?php echo $user->getId(); ?>;
var globalError = "";
var whatRequest = "";
var site_url = "<?php echo SITE_URL_DAP; ?>";

function processChange(responseText, responseStatus, responseXML) {
	if (responseStatus == 200) {// 200 means "OK"
		var resource = eval('(' + responseText + ')');
		if(resource.whatRequest == 'loadAffiliatePayments') {
			changeDiv('aff_payments_div',resource.responseJSON);
		} else if(resource.whatRequest == 'loadAffiliateEarningsSummary') {
			changeDiv('aff_earnings_summary_div',resource.responseJSON);
		} else if(resource.whatRequest == 'loadAffiliateEarningsDetails') {
			changeDiv('aff_earnings_details_div',resource.responseJSON);
		} else if(resource.whatRequest == 'loadUser') {
			changeLoadUser(resource.responseJSON);
		} else if(resource.whatRequest == 'updateUser') {
			changeDiv('user_msg_div',resource.responseJSON);
			NLBfadeBg('user_msg_div','#009900','#FFFFFF','1000');
			loadUser(affId);
		} else if(resource.whatRequest == 'loadAffiliateStats') {
			changeDiv('aff_stats_div',resource.responseJSON);
		} else if(resource.whatRequest == 'deleteCurrentPic') {
			changeDiv('current_pic_div',"<b>SUCCESS! Your current photo has been deleted.</b>");
			NLBfadeBg('current_pic_div','#009900','#FFFFFF','1500');
		} else if(resource.whatRequest == 'loadAffiliatePerformanceSummary') {
			changeDiv('aff_perf_summary_div',resource.responseJSON);
		}
	} else {// anything else means a problem
		//alert("There was a problem in the returned data:\n");
	}
}


function deleteCurrentPic() {
	url  =  'admin/ajax/deleteCurrentPicAjax.php';
	var request = new ajaxObject(url,processChange);
	request.update('userId=' + affId + '&whatRequest=deleteCurrentPic');
}

function loadAffiliateStats() {
	changeDiv('aff_stats_div','Please wait... Loading Affiliate Stats ...<br><img src="images/progressbar.gif">');
	url  =  'admin/ajax/loadAffiliateStatsAjax.php';
	var request = new ajaxObject(url,processChange);
	request.update('email=<?php echo $user->getEmail(); ?>' + '&whatRequest=loadAffiliateStats');
}


function loadUser() {
	url  =  'admin/ajax/loadUserAjax.php';
	var request = new ajaxObject(url,processChange);
	request.update('userId=' + affId + '&whatRequest=loadUser');
}


function changeLoadUser(user){
	form = document.UserHomeForm;
	
	//Fill form values
	form.first_name.value = (user[0].first_name == null) ? "" : user[0].first_name;
	form.last_name.value = (user[0].last_name == null) ? "" : user[0].last_name;
	form.user_name.value = (user[0].user_name == null) ? "" : user[0].user_name;
	form.email.value = (user[0].email == null) ? "" : user[0].email;
	form.password.value = (user[0].password == null) ? "" : user[0].password;
	form.paypal_email.value = (user[0].paypal_email == null) ? "" : user[0].paypal_email;
	form.address1.value = (user[0].address1 == null) ? "" : user[0].address1;
	form.address2.value = (user[0].address2 == null) ? "" : user[0].address2;
	form.city.value = (user[0].city == null) ? "" : user[0].city;
	form.state.value = (user[0].state == null) ? "" : user[0].state;
	form.zip.value = (user[0].zip == null) ? "" : user[0].zip;
	form.country.value = (user[0].country == null) ? "" : user[0].country;
	form.phone.value = (user[0].phone == null) ? "" : user[0].phone;
	form.fax.value = (user[0].fax == null) ? "" : user[0].fax;
	form.company.value = (user[0].company == null) ? "" : user[0].company;
	form.title.value = (user[0].title == null) ? "" : user[0].title;
	
	if(user[0].opted_out == "Y") {
		form.opted_out.checked = false;
	} else if(user[0].opted_out == "N") {
		form.opted_out.checked = true;
	}
	
	if( (user[0].user_name != null) && (user[0].user_name != "") ) {
		form.user_name.readOnly = true;
		form.user_name.style.background="#CCCCCC";
	}
}

function updateUser(form) {
	var opted_out;
	if(form.opted_out.checked) {
		opted_out = "N";
	} else {
		opted_out = "Y";
	}
	
	if( !validateEmailJS(form.email.value) ){
		alert(MSG_EMAIL_INVALID);
		form.email.focus();
		return false; 
	}
	
	if(form.password.value == "") {
		alert(MSG_ENTER_PASSWORD);
		form.password.focus();
		return false; 
	}
	
	if(form.password.value != form.password_repeat.value) {
		alert(MSG_PASSWORDS_MISMATCH);
		form.password.focus();
		return false; 
	}
	
	if(!validatePassword(form.password.value)) {
		alert(MSG_PASSWORD_INVALID);
		return false;
	}
	
	changeDiv('user_msg_div','Please wait... Updating User Profile ...<br><img src="images/progressbar.gif">');
	var url = 'admin/ajax/updateUserUserAjax.php';
	var request = new ajaxObject(url,processChange);
	
	request.update('userId=' + affId + '&' +
			'first_name=' + form.first_name.value + '&' +
			'last_name=' + form.last_name.value + '&' +
			'user_name=' + form.user_name.value + '&' +
			'email=' + form.email.value + '&' +
			'password=' + escape(form.password.value) + '&' +
			'paypal_email=' + form.paypal_email.value + '&' + 
			'address1=' + form.address1.value + '&' + 
			'address2=' + form.address2.value + '&' + 
			'city=' + form.city.value + '&' + 
			'state=' + form.state.value + '&' + 
			'zip=' + form.zip.value + '&' + 
			'country=' + form.country.value + '&' + 
			'phone=' + form.phone.value + '&' + 
			'fax=' + form.fax.value + '&' + 
			'company=' + form.company.value + '&' + 
			'title=' + form.title.value + '&' + 
			'opted_out=' + opted_out + 
			'&whatRequest=updateUser', 'POST');
}

function loadAffiliatePayments() {
	changeDiv('aff_payments_div','Please wait... Loading Affiliate Payments ...<br><img src="images/progressbar.gif">');
	url = 'admin/ajax/loadAffiliatePaymentsAjax.php';
	request = new ajaxObject(url,processChange);
	request.update('email=<?php echo $user->getEmail(); ?>' + '&whatRequest=loadAffiliatePayments');
}

function loadAffiliateEarningsSummary() {
	changeDiv('aff_earnings_summary_div','Please wait... Loading Earnings Summary ...<br><img src="images/progressbar.gif">');
	url = 'admin/ajax/loadAffiliateEarningsSummaryAjax.php';
	request = new ajaxObject(url,processChange);
	request.update('email=<?php echo $user->getEmail(); ?>' + '&whatRequest=loadAffiliateEarningsSummary');
}

function loadAffiliateEarningsDetails() {
	changeDiv('aff_earnings_details_div','Please wait... Loading Earnings Details ...<br><img src="images/progressbar.gif">');
	url = 'admin/ajax/loadAffiliateEarningsDetailsAjax.php';
	request = new ajaxObject(url,processChange);
	request.update('email=<?php echo $user->getEmail(); ?>' + '&whatRequest=loadAffiliateEarningsDetails', 'POST');
}

function doWarning(form) {
	if(form.opted_out.checked) return;
	
	//Else, it means that user is trying to optout
	var check_box = confirm("WARNING: If you uncheck this box, you may not receive emails relevant to the product you have purchased. Are you sure you still want to Unsubscribe?");
	if (check_box==true) { 
		// Output when OK is clicked
		return; 
	} else {
		// Output when Cancel is clicked
		form.opted_out.checked = true;
		return;
	}
}

function loadAffiliatePerformanceSummary() {
	showLongProgressBar('aff_perf_summary_div', 'Please wait... Loading Your Affiliate Performance Summary...<br>');
	url  =  'admin/ajax/loadAffiliatePerformanceSummaryAjax.php';
	var start_date = '01-01-2008';
	var request = new ajaxObject(url,processChange);
	request.update(
		'email=<?php echo $user->getEmail(); ?>' + 
		'&start_date=' + start_date + 
		'&whatRequest=loadAffiliatePerformanceSummary'
	);
	return;
}


</script>

</head>
<body> 
<?php displayTemplate("HEADER_CONTENT"); ?> 
<div align="center" class="headingSuperRed"><?php echo $user->getFirst_name(); ?>'s Home Page</div>
<?php 
	if(isset($_GET['msg'])) { ?> 
<p align="center"><font face="Arial, Helvetica, sans-serif" size="2" color="#CC0000"><i><b><?php echo $_GET['msg']; ?></b></i></font></p> 
  <?php }
?> 
<br/>
<form name="UserHomeForm">
<table width="800" border="0" cellspacing="0" cellpadding="5" align="center" class="bodytextArial"> 
<?php if($session->isAdmin()) { ?>
	<td align="center"><a href="/dap/admin/" title="Admin Home">Admin Home</a></td>
   <?php } ?>
<?php if( Dap_Config::get("SELF_SERVICE_ENABLED") == "Y") { ?>
	<td align="center"><a href="/dap/inc/content/sss.php" title="Self Service Credits">Self-Service 
    (<?php echo $user->getCredits_available(); ?> Credits)
	 </a></td>
	<td align="center"><a href="/dap/inc/content/creditHistory.php" title="Credit History">Credit History</a></td>
   <?php } ?>
    <td align="center"><a href="#my_profile" title="Edit Profile">Edit Profile</a></td>
    <td align="center">
	<?php
		if( Dap_Config::get('DISPLAY_AFFILIATE') == "Y" ) { ?><a href="#affiliate_details" title="My Affiliate Stats">My affiliate stats</a><?php } 
	?></td>
    <td align="center"><a href="logout.php" title="Logout">Logout</a></td>
    </tr>
  <tr>
    <td colspan="4"><?php displayTemplate("USER_MESSAGE_CONTENT"); ?> </td>
  </tr>
  <tr> 
    <td colspan="4"><?php echo "<b>Hello ".$user->getFirst_name()."</b>.<br/><br/> You currently have access to ".count($userProducts)." product(s)."; ?></td> 
  </tr> 
  <?php
			//loop over each product from the list
			foreach ($userProducts as $userProduct) { 
					$product = Dap_Product::loadProduct($userProduct->getProduct_id());
			?> 
  <tr align="left"> 
    <td colspan="7">      <table id="dap_product_links_table"> 
        <tr> 
          <td><span class="scriptheader"><?php echo $product->getName(); ?></span></td> 
        </tr> 
        <tr> 
          <td><strong><?php echo USER_LINKS_ACCESS_START_DATE_TEXT; ?></strong>: <?php echo $userProduct->getAccess_start_date(); ?></td> 
        </tr> 
        <tr> 
          <td><strong><?php echo USER_LINKS_ACCESS_END_DATE_TEXT; ?></strong>: <?php echo $userProduct->getAccess_end_date(); ?></td> 
        </tr> 
        <tr> 
          <td><strong><?php echo USER_LINKS_DESCRIPTION_TEXT; ?></strong>: <?php echo $product->getDescription(); ?></td> 
        </tr> 
        <tr> 
          <td><strong><?php echo USER_LINKS_LINKS_TEXT; ?></strong>: <?php echo $userProduct->getActiveResources(); ?></td> 
        </tr> 
      </table><br/><br/></td> 
  </tr> 
  <?php
			}//end foreach
?> 
</table> 

<?php if( Dap_Config::get('DISPLAY_AFFILIATE') == "Y" ) { ?>
<br/>
<div id="affiliate_details_div">
<a name="affiliate_details"></a>
<table width="600" border="0" align="center" cellpadding="5" cellspacing="0" bordercolor="#EFEFEF" class="bodytextLarge">
  <tr>
    <td><div align="center" class="headingSuperRed"><?php echo AFFILIATE_INFO_HEADING; ?></div></td>
  </tr>
  <tr>
    <td><?php displayTemplate("AFF_MESSAGE_CONTENT"); ?></td>
  </tr>
  <tr>
    <td nowrap class="scriptheader" align="center">&nbsp;</td>
  </tr>
  <tr>
    <td nowrap class="scriptheader" align="center"><?php echo AFFILIATE_INFO_TOTALEARNINGS_SUBHEADING; ?></td>
  </tr>
    <tr>
      <td nowrap class="headingSuperRed"><div id="aff_earnings_summary_div" align="center"></div></td>
    </tr>
  <tr>
    <td nowrap class="scriptheader" align="center"><?php echo AFFILIATE_INFO_PERFSUMM_SUBHEADING; ?></td>
  </tr>
    <tr>
      <td><div id="aff_perf_summary_div" align="center"></div></td>
    </tr>    
  <tr>
    <td><table width="600" border="0" align="center" cellpadding="5" cellspacing="0" class="bodytextArial">
      <tr>
        <td><p><span class="bodytextSubHeading"><?php echo AFFILIATE_INFO_YOURAFFLINKHOME_LABEL; ?></span><br>
              <input name="textfield2" onClick="this.select();" type="text" value="<?php echo SITE_URL_DAP . "/dap/a/?a=".$user->getId(); ?>" size="80">
            &nbsp;&nbsp;<a href="<?php echo SITE_URL_DAP . "/dap/a/?a=".$user->getId(); ?>" title="test affiliate link" target="_blank" class="bodytext">test</a></p>
          <p class="bodytextSubHeading"><?php echo AFFILIATE_INFO_AFFLINKSPECIFIC_LABEL; ?></p>
          <p class="bodytextArial"><?php echo AFFILIATE_INFO_AFFLINKSPECIFIC_EXTRA_TEXT; ?><br>
              <input name="textfield22" onClick="this.select();" type="text" value="<?php echo SITE_URL_DAP . "/dap/a/?a=".$user->getId(); ?>&p=www.example.com/somepage.html" size="80">
</p>          </td>
      </tr>
    </table>
      <br>
      <table width="100%"  border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td><div align="center" class="scriptheader"><?php echo AFFILIATE_INFO_PAYMENT_DETAILS_SUBHEADING; ?></div></td>
        </tr>
        <tr>
          <td><div id="aff_payments_div"></div></td>
        </tr>
      </table>
      <p>&nbsp;</p>
      <table width="100%"  border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td><div align="center" class="scriptheader"><?php echo AFFILIATE_INFO_EARNINGS_DETAILS_SUBHEADING; ?></div></td>
        </tr>
        <tr>
          <td><div id="aff_earnings_details_div"></div></td>
        </tr>
      </table>
      <p>&nbsp;</p>
      <table width="100%"  border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td><div align="center" class="scriptheader"><?php echo AFFILIATE_INFO_TRAFFIC_STATISTICS_SUBHEADING; ?></div></td>
        </tr>
        <tr>
          <td><div id="aff_stats_div" style="position:relative; width:100%; height:400px; background-color:#ffffff; overflow:scroll;"></div></td>
        </tr>
      </table>      </td>
  </tr>
</table>
</div>
<?php } ?>
<p>&nbsp;</p>
<a name="my_profile" id="my_profile"></a>
<table width="400" border="1" align="center" cellpadding="5" cellspacing="0" bordercolor="#EFEFEF" class="bodytextLarge">
  <tr>
    <td><div align="center" class="headingSuperRed">
      <p><?php echo USER_PROFILE_HEADING_TEXT; ?></p>
    </div>
      <div align="center" id="user_msg_div" class="bodytextLarge"></div>
    </td>
  </tr>
  <tr>
    <td><table width="100%"  border="0" cellspacing="0" cellpadding="5">
          <tr>
            <td><div align="left"><?php echo USER_PROFILE_FIRST_NAME_LABEL; ?></div></td>
            <td><div align="left">
              <input name="first_name" type="text" id="first_name">
            </div></td>
          </tr>
          <tr>
            <td><div align="left"><?php echo USER_PROFILE_LAST_NAME_LABEL; ?></div></td>
            <td><div align="left">
              <input name="last_name" type="text" id="last_name">
            </div></td>
          </tr>
          <tr>
            <td><div align="left"><?php echo USER_PROFILE_EMAIL_LABEL; ?></div></td>
            <td><div align="left"><input name="email" type="text" id="email"></div></td>
          </tr>
          <tr>
            <td><div align="left"><?php echo USER_PROFILE_USER_NAME_LABEL; ?></div></td>
            <td><div align="left"><input name="user_name" type="text" id="user_name"></div></td>
          </tr>
          <tr>
            <td><div align="left"><?php echo USER_PROFILE_NEW_PASSWORD_LABEL; ?></div></td>
            <td><div align="left">
              <input name="password" type="password" id="password">
            </div></td>
          </tr>
          <tr>
            <td><div align="left"><?php echo USER_PROFILE_REPEAT_PASSWORD_LABEL; ?></div></td>
            <td><div align="left">
              <input name="password_repeat" type="password" id="password_repeat">
            </div></td>
          </tr>
<?php if( Dap_Config::get('DISPLAY_AFFILIATE') == "Y" ) { ?>
          <tr>
            <td><div align="left"><?php echo USER_PROFILE_PAYPAL_EMAIL_LABEL; ?></div></td>
            <td><div align="left">
              <input name="paypal_email" type="text" id="paypal_email">
            </div></td>
          </tr>
<?php } ?>
<?php include ("inc/content/profileExtended.inc.php"); ?>
          <tr>
            <td class="bodytext"><div align="left"><?php echo USER_PROFILE_UNSUBSCRIBE_LABEL; ?></div></td>
            <td><div align="left">
              <input name="opted_out" type="checkbox" id="opted_out" value="N" onClick="doWarning(this.form);">
            </div></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td><input name="button_edit_user" type="button" id="button_edit_user" value="<?php echo BUTTON_UPDATE; ?>" onClick="updateUser(this.form);"></td>
          </tr>
        </table>
      </td>
  </tr>
</table>
<?php if( Dap_Config::get('DISPLAY_AFFILIATE') != "Y" ) { ?>
<input name="paypal_email" type="hidden" id="paypal_email" value="">
<?php } ?>
</form>

<p>&nbsp;</p>

<?php 
	if(file_exists("inc/content/photoUpload.inc.php")) {
		include ("inc/content/photoUpload.inc.php");
	}
?>

<script language="javascript">
	loadUser();
</script>

<?php
if( Dap_Config::get('DISPLAY_AFFILIATE') == "Y" ) { ?>
<script language="javascript">
loadAffiliatePayments();
loadAffiliateEarningsSummary();
loadAffiliateEarningsDetails();
loadAffiliateStats();
loadAffiliatePerformanceSummary();
</script>
<?php } ?>

<?php displayTemplate("FOOTER_CONTENT"); ?> 

</body>
</html>