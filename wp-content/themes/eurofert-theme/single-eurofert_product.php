    <?php get_header(); // NEW: ensures wp_head() runs so enqueued CSS loads

    //VERSION 1.6 : 2026-02-19 =>  Added recommendation Table
    /*  this is the single product details page 
    
    1) multi pipe validation and stream tokenization 
       Step A1 complete 
       2) application recommendation table setup 
           A- defined fields 
           B- implemented function
        
    */

    //functions definitions 
    function normalize_string($stringPerRow)
    {

      // 1) Normalize different newline formats into "\n"
      $stringPerRow = trim((string) $stringPerRow);
      $stringPerRow = str_replace(["\r\n", "\r"], "\n", $stringPerRow);
      $stringPerRow = str_replace(["–", "—"], "-", $stringPerRow);

      $lines = explode("\n", $stringPerRow);
      $lines = array_map(
        function ($line) {
          $line = trim($line);

          $line = preg_replace('/[ \t]+/', ' ', $line);

          return $line;
        },
        $lines
      );

      $lines = array_values(array_filter($lines, 'strlen'));
      return implode("\n", $lines);
      // normalize newlines + dashes (keep line breaks!)
    }

    while (have_posts()) {
      the_post();

      // 1) Get product category name (taxonomy: fertilizer_category)
      $categoryname = '';

      //2) get categories get_the_terms returns an array of objects => categories 
      $product_categories = get_the_terms(get_the_ID(), 'fertilizer_category');


      if (!empty($product_categories) && !is_wp_error($product_categories)) {
        $categoryname = $product_categories[0]->name;
      }


      // ACF: Product Fields (these MUST be outside category check)
      $formula = function_exists('get_field') ? get_field('formula') : '';
      $subtitle = function_exists('get_field') ? get_field('subtitle') : '';
      $key_benefits = function_exists('get_field') ? get_field('key_benefits') : '';


      // table fields
      $nutrient_table_rows = function_exists('get_field') ? (string) get_field('nutrient_table_rows') : '';
      $nutrient_table_rows = trim($nutrient_table_rows);
      $recom_table_rows = get_post_meta(get_the_ID(), 'reco_rows', true);
      $recom_table_rows = is_array($recom_table_rows) ? $recom_table_rows : []; // verify it an array 

      //  Fetching the data from the nutrient table
      $nutrient_array_rows = [];
      $nutrient_invalid_lines = [];
      $nutrient_autofixed_lines = []; // staff-only reporting auto fixes
      $nutrient_state = 'empty';   // it can be empty | ok | partial | invalid , keep track of the input




      if (!empty($nutrient_table_rows)) {

        //creating an array of lines
        $dataArray = preg_split("/\r\n|\n|\r/", $nutrient_table_rows);
        // loop each line
        foreach ($dataArray as  $i => $line) {
          $line  = trim($line);

          // Skip empty lines (safety)
          if ($line === '')
            continue;

          $verifyChar_count = substr_count($line, '|');
          //verify if the separator char '|' present 
          if ($verifyChar_count  === 0) {
            $nutrient_invalid_lines[] =
              [
                'line' => $i + 1,
                'reason' => 'Missing "|" make sure to separate label and value, 
                for example : Potassium (K)|20 ',
                'original' => $line
              ];
            continue;
          }

          /* NEW START: Rescue Layer in case of false input */
          $data_rows = [];
          $parse_errors = []; // leftovers (Option B)


          if ($verifyChar_count === 1) // verify each line , if they have exactly 1 | 
          {
            // Normal strict case: exactly one pipe
            $data_rows[] = array_map('trim', explode('|', $line, 2));
          } else {

            $data_parts = array_map('trim', explode('|', $line));
            //modify data_parts array, filter each array element to check for empty cells
            $data_parts = array_filter($data_parts, function ($p) {
              return $p !== '';
            });

            $filtered_data_parts = array_values($data_parts);
            $token_total = count($filtered_data_parts);
            $token_index = 0; //position 1 in the array
            $fixed_lines  = []; // what we successfully extracted


            while ($token_index < $token_total) {

              $current = $filtered_data_parts[$token_index]; //store first data


              if (!check_value($current)) //if not a number first cell 

              {
                //  CASE 1: detect pattern ['N','P', '20'] missing value 
                if (
                  ($token_index + 2) < $token_total &&
                  $filtered_data_parts[$token_index + 1] !== '%' &&
                  !check_value($filtered_data_parts[$token_index + 1]) &&
                  check_value($filtered_data_parts[$token_index + 2])
                ) {

                  $single_label = $current; // this label had no numeric value next to it, staff should fix

                  // adjacent-to-number wins:
                  $label = $filtered_data_parts[$token_index + 1];
                  $value = $filtered_data_parts[$token_index + 2];

                  $data_rows[]   = [$label, $value];
                  $fixed_lines[] = $label . '|' . $value;

                  // register auto fix (include the missing label info inside rule so staff sees it)
                  register_auto_fix(
                    $nutrient_autofixed_lines,
                    $i + 1,
                    'Case 1 label missing number => "' . $single_label . '" expected a number after it (kept adjacent pair)',
                    $line,
                    $label . '|' . $value
                  );
                  // consumed 3 tokens: [label_missing][label_kept][number]
                  $token_index += 3;
                  continue;
                } // close Case 1 if (Label|Label|Number)

                // Case 2 : check if % is before number 
                /*  - token_index     => label
                   - token_index + 1 => '%' token
                   - token_index + 2 => numeric value */

                if (
                  ($token_index + 2) < $token_total &&
                  $filtered_data_parts[$token_index + 1] === '%' &&
                  check_value($filtered_data_parts[$token_index + 2])
                ) {

                  $label = $current;
                  $value = $filtered_data_parts[$token_index + 2] . '%';
                  $data_rows[]   = [$label, $value];
                  $fixed_lines[] = $label . '|' . $value;

                  register_auto_fix(
                    $nutrient_autofixed_lines,
                    $i + 1,
                    'Error caught:  Case 2: "%" before number — converted "Label|%|20" to "Label|20%"',
                    $line,
                    $label . '|' . $value
                  );
                  $token_index += 3;
                  continue;
                } //close Case 2 %error case 




                // Case 3 Error case percentage after number Label | Number | %  => merge into Number%
                if ((($token_index + 2) < $token_total &&
                  check_value($filtered_data_parts[$token_index + 1]) &&
                  $filtered_data_parts[$token_index + 2] === '%'
                )) {
                  $label = $current;
                  $value = $filtered_data_parts[$token_index + 1] . '%';
                  $data_rows[]  = [$label, $value];
                  $fixed_lines[] = $label . '|' . $value;


                  //registering auto fix
                  register_auto_fix(
                    $nutrient_autofixed_lines,
                    $i + 1,
                    'Error caught:  % place before number. we fixed it for you, but make sure you check the Table',
                    $line,
                    $label . '|' . $value
                  );

                  $token_index += 3;
                  continue;
                } // close if case 3 

                // forming pair Pattern : Label | Number => normal pair
                if (
                  ($token_index + 1) < $token_total &&
                  check_value($filtered_data_parts[$token_index + 1])
                ) {
                  $label = $current;
                  $value = $filtered_data_parts[$token_index + 1];

                  $data_rows[]   = [$label, $value];
                  $fixed_lines[] = $label . '|' . $value;

                  $token_index += 2;
                  continue;
                } // close if pair pattern Label|Number

                // Option B leftover label => prevents infinite loop by advancing token_index
                $parse_errors[] = 'label_without_value:' . $current;
                $token_index += 1;
                continue;
              } //closed bigger if label first  

              //case 5 Error case :  Number is before label ( Number | label) for example : 6 | N
              if (
                ($token_index + 1) < $token_total &&
                !check_value($filtered_data_parts[$token_index + 1])
              ) {

                $label = $filtered_data_parts[$token_index + 1];
                $value = $current;

                // safe consistency call (should not swap here, but ok)
                swap_data($label, $value);

                $data_rows[]   = [$label, $value];
                $fixed_lines[] = $label . '|' . $value;

                register_auto_fix(
                  $nutrient_autofixed_lines,
                  $i + 1,
                  'Swap pair: number before label => converted "number|label" into "label|number"',
                  $line,
                  $label . '|' . $value
                );

                $token_index += 2;
                continue;
              } // close label 5 (Number|Label)
              $parse_errors[] = 'value_without_label:' . $current;
              $token_index += 1;
              continue;
            } //close while loop
          } //close else statement  Bigger if (!check_value($current))

          if (!empty($parse_errors)) {
            $nutrient_invalid_lines[] = [
              'line' => $i + 1,
              'reason' => 'multi pipe "|" unpaired tokens',
              'original' => $line . ' [' . implode(', ', $parse_errors) . ']',
            ];
          }


          foreach ($data_rows as $dataLineParts) {
            $label = $dataLineParts[0] ?? '';
            $value = $dataLineParts[1] ?? '';

            // SMART RESCUE #1: if the left cell looks numeric and the right does not, swap them.
            // Example: '11.0|Nitric-N' becomes 'Nitric-N|11.0'.
            if (check_value($label) && !check_value($value)) {
              $original_line = $line;

              $tmp = $label; // swap values 
              $label = $value;
              $value = $tmp;

              $nutrient_autofixed_lines[] = [
                'line' => $i + 1,
                'rule' => 'swap_columns_numeric_first',
                'original' => $original_line,
                'fixed' => $label . '|' . $value,
              ];
            }
            if ($label === '' && $value === '') {
              $nutrient_invalid_lines[] =
                ['line' => $i + 1, 'reason' => 'empty_label_and_value', 'original' => $line];
              continue;
            }

            if ($label === '') {
              $nutrient_invalid_lines[] = ['line' => $i + 1, 'reason' => 'empty label detected', 'original' => $line];
              continue;
            }

            if ($value === '') {
              $nutrient_invalid_lines[] = ['line' => $i + 1, 'reason' => 'empty value detected', 'original' => $line];
              continue;
            }


            // Valid row ✅
            $nutrient_array_rows[] = [
              'label' => $label,
              'value' => $value,
            ];
          }  //end foreach pair loop
        } // -------------- END foreach ($dataArray as $i => $line) ----------------

        // Decide state after parsing
        if (!empty($nutrient_array_rows)) {
          $nutrient_state = empty($nutrient_invalid_lines) ? 'ok' : 'partial';
        } else {
          $nutrient_state = 'invalid';
        }
      }

      $is_internal_user = is_user_logged_in() && current_user_can('edit_posts'); ?>

      <main class="content">
        <div class="product-details__wrapper">
          <!-- Navigation Panel (Right Drawer) -->
          <nav class="product-navigation-panel">
            <ul class="nav-panel-list">
              <li>
                <a
                  href="#hero"
                  class="nav-panel-link active"
                  data-section="hero">
                  <span class="nav-link-text">Overview & Key Benefits</span>
                  <span class="menu-dash" aria-hidden="true"></span>
                </a>
              </li>
              <li>
                <a
                  href="#description"
                  class="nav-panel-link"
                  data-section="description"><span class="nav-link-text">Description</span>
                  <span class="menu-dash" aria-hidden="true"></span></a>
              </li>
              <li>
                <a
                  href="#nutrient-declaration"
                  class="nav-panel-link"
                  data-section="nutrient-declaration"><span class="nav-link-text">Declaration</span>
                  <span class="menu-dash" aria-hidden="true"></span></a>
              </li>
              <li>
                <a
                  href="#application-notes"
                  class="nav-panel-link"
                  data-section="application-notes"><span class="nav-link-text">Application Notes</span>
                  <span class="menu-dash" aria-hidden="true"></span></a>
              </li>
              <li>
                <a
                  href="#application-recommendations"
                  class="nav-panel-link"
                  data-section="application-recommendations"><span class="nav-link-text">Application Recommendations</span><span class="menu-dash" aria-hidden="true"></span></a>
              </li>
              <li>
                <a
                  href="#packaging"
                  class="nav-panel-link"
                  data-section="packaging"><span class="nav-link-text">Packaging</span>
                  <span class="menu-dash" aria-hidden="true"></span></a>
              </li>
              <li>
                <a
                  href="#documents"
                  class="nav-panel-link"
                  data-section="documents"><span class="nav-link-text">Documents & Downloads</span>
                  <span class="menu-dash" aria-hidden="true"></span></a>
              </li>
            </ul>
          </nav>


          <section class="overview-section" id="hero">
            <div class="product-details__container">
              <!-- Product Image -->
              <div class="product-details-image__container">
                <?php if (has_post_thumbnail()) {
                  echo get_the_post_thumbnail(
                    get_the_ID(),
                    'productImage_large',
                    array(
                      'id' => 'productImage',
                      'loading'  => 'lazy',
                      'decoding' => 'async'
                    )
                  );
                }
                ?>

              </div>

              <div class="product-details__content ">
                <p class="product-category" id="productCategory___title">
                  <?php
                  //  Escapes the text so it is safe to output in HTML, HTML/script injection
                  echo esc_html($categoryname);
                  ?>
                </p>
                <h1 class="product-name" id="productTitle"><?php the_title(); ?></h1>

                <?php
                if (!empty($formula)) {
                ?>
                  <p class="product-formula" id="productFormula">
                    <?php echo esc_html($formula); ?>
                  </p>
                <?php } ?>



                <?php
                if (!empty($subtitle)) {
                ?>
                  <!-- opens p inside if statement -->
                  <p class="product-teaser" id="productTeaser">
                    <?php echo esc_html($subtitle); ?>
                  </p>
                <?php } ?>

                <!-- Key benefits -->
                <!-- split benefits into individual lines in php mode -->
                <?php
                $benefits = array();
                if (!empty($key_benefits)) {
                  // 1) split by new lines (works on Windows / Mac / Linux)
                  $benefits = preg_split("/\r\n|\n|\r/", $key_benefits, -1, PREG_SPLIT_NO_EMPTY);
                  $benefits =  array_map('trim', $benefits);

                  /*run the function trim on each line ,
                                      here on each element in the array*/
                  $benefits = array_filter($benefits);
                  $benefits = array_values($benefits);
                }
                ?>

                <div class="key-benefits">
                  <h3 class="benefits-heading">Key Benefits</h3>
                  <?php if (!empty($benefits)) {
                  ?>
                    <ul id="productBenefits">
                      <?php foreach ($benefits as $b) { ?>
                        <li><?php echo esc_html($b); ?></li>
                      <?php } ?>
                    </ul>
                  <?php } ?>
                </div>
              </div> <!-- end of hero content-->
            </div>
          </section>


          <div class="section-divider" aria-hidden="true">
            <div class="separator-line"></div>
          </div>

          <section title="description" class="description-section content-section" id="description">
            <h2 class="section-heading">Description</h2>
            <div class="inner__content">
              <p id="productDescription">
                <?php the_content(); ?>
              </p>
            </div>
          </section>

          <div class="section-divider" aria-hidden="true">
            <div class="separator-line"></div>
          </div>

          <!-- adding data to Nutrient Content table -->
          <?php if (!empty($nutrient_array_rows)) { ?>
            <section class="content-section nutrient-declaration-section" id="nutrient-declaration">

              <h2 class="section-heading">Nutrient Declaration</h2>
              <div class="inner__content">
                <table class="nutrient-table" id="productNutrientTable">
                  <tbody>
                    <!-- ADD DATA TABLE From database -->
                    <?php
                    foreach ($nutrient_array_rows as $rows) { ?>
                      <tr>
                        <td><?php echo esc_html($rows['label']); ?></td>
                        <td><?php echo esc_html($rows['value']); ?></td>
                      </tr>
                    <?php  } //close for each loop
                    ?>
                  </tbody>
                </table>
              </div>
            </section>
          <?php } ?>

          <?php
          // Internal staff notice block , Prompting Staff  
          if ($is_internal_user && ($nutrient_state != 'ok' || !empty($nutrient_autofixed_lines))) :
            if (!empty($nutrient_autofixed_lines)) : ?>
              <div class="editor-only-prompt" role="note">
                <strong>Internal notice (staff only):</strong>
                <div>Nutrient table were auto-fixed before display.</div>
                <details>
                  <summary>Show auto-fixed lines</summary>
                  <ul>
                    <?php foreach ($nutrient_autofixed_lines as $fix) : ?>
                      <li>
                        <?php echo esc_html('Line ' . $fix['line'] . ': ' . $fix['original'] . ' → ' . $fix['fixed'] . ' (' . $fix['rule'] . ')'); ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </details>
              </div>
            <?php endif; /* NEW */ ?>

            <?php
            // 1) Field is empty (not an error)
            if ($nutrient_state === 'empty') : ?>
              <div class="editor-only-prompt" role="note">
                <strong>Internal notice (staff only):</strong>
                <div>Nutrient table is hidden because the ACF field is empty (no data yet).</div>
                <div>Expected format: <code>Label|Value</code> (one per line).</div>
              </div>

            <?php
            // 2) Partial: table is shown but some lines ignored
            elseif ($nutrient_state === 'partial') :

              // Count reasons
              $reason_counts = [];
              foreach ($nutrient_invalid_lines as $issue) {
                $reason = $issue['reason'];
                $reason_counts[$reason] = ($reason_counts[$reason] ?? 0) + 1;
              }
            ?>
              <div class="editor-only-prompt" role="alert">
                <strong>Internal notice (staff only):</strong>
                <div>Some nutrient lines were ignored — valid rows are shown above.</div>
                <div>Expected format: <code>Label|Value</code> (one per line).</div>

                <ul>
                  <?php foreach ($reason_counts as $reason => $count) : ?>
                    <li><?php echo esc_html($count . ' × ' . $reason); ?></li>
                  <?php endforeach; ?>
                </ul>

                <details>
                  <summary>Show invalid lines</summary>
                  <ul>
                    <?php foreach ($nutrient_invalid_lines as $issue) : ?>
                      <li>
                        <?php echo esc_html('Line ' . $issue['line'] . ': ' . $issue['original'] . ' — ' . $issue['reason']); ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </details>
              </div>

            <?php
            // 3) Invalid: field has content but 0 valid rows
            elseif ($nutrient_state === 'invalid') : ?>
              <div class="editor-only-prompt" role="alert">
                <strong>Internal notice (staff only):</strong>
                <div>Nutrient table is hidden because all lines are invalid.</div>
                <div>Expected format: <code>Label|Value</code> (one per line).</div>

                <details>
                  <summary>Show invalid lines</summary>
                  <ul>
                    <?php foreach ($nutrient_invalid_lines as $issue) : ?>
                      <li>
                        <?php echo esc_html('Line ' . $issue['line'] . ': ' . $issue['original'] . ' — ' . $issue['reason']); ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </details>
              </div>
            <?php endif; ?>

          <?php endif; // END is_internal_user 
          ?>

          <!-- NEW START: Build a cleaned rows array (separate from raw meta) !-->

          <?php
          $recom_rows = [];

          foreach ($recom_table_rows as $row) {
            if (!is_array($row)) {
              continue;
            }

            $crop        = normalize_string($row['crop'] ?? '');
            $fertigation = normalize_string($row['fertigation'] ?? '');
            $foliar      = normalize_string($row['foliar'] ?? '');
            $time        = normalize_string($row['time'] ?? '');

            if ($crop === '' && $fertigation === '' && $foliar === '' && $time === '')
              continue;

            $recom_rows[] = [
              'crop'        => $crop,
              'fertigation' => $fertigation,
              'foliar'      => $foliar,
              'time'        => $time,
            ];
          } //end of for loop

          // Store the cleaned row for rendering


          ?>

          <div class="section-divider" aria-hidden="true">
            <div class="separator-line"></div>
          </div>

          <?php if (!empty($recom_rows)) { ?>
            <!-- Adding Data to Application Recommendations Table -->
            <section
              class="content-section application-recommendations-section"
              id="application-recommendations">
              <h2 class="section-heading">Application Recommendations</h2>
              <div class="inner__content">
                <div class="table-container">
                  <table class="recommendations-table">
                    <thead>
                      <tr>
                        <th rowspan="2">Crop</th>
                        <th colspan="2">Application Rate</th>
                        <th rowspan="2">Time of Application</th>
                      </tr>
                      <tr>
                        <th>Fertigation</th>
                        <th>Foliar ml/100 L</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($recom_rows as $data_rows) { ?>
                        <tr>
                          <!-- nl2br converts "\n" inside the cell into <br> for HTML display -->
                          <td><?php echo nl2br(esc_html($data_rows['crop'])); ?></td>
                          <td>

                            <?php
                            $fert_lines = explode("\n", $data_rows['fertigation']);
                            foreach ($fert_lines as $line) {
                              $line = trim($line);
                              if (!empty($line)) {
                                echo '<span style="white-space: nowrap; display: inline-block;">' . esc_html($line) . '</span><br/>';
                              }
                            }
                            ?></td>
                          <td><?php
                              $foliar_lines = explode("\n", $data_rows['foliar']);
                              foreach ($foliar_lines as $line) {
                                $line = trim($line);

                                if (!empty($line)) {
                                  echo '<span style="white-space: nowrap; display: inline-block;">' . esc_html($line) . '</span><br/>';
                                }
                              }
                              ?></td>
                          <td><?php echo nl2br(esc_html($data_rows['time'])); ?></td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </section>
          <?php } //end of if 
          ?>


        </div> <!-- wrapper end of content-->

      </main>
    <?php }
    get_footer();
    ?>