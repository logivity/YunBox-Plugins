<?php

namespace Includes\Settings;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function formOptionsSection($form_data)
{
    ?>
	<fieldset>

		<?php
        echo '<legend>' . __('Form Options', 'quoteup') . '</legend>';

        enquiryButtonLabel($form_data);
        viewCartButtonLabel($form_data);
        replaceEnquiry($form_data);
        enquiryButtonLocation($form_data);
        enquiryAsLink($form_data);
        displayWisdmlabs($form_data);
        sendMeCopy($form_data);
        dateField($form_data);
        dateFieldMandatory($form_data);
        dateFieldLabel($form_data);
        telephoneNumber($form_data);
        telephoneNumberMandatory($form_data);
        countryTelephoneNumber($form_data);
        ?>
	</fieldset>
	<?php
}

/**
 * This is used to show Enquiry button label on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function enquiryButtonLabel( $form_data ) {
    global $quoteupSettings;
    ?>

	<div class="fd">
		<div class='left_div'>
			<label for="custom_label">
				<?php _e(' Enquiry Button Label ', 'quoteup') ?>
			</label>
		</div>
		<div class='right_div'>
			<?php
            $helptip = __('Add custom label for Enquiry or Quote button.', 'quoteup');
			echo \quoteupHelpTip( $helptip, true );
            ?>
			<input type="text" class="wdm_wpi_input wdm_wpi_text" name="wdm_form_data[custom_label]"
				   value="<?php echo empty($form_data[ 'custom_label' ]) ? _e('Make an Enquiry', 'quoteup') : $form_data[ 'custom_label' ];
            ?>" id="custom_label"  />
                    <?php //echo '<em>'.__(' This is the text that is shown on the button ', 'quoteup').'</em>';     ?>
		</div>
        <!-- <div class="clear"></div> -->
	</div>


	<?php
}

/**
 * This is used to show cart button label on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function viewCartButtonLabel($form_data)
{
    global $quoteupSettings;
    if(!isset($form_data[ 'cart_custom_label' ]) || empty($form_data[ 'cart_custom_label' ])){
        if (isset($form_data[ 'enable_disable_quote' ]) && $form_data[ 'enable_disable_quote' ] ==0) {
        $form_data[ 'cart_custom_label' ] = __('View Enquiry & Quote Cart', 'quoteup');
    } else {
        $form_data[ 'cart_custom_label' ] = __('View Enquiry Cart', 'quoteup');
    }
    }
    ?>

    <div class="fd">
        <div class='left_div'>
            <label for="cart_custom_label">
                <?php _e(' View Cart Button Label ', 'quoteup') ?>
            </label>
        </div>
        <div class='right_div'>
            <?php
            $helptip = __('Add custom label for Enquiry or Quote cart button.', 'quoteup');
            echo \quoteupHelpTip( $helptip, true );
            ?>
            <input type="text" class="wdm_wpi_input wdm_wpi_text" name="wdm_form_data[cart_custom_label]"
                   value="<?php echo $form_data[ 'cart_custom_label' ];
            ?>" id="cart_custom_label"  />
                    <?php //echo '<em>'.__(' This is the text that is shown on the button ', 'quoteup').'</em>';     ?>
        </div>
        <!-- <div class="clear"></div> -->
    </div>


    <?php
}


/**
 * This is used to replace 'enquiry' words on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function replaceEnquiry($form_data)
{
    global $quoteupSettings;
    ?>

    <div class="fd">
        <div class='left_div'>
            <label for="replace_enquiry">
                <?php _e(' Alternate word for Enquiry ', 'quoteup') ?>
            </label>
        </div>
        <div class='right_div'>
            <?php
            $helptip = __('Alternate word for Enquiry.', 'quoteup');
            echo \quoteupHelpTip($helptip, true);
            ?>
            <input type="text" class="wdm_wpi_input wdm_wpi_text" name="wdm_form_data[replace_enquiry]"
                   value="<?php echo empty($form_data[ 'replace_enquiry' ]) ? 'Enquiry' : $form_data[ 'replace_enquiry' ];
            ?>" id="replace_enquiry"  />
        </div>
        <div class="clear"></div>
    </div>


    <?php
}



/**
 * This is used to show Enquiry button location on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function enquiryButtonLocation($form_data)
{
    ?>

	<div class="fd">
		<div class='left_div'>
			<label>
				<?php _e(' Button Location', 'quoteup') ?>

			</label>

		</div>
		<div class='right_div'>
			<?php
            if (isset($form_data[ 'pos_radio' ])) {
                $pos = $form_data[ 'pos_radio' ];
            } else {
                $pos = 'show_after_summary';
            }
            ?>

			<input type="radio" class="wdm_wpi_input wdm_wpi_checkbox input-without-tip" name="wdm_form_data[pos_radio]"
				   value="show_after_summary" <?php if ($pos == 'show_after_summary') {
                ?> checked <?php
}
                    ?> id="show_after_summary"   />
                    <?php echo '<em>' . __(' After single product summary ', 'quoteup') . '</em>';
                    ?>

			<br />


			<input type="radio" class="wdm_wpi_input wdm_wpi_checkbox input-without-tip" name="wdm_form_data[pos_radio]" value="show_at_page_end" <?php if ($pos == 'show_at_page_end') {
                        ?> checked <?php
}
                    ?> id="show_at_page_end" />

			<?php echo '<em>' . __(' At the end of single product page ', 'quoteup') . '</em>';
            ?>
		</div>
		<div class="clear"></div>
	</div>

	<?php
}

/**
 * This is used to show checkbox for show enquiry button as a link on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function enquiryAsLink($form_data)
{
    $showButtonAsLink = isset($form_data[ 'show_button_as_link' ]) ? $form_data[ 'show_button_as_link' ] : 0;
    ?>

	<div class="fd">
		<div class='left_div'>
			<label for="link">
				<?php _e(' Display Enquiry Button As A Link ', 'quoteup') ?>
			</label>
		</div>
		<div class='right_div'>
			<input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox input-without-tip" value="1" <?php checked(1, $showButtonAsLink); ?> id="show_button_as_link" />
			<input type="hidden" name="wdm_form_data[show_button_as_link]" value="<?php echo isset($form_data[ 'show_button_as_link' ]) && $form_data[ 'show_button_as_link' ] == 1 ? $form_data[ 'show_button_as_link' ] : 0 ?>" />

		</div>
		<div class="clear"></div>
	</div>

	<?php
}

/**
 * This is used to show checkbox for show footer on form
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function displayWisdmlabs($form_data)
{
    $displayWisdmlabs = isset($form_data[ 'show_powered_by_link' ]) ? $form_data[ 'show_powered_by_link' ] : 0;
    //Don't show option to Display Powered by WisdmLabs if not checked till now
    if ($displayWisdmlabs != 1) {
        return;
    }
    ?>

	<div class="fd">
		<div class='left_div'>
			<label for="link">
				<?php _e(" Display 'Powered by WisdmLabs' ", 'quoteup') ?>
			</label>
		</div>
		<div class='right_div'>
			<input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox input-without-tip" value="1" <?php checked(1, $displayWisdmlabs); ?> id="show_powered_by_link" />
			<input type="hidden" name="wdm_form_data[show_powered_by_link]" value="<?php echo isset($form_data[ 'show_powered_by_link' ]) && $form_data[ 'show_powered_by_link' ] == 1 ? $form_data[ 'show_powered_by_link' ] : 0 ?>" />

		</div>
		<div class="clear"></div>
	</div>

	<?php
}

/**
 * This is used to show checkbox for send me a copy on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function sendMeCopy($form_data)
{
    global $quoteupSettings;
    ?>

	<div class="fd">
		<div class='left_div'>
			<label for="enable_send_mail_copy">
				<?php _e(" Display 'Send me a copy' ", 'quoteup') ?>
			</label>

		</div>
		<div class='right_div'>
			<?php
            $helptip = __('This will display \'Send me a copy\' checkbox on Enquiry or Quote request form.', 'quoteup');
			echo \quoteupHelpTip( $helptip, true );
            ?>
			<input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox" value="1" <?php checked(1, isset($form_data[ 'enable_send_mail_copy' ]) ? $form_data[ 'enable_send_mail_copy' ] : 0); ?> id="enable_send_mail_copy" />
			<input type="hidden" name="wdm_form_data[enable_send_mail_copy]" value="<?php echo isset($form_data[ 'enable_send_mail_copy' ]) && $form_data[ 'enable_send_mail_copy' ] == 1 ? $form_data[ 'enable_send_mail_copy' ] : 0 ?>" />

		</div>
		<div class="clear"></div>
	</div>

	<?php
}

/**
 * This is used to show checkbox for 'Date Field' on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function dateField($form_data)
{
    global $quoteupSettings;
    ?>

    <div class="fd">
        <div class='left_div'>
            <label for="enable_date_field">
                <?php _e(" Display 'Date Field' ", 'quoteup') ?>
            </label>

        </div>
        <div class='right_div'>
            <?php
            $helptip = __('This will display \'Date Field\' on Enquiry or Quote request form.', 'quoteup');
            echo \quoteupHelpTip( $helptip, true );
            ?>
            <input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox" value="1" <?php checked(1, isset($form_data[ 'enable_date_field' ]) ? $form_data[ 'enable_date_field' ] : 0); ?> id="enable_date_field" />
            <input type="hidden" name="wdm_form_data[enable_date_field]" value="<?php echo isset($form_data[ 'enable_date_field' ]) && $form_data[ 'enable_date_field' ] == 1 ? $form_data[ 'enable_date_field' ] : 0 ?>" />

        </div>
        <div class="clear"></div>
    </div>

    <?php
}

/**
 * This is used to show Date Field label on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function dateFieldLabel($form_data)
{
    global $quoteupSettings;
    ?>

    <div class="fd toggle-date">
        <div class='left_div'>
            <label for="date_field_label">
                <?php _e(' Date Field Label ', 'quoteup') ?>
            </label>
        </div>
        <div class='right_div'>
            <?php
            $helptip = __('Add custom label for Date Field.', 'quoteup');
            echo \quoteupHelpTip( $helptip, true );
            ?>
            <input type="text" class="wdm_wpi_input wdm_wpi_text" name="wdm_form_data[date_field_label]"
                   value="<?php echo empty($form_data[ 'date_field_label' ]) ? _e('Date', 'quoteup') : $form_data[ 'date_field_label' ];
            ?>" id="date_field_label"  />
        </div>
        <div class="clear"></div>
    </div>


    <?php
}


/**
 * This is used to show checkbox for date field mandatory on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function dateFieldMandatory($form_data)
{
    $display = '';
    // if (! isset($form_data[ 'enable_telephone_no_txtbox' ])) {
    //     $display = "style='display:none'";
    // } elseif (isset($form_data[ 'enable_telephone_no_txtbox' ]) && $form_data[ 'enable_telephone_no_txtbox' ] == 0) {
    //     $display = "style='display:none'";
    // }
    ?>
    <div class="fd toggle-date" <?php echo $display; ?>>
        <div class='left_div'>
            <label for="make_date_mandatory">
                <?php _e(' Make Date Field Mandatory', 'quoteup') ?>
            </label>
        </div>
        <div class='right_div'>
            <input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox input-without-tip" value="1" <?php checked(1, isset($form_data[ 'make_date_mandatory' ]) ? $form_data[ 'make_date_mandatory' ] : 0); ?> id="make_date_mandatory" />
            <input type="hidden" name="wdm_form_data[make_date_mandatory]" value="<?php echo isset($form_data[ 'make_date_mandatory' ]) && $form_data[ 'make_date_mandatory' ] == 1 ? $form_data[ 'make_date_mandatory' ] : 0 ?>" />
        </div>
        <div class="clear"></div>
    </div>

    <?php
}



/**
 * This is used to show checkbox for Telephone number on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function telephoneNumber($form_data)
{
    global $quoteupSettings;
    ?>

	<div class="fd">
		<div class='left_div'>
			<label for="enable_telephone_no_txtbox">
				<?php _e(' Display Telephone Number Field', 'quoteup') ?>
			</label>
		</div>
		<div class='right_div'>
			<?php
            $helptip = __('Display Telephone number field on Enquiry and Quote Request form.', 'quoteup');
			echo \quoteupHelpTip( $helptip, true );
            ?>
			<input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox" value="1" <?php checked(1, isset($form_data[ 'enable_telephone_no_txtbox' ]) ? $form_data[ 'enable_telephone_no_txtbox' ] : 0); ?> id="enable_telephone_no_txtbox" />
			<input type="hidden" name="wdm_form_data[enable_telephone_no_txtbox]" value="<?php echo isset($form_data[ 'enable_telephone_no_txtbox' ]) && $form_data[ 'enable_telephone_no_txtbox' ] == 1 ? $form_data[ 'enable_telephone_no_txtbox' ] : 0 ?>" />
		</div>
		<div class="clear"></div>
	</div>

	<?php
}

/**
 * This is used to show checkbox for telephone number mandatory on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function telephoneNumberMandatory($form_data)
{
    $display = '';
    if (! isset($form_data[ 'enable_telephone_no_txtbox' ])) {
        $display = "style='display:none'";
    } elseif (isset($form_data[ 'enable_telephone_no_txtbox' ]) && $form_data[ 'enable_telephone_no_txtbox' ] == 0) {
        $display = "style='display:none'";
    }
    ?>
	<div class="fd toggle" <?php echo $display; ?>>
		<div class='left_div'>
			<label for="make_phone_mandatory">
				<?php _e(' Make Telephone Number Field Mandatory', 'quoteup') ?>
			</label>
		</div>
		<div class='right_div'>
			<input type="checkbox" class="wdm_wpi_input wdm_wpi_checkbox input-without-tip" value="1" <?php checked(1, isset($form_data[ 'make_phone_mandatory' ]) ? $form_data[ 'make_phone_mandatory' ] : 0); ?> id="make_phone_mandatory" />
			<input type="hidden" name="wdm_form_data[make_phone_mandatory]" value="<?php echo isset($form_data[ 'make_phone_mandatory' ]) && $form_data[ 'make_phone_mandatory' ] == 1 ? $form_data[ 'make_phone_mandatory' ] : 0 ?>" />
		</div>
		<div class="clear"></div>
	</div>

	<?php
}

/**
 * This is used to show dropdown for country on settings page
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function countryTelephoneNumber($form_data)
{
    global $quoteupSettings;
    $display = '';
    if (! isset($form_data[ 'enable_telephone_no_txtbox' ])) {
        $display = "style='display:none'";
    }
    ?>

	<!--Code added for validation of telephone by country starts here-->
	<div class="fd toggle" <?php echo $display; ?> >
		<div class='left_div'>
			<label for="phone_country">
				<?php _e(' Select Country for Telephone Number Validation', 'quoteup') ?>
			</label>
		</div>
		<div class='right_div'>
			<?php
            $helptip = __('To accept any number, do not select a country.', 'quoteup');
			echo \quoteupHelpTip( $helptip, true );

            if (isset($form_data[ 'phone_country' ])) {
                $country = $form_data[ 'phone_country' ];
            } else {
                $country = '';
            }
            ?>

			<select name='wdm_form_data[phone_country]'>
				<option value=''> -<?php echo __('Select one', 'quoteup') ?> -</option>
				<?php echo displayCountries($country);
                ?>
			</select>
		</div>
		<div class="clear"></div>
	</div>
	<!--Code added for validation of telephone by country ends here-->

	<?php
}

/**
 * List of countries for phine number validation
 * @param  [type] $country [Last selected coountry in settings]
 * @return [type]          [description]
 */
function displayCountries($country)
{
    $arr = array(
        'AF' => 'Afghanistan',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua And Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia And Herzegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'BN' => 'Brunei',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Columbia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => "Cote D'Ivorie (Ivory Coast)",
        'HR' => 'Croatia (Hrvatska)',
        'CU' => 'Cuba',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'CD' => 'Democratic Republic Of Congo (Zaire)',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'TP' => 'East Timor',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands (Malvinas)',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'FX' => 'France, Metropolitan',
        'GF' => 'French Guinea',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard And McDonald Islands',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IM' => 'Isle of Man',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Laos',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macau',
        'MK' => 'Macedonia',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar (Burma)',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'AN' => 'Netherlands Antilles',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'KP' => 'North Korea',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'RW' => 'Rwanda',
        'SH' => 'Saint Helena',
        'KN' => 'Saint Kitts And Nevis',
        'LC' => 'Saint Lucia',
        'PM' => 'Saint Pierre And Miquelon',
        'VC' => 'Saint Vincent And The Grenadines',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome And Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovak Republic',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia And South Sandwich Islands',
        'KR' => 'South Korea',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard And Jan Mayen',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syria',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad And Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks And Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'UM' => 'United States Minor Outlying Islands',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VA' => 'Vatican City (Holy See)',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'VG' => 'Virgin Islands (British)',
        'VI' => 'Virgin Islands (US)',
        'WF' => 'Wallis And Futuna Islands',
        'EH' => 'Western Sahara',
        'WS' => 'Western Samoa',
        'YE' => 'Yemen',
        'YU' => 'Yugoslavia',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
    );
    $str = '';
    foreach ($arr as $key => $val) {
        if ($country == $key) {
            $selected = 'selected';
        } else {
            $selected = '';
        }
        $str .= "<option value='$key' $selected>$val</option>";
    }

    return $str;
}

formOptionsSection($form_data);
