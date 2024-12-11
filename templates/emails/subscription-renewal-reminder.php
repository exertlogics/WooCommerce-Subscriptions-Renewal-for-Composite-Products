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
          <!-- Header -->
          <tr>
            <td style="padding: 40px 30px; background-color: #1DB9F2; text-align: center;">
              <h1 style="color: #ffffff; font-size: 28px; font-weight: bold; margin: 0;">Subscription Renewal Reminder</h1>
            </td>
          </tr>
          <!-- Content -->
          <tr>
            <td style="padding: 40px 30px; text-align: left; font-size: 16px; line-height: 1.5;">
              <p style="margin: 0 0 20px 0;">Dear <?php echo esc_html( $customer_name ); ?>,</p>
              <p style="margin: 0 0 20px 0;">We hope you're enjoying your exclusive Tennis Head experience. Here's a summary of your subscription details:</p>

              <!-- Subscription Details Table -->
              <table role="presentation" style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; color: #333;">
                <thead>
                  <tr>
                    <th style="padding: 10px; background-color: #1DB9F2; color: #ffffff; text-align: left; font-weight: bold; border: 1px solid #dddddd;">Product</th>
                    <th style="padding: 10px; background-color: #1DB9F2; color: #ffffff; text-align: left; font-weight: bold; border: 1px solid #dddddd;">Start Date</th>
                    <th style="padding: 10px; background-color: #1DB9F2; color: #ffffff; text-align: left; font-weight: bold; border: 1px solid #dddddd;">End Date</th>
                    <th style="padding: 10px; background-color: #1DB9F2; color: #ffffff; text-align: left; font-weight: bold; border: 1px solid #dddddd;">Price</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ( $subscription->get_items() as $item_id => $item ) :
                      $product = $item->get_product();
                      if ( ! $product ) continue;
                      if ( !$composite_product_link ) {
                        $composite_product_link = $product->get_permalink();
                      }
                      ?>
                      <tr>
                        <td style="padding: 10px; border: 1px solid #dddddd;"><?php echo esc_html( $item->get_name() ); ?></td>
                        <td style="padding: 10px; border: 1px solid #dddddd;"><?php echo esc_html( date( 'Y-m-d', strtotime( $start_date ) ) ); ?></td>
                        <td style="padding: 10px; border: 1px solid #dddddd;"><?php echo esc_html( date( 'Y-m-d', strtotime( $end_date ) ) ); ?></td>
                        <td style="padding: 10px; border: 1px solid #dddddd; text-align: right;"><?php echo wc_price( $item->get_total() ); ?></td>
                      </tr>
                      <?php if ( $product->is_type( 'composite' ) ) :
                          $composite_items = $item->get_meta( '_composite_children' );
                          if ( $composite_items ) :
                              foreach ( $composite_items as $child_item_id ) :
                                  $child_item = $subscription->get_item( $child_item_id );
                                  if ( ! $child_item ) continue;
                                  ?>
                                  <tr>
                                    <td colspan="4" style="padding: 5px 10px; background-color: #f9f9f9; border: 1px solid #dddddd;">
                                      <?php echo esc_html( $child_item->get_name() ); ?> - <?php echo wc_price( $child_item->get_total() ); ?>
                                    </td>
                                  </tr>
                              <?php endforeach;
                          endif;
                      endif;
                  endforeach; ?>
                </tbody>
              </table>

              <p style="margin: 0 0 20px 0;">Renew now to ensure uninterrupted access to your subscription!</p>
              <a href="<?php echo esc_url( $composite_product_link ); ?>"
              style="display: inline-block; padding: 12px 20px; background-color: #1DB9F2; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold;">Renew Now</a>
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
