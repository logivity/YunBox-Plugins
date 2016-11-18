<?php
namespace Includes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class QuoteUpAddCustomField {

    public $fields = array();
    public $temp_fields = array();
    public $meta_key;

	public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'addScripts'));
        add_action('quoteup_add_custom_field_in_form', array($this, 'addCustomFields')); //done
        add_action('quoteup_add_custom_field_in_db', array($this, 'addCustomFieldsDb')); //done
        add_action('quoteup_create_custom_field', array($this, 'createCustomFields'));  //done
        add_filter('quoteup_get_custom_field', array($this, 'getCustomFields'));
        add_filter('quoteup_add_custom_field_admin_email', array($this, 'addCustomFieldsAdminEmail'), 10, 1);
        add_filter('quoteup_add_custom_field_customer_email', array($this, 'addCustomFieldsCustomerEmail'), 10, 1);
        add_action('quoteup_custom_fields_header', array($this, 'quoteupCustomFieldsHeader')); //done
        add_action('quoteup_custom_fields_data', array($this, 'quoteupCustomFieldsData'), 10, 1); //done
        add_action('mep_custom_fields', array($this, 'mpeCustomFieldDashboard'), 10, 1);
        add_action('quoteup_delete_custom_fields', array($this, 'deleteCustomFields'));
        add_action('mpe_add_custom_field_in_form', array($this, 'addCustomFieldsOnMPEForm'));
    }

	public function customTextField( $val ) {
        $temp = '<div class="form_input">';
        $temp .= "<div class='form-wrap'><div class='form-wrap-inner'>";
        $temp .= "<input type='text'";
        if (isset($val[ 'id' ])) {
            $temp .= " name='".$val[ 'id' ]."'";
        }
        $temp .= " id='".$val[ 'id' ]."'";
        if (isset($val[ 'required' ])) {
            $temp .= ' '.(($val[ 'required' ] == 'yes') ? 'required' : '');
        }

        $temp .= $this->addClassToField($val);

        if (isset($val[ 'value' ])) {
            if ($val[ 'value' ] != '') {
                $temp .= " value='".$val[ 'value' ]."'";
            }
        }
        $temp .= ' placeholder="'.$val[ 'placeholder' ].(($val[ 'required' ] == 'yes') ? '*' : '').'"';
        $temp .= '/>';
        $temp .= '</div></div></div>';

        return $temp;
    }

	public function customTextareaField( $val ) {
        $temp = '<div class="form_input">';
        $temp .= "<div class='form-wrap'><div class='form-wrap-inner'>";
        $temp .= '<textarea';
        if (isset($val[ 'id' ])) {
            $temp .= " name='".$val[ 'id' ]."'";
            $temp .= " id='".$val[ 'id' ]."'";
        }
        if (isset($val[ 'required' ])) {
            $temp .= ' '.(($val[ 'required' ] == 'yes') ? 'required' : '');
        }
        $temp .= $this->addClassToField($val);

        $temp .= ' placeholder="'.$val[ 'placeholder' ].(($val[ 'required' ] == 'yes') ? '*' : '').'"';
        $temp .= " rows='5'>";
        if (isset($val[ 'value' ])) {
            $temp .= $val[ 'value' ];
        }

        $temp .= '</textarea>';
        $temp .= '</div></div></div>';

        return $temp;
    }

	public function customRadioField( $val ) {
        $temp = '<div class="form_input ';
        if (isset($val[ 'class' ])) {
            $temp .= $val[ 'class' ];
        }
        $temp .= '">';
        $temp .= "<div class='form-wrap'><div class='form-wrap-inner'>";
        $temp .= $val[ 'label' ].(($val[ 'required' ] == 'yes') ? '<sup class="req">*</sup>' : '').':&nbsp&nbsp';
        if (count($val[ 'options' ]) > 0) {
            $temp = $this->forEachRadioField($val, $temp);

            $temp .= '</div></div></div>';

            return $temp;
        }
    }

	private function forEachRadioField( $val, $temp ) {
        foreach ($val[ 'options' ] as $key => $value) {
            $temp .= "<input type='radio' ";
            if (isset($val[ 'id' ])) {
                $temp .= " name='".$val[ 'id' ]."'";
                $temp .= " id='".$val[ 'id' ]."'";
            }
            if (isset($val[ 'required' ])) {
                $temp .= ' '.(($val[ 'required' ] == 'yes') ? 'required' : '');
            }
            $temp .= ' placeholder="'.$val[ 'placeholder' ].(($val[ 'required' ] == 'yes') ? '*' : '').'"';
            if (isset($value)) {
                $temp .= " value='".$value."'/>".$value.'&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
            } else {
                $temp .= '/>';
            }
            unset($key);
        }

        return $temp;
    }

	public function customSelectField( $val ) {
        $temp = '<div class="form_input ';
        $temp .= $this->addClassToField($val);
                    $temp .= '">';
                    $temp .= "<div class='form-wrap'><div class='form-wrap-inner'>";
                    $temp .= $val[ 'label' ].(($val[ 'required' ] == 'yes') ? '<sup class="req">*</sup>' : '').':&nbsp&nbsp<select ';
        if (isset($val[ 'id' ])) {
            $temp .= " name='".$val[ 'id' ]."'";
        }
        if (isset($val[ 'id' ])) {
            $temp .= " id='".$val[ 'id' ]."'";
        }
                    $temp .= ' >';

        if (count($val[ 'options' ]) > 0) {
            if (isset($val['default_text'])) {
                $temp .= "<option value='#'>".$val['default_text'].'</option>';
            }
            foreach ($val[ 'options' ] as $key => $value) {
                if (isset($value)) {
                    $temp .= "<option value='".$value."'>".$value.'</option>';
                }

                unset($key);
            }
        }
                    $temp .= '</select>';
                    $temp .= '</div></div></div>';

        return $temp;
    }

	public function customcheckboxField( $val ) {
        $temp = '<div class="mpe_form_input ';

        $temp .= $this->addClassToField($val);

        $temp .= '">';
        $temp .= '<label class="mpe-left wdm-enquiry-form-label">';
        $temp .= $val[ 'label' ].(($val[ 'required' ] == 'yes') ? '<sup class="req">*</sup>' : '');
        // $temp .= 'Select'.(($val[ 'required' ] == 'yes') ? '<sup class="req">*</sup>' : '');
        $temp .= '</label>
                            <div class="mpe-right"><div class="mpe-right-inner">';
        // $temp .= $val[ 'label' ].':&nbsp&nbsp';
        if (count($val[ 'options' ]) > 0) {
            foreach ($val[ 'options' ] as $key => $value) {
                $temp .= "<input type='checkbox' ";
                if (isset($val[ 'id' ])) {
                    $temp .= " name='".$val[ 'id' ]."[]'";
                    $temp .= " id='".$val[ 'id' ]."'";
                }
                if (isset($val[ 'required' ])) {
                    $temp .= ' '.(($val[ 'required' ] == 'yes') ? 'required' : '');
                }
                //$temp .= " class='".$val['class']."'";
                if (isset($value)) {
                    $temp .= " value='".$value."'>".$value.'&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
                }
                unset($key);
            }
        }
        $temp .= '</div></div></div>';

        return $temp;
    }

	public function forEachOption( $val, $value ) {
        $temp = "<input type='checkbox' ";
        if (isset($val[ 'id' ])) {
            $temp .= " name='".$val[ 'id' ]."[]'";
            $temp .= " id='".$val[ 'id' ]."'";
        }

        if (isset($val[ 'required' ])) {
            $temp .= ' '.(($val[ 'required' ] == 'yes') ? 'required' : '');
        }

        if (isset($value)) {
            $temp .= " value='".$value."'>".$value.'&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
        }

        return $temp;
    }

	public function customMultipleField( $val ) {
        $temp = '<div class="form_input ';
        if (isset($val[ 'class' ])) {
            $temp .= $val[ 'class' ];
        }
                    $temp .= '">';
                    $temp .= $val[ 'label' ].(($val[ 'required' ] == 'yes') ? '<sup class="req">*</sup>' : '');
                    $temp .= '<select class="wdm-custom-multiple-fields" ';
        if (isset($val[ 'id' ])) {
            $temp .= " name='".$val[ 'id' ]."'";
        }
        if (isset($val[ 'id' ])) {
            $temp .= " id='".$val[ 'id' ]."'";
        }
                    $temp .= ' multiple>';
        if (count($val[ 'options' ]) > 0) {
            foreach ($val[ 'options' ] as $key => $value) {
                if (isset($value)) {
                    $temp .= "<option value='".$value."'>".$value.'</option>';
                }
                unset($key);
            }
        }
                    $temp .= '</select>';
                    $temp .= '</div>';

        return $temp;
    }

    public function addScripts()
    {
        wp_enqueue_style('multipleSelectCss', QUOTEUP_PLUGIN_URL . '/css/public/multiple-select.css');
		wp_enqueue_script( 'multipleSelectJs', QUOTEUP_PLUGIN_URL . '/js/public/multiple-select.js', array(
			'jquery' ), '', true );
    }

    //creating custom fields html
	public function addCustomFields() {
        $temp = '';
        foreach ($this->fields as $key => $val) {
            if (isset($val[ 'type' ])) {
                if ($val[ 'type' ] == 'text') {
                    $temp .= $this->customTextField($val);
                } elseif ($val[ 'type' ] == 'textarea') {
                    $temp .= $this->customTextareaField($val);
                } elseif ($val[ 'type' ] == 'radio') {
                    $temp .= $this->customRadioField($val);
                } elseif ($val[ 'type' ] == 'select') {
                    $temp .= $this->customSelectField($val);
                } elseif ($val[ 'type' ] == 'checkbox') {
                    $temp .= $this->customcheckboxField($val);
                } elseif ($val[ 'type' ] == 'multiple') {
                    $temp .= $this->customMultipleField($val);
                }
            }
            unset($key);
        }
        echo $temp;
    }

	public function addCustomFieldsOnMPEForm() {
        $temp = '';

        foreach ($this->fields as $key => $v) {
            if (isset($v[ 'type' ])) {
                if ($v[ 'type' ] == 'text') {
                    $temp .= $this->addTextField($v);
                } elseif ($v[ 'type' ] == 'textarea') {
                    $temp .= $this->addTextareaField($v);
                } elseif ($v[ 'type' ] == 'radio') {
                    $temp .= $this->addRadioField($v);
                } elseif ($v[ 'type' ] == 'select') {
                    $temp .= $this->addSelectField($v);
                } elseif ($v[ 'type' ] == 'checkbox') {
                    $temp .= $this->addCheckBoxField($v);
                } elseif ($v[ 'type' ] == 'multiple') {
                    $temp .= $this->addMultipleField($v);
                }
            }
        }
        echo $temp;
        unset($key);
    }

	public function addMultipleField( $val ) {
        $temp = '<div class="mpe_form_input ';
        if (isset($val[ 'class' ])) {
            $temp .= $val[ 'class' ];
        }
                    $temp .= '">';
                    $temp .= '<label class="mpe-left wdm-enquiry-form-label">';
                    $temp .= $val[ 'label' ].(($val[ 'required' ] == 'yes') ? '<sup class="req">*</sup>' : '');
                    $temp .= '</label>
                            <div class="mpe-right"><div class="mpe-right-inner">';
                    $temp .= '<select class="wdm-custom-multiple-fields" ';
        if (isset($val[ 'id' ])) {
            $temp .= " name='".$val[ 'id' ]."'";
            $temp .= " id='".$val[ 'id' ]."'";
        }
                    $temp .= ' multiple>';
        if (count($val[ 'options' ]) > 0) {
            foreach ($val[ 'options' ] as $key => $value) {
                if (isset($value)) {
                    $temp .= "<option value='".$value."'>".$value.'</option>';
                }
                unset($key);
            }
        }
                    $temp .= '</select>';
                    $temp .= '</div></div></div>';

        return $temp;

        // return $temp;
        unset($key);
    }


	public function addClassToField( $val ) {
        $temp = '';
        if (isset($val[ 'class' ])) {
            $temp = " class='".$val[ 'class' ]."'";
        }

        return $temp;
    }

	public function addTextField( $val ) {
        $temp = '<div class="mpe_form_input">';
        $temp .= '<label class="mpe-left wdm-enquiry-form-label">';
        $temp .= $val[ 'label' ].(($val[ 'required' ] == 'yes') ? '<sup class="req">*</sup>' : '');
        $temp .= '</label>
                            <div class="mpe-right"><div class="mpe-right-inner">';
        $temp .= "<input type='text'";
        if (isset($val[ 'id' ])) {
            $temp .= " name='".$val[ 'id' ]."'";
        }
        $temp .= " id='".$val[ 'id' ]."'";
        if (isset($val[ 'required' ])) {
            $temp .= ' '.(($val[ 'required' ] == 'yes') ? 'required' : '');
        }

        $temp .= $this->addClassToField($val);

        if (isset($val[ 'value' ])) {
            if ($val[ 'value' ] != '') {
                $temp .= " value='".$val[ 'value' ]."'";
            } else {
                $temp .= " placeholder='".$val[ 'placeholder' ]."'";
            }
        }
        $temp .= '>';
        $temp .= '</div></div></div>';

        return $temp;
    }

	public function addTextareaField( $val ) {
        $temp = '<div class="mpe_form_input">';
        $temp .= '<label class="mpe-left wdm-enquiry-form-label">';
        $temp .= $val[ 'placeholder' ].(($val[ 'required' ] == 'yes') ? '<sup class="req">*</sup>' : '');
        $temp .= '</label>
                            <div class="mpe-right"><div class="mpe-right-inner">';
        $temp .= '<textarea';
        if (isset($val[ 'id' ])) {
            $temp .= " name='".$val[ 'id' ]."'";
            $temp .= " id='".$val[ 'id' ]."'";
        }

        if (isset($val[ 'required' ])) {
            $temp .= ' '.(($val[ 'required' ] == 'yes') ? 'required' : '');
        }

        $temp .= $this->addClassToField($val);

        $temp .= " placeholder='".$val[ 'placeholder' ]."'";
        $temp .= " rows='5'>";
        if (isset($val[ 'value' ])) {
            $temp .= $val[ 'value' ];
        }
        $temp .= '</textarea>';
        $temp .= '</div></div></div>';

        return $temp;
    }

	public function addRadioField( $val ) {
        $temp = '<div class="mpe_form_input ';

        $temp .= $this->addClassToField($val);

        $temp .= '">';
        $temp .= '<label class="mpe-left wdm-enquiry-form-label">';

        $temp .= $val[ 'label' ].(($val[ 'required' ] == 'yes') ? '<sup class="req">*</sup>' : '');
        $temp .= '</label>
                <div class="mpe-right"><div class="mpe-right-inner">';
        $temp .= '&nbsp&nbsp';
        if (count($val[ 'options' ]) > 0) {
            foreach ($val[ 'options' ] as $key => $value) {
                $temp .= "<input type='radio' ";
                if (isset($val[ 'id' ])) {
                    $temp .= " name='".$val[ 'id' ]."'";
                    $temp .= " id='".$val[ 'id' ]."'";
                }
                if (isset($val[ 'required' ])) {
                    $temp .= ' '.(($val[ 'required' ] == 'yes') ? 'required' : '');
                }
                //$temp .= " class='".$val['class']."'";
                if (isset($value)) {
                    $temp .= " value='".$value."'>".$value.'&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
                }
                unset($key);
            }
        }
        $temp .= '</div></div></div>';

        return $temp;
    }

	public function setID( $val, $temp ) {
        if (isset($val[ 'id' ])) {
            $temp = " id='".$val[ 'id' ]."'";
        }
        return $temp;
    }

	public function addSelectField( $val ) {
        $temp    = '<div class="mpe_form_input">';
        $temp .= '<label class="mpe-left wdm-enquiry-form-label">';
        $temp .= $val[ 'label' ]. (($val[ 'required' ] == 'yes') ? '<sup class="req">*</sup>' : '');
        $temp .= '</label>
                            <div class="mpe-right"><div class="mpe-right-inner">';
        $temp .= "<select";
        if (isset($val[ 'id' ])) {
            $temp .= " name='".$val[ 'id' ]."'";
        }
        $temp .= " id='".$val[ 'id' ]."'";
        if (isset($val[ 'required' ])) {
            $temp .= ' '.(($val[ 'required' ] == 'yes') ? 'required' : '');
        }

        $temp .= $this->addClassToField($val);
        $temp .= ' >';
        
        if (count($val[ 'options' ]) > 0) {
            if (isset($val['default_text'])) {
                $temp .= "<option value='#'>".$val['default_text'].'</option>';
            }
            foreach ($val[ 'options' ] as $key => $value) {
                if (isset($value)) {
                    $temp .= "<option value='".$value."'>".$value.'</option>';
                }
                unset($key);
            }
        }
        $temp .= '</select>';
        $temp .= '</div></div></div>';

        return $temp;
    }

	public function getNameID( $val ) {
        $temp = "";
        if (isset($val[ 'id' ])) {
            $temp = " name='".$val[ 'id' ]."[]'";
        }
        if (isset($val[ 'id' ])) {
            $temp .= " id='".$val[ 'id' ]."'";
        }
        return $temp;
    }

	public function addCheckBoxField( $val ) {
        $temp = '<div class="mpe_form_input ';

        $temp .= $this->addClassToField($val);

        $temp .= '">';
        $temp .= '<label class="mpe-left wdm-enquiry-form-label">';
        $temp .= $val[ 'label' ].(($val[ 'required' ] == 'yes') ? '<sup class="req">*</sup>' : '');
        $temp .= '</label>
                            <div class="mpe-right"><div class="mpe-right-inner">';
        if (count($val[ 'options' ]) > 0) {
            foreach ($val[ 'options' ] as $key => $value) {
                $temp .= "<input type='checkbox' ";
                $temp .= $this->getNameID($val);
                if (isset($val[ 'required' ])) {
                    $temp .= ' '.(($val[ 'required' ] == 'yes') ? 'required' : '');
                }
                if (isset($value)) {
                    $temp .= " value='".$value."'>".$value.'&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
                }
                unset($key);
            }
        }
        $temp .= '</div></div></div>';

        return $temp;
    }

    //adding custom fields to enquiry_meta table
	public function addCustomFieldsDb( $enquiryID ) {
        global $wpdb;
        $tbl = $wpdb->prefix.'enquiry_meta';
        foreach ($this->fields as $key => $v) {
            if ($v[ 'id' ] != 'custname' && $v[ 'id' ] != 'txtemail' && $v[ 'id' ] != 'txtphone' && $v[ 'id' ] != 'txtsubject' && $v[ 'id' ] != 'txtmsg' && $v[ 'id' ] != 'txtmsg' && $v[ 'id' ] != 'txtdate') {
                $wpdb->insert(
                    $tbl,
                    array(
                    'enquiry_id' => $enquiryID,
                    'meta_key' => $v[ 'label' ],
                    'meta_value' => (isset($_POST[ $v[ 'id' ] ]) ? $_POST[ $v[ 'id' ] ] : ''),
                    ),
                    array(
                    '%d',
                    '%s',
                    '%s',
                    )
                );
            }
            unset($key);
        }
    }

    //creating custom fields array
    public function createCustomFields()
    {
        $default_vals = array('show_after_summary' => 1,
            'button_CSS' => 0,
            'pos_radio' => 0,
            'show_powered_by_link' => 0,
            'enable_send_mail_copy' => 0,
            'enable_telephone_no_txtbox' => 0,
            'dialog_product_color' => '#3079ED',
            'dialog_text_color' => '#000000',
            'dialog_color' => '#F7F7F7',
        );
        $form_data = get_option('wdm_form_data', $default_vals);

        $email = '';
        $name = '';
        if (is_user_logged_in()) {
            global $current_user;
            wp_get_current_user();
            $email = $current_user->user_email;
            $name = $current_user->user_firstname.' '.$current_user->user_lastname;
            if ($name == ' ') {
                $name = $current_user->user_login;
            }
        } else {
            if (isset($_COOKIE[ 'wdmusername' ])) {
                $name = $_COOKIE[ 'wdmusername' ];
            }
            if (isset($_COOKIE[ 'wdmuseremail' ])) {
                $email = $_COOKIE[ 'wdmuseremail' ];
            }
        }
        $custname = array(
            array(
                'id' => 'custname',
                'class' => 'wdm-modal_text',
                'type' => 'text',
                'placeholder' => __('Name', 'quoteup'),
                'required' => 'yes',
                'required_message' => __('Please Enter Name', 'quoteup'),
                'validation' => '^[a-zA-Z\u00C0-\u00ff ]+$',
                'validation_message' => __('Please Enter Valid Name', 'quoteup'),
                'include_in_admin_mail' => 'yes',
                'include_in_customer_mail' => 'no',
                'label' => __('Customer Name','quoteup'),
                'value' => $name,
            ),
            array(
                'id' => 'txtemail',
                'class' => 'wdm-modal_text',
                'type' => 'text',
                'placeholder' => __('Email', 'quoteup'),
                'required' => 'yes',
                'required_message' => __('Please Enter Email', 'quoteup'),
                'validation' => '^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$',
                'validation_message' => __('Please Enter Valid Email Address', 'quoteup'),
                'include_in_admin_mail' => 'yes',
                'include_in_customer_mail' => 'no',
                'label' => __('Email','quoteup'),
                'value' => $email,
            ),
        );
        if (isset($form_data[ 'enable_telephone_no_txtbox' ])) {
            if ($form_data[ 'enable_telephone_no_txtbox' ] == '1') {
                $ph_req = 'no';

                $ph_req = $this->phoneMandatory($ph_req, $form_data);
                

                $custname = array_merge(
				$custname, array(
                    array(
                        'id' => 'txtphone',
                        'class' => 'wdm-modal_text',
                        'type' => 'text',
                        'placeholder' => __('Phone Number', 'quoteup'),
                        'required' => $ph_req,
                        'required_message' => __('Please Enter Phone Number', 'quoteup'),
                        'validation' => '^[0-9]{6,}$',
                        'validation_message' => __('Please Enter Valid Telephone No', 'quoteup'),
                        'include_in_admin_mail' => 'yes',
                        'include_in_customer_mail' => 'no',
                        'label' => __('Phone Number', 'quoteup'),
                        'value' => '',
                    ),
                    )
                );
            }
        }
        if (isset($form_data[ 'enable_date_field' ])) {
            if ($form_data[ 'enable_date_field' ] == '1') {
                global $wp_scripts;
                wp_enqueue_script('jquery');
                wp_enqueue_script('jquery-ui-core');
                // JS and css required for datepciker
                wp_enqueue_script('datepicker', quoteupPluginUrl().'/js/public/datepicker.js', array('jquery', 'jquery-ui-core', 'jquery-effects-highlight', 'jquery-ui-datepicker'), true);

                // get registered script object for jquery-ui
                $ui = $wp_scripts->query('jquery-ui-core');
                // tell WordPress to load the Smoothness theme from Google CDN
                $protocol = is_ssl() ? 'https' : 'http';
                $url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css";
                wp_enqueue_style('jquery-ui-smoothness', $url, false, null);
                wp_enqueue_style('jquery-ui-datepicker', QUOTEUP_PLUGIN_URL.'/css/admin/datepicker.css');
                // wp_enqueue_style('wdm_datepicker_css', plugins_url('css/public/wdm_date_picker_css.css', __FILE__));
                // 
                global $wp_locale;

                $aryArgs = array(
                    'closeText'         => __('Done', 'quoteup'),
                    'currentText'       => __('Today', 'quoteup'),
                    'monthNames'        => $this->stripArrayIndices($wp_locale->month),
                    'monthNamesShort'   => $this->stripArrayIndices($wp_locale->month_abbrev),
                    'monthStatus'       => __('Show a different month', 'quoteup'),
                    'dayNames'          => $this->stripArrayIndices($wp_locale->weekday),
                    'dayNamesShort'     => $this->stripArrayIndices($wp_locale->weekday_abbrev),
                    'dayNamesMin'       => $this->stripArrayIndices($wp_locale->weekday_initial),
                    // set the date format to match the WP general date settings
                    'dateFormat'        => $this->dateFormatTojQueryUIDatePickerFormat(get_option('date_format')),
                    // get the start of week from WP general setting
                    'firstDay'          => get_option('start_of_week'),
                    // is Right to left language? default is false
                    'isRTL'             => is_rtl(),
                );

    wp_localize_script('datepicker', 'dateData', $aryArgs);


                $dt_req = 'no';

                $dt_req = $this->dateMandatory($dt_req, $form_data);
                $label = isset($form_data[ 'date_field_label' ]) ? $form_data[ 'date_field_label' ] : __('Date', 'quoteup');

                $custname = array_merge(
                    $custname,
                    array(
                    array(
                        'id' => 'txtdate',
                        'class' => 'wdm-modal_text date-field',
                        'type' => 'text',
                        'placeholder' => $label,
                        'required' => $dt_req,
                        'required_message' => __('Please Enter Date', 'quoteup'),
                        'include_in_admin_mail' => 'yes',
                        'include_in_customer_mail' => 'yes',
                        'label' => $label,
                        'value' => '',
                    ),
                    )
                );
            }
        }
        $custname = array_merge(
            $custname,
            array(
            array(
                'id' => 'txtsubject',
                'class' => 'wdm-modal_text',
                'type' => 'text',
                'placeholder' => __('Subject', 'quoteup'),
                'required' => 'no',
                'required_message' => __('Please Enter Subject', 'quoteup'),
                'validation' => '',
                'validation_message' => __('Please Enter Valid Subject', 'quoteup'),
                'include_in_admin_mail' => 'yes',
                'include_in_customer_mail' => 'no',
                'label' => __('Subject', 'quoteup'),
                'value' => '',
            ),
            array(
                'id' => 'txtmsg',
                'class' => 'wdm-modal_textarea',
                'type' => 'textarea',
                'placeholder' => __('Message', 'quoteup'),
                'required' => 'yes',
                'required_message' => __('Please Enter Message', 'quoteup'),
                'validation' => '',    //  ([\w\W]{15,500}) validation for 15 to 500 characters if needed in future
                'validation_message' => __('Message length must be between 15 to 500 characters', 'quoteup'),
                'include_in_admin_mail' => 'yes',
                'include_in_customer_mail' => 'yes',
                'label' => __('Message', 'quoteup'),
                'value' => '',
            ),
            )
        );

        foreach ($custname as $single_custname) {
            $single_custname = apply_filters('pep_fields_'.$single_custname[ 'id' ], $single_custname);

            if (isset($single_custname[ 'id' ])) {
                $single_custname = apply_filters('quoteup_fields_'.$single_custname[ 'id' ], $single_custname);
            }

            if (!empty($single_custname)) {
                if (isset($single_custname[ 'id' ])) {
                    $this->fields[] = $single_custname;
                } else {
                    $this->fields = array_merge($this->fields, $this->addFieldsRecursively($single_custname));

                    unset($this->temp_fields);
                }
            }
        }
        //$this->fields = apply_filters ('quoteup_fields_array', $custname);
    }

    /**
     * Format array for the datepicker
     *
     * WordPress stores the locale information in an array with a alphanumeric index, and
     * the datepicker wants a numerical index. This function replaces the index with a number
     */
    public function stripArrayIndices($ArrayToStrip)
    {
        foreach ($ArrayToStrip as $objArrayItem) {
            $NewArray[] =  $objArrayItem;
        }
     
        return( $NewArray );
    }

    /** 
     * Convert a date format to a jQuery UI DatePicker format  
     * 
     * @param string $dateFormat a date format 
     * @return string 
     */ 
    function dateFormatTojQueryUIDatePickerFormat($dateFormat) { 

        $chars = array( 
            // Day
            'd' => 'dd', 'j' => 'd', 'l' => 'DD', 'D' => 'D',
            // Month 
            'm' => 'mm', 'n' => 'm', 'F' => 'MM', 'M' => 'M', 
            // Year 
            'Y' => 'yy', 'y' => 'y', 
        ); 

        return strtr((string)$dateFormat, $chars); 
    } 

    public function phoneMandatory($ph_req, $form_data)
    {
        if (isset($form_data[ 'make_phone_mandatory' ])) {
            $phone_mandate = $form_data[ 'make_phone_mandatory' ];
            if ($phone_mandate == 1) {
                $ph_req = 'yes';
            }
        }
        return $ph_req;

    }

    public function dateMandatory($dt_req, $form_data)
    {
        if (isset($form_data[ 'make_date_mandatory' ])) {
            $phone_mandate = $form_data[ 'make_date_mandatory' ];
            if ($phone_mandate == 1) {
                $dt_req = 'yes';
            }
        }
        return $dt_req;

    }

    //get custom fields array
    public function getCustomFields()
    {
        return $this->fields;
    }

    public function addFieldsRecursively($single_custname)
    {
        foreach ($single_custname as $single_array) {
            if (is_array($single_array)) {
                if (isset($single_array[ 'id' ])) {
                    $this->temp_fields[] = $single_array;
                } else {
                    $this->addFieldsRecursively($single_array);
                }
            } else {
                return $this->temp_fields;
            }
        }

        return $this->temp_fields;
    }

    public function custnameID($val)
    {
        $email = '';
        if ($val[ 'id' ] == 'custname' && $val[ 'include_in_admin_mail' ] == 'yes') {
            $email = "
           <tr >
            <th style='width:25%;text-align:left'>".__('Customer Name', 'quoteup')." </th>
                <td style='width:75%'>: ".stripslashes($_POST[ $val[ 'id' ] ]).'</td>
           </tr>';
        }
        return $email;
    }

    public function forEachFieldAdminEmail($val)
    {
        $email = '';
        $email .= $this->custnameID($val);
        
        if ($val[ 'id' ] == 'txtemail' && $val[ 'include_in_admin_mail' ] == 'yes') {
            $email .= "
           <tr >
            <th style='width:25%;text-align:left'>".__('Customer Email', 'quoteup')." </th>
                <td style='width:75%'>: ".stripslashes($_POST[ $val[ 'id' ] ]).'</td>
           </tr>';
        }
        if ($val[ 'id' ] == 'txtphone' && $val[ 'include_in_admin_mail' ] == 'yes') {
            $email .= "
           <tr >
            <th style='width:25%;text-align:left'>".__('Telephone', 'quoteup')." </th>
                <td style='width:75%'>: ".stripslashes($_POST[ $val[ 'id' ] ]).'</td>
           </tr>';
        }
        if ($val[ 'id' ] == 'txtmsg' && $val[ 'include_in_admin_mail' ] == 'yes') {
            $email .= "
           <tr >
            <th style='width:25%;text-align:left'>".__('Message', 'quoteup')." </th>
                <td style='width:75%'>: ".stripslashes($_POST[ $val[ 'id' ] ]).'</td>
           </tr>';
        }
        if ($val[ 'id' ] == 'txtdate' && $val[ 'include_in_admin_mail' ] == 'yes') {
            $email .= "
           <tr >
            <th style='width:25%;text-align:left'>".__($val[ 'label' ], 'quoteup')." </th>
                <td style='width:75%'>: ".stripslashes($_POST[ $val[ 'id' ] ]).'</td>
           </tr>';
        }

        if ($val[ 'id' ] != 'custname' && $val[ 'id' ] != 'txtemail' && $val[ 'id' ] != 'txtphone' && $val[ 'id' ] != 'txtsubject' && $val[ 'id' ] != 'txtmsg' && $val[ 'id' ] != 'txtdate') {
            if ($val[ 'include_in_admin_mail' ] == 'yes') {
                $email .= "
           <tr >
            <th style='width:25%;text-align:left'>".__($val[ 'label' ], 'quoteup')."</th>
            <td style='width:75%'>: ".stripslashes($_POST[ $val[ 'id' ] ]).'</td>
           </tr>';
            }
        }

        return $email;
    }

    public function addCustomFieldsAdminEmail($email_content)
    {
        $email = '';
        foreach ($this->fields as $key => $v) {
            $email .= $this->forEachFieldAdminEmail($v);
            unset($key);
        }

        return $email_content . $email;
    }

    // fetching meta fields header for data table
    public function quoteupCustomFieldsHeader()
    {
        global $wpdb;
        $tbl = $wpdb->prefix.'enquiry_meta';
        $header = '';
        $sql = 'SELECT distinct meta_key FROM '.$tbl;
        $results = $wpdb->get_results($sql);
        if (count($results) > 0) {
            foreach ($results as $key => $v) {
                $this->meta_key[] = $v->meta_key;
                $header .= apply_filters('pep_meta_key_header_in_table', "<th class='td_norm'>".$v->meta_key.'</th>', $v->meta_key);
                $header = apply_filters('quoteup_meta_key_header_in_table', $header, $v->meta_key);
                unset($key);
            }
        }
        echo $header;
    }

    public function mpeCustomFieldDashboard($enquiry_id)
    {
        global $wpdb;
        $tbl = $wpdb->prefix.'enquiry_meta';
        $custom_field_data = '';
        $sql = "SELECT meta_key,meta_value FROM {$tbl} WHERE enquiry_id='$enquiry_id'";
        $results = $wpdb->get_results($sql);
        if (count($results) > 0) {
            foreach ($results as $key => $v) {
                $this->meta_key[] = $v->meta_key;
                $meta_key_name = apply_filters('pep_meta_key_header_in_table', $v->meta_key);
                $meta_key_name = apply_filters('quoteup_meta_key_header_in_table', $meta_key_name);
                $custom_field_data .= "<div class='wdm-user-custom-info'>";
                $custom_field_data .= "<input type='text' value='" . $v->meta_value ."' class='wdm-input-custom-info wdm-input' disabled required>";
                $custom_field_data .= '<label placeholder="' . $meta_key_name . '" alt="' . $meta_key_name .'"></label></div>';
                unset($key);
            }
        }

        echo $custom_field_data;
    }
    /*
 * Find a value associated with meta key of particular enquiry
 *
 * @param int $enquiry_id ID of enquiry
 * @param string $meta_key Meta Key whose value to be found
 * @return mixed If value is found, it is returned. Else NULL is returned.
 */

    public static function quoteupGetCustomFieldData($enquiry_id, $meta_key)
    {
        global $wpdb;
        $tbl = $wpdb->prefix.'enquiry_meta';
        $result = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$tbl} WHERE meta_key LIKE %s AND enquiry_id = %d", $meta_key, $enquiry_id));

        return $result;
    }

    // fetching meta fields data for data table
    public function quoteupCustomFieldsData($enquiryID)
    {
        global $wpdb;
        $tbl = $wpdb->prefix.'enquiry_meta';
        $data = '';

        //echo "<pre>";print_r($this->meta_key);echo "</pre>";exit;
        $term = $this->meta_key;
        //echo "<pre>";print_r($term);echo "</pre>";exit;
        $temp = '';
        if (count($term) > 0) {
            foreach ($term as $key => $v) {
                if ($key == 0) {
                    $temp_array[] = $v;
                    $temp = 'SELECT MAX(IF(meta_key = %s, meta_value, NULL)) as %s';
                    $temp_array[] = $v;
                } else {
                    $temp_array[] = $v;
                    $temp .= ',MAX(IF(meta_key = %s, meta_value, NULL)) as %s';
                    $temp_array[] = $v;
                }
            }
            $temp .= " FROM {$tbl} WHERE enquiry_id = %d";
            $temp_array[] = $enquiryID;

            $result = $wpdb->get_results($wpdb->prepare($temp, $temp_array));
            if (isset($result[ 0 ])) {
                foreach ($result[ 0 ] as $key => $v) {
                    $current_meta_key = $key;
                    $data .= apply_filters('pep_meta_key_data_in_table', "<td class='enq_td td_norm'>".((isset($v)) ? $v : '').'</td>', $current_meta_key);
                    $data = apply_filters('quoteup_meta_key_data_in_table', $data, $current_meta_key);
                }
            }
        }
        echo $data;
    }

    // deleting meta fields data
    public function deleteCustomFields($enquiryID)
    {
        global $wpdb;
        $tbl = $wpdb->prefix.'enquiry_meta';
        $query = 'DELETE FROM '.$tbl." WHERE enquiry_id='".$enquiryID."'";
        $wpdb->query($query);
    }

	public function addCustomFieldsCustomerEmail( $msg ) {
        $email = $msg;
        //echo "<pre>";print_r($this->fields);echo "</pre>";exit;
        foreach ($this->fields as $key => $v) {
            if ($v[ 'include_in_customer_mail' ] == 'yes') {
                $email .= "
		   <tr >
			<th style='width:25%;text-align:left'>".__($v[ 'label' ], 'quoteup')." </th>
			    <td style='width:75%'>: ".stripslashes($_POST[ $v[ 'id' ] ]).'</td>
		   </tr>';
            }
            unset($key);
        }

        return $email;
    }
}

new QuoteUpAddCustomField();
