
<?php while ($token_index < $token_total) {

    $current = $filtered_data_parts[$token_index];

    // Branch A: current token is label-like (NOT numeric)
    // We try to "form a pair" starting at this label.
    if (!check_value($current)) { // label-like token first

        /* Pair-forming pattern 1: Label | Label | Number
    Example tokens: ['Ammoniacal-N','Nitrogen','11.0']
    - token_index=> first label we KEEP
    - token_index + 1 => second label we DROP (extra label)
    - token_index + 2 => numeric value we KEEP
    Why check token_index+2?
    Because we are specifically recognizing the shape: label, label, number.
    We guard with ($token_index + 2) < $token_total to avoid out-of-bounds.
        */
        if (
            ($token_index + 2) < $token_total &&
            !check_value($filtered_data_parts[$token_index + 1]) && // next token is label-like
            check_value($filtered_data_parts[$token_index + 2]) // third token is numeric-like
        ) {

            $label = $current;
            $value = $filtered_data_parts[$token_index + 2];

            $data_rows[] = [$label, $value];
            $fixed_lines[] = $label . '|' . $value;

            $nutrient_autofixed_lines[] = [
                'line' => $i + 1,
                'rule' => 'drop_middle_label',
                'original' => $line,
                'fixed' => $label . '|' . $value,
            ];

            // We consumed 3 tokens: [label][label][number]
            $token_index += 3;
            continue;
        } // close Pair-forming pattern 1 (Label|Label|Number)


        /* Pair-forming pattern 2: Label | Number | %
        Example tokens: ['Nitric-N','11','%']
        - token_index => label
        - token_index + 1 => numeric value
        - token_index + 2 => the literal '%' sign as its own token
        We merge '11' + '%' => '11%' so the table value becomes a clean single cell.
        Again, guard with ($token_index + 2) < $token_total because we read +1 and +2.
            */
        if (
            ($token_index + 2) < $token_total &&
            check_value($filtered_data_parts[$token_index + 1]) &&
            $filtered_data_parts[$token_index + 2] === '%'
        ) {

            $label = $current;
            $value = $filtered_data_parts[$token_index + 1] . '%';

            $data_rows[] = [$label, $value];
            $fixed_lines[] = $label . '|' . $value;

            $nutrient_autofixed_lines[] = [
                'line' => $i + 1,
                'rule' => 'merge_value_and_unit_percent',
                'original' => $line,
                'fixed' => $label . '|' . $value,
            ];

            // We consumed 3 tokens: [label][number]['%']
            $token_index += 3;
            continue;
        } // close Pair-forming pattern 2 (Label|Number|%)


        /* Pair-forming pattern 3 (normal): Label | Number
            Example tokens: ['Nitrogen','20%'] or ['Nitrogen','20']
            - token_index => label
            - token_index + 1 => numeric-like value
            This is the "main valid pattern", not an error case.
            We still must check the next token is numeric-like before pairing.
            */
        if (
            ($token_index + 1) < $token_total &&
            check_value($filtered_data_parts[$token_index + 1])
        ) {

            $label = $current;
            $value = $filtered_data_parts[$token_index + 1];

            $data_rows[] = [$label, $value];
            $fixed_lines[] = $label . '|' . $value;

            // We consumed 2 tokens: [label][number]
            $token_index += 2;
            continue;
        } // close Pair-forming pattern 3 (Label|Number)


        /* Option B: leftover label (no pair could be formed)
                Why this prevents an infinite loop:
                - If we do NOT advance $token_index here, we will keep reading the same $current
                token forever, because none of the patterns matched.
                Option B meaning:
                - Keep any valid pairs we already found in this line (do NOT throw everything away)
                - BUT report the leftover token(s) to staff as invalid=> overall state becomes "partial"
                */
        $parse_errors[] = 'label_without_value:' . $current;
        $token_index += 1;
        continue;
    } // close label-like branch (if !check_value($current))


    // Branch B: current token is numeric-like (VALUE first)
    // We try to form a pair in swapped order: Number | Label => Label|Number
    /* Pair-forming pattern 4 (swap pairing): Number | Label
                Example tokens: ['11.0','Nitric-N'] or after consuming 'N|20', tokens might be ['19','C']
                - token_index => numeric value
                - token_index + 1 => label-like name
                We form: Label|Number
                */
    if (
        ($token_index + 1) < $token_total &&
        !check_value($filtered_data_parts[$token_index + 1]) // next token is label-like
    ) {

        $label = $filtered_data_parts[$token_index + 1];
        $value = $current;

        // uses your helper; should not change anything here, but keeps behavior consistent
        swap_data($label, $value);

        $data_rows[] = [$label, $value];
        $fixed_lines[] = $label . '|' . $value;

        $nutrient_autofixed_lines[] = [
            'line' => $i + 1,
            'rule' => 'swap_number_label_pair',
            'original' => $line,
            'fixed' => $label . '|' . $value,
        ];

        // We consumed 2 tokens: [number][label]
        $token_index += 2;
        continue;
    } // close Pair-forming pattern 4 (Number|Label)


    /* Option B: leftover number
                    Same reason: must advance token_index to avoid infinite loop.
                    Example: ['20','19'] => '20' is numeric, next is numeric => cannot form Number|Label pair.
                    */
    $parse_errors[] = 'value_without_label:' . $current;
    $token_index += 1;
    continue;
} // close while loop



$recom_table_rows = [
    [
        'crop' => "Vegetables\n(GH/Open Field)",
        'fertigation' => "3—6 L/ha",
        'foliar' => "100-200 ml",
        'time' => "Throughout the growing cycle",
    ],
    [
        'crop' => "Nurseries",
        'fertigation' => "2 L/ha",
        'foliar' => "100-150 ml",
        'time' => "Throughout the growing cycle",
    ],
];
?>