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
        'priority'     => 'high',
        'show_in_rest' =>  true
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
 * Register the recommendations_table meta key for the REST API.
 * This allows our Node.js script to actually SAVE the data.
 */
add_action('init', function () {
    // 1. Register the Table (Must be type 'array' to accept your JSON)
    register_post_meta('eurofert_product', 'reco_rows', array(
        'show_in_rest' => array(
            'schema' => array(
                'type'  => 'array',
                'items' => array('type' => 'object'),
            ),
        ),
        'single'       => true,
        'type'         => 'array', // Changed from 'string' to 'array'
    ));
});
