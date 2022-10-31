=== CityPay Paylink PayForm WP ===
Contributors: _citypay_to_be_confirmed_
Tags: ecommerce, e-commerce, payment gateway
Donate link: http://citypay.com/
Requires at least: 4.0
Tested up to: 5.3
Stable tag: 1.0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CityPay Paylink PayForm WP is a plugin that supplements WordPress with
support for a form leading to payment processing using CityPay hosted payment
forms.

== Description ==

CityPay Paylink PayForm WP is a plugin that supplements WordPress with
support for a form leading to payment processing using CityPay hosted payment
forms.

The nature of payment processing using the plugin is limited to the
situations where the customer is responsible for specifying an amount
to pay to the merchant by reference to some sort of customer or invoice
identifier; the plugin does not at present provide or support -

 * shopping cart functionality;
 * connectivity between any external source of verification of invoice
references, amounts payable thereunder or any external database able
to receive notification of successful and failed payment transactions; and
 * maintenance of any records of payments or payment attempts that can be
accessed from the WordPress console.

== Installation ==

# Minimum requirements

* PHP version 5.2.4 or greater with libcurl support
* MySQL version 5.0 or greater
* libcurl version 7.10.5 or later with SSL / TLS support
* openssl, to current patch levels
* WordPress 4.0 or greater

# Automatic installation

To perform an automatic installation of the CityPay Paylink WordPress plugin,
login to your WordPress dashboard, select the Plugins menu and click Add New.

In the search field, type "CityPay" and click Search Plugins. Once you have
found our payment gateway plugin, it may be installed by clicking Install Now.

# Manual installation

To perform a manual installation of the CityPay Paylink WordPress plugin,
login to your WordPress dashboard, select the Plugins menu and click Add New. 

Then select Upload Plugin, browse to the location of the ZIP file containing
the plugin (typically named *citypay-paylink-wordpress.zip*) and then click
Install Now.

# Post installation: the plugin settings form

Once the plugin has been installed, you may need to activate it by selecting
the Plugins menu, clicking Installed Plugins and then activating the plugin
with the name "CityPay WordPress Payments" by clicking on the link labeled
Activate.

The merchant account, the license key, the transaction currency and other
information relating to the processing of transactions through the CityPay
Paylink hosted form payment gateway may be configured by selecting the
settings page which is accessed through the WordPress plugins page.

After the settings for the plugin have been configured, they must be saved
by clicking on the button labeled 'Save Changes' before they take effect.

## Creating a page in WordPress to accept payments

To accept payments using the plugin, it is necessary to create and mark-up
a static page containing the layout, fields, field names and custom error
messages as follows -

    [citypay-payform]
        [citypay-payform-field name="customer-name" type="customer-name" label="Name" order="1"]
        [citypay-payform-field name="email" type="email-address" label="Email Address" order="2"]
        [citypay-payform-field name="identifier" type="identifier" label="Invoice Number" pattern="AAnnnn" order="3" passthrough="true"]
        [citypay-payform-amount-field name="amount" label="Amount" maximum="150.00" minimum="1.00" order="4"]
            [error-message handle="CP_PAYLINK_AMOUNT_PARSE_ERROR_EMPTY_STRING"]
                You have specified an empty string (testing "").
            [/error-message]
            [error-message handle="CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_CHARACTER"]
                You have specified an invalid character.
            [/error-message]
            [error-message handle="CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_PRECISION"]
                You have put too many digits after the decimal point.
            [/error-message]
            [error-message handle="CP_PAYLINK_AMOUNT_PARSE_ERROR_BELOW_MINIMUM_VALUE"]
                You have specified an amount that less than that the practice is able to accept.
            [/error-message]
            [error-message handle="CP_PAYLINK_AMOUNT_PARSE_ERROR_ABOVE_MAXIMUM_VALUE"]
                You have specified an amount that more than that the practice is able to accept.
            [/error-message]
        [/citypay-payform-amount-field]
        [citypay-payform-checkbox-field id="accept-terms-and-conditions" name="accept-terms-and-conditions" type="accept-terms-and-conditions" order="5"]
            I accept the "terms and conditions" of payment.
        [/citypay-payform-checkbox-field]
        [citypay-payform-on-page-load]
            You can pay here -
            Please ensure the account number you intend to use is valid.
        [citypay-payform-display submit="Pay this invoice" /]
            Please ensure that you enter the correct amount.
        [/citypay-payform-on-page-load]
        [citypay-payform-on-redirect-success]
            Your payment was successful.
        [/citypay-payform-on-redirect-success]
        [citypay-payform-on-redirect-failure]
            Your payment failed.
        [/citypay-payform-on-redirect-failure]
        [citypay-payform-on-redirect-cancel]
            You cancelled your payment.
        [/citypay-payform-on-redirect-cancel]
        [citypay-payform-on-error]
            A request was submitted to the service provider to effect the
            payment which unfortunately failed. Please contact <a href="...">
            &lt;&gt; for more
            information
        [/citypay-payform-on-error]
    [/citypay-payform]

The structure of the markup may be described briefly, in terms of WordPress
short codes, as follows -

`[citypay-payform]`, and `[/citypay-payform]`: activates the CityPay Paylink
PayForm WP plugin thereby providing the context for configuration, display
and processing of the payment processing forms.

## PayForm active short codes

### On page load

`[citypay-payform-on-page-load]`, and `[/citypay-payform-on-page-load]`:
indicates the actions to be performed by WordPress on initial loading
the page.

### Display

`[citypay-payform-display /]`: instructs WordPress to display the payment
initiating form in the relevant action. The text to be used for the submit
button is specified using the `submit` shortcode attribute as follows -

    [citypay-payform-display submit="Pay this invoice" /]

### On redirect success

`[citypay-payform-on-redirect-success]`, and
`[/citypay-payform-on-redirect-success]`: indicates the action to be performed
by WordPress on payment processing being completed successfully.

### On redirect failure

`[citypay-payform-on-redirect-failure]`, and
`[/citypay-payform-on-redirect-failure]`: indicates the action to be performed
by Wordpress on failure of the payment process.

### On redirect cancel

`[citypay-payform-on-redirect-cancel]`, and
`[/citypay-payform-on-redirect-cancel]`: indicates the action to be performed
by WordPress on cancellation of the payment process.

### On error

`[citypay-payform-on-error]`, and `[/citypay-payform-on-error]`: indicates the
action to be performed by WordPress on the occurrence of an error.

## PayForm configuration short codes

### Text fields

`[citypay-payform-field]`, and `[/citypay-payform-field]`: enables configuration
of the various text-based PayForm fields by reference to the following shortcode
attributes -

`label`: specifies the text to be generated by WordPress for the relevant
    field.

`name`: specifies the name of the field which is used to identify the
    value in the context of the form submitted to WordPress on submission
    of the PayForm.

`order`: specifies the order of the field on the PayForm generated by
    WordPress.

`placeholder`: specifies the text, if any, to be generated as a placeholder
    for the relevant PayForm field.

`pattern`: specifies the pattern, if any, that the value submitted by
    the visitor must conform with prior to being referred to the CityPay
    PayLink hosted payment form.

`type`: provides one of the following values `customer-name`,
    `email-address`, `identifier` and `text` representing the type of
    the relevant field, thereby guiding processing.

`id`: specifies the identifier, if any, for the HTML form input field
    generated by WordPress for the relevant field to enable
    cross-referencing and automation by, for example and only if
    necessary, JavaScript scripts deployed through supplementary
    WordPress.

`passthrough`: a boolean value indicating that the value submitted by the
    visitor should be passed to the CityPay PayLink hosted payment form
    as a PayLink 'custom parameter' using a hidden field.

### Amount fields

`[citypay-payform-amount-field]`, and `[/citypay-payform-amount-field]`:
enables configuration of a currency amount-based PayForm field by reference
to the following shortcode attributes -
       
`id`: specifies the identifier, if any, for the HTML form input field
    generated by WordPress for the relevant field to enable
    cross-referencing and automation by, for example and only if
    necessary, JavaScript scripts deployed through supplementary
    WordPress.

`label`: specifies the text to be generated by WordPress for the relevant
    field.

`maximum`: specifies the maximum amount for which the prospective
    transaction is to be processed, thereby enabling the PayForm to
    decline transactions the exceed floor limits, or may erroneous.

`minimum`: specifies the minimum amount for which the prospective
    transaction is to be processed, thereby enabling the PayForm to
    decline low value transactions that fall below an economic
    value to handle electronically.

`name`: specifies the name of the field which is used to identify the
    value in the context of the form submitted to Wordpress on submission
    of the PayForm.

`order`: specifies the order of the field on the PayForm generated by
    WordPress.

### Checkbox fields

[citypay-payform-checkbox-field]`, and `[/citypay-payform-checkbox-field]`:
enables configuration of a checkbox-based PayForm field by reference to the
following shortcode attributes -

`id`: specifies the identifier, if any, for the HTML form input field
    generated by WordPress for the relevant field to enable
    cross-referencing and automation by, for example and only if
    necessary, JavaScript scripts deployed through supplementary
    WordPress.

`name`: specifies the name of the field which is used to identify the
    value in the context of the form submitted to WordPress on submission
    of the PayForm.

`type`: provides one of the following values `accept-terms-and-conditions`,
    and `checkbox` representing the type of the relevant check-box field,
    thereby guiding processing.

    `accept-terms-and-conditions`

    A checkbox field with the type set to `accept-terms-and-conditions`,
    is a special checkbox that prevents onward processing of the PayForm
    if the visitor is unwilling to accede to the merchant's terms and
    conditions in connection with the payment. This step is typically
    required by acquirers to avoid unnecessary charge-back disputes.

`order`: specifies the order of the field on the PayForm generated by
    WordPress.

### Error messages

`[error-message]` and `[/error-message]`: enables configuration of the error
    messages associated with particular field-based error events. The error
    messages associated with particular error handles are configured
    on a field-by-field basis to enable errors associated with individual field
    error events to be tailored to customer requirements. 

`handle`: refers to the handle for the relevant error message as follows -

`CP_PAYLINK_TEXT_FIELD_PARSE_ERROR_EMPTY_STRING`: the error generated
    if the value submitted to a text field is an empty string.

`CP_PAYLINK_IDENTIFIER_FIELD_PARSE_ERROR_EMPTY_STRING`: the error
    generated if the value submitted to a text field where the
    `type` is set to `identifier` is an empty string.

`CP_PAYLINK_IDENTIFIER_FIELD_PARSE_ERROR_NOT_VALID`: the error
    generated if the value submitted to a text field where the
    `type` is set to `identifier` is, when parsed by reference to
    the `pattern` indicated for the shortcode, found to be invalid.

`CP_PAYLINK_NAME_FIELD_PARSE_ERROR_EMPTY_STRING`: the error generated
    if the value submitted to a text field where the `type` is set
    to `customer-name` is an empty string.

`CP_PAYLINK_NAME_FIELD_PARSE_ERROR_NOT_VALID`: the error generated
    if the value submitted to a text field where the `type` is set
    to `customer-name` is, when parsed, found to be invalid.

`CP_PAYLINK_EMAIL_ADDRESS_FIELD_PARSE_ERROR_EMPTY_STRING`: the error
    generated if the value submitted to a text field where the `type`
    is set to `email-address` is an empty string.

`CP_PAYLINK_EMAIL_ADDRESS_FIELD_PARSE_ERROR_NOT_VALID`: the error
    generated if the value submitted to a text field where the `type`
    is set to `email-address` is, when parsed, found to be invalid.

`CP_PAYLINK_AMOUNT_PARSE_ERROR_EMPTY_STRING`: the error generated if
    the value submitted to an amount field is found to be invalid. A
    value is invalid if it cannot be converted to a numerical value.

`CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_CHARACTER`: the error generated
    if the value submitted to an amount field is found to contain one
    or more invalid characters such as upper and lower letters (A-Z, and
    a-z respectively), and punctuation such as ',' (commas), '£' (pound
    signs), '$' (dollar signs) and so forth. The only permissible
    characters for an amount value are numeric (0-9) and a single
    period (.) being the decimal point indicating the fractional
    component of an amount.

`CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_PRECISION`: the error generated
    if the value submitted to an amount field has been specified, for
    the relevant currency, to an invalid precision. This error typically
    occurs if the fractional component of the amount has been specified
    to more decimal places than expected for the relevant currency.

`CP_PAYLINK_AMOUNT_PARSE_ERROR_BELOW_MINIMUM_VALUE`: the error
    generated if the value submitted to a currency amount-based
    is below the specified minimum value.

`CP_PAYLINK_AMOUNT_PARSE_ERROR_ABOVE_MAXIMUM_VALUE`: the error
    generated if the value submitted to a currency amount-based
    is above the specified maximum value.

`CP_PAYLINK_TERMS_AND_CONDITIONS_NOT_ACCEPTED`: the error generated
    if a checkbox where the `type` is set to `terms-and-conditions`
    is left unchecked on submission of the relevant form.

Example:

    [error-message handle="CP_PAYLINK_AMOUNT_PARSE_ERROR_EMPTY_STRING"]
        You have specified an empty string (testing "").
    [/error-message]
    [error-message handle="CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_CHARACTER"]
        You have specified an invalid character.
    [/error-message]
    [error-message handle="CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_PRECISION"]
        You have put too many digits after the decimal point.
    [/error-message]
    [error-message handle="CP_PAYLINK_AMOUNT_PARSE_ERROR_BELOW_MINIMUM_VALUE"]
        You have specified an amount that less than that the practice is able to accept.
    [/error-message]
    [error-message handle="CP_PAYLINK_AMOUNT_PARSE_ERROR_ABOVE_MAXIMUM_VALUE"]
        You have specified an amount that more than that the practice is able to accept.
    [/error-message]

## Processing test transactions

To test the operation of an e-commerce solution based on WooCommerce in
combination with the CityPay Paylink WooCommerce plugin without processing
transactions that will be settled by the upstream acquirer, the check box
labeled Test Mode appearing on the plugin settings form should be ticked.

## Processing live transactions

To process live transactions for settlement by the upstream acquirer, the
checkbox labeled Test Mode referenced in the paragraph above must be
unticked.

## Enabling logging

The interaction between WordPress, WooCommerce and the CityPay Paylink
hosted payment form service may be monitored by ticking the checkbox labeled
Debug Log appearing on the plugin settings form.

Log payment events appearing in the resultant log file will help to trace
any difficulties you may experience accepting payments using the CityPay
Paylink service.

The location of the log file is provided on the plugin settings form.

## Frequently Asked Questions

### WordPress / WooCommerce displays "Sorry, unable to process your order at this time" at the time of checkout

WordPress / WooCommerce displays the generic error "Sorry, unable to process
your order at this time" if is not possible for the application to refer the
customer to the CityPay Paylink hosted payment form.

There may be a variety of reasons that such a referral cannot be made. If
connection failure is persistent, then this may be caused by -

1. incorrect configuration of the merchant identifier (the "MID") or the licence
key used by the plugin to obtain a payment session token from the Paylink
service; or
2. incorrect configuration of the IP address registered with CityPay for
receiving API calls from merchant applications to the CityPay Paylink service
API.

The specific cause of the problem can be determined by enabling debug logging
for the plugin and, after attempting to process a transaction, checking the
debug log.

Configuration of the MID, or the licence key for the installed version of 
WordPress / WooCommerce and the CityPay Paylink plugin is performed through
the WooCommerce settings forms; whereas configuration of the IP address
for the merchant application registered with CityPay is administered by
CityPay. To request configuration of the IP address for the merchant application
please contact <support@citypay.com>.

If the connection failure is not persistent, and intermittent in nature, the
problem is most likely caused by connectivity or DNS name resolution problems
affecting the merchant application generally.

### CityPay Paylink service connectivity issues involving WordPress / WooCommerce implementations

The CityPay Paylink WooCommerce plugin relies upon being able to establish
a secure, encrypted session with the CityPay Paylink service. The OpenSSL
library typically installed with Windows binary versions of PHP is not
accompanied by any certificate authority ("CA") certificates that exist to
certify, through a chain of certificates, the identity of the remote endpoint;
in the present case, the CityPay Paylink service. The problem may affect binary
distributions of PHP for other operating systems as well.

With debug logging enabled in the plugin settings, a problem involving access
to SSL CA certificates may be indicated by a line of the form -

    SSL certificate problem: unable to get local issuer certificate

To resolve this problem, the CA bundle may be downloaded from
<https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt>,
installed in an appropriate location, and referenced in the php.ini
configuration file for PHP using the curl.cainfo configuration setting.

== Screenshots ==


== Changelog ==

= 1.2.6 =

* Updated readme file.

= 1.2.5 =

* Fixed Paylink Button to handle postback and added configuration for custom postback url.

= 1.2.3 =

* Fixed Paylink Standalone Payment Button.

= 1.2.1 =

* Enforcement of TLS_v1.2.

= 1.2.0 =

* Addition of standalone payment button.

= 1.1.7 =

* Added compatibility with PHP 7.3.

= 1.1.6 =

* Fixed identifier validation which did not check characters/a minimum of 5 characters correctly.

= 1.0.5 =

* Improved project structure, and unit tests.

= 1.0.4 =

* Version number skipped to bring project in line with version delivered
  to customers.

= 1.0.3 =

* Resolution for incorrect email address parsing / recognition (per PPWD-21)

= 1.0.2 =

* Introduces improved error reporting for SSL connectivity issues.

= 1.0.1 =

* Support for WooCommerce versions 2.3 and above.

= 1.0.0 =

* Initial version.

== Upgrade Notice ==

= 1.0.5 =

* Update follows improvement to the structure of the project.

= 1.0.4 =

* Version number skipped to bring project in line with version delivered
  to customers.

= 1.0.3 =

* Update resolves incorrect email address parsing / recognition (per PPWD-21)

= 1.0.2 =

* Update improves error reporting for SSL connectivity issues.

= 1.0.1 =

* Upgrade supports WooCommerce versions 2.3 and above.

= 1.0.0 =

* Initial version.
