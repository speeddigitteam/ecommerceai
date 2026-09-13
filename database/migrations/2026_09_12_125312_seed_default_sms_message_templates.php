<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $groups = [
            'transactional' => [
                'Registration' => 'Welcome {{customer_name}}! Your {{store_name}} account has been created.',
                'Verification' => '{{store_name}} verification code/link: {{verification_url}}',
                'Password Reset' => 'Reset your {{store_name}} password: {{reset_url}}',
                'Order Confirmation' => 'Thanks {{customer_name}}. Order {{order_number}} confirmed. Total {{order_total}}. Track: {{tracking_url}}',
                'Payment' => 'Payment received for order {{order_number}}. Amount: {{order_total}}.',
                'Invoice' => 'Invoice for order {{order_number}}: {{invoice_url}}',
                'Order Status' => 'Order {{order_number}} status is now {{order_status}}. Track: {{tracking_url}}',
                'Shipping' => 'Order {{order_number}} has shipped. Track: {{tracking_url}}',
                'Delivery' => 'Order {{order_number}} has been delivered. Thank you for shopping with {{store_name}}.',
                'Cancellation' => 'Order {{order_number}} has been cancelled. Contact {{store_phone}} for help.',
                'Return' => 'Return update for order {{order_number}}: {{order_status}}.',
                'Refund' => 'Refund update for order {{order_number}}: {{refund_amount}}.',
            ],
            'marketing' => [
                'Offers' => 'New offer from {{store_name}}: {{offer_text}} {{campaign_url}}',
                'Discounts' => 'Save {{discount_amount}} at {{store_name}}. Shop: {{campaign_url}}',
                'New Products' => 'New at {{store_name}}: {{product_name}}. View: {{product_url}}',
                'Flash Sale' => '{{store_name}} flash sale is live! Shop now: {{campaign_url}}',
                'Coupon' => 'Use coupon {{coupon_code}} at {{store_name}}. {{campaign_url}}',
                'Product Recommendation' => '{{customer_name}}, you may like {{product_name}}: {{product_url}}',
                'Back in Stock' => '{{product_name}} is back in stock at {{store_name}}: {{product_url}}',
            ],
            'automation' => [
                'Abandoned Cart' => '{{customer_name}}, items are waiting in your cart: {{cart_url}}',
                'Wishlist Reminder' => 'A wishlist item is waiting for you at {{store_name}}: {{wishlist_url}}',
                'Review Request' => 'How was order {{order_number}}? Share your review: {{review_url}}',
                'Reorder Reminder' => 'Ready to reorder {{product_name}}? {{product_url}}',
                'Birthday/Anniversary' => 'Best wishes from {{store_name}}! Enjoy your special offer: {{campaign_url}}',
                'Customer Win-back Email' => 'We miss you at {{store_name}}. Come back for a special offer: {{campaign_url}}',
            ],
        ];

        foreach ($groups as $category => $templates) {
            foreach ($templates as $name => $body) {
                $trigger = Str::slug($name);
                DB::table('message_templates')->insertOrIgnore([
                    'channel' => 'sms',
                    'key' => 'sms-'.$trigger,
                    'category' => $category,
                    'trigger' => $category === 'transactional' ? $trigger : null,
                    'name' => $name,
                    'subject' => null,
                    'preview_text' => null,
                    'body' => $body,
                    'button_text' => null,
                    'button_url' => null,
                    'is_important' => $category === 'transactional',
                    'is_active' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('message_templates')->where('channel', 'sms')->where('key', 'like', 'sms-%')->delete();
    }
};
