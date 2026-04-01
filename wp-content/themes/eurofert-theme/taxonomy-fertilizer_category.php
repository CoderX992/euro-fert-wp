 <?php get_header();
  /**
   * taxonomy-fertilizer_category.php
   * Purpose: WP version of products.html (category -> products page) */

  /* NEW START: Current category (taxonomy term) context
   This template is loaded when visiting:
   /product-category/<term-slug>*/


  $category_obj = get_queried_object();
  $category_name = isset($category_obj->name) ? (string) $category_obj->name : 'Product Category';


  $category_desc_input = isset($category_obj->description) ? (string) $category_obj->description : 'Description of Category';
  $category_desc_input = trim($category_desc_input);

  $category_desc = ($category_desc_input !== '')
    ? trim($category_desc_input)
    : 'Explore our range of products within this category.';


  // get the image for the hero section 
  $category_hero_img = get_page_by_path('eurofert-category-hero-bottles-HD',  OBJECT, 'attachment');
  $category_hero_id = $category_hero_img ? (int) $category_hero_img->ID : 0;
  ?>
 <main class="content">
   <section class="category-hero py-5">
     <div class="container">
       <div class="row align-items-stretch">

         <div class="category-container  d-flex col-12 col-lg-8 justify-content-center">
           <div class="category-info text-center">

             <h1 class="category-title display-4 fw-bold">
               <?php echo esc_html($category_name); ?>
             </h1>
             <div class="category-description" id="pageHeaderLead">
               <?php echo wpautop(esc_html($category_desc)); ?>
             </div>
             <div class="button-container mt-4">
               <a href="#productGrid" class="btn btn-primary btn-attention">Download Brochure</a>
             </div>
           </div>
         </div>

         <div class="col-12 col-lg-4 image-wrapper">
           <?php if ($category_hero_id) :
              echo wp_get_attachment_image(
                $category_hero_id,
                'full',
                false,
                array(
                  'class' => 'category-hero-img',
                  'alt' => '',
                  'aria-hidden' => 'true',
                  'loading'  => 'eager',
                  'decoding' => 'async',
                  'sizes' => '(max-width: 767.98px) 100vw, (max-width: 1199.98px) 50vw, 800px'
                )
              );
            // END wp_get_attachment_image()
            endif;
            ?>
         </div>
       </div>
     </div>
   </section>

   <!-- Product Grid -->
   <section class="product-grid py-5" id="productGrid">

     <div class="container">
       <div class="d-flex justify-content-between align-items-center mb-4">
         <a class="btn btn-outline-secondary" href="<?php echo esc_url(home_url('/')); ?>">
           <i class="fas fa-arrow-left me-2"></i>Back to Categories
         </a>
       </div>



       <div class="row g-3" id="productGridContainer">

         <?php
          /** 1 check if current taxonomy term have products */
          if (have_posts()) :
            while (have_posts()):
              the_post();

              $product_url = get_permalink();
              $product_formula = function_exists('get_field') ?  get_field('formula') : '';

          ?>
             <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
               <div class="product-grid-item">
                 <a href="<?php echo esc_url($product_url); ?>">
                   <div class="card">
                     <div class="product-grid-item__media">
                       <div class="product-grid-item__media-inner">
                         <?php
                          $product_image_id = get_post_thumbnail_id(get_the_ID());

                          if ($product_image_id) {
                            echo wp_get_attachment_image(
                              $product_image_id,
                              'full',
                              false,
                              array(
                                'class' => 'card-img-top',
                                'alt'   => get_the_title(),
                                'loading' => 'lazy',
                                'decoding' => 'async'
                              )
                            );
                          }
                          ?>
                       </div>
                     </div>

                     <div class="card-body p-3">
                       <h5 class="card-title"> <?php echo esc_html(get_the_title()); ?></h5>
                       <?php if (!empty($product_formula)) : ?>
                         <p class="card-text small text-muted mb-2">
                           <?php echo esc_html($product_formula) ?></p>
                       <?php endif; ?>

                       <div class="d-flex justify-content-between align-items-center">
                         <small class="text-primary fw-bold">View Details</small>
                         <i class="fas fa-arrow-right text-primary"></i>
                       </div>
                     </div>

                   </div>
                 </a>
               </div>
             </div>
           <?php endwhile;

          else:  ?>
           <div class="col-12">
             <p class="text-muted mb-0">
               <?php echo esc_html__('No products found in this category yet.', 'eurofert'); ?>
             </p>
           </div>
         <?php endif; // END if (have_posts()) 
          ?>

       </div>
     </div>
   </section>
 </main>
 <?php
  get_footer();
  ?>