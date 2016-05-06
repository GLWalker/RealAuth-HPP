<?php
/*
  $Id: $realauth

  RealAuth – Hosted Payment Page
  V 1.1.2
  Author: G.L. Walker http://wsfive.com

  Script is intended for use with:

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class realauth {
    var $code, $title, $description, $enabled, $oid;

// class constructor
    function realauth() {
      global $order;
	  
	  $this->signature = 'realex|realauth_HPP|1.1.2';
      $this->api_version = '1.1.2';

      $this->code = 'realauth';
      $this->title = MODULE_PAYMENT_REALAUTH_TEXT_TITLE;
	  $this->public_title = MODULE_PAYMENT_REALAUTH_TEXT_PUBLIC_TITLE;
      $this->description = MODULE_PAYMENT_REALAUTH_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_REALAUTH_SORT_ORDER;
	  $this->enabled = defined('MODULE_PAYMENT_REALAUTH_STATUS') && (MODULE_PAYMENT_REALAUTH_STATUS == 'True') ? true : false;
	  
	  $this->order_status = defined('MODULE_PAYMENT_REALAUTH_ORDER_STATUS_ID') && ((int)MODULE_PAYMENT_REALAUTH_ORDER_STATUS_ID > 0) ? (int)MODULE_PAYMENT_REALAUTH_ORDER_STATUS_ID : 0;

      if ( defined('MODULE_PAYMENT_REALAUTH_STATUS') ) {
        if (MODULE_PAYMENT_REALAUTH_TRANSACTION_SERVER == 'Test' ) {
          $this->title .= ' [Test]';
          $this->public_title .= ' (' . $this->code . '; Test)';
        }
      }
	  
      if ( $this->enabled === true ) {
        if ( !tep_not_null(MODULE_PAYMENT_REALAUTH_ID) || !tep_not_null(MODULE_PAYMENT_REALAUTH_SECRET) ) {
          $this->description = '<div class="secWarning">' . MODULE_PAYMENT_REALAUTH_ERROR_ADMIN_CONFIGURATION . '</div>' . $this->description;

          $this->enabled = false;
        }
      }

      if ( $this->enabled === true ) {
        if ( isset($order) && is_object($order) ) {
          $this->update_status();
        }
      }

   //   if (is_object($order)) $this->update_status();
   
      if ( MODULE_PAYMENT_REALAUTH_TRANSACTION_SERVER == 'Live' ) {
        $this->form_action_url = 'https://hpp.realexpayments.com/pay';
      } else {
        $this->form_action_url = 'https://hpp.sandbox.realexpayments.com/pay';
      }
	  
    }

// class methods
    function update_status() {
      global $order;
	
	      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_REALAUTH_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_REALAUTH_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }
	
    function javascript_validation() {
      return false;
    }

    function selection() {
      return array('id' => $this->code,
                   'module' => $this->public_title);
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
      return false;
    }

    function process_button() {
	  global $customer_id, $order,  $currencies, $currency;

      if (MODULE_PAYMENT_REALAUTH_CURRENCY == 'Selected Currency') {
        $my_currency = $currency;
      } else {
        $my_currency = substr(MODULE_PAYMENT_REALAUTH_CURRENCY, 5);
      }

      if (!in_array($my_currency, array('CHF', 'EUR', 'GBP', 'JPY', 'USD', 'SEK', 'HKD'))) {
        $my_currency = 'USD';
      }

      //Replace these with the values you receive from Realex Payments
      $merchantid = MODULE_PAYMENT_REALAUTH_ID;
      $secret = MODULE_PAYMENT_REALAUTH_SECRET;
      $account = MODULE_PAYMENT_REALAUTH_ACCOUNT;

      //The code below is used to create the timestamp format required by Realex Payments
      $timestamp = strftime("%Y%m%d%H%M%S");
      mt_srand((double)microtime()*1000000);
      /* orderid: Timestamp - randvalue */
      $orderid = $timestamp . "-" . mt_rand(1, 999);

      $curr = $my_currency;
      $amount = number_format($order->info['total'] * $currencies->get_value($my_currency), $currencies->get_decimal_places($my_currency), '.', '') * 100;

      /*sha1 crypt*/
      $tmp = "$timestamp.$merchantid.$orderid.$amount.$curr";
      $sha1hash = sha1($tmp);
      $tmp = "$sha1hash.$secret";
      $sha1hash = sha1($tmp);
	  
      //pass order.attribute ID to realex
	  $contents = array();

        foreach ($order->products as $product) {
          $product_id = $product['id'];
          $contents[] = str_replace(array(',', "\n", "\r", '&', '}', '{'), '+', $product_id .' X ' . $product['qty']);
        }

        $products_ordered = substr(implode(' - ', $contents), 0, 7500);	  
	  
	  
      $process_button_string = MODULE_PAYMENT_REALAUTH_TEXT_BEFORE_BUTTON .
	                         /* mandatory fields by Realex */
	                           tep_draw_hidden_field('MERCHANT_ID', MODULE_PAYMENT_REALAUTH_ID) .
                               tep_draw_hidden_field('ORDER_ID', $orderid) .
                               tep_draw_hidden_field('AMOUNT', $amount) .
                               tep_draw_hidden_field('CURRENCY', $curr) .
                               tep_draw_hidden_field('TIMESTAMP', $timestamp) .
							   tep_draw_hidden_field('SHA1HASH', $sha1hash).
							 /* eol manditory fields */
							 /* Optional Fields by RealEx */
							   tep_draw_hidden_field('ACCOUNT', MODULE_PAYMENT_REALAUTH_ACCOUNT) .
                               tep_draw_hidden_field('AUTO_SETTLE_FLAG', 1) .
							   
							   tep_draw_hidden_field('COMMENT1', substr($order->billing['firstname'] . ' ' . $order->billing['lastname'], 0, 100) . (strlen($order->billing['company']) > 0 ? ' ' . substr($order->billing['company'], 0, 50):'') . ' ' . substr($order->customer['telephone'], 0, 20) . ' ' . substr($order->customer['email_address'], 0, 255) ) .
							   tep_draw_hidden_field('COMMENT2', urldecode($order->info['comments'])) .
                               tep_draw_hidden_field('RETURN_TSS', 1) .
							   tep_draw_hidden_field('SHIPPING_CODE', substr($order->delivery['postcode'], 0, 10)) .
                               tep_draw_hidden_field('SHIPPING_CO', $order->delivery['country']['iso_code_2']) .
                               tep_draw_hidden_field('BILLING_CODE',substr($order->billing['postcode'], 0, 10)) .
                               tep_draw_hidden_field('BILLING_CO', $order->billing['country']['iso_code_2']) .
							   tep_draw_hidden_field('CUST_NUM', $customer_id) .
							   tep_draw_hidden_field('VAR_REF', STORE_NAME) .
							   tep_draw_hidden_field('PROD_ID', $products_ordered) .

                            /* the following is for some who may offer additional payment methods as enabled in their Realex account. Do not blindly uncomment, see developer guide for assistance. https://resourcecentre.realexpayments.com/documents/pdf.html?id=169 Chapter 10 Alternative Payment Methods
							*/
							
							 //tep_draw_hidden_field('PM_METHODS', 'cards|paypal|sofort|giropay|elv|ideal') . 
							 
							// the following URL needs whitelisted @ realex 
							   tep_draw_hidden_field('MERCHANT_RESPONSE_URL', tep_href_link('ext/modules/payment/realauth/redirect.php', '', 'SSL', true));
                               

      return $process_button_string;
    }
	
	
	
	

    function before_process() {
      global $HTTP_POST_VARS;

      $error = false;
      
      $merchantid = MODULE_PAYMENT_REALAUTH_ID;
      $secret = MODULE_PAYMENT_REALAUTH_SECRET;

      $account = $HTTP_POST_VARS['ACCOUNT'];
      $timestamp = $HTTP_POST_VARS['TIMESTAMP'];
      $result = $HTTP_POST_VARS['RESULT'];
      $orderid = $HTTP_POST_VARS['ORDER_ID'];
      $message = $HTTP_POST_VARS['MESSAGE'];
      $authcode = $HTTP_POST_VARS['AUTHCODE'];
      $pasref = $HTTP_POST_VARS['PASREF'];
      $sha1hash_post = $HTTP_POST_VARS['SHA1HASH'];

      $tmp = "$timestamp.$merchantid.$orderid.$result.$message.$pasref.$authcode";
      $sha1hash_new = sha1($tmp);
      $tmp = "$sha1hash_new.$secret";
      $sha1hash_new = sha1($tmp);

      //Check to see if hashes match or not
      if ($sha1hash_new != $sha1hash_post) {
                                $payment_error_return = 'payment_error=' . $this->code . '&error=' . TEXT_REALAUTH_HASH_ERROR;
                                tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
      }

      if ($result !='00') {
                                $payment_error_return = 'payment_error=' . $this->code . '&error=' . urlencode($message);
                                tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, $payment_error_return, 'SSL', true, false));
      }
      

      return false;
    }

    function after_process() {
      return false;
    }

   function get_error() {
      global $HTTP_GET_VARS;

      $error = array('title' => REALEX_ERROR_TITLE,
                     'error' => ((isset($HTTP_GET_VARS['error'])) ? stripslashes(urldecode($HTTP_GET_VARS['error'])) : IPAYMENT_ERROR_MESSAGE));

      return $error;
    }

    function output_error() {
      return false;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_REALAUTH_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
		
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Realauth Module', 'MODULE_PAYMENT_REALAUTH_STATUS', 'True', 'Do you want to accept Realex Realauth HPP payments?', '6', '3', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
	  
tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Account', 'MODULE_PAYMENT_REALAUTH_ACCOUNT', 'internet', 'The Account (or sub-Account) is provided by realex. Leave it set to \'internet\' unless instructed by Realex.', '6', '5', now())");

      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_REALAUTH_ID', 'MerchantID', 'The merchant ID provided by realex', '6', '5', now())");
	  
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Shared Secret', 'MODULE_PAYMENT_REALAUTH_SECRET', 'secret', 'The Shared Secret provided by realex', '6', '4', now())");
	  
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Currency', 'MODULE_PAYMENT_REALAUTH_CURRENCY', 'Selected Currency', 'The currency to use for credit card transactions', '6', '6', 'tep_cfg_select_option(array(\'Selected Currency\',\'Only USD\',\'Only CHF\',\'Only EUR\',\'Only GBP\',\'Only JPY\', \'Only HKD\', \'Only SEK\'), ', now())");
	  
	  
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_REALAUTH_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
	  
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Server', 'MODULE_PAYMENT_REALAUTH_TRANSACTION_SERVER', 'Test', 'Perform transactions on the production server or on the testing server.', '6', '3', 'tep_cfg_select_option(array(\'Test\', \'Live\'), ', now())");
	  
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_REALAUTH_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
	  
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_REALAUTH_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
	  
    }
	
	

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_REALAUTH_STATUS', 'MODULE_PAYMENT_REALAUTH_ACCOUNT', 'MODULE_PAYMENT_REALAUTH_ID','MODULE_PAYMENT_REALAUTH_SECRET',  'MODULE_PAYMENT_REALAUTH_CURRENCY', 'MODULE_PAYMENT_REALAUTH_ZONE', 'MODULE_PAYMENT_REALAUTH_TRANSACTION_SERVER', 'MODULE_PAYMENT_REALAUTH_ORDER_STATUS_ID', 'MODULE_PAYMENT_REALAUTH_SORT_ORDER');
    }
  }
?>