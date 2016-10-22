<?php

/**
 * Check if WooCommerce is active
 */
//if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	if ( ! class_exists( 'WC_Product_Quotation_Email' ) ) {
		
		/**
		 * Localisation
		 **/
		load_plugin_textdomain( 'wc_product_quotation_email', false, dirname( plugin_basename( __FILE__ ) ) . '/' );


		class WC_Product_Quotation_Email {

			public function __construct() {

				// Hook to add Quotation Receive button on cart page
				add_action( 'woocommerce_product_quotation_button', array( $this, 'woocommerce_button_quotation' ), 99 );
			}

			/*
			 * Mail send function
			 */
			public function send_email( $to, $subject, $message, $headers ) {
				return wp_mail( $to, $subject, $message, $headers );
			}


			/*
			 * Admin email preparation
			 */
			public function set_email_headers_for_admin( $headers, $current_user, $admin_email ) {
				$headers .= 'From: '.$current_user->user_firstname .' '.$current_user->user_lastname . ' <' . $current_user->user_email . '>' . "\r\n";
				$email_template = $this->quotation_admin_email_template( $current_user );
				$send_enable_users = get_option( 'product_quotation_send_to' );
				$administrative_users = explode( ",", $send_enable_users );
				if ( ! empty ( $administrative_users ) ) {
					foreach ( $administrative_users as $administrative_user ) {
						$this->send_email( $administrative_user, 'New Product Quotation Request Received', $email_template, $headers );
					}
				}
			}

			/*
			 * User email preparation
			 */
			public function set_email_headers_for_user( $headers, $admin_email, $current_user ) {
				$from_email = get_option( 'product_quotation_from_email' );
				if ( $from_email ) {
					$headers .= 'From: PTC Team <' . $from_email . '>' . "\r\n";
					$email_template = 'Dear ' . $current_user->user_firstname .' '. $current_user->user_lastname . ',<br /><br />';
					$email_template .= $this->quotation_user_email_template( $current_user );
					$response = $this->send_email( $current_user->user_email, 'Thank you for requesting a quotation from PTC Computers', $email_template, $headers );

					if ( $response ) {
						$last_quotation_number = get_user_meta( $current_user->ID, 'product_quotation_number', true );
						$last_quotation_date = get_user_meta ( $current_user->ID, 'product_quotation_date', true );
						$today_date = date( 'Y-m-d' );
						if ( $last_quotation_date ) { 
							$time = strtotime( $last_quotation_date );
							$old_date = date( 'Y-m-d', $time );
							if ( $old_date == $today_date ) {
								$new_quotation_number = $last_quotation_number + 1;
							} else {
								$new_quotation_number = 1;
								update_user_meta( $current_user->ID, 'product_quotation_date', $today_date );
							}
						} else {
							$new_quotation_number = 1;
							update_user_meta( $current_user->ID, 'product_quotation_date', $today_date );
						}
						update_user_meta( $current_user->ID, 'product_quotation_number', $new_quotation_number );

						echo '<div id="quotation-message" class="woocommerce-message">' . __( 'Thanks. Your product list is sent to PTC team for quotation, They will contact as soon as possible.', 'woocommerce_button_quotation' ) . '</div>';
					} else {
						echo '<div id="quotation-message" class="woocommerce-error">' . __( 'Due to some error mail is not reach to admin, please try again later.', 'woocommerce_button_quotation' ) . '</div>';
					}
				}
			}

			public function send_product_quotation() {
				if ( isset ( $_POST['zl_product_quotation'] ) ) {
					$headers = "MIME-Version: 1.0" . "\r\n";
					$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

					// More headers
					$admin_email = get_option( 'admin_email' );
					$current_user = wp_get_current_user();

					$this->set_email_headers_for_admin( $headers, $current_user, $admin_email );
					$this->set_email_headers_for_user( $headers, $admin_email, $current_user );
				}
			}

			/**
			 * 
			 * Button to receive quotation which 
			 * products are added right now in the cart.
			 *
			*/
			public function woocommerce_button_quotation() { 

				if ( is_user_logged_in() ) {
					$disable_users = get_option( 'product_quotation_disable_users' );

					$current_user = wp_get_current_user();
					if ( ! in_array( $current_user->ID, $disable_users ) ) {


						if ( isset ( $_POST['zl_product_quotation'] ) ) { 
							$last_quotation_date = get_user_meta ( $current_user->ID, 'product_quotation_date', true );
							if ( $last_quotation_date ) { 
								$time = strtotime( $last_quotation_date );
								$old_date = date( 'Y-m-d', $time );
								$today_date = date( 'Y-m-d' );
								if ( $old_date == $today_date ) {
									$last_quotation_number = get_user_meta ( $current_user->ID, 'product_quotation_number', true );
									$product_quotation_limit = get_option( 'product_quotation_per_day_per_user' );
									if ( $last_quotation_number < $product_quotation_limit ) {
										$this->send_product_quotation();
									} else {
										echo '<div id="quotation-message" class="woocommerce-error">' . __( 'Sorry, quotation requests are limited '. $product_quotation_limit .' times in a day per user., you can try tomorrow.', 'woocommerce_button_quotation' ) . '</div>';
									}
								} else {
									$this->send_product_quotation();
								}
							} else {
								$this->send_product_quotation();
							}
						} else { ?>
							<input type="submit" name="zl_product_quotation" id="quotation-button" class="checkout-button button alt wc-forward" value="<?php _e( 'Request for Quotation', 'woocommerce-product-quotation' ); ?>" /><?php
						}
					}
				}
			}
			
			/*
			 *  Admin Email template to sending product quotation
			 */
			public function quotation_admin_email_template( $current_user ) {
				ob_start();

				echo wc_get_template( 'emails/email-header.php', array( 'email_heading' => '<div style="text-align: center;">New request for quotation is received</div>' ) );?>
						<p style="margin:0 0 16px; text-align: center;">
						   <?php _e( 'You received new quotation request and the details are shown below for your reference:', 'woocommerce-product-quotation' ); ?>
						</p>
						<hr />
						<p><?php _e( '<strong>Customer Name:</strong> ' . $current_user->user_firstname .' '.$current_user->user_lastname, 'woocommerce-product-quotation' ) ?></p>
						<p><?php _e( '<strong>Customer Number:</strong> ' . get_user_meta( $current_user->ID, 'billing_phone', true ), 'woocommerce-product-quotation' );?></p>
						<p><?php _e( '<strong>Email:</strong> ' . $current_user->user_email, 'woocommerce-product-quotation' ) ?></p>
						<br />
						<table cellspacing="0" cellpadding="6" border="1" style="width:100%;border:1px solid #eee">
						   <thead>
							  <tr>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'No', 'woocommerce-product-quotation' ); ?></th>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'SKU', 'woocommerce-product-quotation' ); ?></th>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'Product Description', 'woocommerce-product-quotation' ); ?></th>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'Quantity', 'woocommerce-product-quotation' ); ?></th>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'Unit Price', 'woocommerce-product-quotation' ); ?></th>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'Total', 'woocommerce-product-quotation' ); ?></th>
							  </tr>
						   </thead>
						   <tbody>
								<?php
									global $woocommerce;
									$serial_no = 1;
									$items = $woocommerce->cart->get_cart();
									foreach ( $items as $item => $values ) { 
										$sku = get_post_meta( $values['product_id'], '_sku', true );
//										$sale_price = get_post_meta( $values['product_id'], '_sale_price', true );
//										if ( ! empty ( $sale_price ) )
//											$price = $sale_price;
//										else 
//											$price = wc_price( $values['data']->price );
										$price = wc_price( $values['line_total'] / $values['quantity'] );

										echo '<tr>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;padding:12px">' . $serial_no . '<br /><small></small></td>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;padding:12px">' . $sku . '<br /><small></small></td>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;padding:12px">' . $values['data']->post->post_title . '<br /><small></small></td>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;padding:12px">' . $values['quantity'] . '</td>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;padding:12px">' . $price . '</td>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;padding:12px"><span>' . wc_price( $values['line_total'] ) . '</span></td>';
										echo '</tr>';
										$serial_no++;
									}
								?>
						   </tbody>
						   <tfoot>
								<tr>
									<th style="text-align:left;border:1px solid #eee;padding:12px"></th>
									<th style="text-align:left;border:1px solid #eee;padding:12px"></th>
									<th style="text-align:left;border:1px solid #eee;padding:12px" colspan="3" scope="row"><?php _e( 'Total:', 'woocommerce-product-quotation' ); ?></th>
									<td style="text-align:left;border:1px solid #eee;padding:12px"><span><?php echo $woocommerce->cart->get_total();?></span></td>
								</tr>
						   </tfoot>
						</table><?php

				echo wc_get_template( 'emails/email-footer.php' );

				$email_template = ob_get_contents();
				ob_end_clean();
				return $email_template;
			}

			/*
			 *  User email template to sending product quotation
			 */
			public function quotation_user_email_template( $current_user ) {
				ob_start();

				echo wc_get_template( 'emails/email-header.php', array( 'email_heading' => 'Thank you for requesting a quotation from PTC Computers' ) );?>
						<p style="margin:0 0 16px">
						   <?php _e( 'Our team is currently reviewing your order and will get back to you as soon as possible. For your reference, you have asked for a quotation for the following order:', 'woocommerce-product-quotation' ); ?>
						</p>
						<br />
						<table cellspacing="0" cellpadding="6" border="1" style="width:100%;border:1px solid #eee">
						   <thead>
							  <tr>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'No', 'woocommerce-product-quotation' ); ?></th>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'SKU', 'woocommerce-product-quotation' ); ?></th>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'Product Description', 'woocommerce-product-quotation' ); ?></th>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'Quantity', 'woocommerce-product-quotation' ); ?></th>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'Unit Price', 'woocommerce-product-quotation' ); ?></th>
								<th style="text-align:left;border:1px solid #eee;padding:12px" scope="col"><?php _e( 'Total', 'woocommerce-product-quotation' ); ?></th>
							  </tr>
						   </thead>
						   <tbody>
								<?php
									global $woocommerce;
									$serial_no = 1;
									$items = $woocommerce->cart->get_cart();
									foreach ( $items as $item => $values ) { 
										$sku = get_post_meta( $values['product_id'], '_sku', true );
										$price = wc_price( $values['line_total'] / $values['quantity'] );

										echo '<tr>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;padding:12px">' . $serial_no . '<br /><small></small></td>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;padding:12px">' . $sku . '<br /><small></small></td>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;padding:12px">' . $values['data']->post->post_title . '<br /><small></small></td>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;padding:12px">' . $values['quantity'] . '</td>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;padding:12px">' . $price . '</td>';
											echo '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;padding:12px"><span>' . wc_price( $values['line_total'] ) . '</span></td>';
										echo '</tr>';
										$serial_no++;
									}
								?>
						   </tbody>
						   <tfoot>
								<tr>
									<th style="text-align:left;border:1px solid #eee;padding:12px"></th>
									<th style="text-align:left;border:1px solid #eee;padding:12px"></th>
									<th style="text-align:left;border:1px solid #eee;padding:12px" colspan="3" scope="row"><?php _e( 'Total:', 'woocommerce-product-quotation' ); ?></th>
									<td style="text-align:left;border:1px solid #eee;padding:12px"><span><?php echo $woocommerce->cart->get_total();?></span></td>
								</tr>
						   </tfoot>
						</table><?php

				echo wc_get_template( 'emails/email-footer.php' );

				$email_template = ob_get_contents();
				ob_end_clean();
				return $email_template;
			}
		}

		// finally instantiate our plugin class and add it to the set of globals
		new WC_Product_Quotation_Email();
	}


//}
