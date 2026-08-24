<?php
function bb_active_projects_count_shortcode() {
    global $wpdb;

    $cache_key = 'bb_active_projects_count';
    $count     = get_transient($cache_key);

    if (false === $count) {
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COUNT(DISTINCT products.ID)
                FROM {$wpdb->posts} AS products

                INNER JOIN {$wpdb->postmeta} AS goal
                    ON products.ID = goal.post_id
                    AND goal.meta_key = %s

                LEFT JOIN {$wpdb->postmeta} AS raised
                    ON products.ID = raised.post_id
                    AND raised.meta_key = %s

                WHERE products.post_type = 'product'
                    AND products.post_status = 'publish'
                    AND CAST(goal.meta_value AS DECIMAL(20,2)) > 0
                    AND CAST(goal.meta_value AS DECIMAL(20,2)) >
                        CAST(
                            COALESCE(raised.meta_value, 0)
                            AS DECIMAL(20,2)
                        )
                ",
                'goal_amount',
                'raised_amount'
            )
        );

        $count = absint($count);

        // کش نتیجه به مدت ۱۰ دقیقه
        set_transient($cache_key,$count,10 * MINUTE_IN_SECONDS);
    }

    if (function_exists('bb_to_persian_num')) {
        return esc_html(
            bb_to_persian_num($count)
        );
    }

    return esc_html($count);
}

add_shortcode('bb_active_projects_count','bb_active_projects_count_shortcode');

function bb_clear_active_projects_count_cache($meta_id,$object_id,$meta_key) {
    if ('goal_amount' === $meta_key || 'raised_amount' === $meta_key) {
        delete_transient('bb_active_projects_count');
    }
}

add_action('added_post_meta','bb_clear_active_projects_count_cache',10,3);

add_action('updated_post_meta','bb_clear_active_projects_count_cache',10,3);

add_action('deleted_post_meta','bb_clear_active_projects_count_cache',10,3);