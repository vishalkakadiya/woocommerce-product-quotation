<?php

/**
 * Create the section for Product Quotation
 * in products sestion in Woocommerce settings
 **/
function product_quotation_section( $sections ) {
	
	$sections['product_quotation'] = __( 'Product Quotation', 'woocommerce-product-quotation' );
	return $sections;
	
}
add_filter( 'woocommerce_get_sections_products', 'product_quotation_section', 99 );



/**
 * Add settings in Product Quotation section(Check above)
 */
function product_quotation_settings( $settings, $current_section ) {

	/**
	 * Check the current section is what we want
	 **/

	if ( $current_section == 'product_quotation' ) {

		$settings_quotation = array();

		// Title
		$settings_quotation[] = array( 
			'receive_quotation' => __( 'Product Quotation', 'woocommerce-product-quotation' ), 
			'type' => 'title', 
			'desc' => __( 'Setting for products quotation', 'woocommerce-product-quotation' ), 
			'id' => 'product_quotation' 
		);

		// Checkbox
		$settings_quotation[] = array(
			'name'     => __( 'Enable product quotation button', 'woocommerce-product-quotation' ),
			'desc_tip' => __( 'Checked to enable quotation button on cart page.', 'woocommerce-product-quotation' ), 
			'id'       => 'product_quotation_button',
			'type'     => 'checkbox',
			'css'      => 'min-width:300px;'
		);

		$user_details = get_users( array( 
			'fields' => array(
				'ID',
				'display_name'
			) 
		) );
		$users = array();
		if ( $user_details ) {
			foreach ( $user_details as $user_detail ) { 
				$users[ $user_detail->ID ] = $user_detail->display_name;
			}
		}

		// Add second text field option
		$settings_quotation[] = array(
			'name'     => __( 'Select User', 'woocommerce-product-quotation' ),
			'desc_tip' => __( 'Multi-select users for whose quotation button is disabled', 'woocommerce-product-quotation' ),
			'id'       => 'product_quotation_disable_users', 
			'type'    => 'multiselect',
			'options' => $users,
			'css'      => 'height: 150px; width:50%;',
			'desc'     => __( 'Multi-select users for whose quotation button is disabled', 'woocommerce-product-quotation' ),
		);

		$settings_quotation[] = array(
			'name'     => __( 'From Email', 'woocommerce-product-quotation' ),
			'desc'     => __( 'Add email address which is used as "from email address" when mail send to customer for quotation request.', 'woocommerce-product-quotation' ),
			'id'       => 'product_quotation_from_email',
			'css'      => 'width:100%;',
			'type'     => 'text',
			'desc_tip' => __( 'Add email address which is used as "from email address" when mail send to customer for quotation request.', 'woocommerce-product-quotation' )
		);

		$settings_quotation[] = array(
			'name'     => __( 'Email addresses', 'woocommerce-product-quotation' ),
			'desc'     => __( 'Add comma seprated email addresses, to which quotation emails will send when any customer request for quotation.', 'woocommerce-product-quotation' ),
			'id'       => 'product_quotation_send_to',
			'css'      => 'width:100%;',
			'type'     => 'text',
			'desc_tip'     => __( 'Add comma seprated email addresses, to which quotation emails will send when any customer request for quotation.', 'woocommerce-product-quotation' ),
		);

		$settings_quotation[] = array(
			'name'     => __( 'Limit quotation request per User in single day', 'woocommerce-product-quotation' ),
			'desc'     => __( 'Add number, which is used to limit request for quotation for a day per user.', 'woocommerce-product-quotation' ),
			'id'       => 'product_quotation_per_day_per_user',
			'css'      => 'width:100%;',
			'type'     => 'text',
			'desc_tip' => __( 'Add number, which is used to limit request for quotation for a day per user.', 'woocommerce-product-quotation' ),
		);
		
		$settings_quotation[] = array( 'type' => 'sectionend', 'id' => 'product_quotation' );

		return $settings_quotation;

	/**
	 * If not, return the standard settings
	 **/

	} else {

		return $settings;

	}

}
add_filter( 'woocommerce_get_settings_products', 'product_quotation_settings', 10, 2 );
