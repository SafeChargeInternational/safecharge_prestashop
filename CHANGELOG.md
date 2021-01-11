# Nuvei Prestashop Changelog

#2.1
```
	* Added checkbox for the user to confirm save of used Payment method.
```

#2.0
```
	* Stop dmn parameter will works only on sandbox mode.
	* Removed unused constants.
	* Changed files paths and classes names according new directory.
	* Changed the name of the plugin directory to "nuvei".
```

#1.8
```
	* Fix for missing Order state_id, when DMN is faild.
	* Checks for not allowed change of the Order status - see canOverrideOrderStatus() method.
	* Do updateOrder request before each openOrder. Call it before APMs page, and after click on "Pay" button.
	* Change in the callRestApi() method parameter.
	* SC_Class create_log() was removed.
	* Do not pass the Order amount and currency to the webSDK anymore.
	* Fixed the JS APMs error, when "Preselect CC payment method" options is set to Yes.
	* Do not request openOrder when webSDK payment was Declined.
```

#1.7.8
```
	* Small template fixes.
	* Fix for the Settle and Refund actions from the site.
	* When site is in Test Mode log account details.
	* SC_CLASS create_log method was deprecated.
```

#1.7.7
```
	* Button Spinner in the "Order with obligation to pay" was replaced from Loading popup.
	* Fix for default checkout flow.
	* Added module method createLog() and start using it.
	* When come DMN with different amount than the Order amount, save all SafeCharge data and let PrestaShope put status Error.
	* Enabled Void button for Orders with status Error, because of the above case.
	* Removed commented code fragments.
	* Move Order-DMN Amount check outside of Order Payment logic.
	* Save Items ID, quantity and price (total_wt parameter) in OpenOrder request - merchantDetails -> customField3
```

#1.7.6
```
	* Added additional check for Order State, because of the slow method validatePaymen().
```

#1.7.5
```
	* Fix for the new Netteler APM behavior.
	* Added log after Order is saved.
	* Change in the new Nuvei status install logic.
	* Fix for the APM/UPO Order with transactionStatus ERROR. Now the user is redirected to Error Page and do not create Order.
	* Added check in scGetDMN method for the owner module of the Order.
	* Pass the Plugin version in merchantDetails and all log records.
	* In the log all arrays are present as json-s.
```

#1.7.4
```
	* Plugin rebranded. Replaced SafeCharge with Nuvei.
	* Added new state - Awaiting Nuvei Payment.
	* Some of the log comments in Payment class were beautified.
	* When DMN with status Pending come, do not change status of the Order again, just save a Note.
	* Added one more check for existing Payment before our try to create an Order in proccessOrder().
	* Fixed the problem when the Order was created by the DMN and the client was redirected to wrong Confirm-Page.
	* Fixed the exception after the Declined DMN and the not existing Order when pay with CC. Now process stop, and we log and return a message.
```

#1.7.3
```
	* Checkout pay button changed type from submit to button.
```

#1.7.2
```
	* Added style for the spinner in add_step.tpl
	* Added check for existing APMs, if there are no APMs we show error message.
	* In case of CC sdk payment, but missing sc_transaction_id parameter, redirect to error page.
```

#1.7.1
```
	* Added trace log on DMN exception.
	* Added default error message, before error reason/message returned from webSDK.
```

# v1.7
```
	* For the Order Status "Awaiting SafeCharge payment" we changed "logable" parameter to true.
	* When client pay with CC we save the Transaction ID from the sdk response in the Order payment.
	* Create Order, based on Success DMN, if Order does not exists. This is in case when client does not reach order-confirm page.
	* When Install (Reset), update existing SC Order state, instead add it every time.
	* When something went wrong, when open Second Step, redirect to order page.
	* Added more checks for variables in template files.
	* Fix of small bugs.
	* Added few logs in few negative cases.
```

# v1.6.1
```
	* Fixed wrong class name Toolss with correct one - Tools.
	* Do not validate IP before the requests, beacuse we do it when get it.
	* Removed an unused row of code.
	* Added checks for few Smarty parameters.
```

# v1.6
```
	* Added possibility to edit the Notify (DMN) URL in the settings. If the URL saved as empty string, the default one will be fill in the input.
```

# v1.5
```
	* Fixed bug for Settle and Void buttons in the Admin.
	* Added new option in the plugin, that adds one more step in the checkout to show the APMs. It is a must to set the option to Yes if use One-Page-Checkout.
	* If site receive DMN with status Declined for already Approved transaction, we ignore it.
```

# v1.4
```
	* Added option in the settings of the plugin to show custom message over each APM.
	* The Safecharge HTML elements are cleaned and with default style. If the merchant want to change it, it must add its style in the Plugin settings textarea.
	* The loading and trash icons now are png files, not Bootstrap glyphs.
```

# v1.3
```
	* Added option in the settings of the plugin to turn ON/OFF the names of the APMs.
	* Added texarea in the plugin settings for the APMs and UPOs style.
	* Added help text to APMs and UPOs.
	* If there is difference between Order Total and Paid Total, mark the Order with status Payment Error and add message.
	* Added new column (error_msg) in table safecharge_order_data, to save the message.
	* Added new option in the plugin settings - Save Order after the APM payment.
```

# 2020-07-09
```
	* Started replacing "SafeCharge" company name with "Nuvei".
```

## 2020-07-02
```
	* When Order failed try to recreate it, or in error message describe to the user how to do it manually.
```

## 2020-07-01
```
	* Some style changes for the UPOs and APMs list.
	* Better message on Error Page.
```

## 2020-06-18
```
	* Add option for the client to delete the UPOs.
	* Use the SDK for card UPOs and direct APMs.
```

## 2020-06-12
```
	* Added UPOs in the payment methods step.
	* Added option in the admin for use or not the UPOs.
```

## 2020-05-26
```
	* Pass better redirect URL in admin, after Order actions.
```

## 2020-05-21
```
	* Fixed the missing shipping email when use APM;
	* Added logs when validation fail in SC_CLASS;
	* Shipping and Billing email are restricted to 70 symbols;
```

## 2020-05-20
```
	* Added custom SC order status, we use it when order wait for payment;
	* Fixed the the problem with the Order Note about the missing security key;
```

## 2020-03-26
```
	* Payment via cards (webSDK) and APMs;
	* In the Admin supports Settle, Refund and Void for the Orders;
	* Keep notes for the Orders statuses;
	* Change the Order status after edit an order via SafeCharge CPanel;
```