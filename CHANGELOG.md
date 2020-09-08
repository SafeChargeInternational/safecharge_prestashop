# SafeCharge Prestashop Changelog

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