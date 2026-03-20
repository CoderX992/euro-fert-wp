<?php
/* NEW START: Admin-only CSS for CMB2 recommendations metabox */

function eurofert_admin_files($hook)

{
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    // Load the stylesheet only on the eurofert_product post type in the editor
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'eurofert_product') {
        return;
    }
    /* NEW START: avoid filemtime warning if admin-cmb2.css doesn't exist yet */
    $admin_cmb2_rel_path = '/inc/admin-cmb2.css';
    $admin_cmb2_abs_path = get_theme_file_path($admin_cmb2_rel_path);

    if (file_exists($admin_cmb2_abs_path)) {
        wp_enqueue_style(
            'eurofert-cmb2-admin',
            get_theme_file_uri($admin_cmb2_rel_path),
            array(),
            filemtime($admin_cmb2_abs_path)
        );
    }
}

add_action('admin_enqueue_scripts', 'eurofert_admin_files');


function eurofert_theme_setup()
{

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    // Use proper sizes for your project (example values)
    add_image_size('productImage_large', 960, 640, false);
    add_image_size('productImage_small', 600, 400, true);
    add_image_size('pageBanner', 1920, 600, true);
}

add_action('after_setup_theme', 'eurofert_theme_setup');
/* Front-end scripts and styles */
function eurofert_files()
{
    wp_enqueue_script(
        'bootstrap-bundle',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
        array(),
        '5.3.0',
        true
    );

    wp_enqueue_script('main-eurofert-js', get_theme_file_uri('/js/main.js'), array('bootstrap-bundle'), '1.0', true);


    /* Fonts */
    wp_enqueue_style(
        'font-awesome',
        '//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
    );
    wp_enqueue_style(
        'eurofert-google-fonts',
        'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap'
    );

    wp_enqueue_style(
        'eurofert_main_styles',
        get_theme_file_uri('/css/styles.css'),
        array(),
        filemtime(get_theme_file_path('/css/styles.css')) // This creates a unique version on every save
    );

    // Other styles that depend on the main styles
    wp_enqueue_style('eurofert_animation_styles', get_theme_file_uri('/css/animations.css'));
    wp_enqueue_style(
        'eurofert_product_styles',
        get_theme_file_uri('/css/product-details.css'),
        array('eurofert_main_styles'),
        filemtime(
            get_theme_file_path('/css/product-details.css')
        )
    );

    wp_enqueue_style('eurofert_category_styles', get_theme_file_uri('/css/category-grid.css'),  array('eurofert_main_styles'),  filemtime(
        get_theme_file_path('/css/product-details.css')
    ));

    wp_enqueue_style('eurofert_header_styles', get_theme_file_uri('/css/header.css'), array('eurofert_main_styles'), filemtime(get_theme_file_path('/css/header.css')));


    wp_enqueue_style('eurofert_footer_styles', get_theme_file_uri('/css/footer.css'), array('eurofert_main_styles'), filemtime(get_theme_file_path('/css/footer.css')));
}

add_action('wp_enqueue_scripts', 'eurofert_files'); //hook into wp_enqueue_scripts


/**
 * Split a fertilizer product name between base name and formula/details part.
 * Detects the first occurrence of a “formula-like” token: either:
 *   - a sequence of digits (optionally with hyphens/plus/percent),
 *   - or a percentage with following letters,
 *   - or a chemical code (letters + digits/percent).
 * Inserts a <br> before the formula part.
 *
 * @param string $title Raw product name.
 * @return string Safe HTML (escaped base + <br> + escaped formula) or plain title if no formula detected.
 */
function format_product_name(string $title): string
{
    $title = trim($title);
    // Regex to find start of formula / detail part:
    // Look for either:
    //  - a standalone digit sequence (optionally with hyphens/plus/percent), or
    //  - a percentage sign followed by letters/digits, or
    //  - a letter sequence with percent or digit after a space
    $pattern = '/\s(?=[0-9]+[0-9\-\+\%]*(?:[A-Za-z\%0-9]*)?)|(\s(?=[A-Za-z]*\s*\d+%))|(\s(?=[A-Za-z]{1,}\s*\d+))/u';

    // Another simpler fallback: find first occurrence of digit or percent or plus-digit
    $pattern_simple = '/\s(?=[0-9]|%|\+)/u';

    // Use simple pattern — easier and covers most formulas
    if (preg_match($pattern_simple, $title, $match, PREG_OFFSET_CAPTURE)) {
        $pos = $match[0][1];
        $before = mb_substr($title, 0, $pos, 'UTF-8');
        $after  = mb_substr($title, $pos + 1, null, 'UTF-8');
        return esc_html(trim($before)) . '<br>' . esc_html(trim($after));
    }

    // Fallback: no formula detected — return escaped title
    return esc_html($title);
}

require_once get_theme_file_path('/inc/cmb2-application-recommendations.php');

/**
 * Eurofert helper: detect strings for nutrient parsing.
 * Examples considered values:
 * - 11
 * - 11.0
 * - 11,0
 * - 11%
 * - 11.0 g/L
 * - 120 ppm
 *
 * @param mixed $s Raw input.
 * @return bool True if it looks like a numeric value (with optional units).
 */


if (! function_exists('check_value')) {

    function check_value($s): bool
    {
        $s = trim((string) $s);

        // Must contain at least one digit.
        if ($s === '' || ! preg_match('/\d/u', $s)) {
            return false;
        }

        // Must start with a number (supports decimals + optional % or units).
        return (bool) preg_match('/^\s*[+-]?\d+(?:[.,]\d+)?\s*(?:%|[a-zA-Zµμ\/].*)?$/u', $s);
    }
}

function swap_data(&$label, &$value): bool
{
    if (check_value($label) && !check_value($value)) {
        $tmp = $label;
        $label = $value;
        $value = $tmp;
        return true;
    }
    return false;
}


/**
 * Register an auto-fix entry for staff debug notices.
 *
 * @param array  $nutrient_autofixed_lines The array we push into (by reference).
 * @param int    $line_number             Original line number in textarea.
 * @param string $rule                    Short explanation of the auto-fix.
 * @param string $original                The original raw line.
 * @param string $fixed                   The fixed output line(s).
 */
function register_auto_fix(array &$nutrient_autofixed_lines, int $line_number, string $rule, string $original, string $fixed): void
{
    $nutrient_autofixed_lines[] = [
        'line'     => $line_number,
        'rule'     => $rule,
        'original' => $original,
        'fixed'    => $fixed,
    ];
}
