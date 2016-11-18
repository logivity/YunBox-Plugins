=== Product Enquiry PRO for WooCommerce ===
Contributors: WisdmLabs
Tags: Enquiry for WooCommerce, Product Enquiry WooCommerce, WooCommerce Add-On
Requires at least: 4.4.0
Tested up to: 4.5.3
Woocommerce tested up to : 2.6.4
Stable tag: 4.1.1
License: GPL2

The Product Enquiry Pro for WooCommerce adds a 'Make an enquiry' or a 'Request a Quote' button to every WooCommerce Product Page, allowing a potential customer to make an enquiry or request a quote for one or multiple products. You can view these enquiries and quote requests made right in your dashboard, and filter enquiry and quotation records to analyse product demands.

== Description ==
1. Adds an Enquiry or Request a Quote Button to every WooCommerce Product Page
2. Enquiry and Quote Request Emails are sent to e-Store Owner and the Product Owner
3. Visitor can Choose to Send a Copy of the Enquiry Email or Quote Request to himself
4. Auto-Generate Quote PDF by admin
5. Show/hide price and purchase button for one or multiple products.
6. Style the buttons and Dialog
7. Responsive Enquiry and Quote Request Dialog
8. View Enquiries and Quote Requests right in your Dashboard
9. Filter and Export Enquiry and Quotation Records to Analyse Product Demands
10. Option to Input a potential Customer's Phone Number
11. Localization Ready
12. Remember's Customer's Name and Email id for those who make Regular Enquiries
13. Hooks provided to Customize the Enquiry Form
14. Mutiple Product Enquiries and Quote requests on a Single Page
15. Feature for admin to reply from the WordPress dashboard.

== Installation ==
Important: This plugin is a premium extension for the WooCommerce plugin. You must have the WooCommerce plugin already installed.

Please Note: 

1. Upon purchasing the Product Enquiry Pro (Also known as QuoteUp) for WooCommerce, an email will be sent to the registered email id, with the download link for the plugin and a purchase receipt id. Download the Product Enquiry Pro plugin using the download link.

2. Go to Plugin-> Add New menu in your dashboard and click on the Upload' tab. Choose the 'product-enquiry-pro.zip' file to be uploaded and click on Install Now.

3. After the plugin has installed successfully, click on the Activate Plugin link or activate the Product Enquiry Pro plugin from your Plugins page.

4. A Product Enquiry Pro License sub-menu will be created under Plugins menu in your dashboard. Click on this menu and enter your purchased product's license key. Click on Activate License. If license in valid, an 'Active' status message will be displayed, else 'Not Active' will be displayed.

5. Upon entering a valid license key, and activating the license, a Product Enquiry menu will be created in your dashboard. Refer to the detailed User Guide (http://wisdmlabs.com/woocommerce-product-enquiry-pro/#user_guide) for additional settings.


== Frequently Asked Questions ==
1. How do I show the telephone number field in the Enquiry Dialog?
A. Go to the Product Enquiry settings in the dashboard. Under ‘Form Options’, check the option 'Enable Telephone Number Field' to show a field to input the telephone number.

2. How do I style the Enquiry Dialog?
A. The ‘Product Enquiry’ settings menu in your dashboard, provides you options to set the colors for the Enquiry Dialog and the Enquiry Button, under 'Styling Options'. You can change the dialog background color, text color, button colors, and even set the product name to a different color.

3. Can I set multiple email ids in the Recipient’s field?
A. The ‘Recipient’s Email’ is set to the admin email id by default. You can replace this id, but you cannot add multiple ids.

4. How can I filter the enquiry records in the Dashboard?
A. In the ‘Enquiry Details’ menu, there is a search box ‘Search’. Enter a value in this box, to filter the enquiry records based on that value. For example, if you want to view the records for your product named PN781, you would have to enter PN781 in the search box.

5. How to export the enquiry records?
A. In the ‘Enquiry Details’ menu, there is an ‘Export As CSV’ button. Use this button to export the enquiry records. By default all records will be exported. To export selective records, filter the records using the ‘Search’ box, or check individual records to be exported, using the checkbox, and then click on the ‘Export As CSV’ button.

6. How can I export records for a particular product?
A. In the ‘Enquiry Details’ menu, enter the product name in the ‘Search’ box and press enter. The records, in the enquiry details table will be filtered for the product name. Now click on the ‘Export As CSV’ button, to export the filtered enquiry records.

7. How can I highlight the Product Name in the Enquiry Dialog?
A. The Product Name text color can be changed from the ‘Product Enquiry’ settings menu. Set a desired color for the product name text, under Product Enquiry-> Styling Options-> Product Name Color.

8. Help! I lost my license key?
A. In case you have misplaced your purchased product license key, kindly go back and retrieve your purchase receipt id from your mailbox. Use this receipt id to make a support request to retrieve your license key.

9. How do I contact you for support?
A. You can direct your support request to us, using the Support form.

10. What will happen if my license expires?
Every purchased license is valid for one year from the date of purchase. During this time you will recieve free updates and support. If the license expires, you will still be able to use PEP, but you will not recieve any support or updates.

11. Do you have a refund policy?
Yes. Refunds will be provided under the following conditions: 
-If PEP does not work with your theme and has integration issues, which we are unable to fix, even after support requests have been made. How to raise a support request.
-Refund is requested within 30 days of the original product purchase date.
-Refund will not be provided, if you do not have a valid reason, to not use the product.

12. Can I disable PEP for specific product?
A. Yes, it is possible to disable for PEP for specific product. This feature is available from 1.3.1 onwards. To do that, please go to the Add/Edit Screen of corresponding product. Search for 'Disable Enquiry for this Product' field. It is above the 'Publish'  or 'Update' button. Setting that dropdown to 'Yes' will prevent PEP from displaying Enquiry form on that product page.

13. I can not see 'Enquiry Form' link on a product. What should I do?
A. This can happen in three scenarios:
     a)  Your license is not activated. 
     b)  PEP has been disabled from settings page of 'Product Enquiry'
     c)  PEP has been disabled for specific product from 'Edit Product' screen.

14. How can I change the Subject and Content of email that goes out to the Admin?
A. Though there is no option in Product Enquiry's settings page to change the email content, PEP allows to do that via coding. One can write a code on the filter 'pep_admin_email_content' and 'pep_admin_email_subject'

15. Is it possible to change the Subject and Content of email that goes to out to the Customer?
A. Yes, it is possible to do that. It can be done using filters: pep_customer_email_subject and pep_customer_email_content

16. I want to add my own styling to the Enquiry or Quote Request button that appears on frontend. How should I do that?
A. On PEP's settings page, we have provided different options, so that you can design the button as per you want. If those options are not sufficient, then you can add your own css in 'Add Custom CSS' box on the settings page.

17. How to disable Product Enquiry forms for products of specific categories?
A. To achieve this, you can add your code on the filter 'pep_before_deciding_position_of_enquiry_form'. Returning false from the associated function with that filter will prevent PEP from showing the button on frontend.
 
18. I am not able to export enquiries/I encounter a white screen when I export enquiries.
A. This could be because WP_DEBUG has been turned on and you are notified with warnings by some other activated plugin or theme.

19. I generated a Quote PDF hours ago. I cannot find it now. What should I do?
A. Clicking the ‘Save and Preview’ button will generate the PDF once again. QuoteUp automatically deletes generated PDFs after an hour of creation, to protect your data in case your server gets compromised and to save space.

20. I feel it takes time to generate PDF. Do you know why so?
A. When ‘Save and Preview’ button is clicked for the first time after installing a plugin, it downloads a font required for your site and then generates a PDF. Time taken to download the font depends upon the size of a font.

21. I had disabled price for all products then to price is visible on products page?
A. when you disable or enable price for all products make sure you check global settings to apply those changes on all products.

== Changelog ==
= 4.1.1 =
* Feature - Added a setting for custom label for 'Enquiry' and 'Quote'.
* Feature - Added a setting for custom label for 'View Enquiry/Quote Cart'.
* Feature - Added a setting for custom label for 'Approve' and 'Reject' button.
* Feature - Added a setting for message to be displayed after quote rejection.
* Feature - Added a setting to add date field with custom date label on the form.
* Fix - Made edit quote page responsive.
* Fix - Changed position of expiration date.
* Fix - Added support for woocommerce 2.4.
* Fix - Added support for variations fetched through ajax.

= 4.1.0 =
* Feature - Added support for all variations of variable products.
* Feature - Provided bulk actions on products listing page for to 'enable/disable enquiry', 'show/hide price' and 'enable/disable add to cart'.
* Fix - CSS issue in some browsers.
* Fix - Made changes in session library to remove conflict with other plugins.

= 4.0.2 =
* Fix - Solved problem with approval/rejection link in PDF.

= 4.0.1 =
* Fix - Added support to variable products same as 3.1.1.

= 4.0 =
* Feature - Added quotation system.
* Feature - Improved quotation details layout (when quotation system is enabled).
* Feature - Receive quote request from customers.
* Feature - Create quotes and send it to your customers right from the dashboard.
* Feature - Auto-generation of quotation PDF.
* Feature - Product specific show or hide price on frontend.
* Fix - Improved the dashboard layout for both enquiry and quotation system.
* Fix - Fixed error with setting woocommerce cart page as enquiry cart.
* Fix - Displaying quote button for products which are displayed using any woocommerce shortcode.
* Fix - Product enquiry pro settings does not take effects on variable products.


= 3.1.1 =
* Feature - Shortcode automatically added on enquiry cart page.
* Feature - Shortcode removed from old enquiry cart page on enquiry cart page change.
* Fix - Problem with adding custom fields on single enquiry form.
* Fix - Problem with adding custom fields on multiple enquiry form.
* Fix - Removed minimum 15 characters message validation on enquiry form.

= 3.1.0 =
* Hover feature added.
* Fix - Enquiry time issue on enquiry details page.
* Fix - Issue when we change products sale price.
* Fix - Issue when changing product image.
* Fix - Message validation on enquiry form.
* Fix - warnings and notices on plugin activation.

= 3.0.1 =
* Issue with plugin update fixed.
* Compatibility with php version 5.3

= 3.0.0 =
* Feature for multi-product enquiry
* Option for admin to reply from the enquiry from dashboard

= 2.2.1 =
Fixed warnings and redirect issues with the last update
Compatible with WordPress 4.3.1 and WooCommerce 2.4.10

= 2.2.0 =
* Enquiry button on shop archive page
* Option to apply global settings for all products

= 2.1.4 =
* Resolved the issue with the Add to cart button being displayed twice
* Feature to select the county for telephone number field validation added
* Compatible with WooCommerce 2.4.6 and WordPress 4.3

= 2.1.3 =
* Feature to make the telephone number field mandatory

= 2.1.2 =
* Updated plugin license code

= 2.1.1 =
* Added feature to redirect user on submitting the enquiry

= 2.1.0 =
* Added feature to enable/disable add to cart button
* Form field accepts the name of the user making the enquiry
* Compatible with WooCommerce 2.3.7

= 2.0.0 = 
* Added filters to allow users to customize enquiry form.
* Added functionality to display additional fields in enquiry table.
* Added German language files.
* Added Dutch language files.

= 1.4.0 =
* Feature to add multiple recipient email addresses.
* Display Enquiry button only when product is out of stock.
* Added Swedish language files.
* Fixed warnings.

= 1.3.1 =
* Added Blog's Name in the Subject of emails that are sent out.
* Many Hooks and Filters have been added. Developers can now add their own hidden field on the form.
* Added SKU Field in the form.
* CSS Box on Settings page to add custom CSS.
* If WPML is enabled, then sends out email to Admin and Author in the language they have set in their profile.
* If WPML is enabled, then sends out email to customers in the language they are viewing the website.

= 1.3.0 =
*Provided checkbox to replace enquiry button with link
*reply-to address changed to senders address

= 1.2.1 =
*Fixed Warnings
*Fixed Enquiry Modal issue for fixed header themes

= 1.2.0 =
*Fixed Enquiry Modal issue 
*Increased Enquiry Message Length

= 1.1 =
*Updated Strings on Product Enquiry Dialog
*Changed text of Post Enquiry message

= 1.0 =
*Plugin Released
