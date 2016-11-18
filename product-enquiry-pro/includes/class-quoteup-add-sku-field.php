<?php
namespace Includes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('QuoteUpAddSkuField')) {
    class QuoteUpAddSkuField
    {
        public $hidden_field_to_be_added = 'product_sku';
        public $name_of_hidden_field = 'SKU';

        public function __construct()
        {
            add_action('quoteup_add_hidden_fields_in_form', array($this, 'addSkuFieldInForm'));
            add_filter('quoteup_before_product_name_in_admin_email', array($this, 'addSkuInfoInAdminMail'), 10, 2);
            add_filter('quoteup_before_product_name_in_customer_email', array($this, 'addSkuInfoInCustomerMail'), 10, 2);
            add_action('quoteup_form_entry_added_in_db', array($this, 'addSkuEntryInDb'), 10, 1);
            add_action('quoteup_after_enquiry_date_column_header', array($this, 'skuColumnHeaderInDashboard'));
            add_action('quoteup_after_enquiry_date_column_data', array($this, 'skuColumnDataInDashboard'), 10, 1);
            add_filter('quoteup_export_csv_product_name_column', array($this, 'addSkuColumnInCsv'), 10, 1);
            add_filter('quoteup_custom_column_in_quoteup_db', array($this, 'addSkuColumnInNewDb'), 10, 1);
        }

        /*
         * Adds new header in table on Product Enquiry Details page
         */

        public function skuColumnHeaderInDashboard()
        {
            do_action('quoteup_before_sku_column_header');
            do_action('pep_before_sku_column_header');
            echo "<th class='td_norm'>".$this->translateHiddenFieldName().'</th>';
            do_action('quoteup_after_sku_column_header');
            do_action('pep_after_sku_column_header');
        }

        /*
         * Adds new column in the exported CSV
         */

        public function addSkuColumnInCsv($column_name)
        {
            return $column_name.', '.$this->hidden_field_to_be_added;
        }

        /*
         * Adds product_sku column in database
         */

        public function addSkuColumnInNewDb($column_name)
        {
            unset($column_name);
            return $this->hidden_field_to_be_added.' VARCHAR(35)';
        }

        /*
         * Shows the SKU value of product in the table being shown on Product Enquiry Details page
         */

        public function skuColumnDataInDashboard($row_data)
        {
            do_action_ref_array('quoteup_before_sku_column_data', array($row_data));
            do_action_ref_array('pep_before_sku_column_data', array($row_data));
            $field  = apply_filters('pep_sku_column_data', $row_data->{$this->hidden_field_to_be_added});
            echo "<td class='enq_td td_norm'>".apply_filters('quoteup_sku_column_data', $field).'</td>';
            do_action_ref_array('quoteup_after_sku_column_data', array($row_data));
            do_action_ref_array('pep_after_sku_column_data', array($row_data));
        }

        /*
         * Adds SKU field on the form
         */

        public function addSkuFieldInForm()
        {
            global $product;
            echo "<input type='hidden' name='".$this->hidden_field_to_be_added."' id='".$this->hidden_field_to_be_added."' class='quoteup_registered_parameter' value='".$product->get_sku()."'>";
        }

        /*
         * Once the form is filled, it adds the entry in database
         */

        public function addSkuEntryInDb($enquiry_id)
        {
            global $wpdb;
            if (isset($_POST[ $this->hidden_field_to_be_added ]) && !empty($_POST[ $this->hidden_field_to_be_added ])) {
                $wpdb->update(
				$wpdb->prefix . 'enquiry_details', array( 'product_sku' => $_POST[ $this->hidden_field_to_be_added ] ), array(
					'enquiry_id' => $enquiry_id ), array( '%s' ), array( '%d' )
                );
            }
        }

        /*
         * Add SKU field in email
         */

        public function addSkuInfoInAdminMail($email_content, $data)
        {
            $data = json_decode($data);
            if (empty($data->{$this->hidden_field_to_be_added}) || !isset($data->{$this->hidden_field_to_be_added})) {
                return $email_content;
            }
             $content_to_be_appended = apply_filters('pep_before_sku_in_admin_email', $email_content, $data);
            $content_to_be_appended = apply_filters('quoteup_before_sku_in_admin_email', $content_to_be_appended, $data);

            $content_to_be_appended .= "<tr > 
			<th style='width:25%;text-align:left'>".$this->translateHiddenFieldName()."</th>
                        <td style='width:75%'>: ".$data->{$this->hidden_field_to_be_added}.'</td>
		   </tr>';
            $content_to_be_appended = apply_filters('pep_after_sku_in_admin_email', $content_to_be_appended, $data);
            $content_to_be_appended = apply_filters('quoteup_after_sku_in_admin_email', $content_to_be_appended, $data);

            return $content_to_be_appended;
        }

        public function addSkuInfoInCustomerMail($email_content, $data)
        {
            $data = json_decode($data);
            if (empty($data->{$this->hidden_field_to_be_added}) || !isset($data->{$this->hidden_field_to_be_added})) {
                return $email_content;
            }

            $content_to_be_appended = apply_filters('pep_before_sku_in_customer_email', $email_content, $data);
            $content_to_be_appended = apply_filters('quoteup_before_sku_in_customer_email', $content_to_be_appended, $data);

            $content_to_be_appended .= "<tr > 
			<th style='width:25%;text-align:left'>".$this->translateHiddenFieldName()."</th>
                        <td style='width:75%'>: ".$data->{$this->hidden_field_to_be_added}.'</td>
		   </tr>';
            $content_to_be_appended = apply_filters('pep_after_sku_in_customer_email', $content_to_be_appended, $data);
            $content_to_be_appended = apply_filters('quoteup_after_sku_in_customer_email', $content_to_be_appended, $data);

            return $content_to_be_appended;
        }

        /*
         * Translate SKU name 
         */

        public function translateHiddenFieldName()
        {
            return __($this->name_of_hidden_field, 'quoteup');
        }
    }
}

new QuoteUpAddSkuField();
