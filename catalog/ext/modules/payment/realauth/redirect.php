<?php
/*
  $Id: $realauth redirect

  RealAuth – Hosted Payment Page
  V 1.1.2
  Author: G.L. Walker http://wsfive.com

  Script is intended for use with:

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/


  chdir('../../../../');
  require('includes/application_top.php');

// if the customer is not logged on, redirect them to the login page
  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_PAYMENT));
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }
  
  global $HTTP_POST_VARS;
  
  $hidden_params =             tep_draw_hidden_field('MERCHANT_ID', $HTTP_POST_VARS['MODULE_PAYMENT_REALAUTH_ID']) .
                               tep_draw_hidden_field('ORDER_ID', $HTTP_POST_VARS['ORDERID']) .
                               tep_draw_hidden_field('TIMESTAMP', $HTTP_POST_VARS['TIMESTAMP']) .
                               tep_draw_hidden_field('AUTHCODE', $HTTP_POST_VARS['AUTHCODE']) .
                               tep_draw_hidden_field('MESSAGE', $HTTP_POST_VARS['MESSAGE']) .
                               tep_draw_hidden_field('RESULT', $HTTP_POST_VARS['RESULT']) .
                               tep_draw_hidden_field('PASREF', $HTTP_POST_VARS['PASREF']) .
                               tep_draw_hidden_field('SHA1HASH', $HTTP_POST_VARS['SHA1HASH']);

  $redirect_url = tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_CONFIRMATION);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>">
<link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body>
<form name="redirect" action="<?php echo $redirect_url; ?>" method="post" target="_top">
<?php echo $hidden_params; ?>
<noscript>
  <p align="center" class="main">The transaction is being finalized. Please click continue to finalize your order.</p>
  <p align="center" class="main"><input type="submit" value="Continue" /></p>
</noscript>
</form>
<script type="text/javascript">
document.redirect.submit();
</script>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
