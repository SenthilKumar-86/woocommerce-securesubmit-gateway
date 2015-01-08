=== WooCommerce SecureSubmit Gateway ===
Contributors: markhagan
Tags: woocommerce, woo, commerce, heartland, payment, systems, gateway, token, tokenize, save cards
Tested up to: 4.1.0
Stable tag: trunk
License: Custom
License URI: https://github.com/SecureSubmit/magento-extension/blob/master/LICENSE

SecureSubmit allows merchants to take PCI-Friendly Credit Card payments on WooCommerce using Heartland Payment Systems Payment Gateway.

== Description ==

This plugin provides a Heartland Payment Systems Gatway addon to the WooCommerce plugin using our SecureSubmit card tokenization library.

Features of SecureSubmit:

* Only two configuration fields: public and secret API key
* Simple to install and configure.
* Tokenized payments help reduce PCI Scope
* Enables credit card saving for a friction-reduced checkout.

== Installation ==
1. In your WordPress admin, go to Plugins > Add New and search for "WooCommerce SecureSubmit".
2. Click Install, once installed click Activate.
3. Configure and Enable the gateway in WooCommerce by adding your public and secret Api Keys.

== How do I get started? ==
Get your Certification (Dev/Sandbox) Api Keys by creating an account on https://developer.heartlandpaymentsystems.com/SecureSubmit/

== Screenshots ==

1. The SecureSubmit gateway configuration screen.
2. A view of the front-end payment form.
3. A view of the Manage Cards section.

== Changelog ==

= 1.1.0 =
* Adding refund capabilities

= 1.0.5 =
* Clearing token variable after form submission

= 1.0.4 =
* Clearing token if it already exists after error

= 1.0.3 =
* Version only update

= 1.0.2 =
* Fixed optional card-saving

= 1.0.1 =
* Made Card-Saving optional
* Reversed order of Public/Secret Keys

= 1.0.0 =
* Initial Release
