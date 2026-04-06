<?php

/**
 * Application Recommendations (CMB2)
 *
 * This file registers a CMB2 metabox on the eurofert_product edit screen.
 * It stores the table rows in post meta under the key: reco_rows
 */


function register_recommendation_table()
{

    // Safety: only run if CMB2 is active
    if (! function_exists('new_cmb2_box')) {
        return; // in case function doesn't exist return here 
    }

    $box = new_cmb2_box(array(
        'id'           => 'recommendations_table',
        'title'        => 'Application Recommendations',
        'object_types' => array('eurofert_product'),
        'context'      => 'normal',
        'priority'     => 'high'
    ));


    $group_id = $box->add_field(array(
        'id' => 'reco_rows',
        'type' => 'group',
        'options' =>    array(
            'group_title' => 'Insert Recommendations',
            'add_button' => 'Add Row',
            'remove_button' => 'Remove Row',
            'sortable'      => true,
            'closed'        => true,
        ),
    ));



    // Column 1: Crop (row label)
    $box->add_group_field($group_id, array(
        'name' => 'Crop',
        'id'   => 'crop',
        'type' => 'textarea_small',
        'desc' => 'Example: Vegetables (GH/Open Field)',
    ));

    // Column 2a: Fertigation (Application Rate)
    $box->add_group_field($group_id, array(
        'name' => 'Fertigation',
        'id'   => 'fertigation',
        'type' => 'textarea_small',
        'desc' => 'Use a new line for GH vs Ha if needed.',
    ));

    // Column 2b: Foliar (Application Rate)
    $box->add_group_field($group_id, array(
        'name' => 'Foliar (ml / 100 L water)',
        'id'   => 'foliar',
        'type' => 'textarea_small',
        'desc' => 'Use a new line for a second value if needed.',
    ));

    // Column 3: Time of Application
    $box->add_group_field($group_id, array(
        'name' => 'Time of Application',
        'id'   => 'time',
        'type' => 'textarea_small',
    ));
}



add_action('cmb2_admin_init', 'register_recommendation_table');

/**
 * Register reco_rows for the REST API.
 *
 * WordPress only persists keys present in the registered meta schema; anything else
 * is ignored with no error, so the post can return 201 while reco_rows never saves.
 * CMB2 stores this group as one serialized array: list of rows, each row an assoc.
 * array with keys crop, fertigation, foliar, time — same shape as your Node payload.
 */
add_action('init', function () {
    register_post_meta('eurofert_product', 'reco_rows', array(
        'show_in_rest' => array(
            'schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'                 => 'object',
                    'additionalProperties' => false,
                    'properties'           => array(
                        'crop'        => array('type' => 'string'),
                        'fertigation' => array('type' => 'string'),
                        'foliar'      => array('type' => 'string'),
                        'time'        => array('type' => 'string'),
                    ),
                ),
            ),
        ),
        'single'        => true,
        'type'          => 'array',
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ));
});

/**
 * Fallback: write reco_rows from the REST request after core meta handling.
 *
 * Ensures rows persist if the theme was not deployed, meta was not registered on
 * the server, or another layer interfered with update_value().
 */
function eurofert_rest_persist_reco_rows($post, $request, $creating)
{
    $meta = $request->get_param('meta');
    if (! is_array($meta) || ! array_key_exists('reco_rows', $meta)) {
        return;
    }

    $raw = $meta['reco_rows'];
    if (! is_array($raw)) {
        return;
    }

    $rows = array();
    foreach ($raw as $row) {
        if (! is_array($row)) {
            continue;
        }
        $rows[] = array(
            'crop'        => isset($row['crop']) ? sanitize_textarea_field((string) $row['crop']) : '',
            'fertigation' => isset($row['fertigation']) ? sanitize_textarea_field((string) $row['fertigation']) : '',
            'foliar'      => isset($row['foliar']) ? sanitize_textarea_field((string) $row['foliar']) : '',
            'time'        => isset($row['time']) ? sanitize_textarea_field((string) $row['time']) : '',
        );
    }

    update_post_meta($post->ID, 'reco_rows', $rows);
}

add_action('rest_after_insert_eurofert_product', 'eurofert_rest_persist_reco_rows', 99, 3);
