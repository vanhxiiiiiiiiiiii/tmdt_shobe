<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $delivered = OrderStatus::Delivered->value;

        DB::statement("CREATE OR REPLACE VIEW v_top_products AS
            SELECT
                oi.product_id,
                oi.product_code,
                oi.product_name,
                SUM(oi.quantity) AS quantity_sold,
                SUM(oi.line_total) AS revenue
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE o.status_id = {$delivered}
            GROUP BY oi.product_id, oi.product_code, oi.product_name");

        DB::statement("CREATE OR REPLACE VIEW v_seller_revenue AS
            SELECT
                o.shop_id,
                s.seller_code,
                s.name AS shop_name,
                COUNT(DISTINCT o.id) AS successful_orders,
                SUM(o.payment_total) AS gross_revenue,
                SUM(o.commission_amount) AS platform_commission
            FROM orders o
            INNER JOIN shops s ON s.id = o.shop_id
            WHERE o.status_id = {$delivered}
            GROUP BY o.shop_id, s.seller_code, s.name");

        DB::statement("CREATE OR REPLACE VIEW v_monthly_revenue AS
            SELECT
                DATE_FORMAT(o.created_at, '%Y-%m-01') AS revenue_month,
                COUNT(DISTINCT o.id) AS successful_orders,
                SUM(o.payment_total) AS gross_revenue,
                SUM(o.commission_amount) AS platform_revenue
            FROM orders o
            WHERE o.status_id = {$delivered}
            GROUP BY DATE_FORMAT(o.created_at, '%Y-%m-01')");

        DB::unprepared("CREATE PROCEDURE sp_monthly_revenue(IN report_year INT)
            BEGIN
                SELECT *
                FROM v_monthly_revenue
                WHERE YEAR(revenue_month) = report_year
                ORDER BY revenue_month;
            END");

        DB::unprepared("CREATE PROCEDURE sp_customer_spending(
                IN customer_id BIGINT,
                IN date_from DATETIME,
                IN date_to DATETIME
            )
            BEGIN
                SELECT
                    u.id,
                    u.customer_code,
                    u.email,
                    COUNT(DISTINCT o.id) AS order_count,
                    COALESCE(SUM(o.payment_total), 0) AS total_spent
                FROM users u
                LEFT JOIN orders o
                    ON o.user_id = u.id
                    AND o.status_id = {$delivered}
                    AND o.created_at BETWEEN date_from AND date_to
                WHERE u.id = customer_id
                GROUP BY u.id, u.customer_code, u.email;
            END");
    }

    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_customer_spending');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_monthly_revenue');
        DB::statement('DROP VIEW IF EXISTS v_monthly_revenue');
        DB::statement('DROP VIEW IF EXISTS v_seller_revenue');
        DB::statement('DROP VIEW IF EXISTS v_top_products');
    }
};
