<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure the subscription object is available
if ( empty( $subscription ) || ! is_object( $subscription ) ) {
    echo "Invalid subscription.";
    return;
}

$customer_name = $subscription->get_billing_first_name();
$start_date    = $subscription->get_date( 'start' );
$end_date      = $subscription->get_date( 'end' );
$composite_product_link = false;

?>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333333;">
  <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f4f4f4;">
    <tr>
      <td align="center" style="padding: 20px;">
        <table role="presentation" style="width: 600px; border-collapse: collapse; background-color: #ffffff; border: 1px solid #dddddd; margin: 0 auto;">
        <!-- Logo -->
        <tr>
            <td style="padding: 40px 30px; background-color: #1DB9F2; text-align: center;">
            <img src="https://staging.tennishead.net/wp-content/uploads/2021/03/tennis-head-logo-black.png" alt="Tennis Head" style="width: 200px; height: auto;">
            </td>
          </tr>

        <!-- Header -->
          <tr>
            <td style="padding: 20px 0; text-align: center;">
            <h1 style="color: #000000; font-size: 28px; font-weight: bold; margin: 0;">Subscription Renewal Reminder</h1>
            </td>
          </tr>
          <!-- Content -->
          <tr>
            <td style="padding: 0 30px 40px 30px; text-align: left; font-size: 16px; line-height: 1.5;">
              <p style="margin: 0 0 20px 0;">Dear <?php echo esc_html( $customer_name ); ?>,</p>
              <p style="margin: 0 0 20px 0;">We hope you're enjoying your exclusive Tennis Head experience. Here's a summary of your subscription details:</p>

              <!-- Subscription Details Table -->
              <table role="presentation" style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; color: #333;">
                <thead>
                  <tr>
                    <th style="padding: 10px; background-color: #1DB9F2; color: #ffffff; text-align: left; font-weight: bold; border: 1px solid #dddddd;"></th>
                    <th style="padding: 10px; background-color: #1DB9F2; color: #ffffff; text-align: left; font-weight: bold; border: 1px solid #dddddd;">Product</th>
                    <th style="padding: 10px; background-color: #1DB9F2; color: #ffffff; text-align: left; font-weight: bold; border: 1px solid #dddddd;">Price</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $first_row = true;
                  foreach ( $subscription->get_items() as $item_id => $item ) : 
                    $product = $item->get_product();
                    $product_image_url = wp_get_attachment_url( $product->get_image_id() );
                    $is_child_product = $item->get_meta('_is_child_product'); // Assuming you have a meta field to identify child products
                  ?>
                  <tr
                  <?php if ( $first_row ) : ?>
                    style="background-color: #dddddd; font-weight: bold;"
                  <?php endif; ?>
                  >
                    <td style="padding: 10px; border: 1px solid #dddddd;">
                      <img src="<?php echo esc_url( $product_image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" style="width: 50px; height: auto;">
                    </td>
                    <td 
                    <?php if ( !$first_row ) : ?>
                      colspan="2"
                    <?php endif; ?>
                    style="padding: 10px; border: 1px solid #dddddd;"><?php echo esc_html( $product->get_name() ); ?></td>
                    <?php if ( $first_row ) : ?>
                    <td style="padding: 10px; border: 1px solid #dddddd;">
                      <?php echo wc_price( $item->get_total() ); ?>
                    </td>
                    <?php $first_row = false; // else: ?>
                    <!-- <td style="padding: 10px; border: 1px solid #dddddd;"></td> -->
                    <?php endif; ?>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>

              <p style="margin: 0 0 20px 0;">Renew now to ensure uninterrupted access to your subscription!</p>
              <!-- <a href="<?php echo esc_url( $composite_product_link ); ?>" -->
              <a href="<?php echo esc_url( $action_url ); ?>"
              style="display: inline-block; padding: 12px 20px; background-color: #1DB9F2; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold;">Renew Now</a>
              <!-- Add url and text in case if button is not working or wanna try on any other device -->
              <p style="margin: 20px 0 0 0;">If the button above doesn't work, you can also renew your subscription by copy pasting or simply clicking on below link: <br> <a href="<?php echo esc_url( $action_url ); ?>" style="color: #1DB9F2; text-decoration: underline;"><?php echo esc_url( $action_url ); ?></a></p>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="padding: 30px; background-color: #eeeeee; text-align: center; font-size: 14px; color: #666666;">
              <p style="margin: 0;">Thank you for being a valued customer.</p>
              <p style="margin: 10px 0 0 0;">&copy; <?php echo date( 'Y' ); ?> Tennis Head. All rights reserved.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
