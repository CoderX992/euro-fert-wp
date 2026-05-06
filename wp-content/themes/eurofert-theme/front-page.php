<?php get_header();
?>
<!-- Hero Section -->

<main class="content home-page">
  <section class="hero-section hero-section--image" id="home">
    <div class="container hero-section__row">
      <div class="hero-section__text fade-in">

        <h1 class="hero-section__title fw-bold">
          Growth Rooted in Quality &amp; Trust
        </h1>

        <p class="hero-section__subtitle">
          Advanced agricultural solutions that respect both soil and nature
          while maximizing crop yield and quality.
        </p>
      </div>

      <div class="hero-section__buttons">
        <a href="" class="btn btn-primary btn-explore">Explore Products</a>
        <a href="#contact" class="btn btn-outline-light">Contact Us</a>
      </div>
    </div>



    <div class="wave-divider" aria-hidden="true">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 1440 320"
        preserveAspectRatio="none"
        style="display: block">
        <path
          fill="#ffffff"
          d="M 0 140 C 216.233 564.4 361.845 93.09 678.729 
          148.922 C 793.75 160.651 964.357 231.648 1175.231 212.578 
          C 1302.39 189.262 1443.778 194.192 1481.472 142.663
           L 1478.484 323.963 
           L 0 320 Z"></path>
      </svg>
    </div>
  </section>

  <!-- Intro Section -->
  <section class="intro-section" id="intro">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-12 text-center">
          <h2 class="section-title display-5 fw-bold mb-3 text-primary">Welcome to Eurofert</h2>
          <p class="intro-lead text-muted mb-3">
            At Eurofert, we believe sustainable agriculture starts with the soil. Our fertilizers are designed to
            enrich crops while preserving the planet. With decades of experience in soil nutrition, Eurofert delivers
            science-backed solutions that improve yield quality, reduce environmental impact, and support farming
            communities worldwide.
          </p>
          <p class="text-muted mb-3">
            From high-concentration pastes to precision liquid and granular fertilizers, every Eurofert product is
            designed to deliver consistent nutrient availability, excellent solubility, and predictable results in
            real-world farming conditions. Our ranges are tested across different crops, climates, and irrigation
            systems to make sure they perform where it matters most: in your fields.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Quick Products Preview / Category Teaser -->

  <?php /*load Product Category terms for homepage teaser */
  $product_categories = get_terms(array(
    'taxonomy'   => 'fertilizer_category',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ));


  ?>
  <section class="category-preview" id="products-preview">
    <div class="container">
      <!-- Section Title-->

      <h2 class="text-center fw-bold text-primary section-title">Colfert Product Lines</h2>
      <p class="text-center text-muted mb-3">Explore our comprehensive range of 7 specialized fertilizer lines</p>

      <!-- Row -->
      <?php if (!is_wp_error($product_categories) && !empty($product_categories)) { ?>
        <div class="row" id="homeProductsGrid">
          <!--categories displayed as teaser -->
          <?php
          foreach ($product_categories as $single_category) {
            $category_name = $single_category->name;
            $category_link = get_term_link($single_category);
            $category_short_description = get_field('short_description', $single_category);
            $category_tagline = get_field('tagline', $single_category);
            $category_featured_image = get_field('category_featured_image', $single_category);

            $category_image_url = '';
            $category_image_alt = $category_name;

            if (!empty($category_featured_image) && is_array($category_featured_image)) {
              $category_image_url = $category_featured_image['url'] ?? '';
              $category_image_alt = !empty($category_featured_image['alt']) ? $category_featured_image['alt'] : $category_name;
            }
          ?>
            <div class="category-wrapper col-12 col-xl-4 col-xxl-3">
              <div class="category-card card h-100 shadow-sm fade-in" style="cursor:pointer">
                <img
                  src="<?php echo esc_url($category_image_url); ?>"
                  class="card-img-top category-thumbnail"
                  alt="<?php echo esc_attr($category_image_alt); ?>"
                  loading="lazy" />
                <!-- Category name-->
                <div class="card-body text-center">
                  <h3 class="card-title"><?php echo esc_html($category_name); ?></h3>

                  <?php if (!empty($category_tagline)): ?>
                    <p class="subtitle"><?php echo esc_html($category_tagline); ?></p>
                  <?php else: ?>
                    <p class="subtitle subtitle--empty" aria-hidden="true"></p>
                  <?php endif; ?>

                  <?php if (!empty($category_short_description)) { ?>
                    <p class="category-short-description card-text">
                      <?php /* Print optional short description only when it exists */ ?>
                      <?php echo esc_html($category_short_description); ?>
                    </p>
                  <?php } ?>
                  <a class="btn btn-primary btn-sm" href="<?php echo esc_url($category_link); ?>">View Category</a>
                </div>
              </div>
            </div>
          <?php
          } // end of foreach loop 
          ?>
        </div>
      <?php } ?>
      <div class="text-center mt-5">
        <a href="product-categories.html" class="btn btn-primary btn-lg">
          View All 7 Product Lines
          <i class="fas fa-arrow-right ms-2"></i>
        </a>
      </div>
    </div>
  </section>
</main>
<?php
get_footer();
?>