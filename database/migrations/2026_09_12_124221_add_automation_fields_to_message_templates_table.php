<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('key')->nullable()->unique()->after('channel');
            $table->string('category', 30)->default('transactional')->index()->after('key');
            $table->string('trigger')->nullable()->after('category');
            $table->string('preview_text')->nullable()->after('subject');
            $table->string('button_text')->nullable()->after('body');
            $table->string('button_url')->nullable()->after('button_text');
            $table->boolean('is_active')->default(false)->index()->after('is_important');
        });

        $groups = [
            'transactional' => ['Registration', 'Verification', 'Password Reset', 'Order Confirmation', 'Payment', 'Invoice', 'Order Status', 'Shipping', 'Delivery', 'Cancellation', 'Return', 'Refund'],
            'marketing' => ['Offers', 'Discounts', 'New Products', 'Flash Sale', 'Coupon', 'Product Recommendation', 'Back in Stock'],
            'automation' => ['Abandoned Cart', 'Wishlist Reminder', 'Review Request', 'Reorder Reminder', 'Birthday/Anniversary', 'Customer Win-back Email'],
        ];

        foreach ($groups as $category => $names) {
            foreach ($names as $name) {
                $key = Str::slug($name);
                $orderTemplate = in_array($key, ['order-confirmation', 'payment', 'invoice', 'order-status', 'shipping', 'delivery', 'cancellation', 'return', 'refund'], true);
                DB::table('message_templates')->insertOrIgnore([
                    'channel' => 'email', 'key' => $key, 'category' => $category,
                    'trigger' => $category === 'transactional' ? $key : null, 'name' => $name,
                    'subject' => $orderTemplate ? $name.' — {{order_number}}' : $name.' from {{store_name}}',
                    'preview_text' => $orderTemplate ? 'An update about order {{order_number}}.' : $name.' notification from {{store_name}}.',
                    'body' => $orderTemplate ? '<h2>Hello {{customer_name}},</h2><p>Here is an update about order <strong>{{order_number}}</strong>.</p><p>Status: {{order_status}}</p><p>Total: {{order_total}}</p>' : '<h2>Hello {{customer_name}},</h2><p>'.$name.' from {{store_name}}.</p>',
                    'button_text' => $orderTemplate ? 'View order' : null, 'button_url' => $orderTemplate ? '{{tracking_url}}' : null,
                    'is_important' => $category === 'transactional', 'is_active' => $category === 'transactional',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropIndex(['category']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['key', 'category', 'trigger', 'preview_text', 'button_text', 'button_url', 'is_active']);
        });
    }
};
