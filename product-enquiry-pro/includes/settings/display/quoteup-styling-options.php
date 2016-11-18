<?php

namespace Includes\Settings;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function stylingOptionsSection($form_data)
{
    $css_opt = "";
    if (isset($form_data[ 'button_CSS' ])) {
        $css_opt = $form_data[ 'button_CSS' ];
    }
    ?>
	<fieldset>
		<?php echo '<legend>' . __('Styling Options ', 'quoteup') . '</legend>'; ?>
		<div class='fd'>
			<div class='left_div'>
				<label >
					<?php _e(' Add Custom CSS ', 'quoteup') ?>
				</label>
			</div>
			<div class='right_div'>
				<?php
                $user_custom_css = '';
                if (isset($form_data[ 'user_custom_css' ])) {
                    $user_custom_css = $form_data[ 'user_custom_css' ];
                }
                ?>
				<textarea name="wdm_form_data[user_custom_css]" rows="10" cols="30"><?php echo $user_custom_css ?></textarea>
			</div>
		</div>
		<div class='fd'>
			<div class='left_div'>
				<label >
					<?php _e(' Custom Styling ', 'quoteup') ?>
				</label>
			</div>
			<div class='right_div'>
				<input type="radio" class="wdm_wpi_input wdm_wpi_checkbox" name="wdm_form_data[button_CSS]" value="theme_css"  id="theme_css" <?php if ($css_opt == 'theme_css' || $css_opt == '') {
                        ?> checked <?php
}
                        ?> />
                        <?php echo '<em>' . __('  Use Activated Theme CSS ', 'quoteup') . '</em>';
                        ?><br>

				<input type="radio" class="wdm_wpi_input wdm_wpi_checkbox" name="wdm_form_data[button_CSS]" value="manual_css"  id="manual_css" <?php if ($css_opt == 'manual_css') {
                            ?> checked <?php
}
                        ?>/>

				<?php echo '<em>' . __(' Manually specify color settings', 'quoteup') . '</em>';
                ?>
			</div>
			<div class="clear"></div>
		</div>
	</fieldset>  

	<div name="Other_Settings" id="Other_Settings"

            <?php if ($css_opt == 'theme_css' || $css_opt == '') {
                ?> style="display:none"  <?php
}
            ?>>
                <?php
                otherSettingsFieldset($form_data);
                ?>
	</div>
	<?php
}

/**
 * This function is used for manually specified display settings.
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function otherSettingsFieldset($form_data)
{
    ?>
	<fieldset >
		<?php echo '<legend>' . __('Specify Color Settings ', 'quoteup') . '</legend>';
        ?>
		<p style="margin-top: -0.1%;margin-bottom: 2%;"><?php _e('Button color settings will be applied to Enquiry or Quote Request button and \'Send\' button on Enquiry and Quote Request Form.', 'quoteup') ?></p>
		<?php
        gradientStart($form_data);
        gradientEnd($form_data);
        buttonTextColor($form_data);
        buttonBorderColor($form_data);
        dialogBackgroundColor($form_data);
        productNameColor($form_data);
        dialogTextColor($form_data);
        ?>
	</fieldset>
	<?php
}

/**
 * Button background color gradient start
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function gradientStart($form_data)
{
    ?>
	<div class="fd">
		<div class='left_div'>
			<label for="color">
				<?php _e('  Button Background Color: Gradient Start ', 'quoteup') ?>

			</label>
		</div>
		<div class='right_div'>

			<input type="text" value="<?php echo empty($form_data[ 'start_color' ]) ? '#ebe9eb' : $form_data[ 'start_color' ];
                ?>" class="wdm-button-color-field" data-default-color="#ebe9eb" name="wdm_form_data[start_color]"/>   
		</div>
		<div class="clear"></div>
	</div>
	<?php
}

/**
 * Button background color gradient End
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function gradientEnd($form_data)
{
    ?>
	<div class="fd">
		<div class='left_div'>
			<label for="color">
	<?php _e('  Button Background Color: Gradient End ', 'quoteup') ?>

			</label>
		</div>
		<div class='right_div'>

			<input type="text" value="<?php echo empty($form_data[ 'end_color' ]) ? '#ebe9eb' : $form_data[ 'end_color' ];
    ?>" class="wdm-button-color-field" data-default-color="#ebe9eb" name="wdm_form_data[end_color]"/>   

		</div>
		<div class="clear"></div>
	</div>
	<?php
}

/**
 * Button Text color
 * @param  [array] $form_data [Settings stored previously in database]
 * @return [type]            [description]
 */
function buttonTextColor($form_data)
{
    ?>
	<div class="fd">
		<div class='left_div'>
			<label for="color">
	<?php _e(' Button Text Color ', 'quoteup') ?>
			</label>
		</div>
		<div class='right_div'>
			<input type="text" id="button_text_color" name="wdm_form_data[button_text_color]" value="<?php echo empty($form_data[ 'button_text_color' ]) ? '#515151' : $form_data[ 'button_text_color' ];
    ?>" data-default-color="#515151"/>

		</div>
		<div class="clear"></div>
	</div>
	<?php
}

/**
 * Button border color
 * @param  [type] $form_data [description]
 * @return [type]            [description]
 */
function buttonBorderColor($form_data)
{
    ?>
	<div class="fd">
		<div class='left_div'>
			<label for="color">
	<?php _e(' Button Border Color ', 'quoteup') ?>

			</label>
		</div>
		<div class='right_div'>
			<input type="text" id="button_border_color" name="wdm_form_data[button_border_color]" value="<?php echo empty($form_data[ 'button_border_color' ]) ? '#ebe9eb' : $form_data[ 'button_border_color' ];
    ?>" data-default-color="#ebe9eb"/>      
		</div>
		<div class="clear"></div>
	</div>
                    <?php
}

               /**
                * Dialog Background color
                * @param  [array] $form_data [Settings stored previously in database]
                * @return [type]            [description]
                */
function dialogBackgroundColor($form_data)
{
    ?>
	<div class="fd" >
<div class='left_div'>
<label for="color">
	<?php _e(' Dialog Background Color ', 'quoteup') ?>

			</label>
		</div>
		<div class='right_div'>

			<input type="text" id="dialog_color" name="wdm_form_data[dialog_color]" value="<?php echo empty($form_data[ 'dialog_color' ]) ? '#ffffff' : $form_data[ 'dialog_color' ];
    ?>" data-default-color="#ffffff"/>
</div>
<div class="clear"></div>
	</div>
    <?php
}

               /**
                * Product Name color
                * @param  [array] $form_data [Settings stored previously in database]
                * @return [type]            [description]
                */
function productNameColor($form_data)
{
    ?>
	<div class="fd">
<div class='left_div'>
<label for="color">
	<?php _e('  Product Name Color ', 'quoteup') ?>

			</label>        
		</div>
		<div class='right_div'>

			<input type="text" id="dialog_product_color" name="wdm_form_data[dialog_product_color]" value="<?php echo empty($form_data[ 'dialog_product_color' ]) ? '#999' : $form_data[ 'dialog_product_color' ];
    ?>" data-default-color="#999" />        
<div id="product_text_picker">          
</div>
</div>
<div class="clear"></div>
	</div>
    <?php
}

               /**
                * Dialog Text color
                * @param  [array] $form_data [Settings stored previously in database]
                * @return [type]            [description]
                */
function dialogTextColor($form_data)
{
    ?>
	<div class="fd">
<div class='left_div'>
<label for="color">
	<?php _e('  Dialog Text Color ', 'quoteup') ?>

			</label>
		</div>
		<div class='right_div'>
			<input type="text" id="dialog_text_color" name="wdm_form_data[dialog_text_color]" value="<?php echo empty($form_data[ 'dialog_text_color' ]) ? '#333' : $form_data[ 'dialog_text_color' ];
    ?>" data-default-color="#333"/>     
<div id="dialog_text_picker">
</div>
</div>
<div class="clear"></div>
	</div>
    <?php
}

               stylingOptionsSection($form_data);
               