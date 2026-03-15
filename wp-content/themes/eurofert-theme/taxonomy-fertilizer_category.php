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
    ? wp_strip_all_tags($category_desc_input)
    : 'Explore our range of products within this category.';


  // get the image for the hero section 
  $category_hero_img = get_page_by_path('eurofert-category-hero-bottles-HD',  OBJECT, 'attachment');
  $category_hero_id = $category_hero_img ? (int) $category_hero_img->ID : 0;
  ?>

 <section class="category-hero">
   <div class="container">
     <div class="image-wrapper">
       <?php if ($category_hero_id) :
          echo wp_get_attachment_image(
            $category_hero_id,
            'full',
            false,
            array(
              'class' => 'category-hero__overlay',
              'alt' => '',
              'aria-hidden' => 'true',
              'loading'  => 'eager',
              'decoding' => 'async',
              'sizes' => '(max-width: 767.98px) 60vw, (max-width: 1199.98px) 520px, 720px'
            )
          );
        // END wp_get_attachment_image()
        endif;
        ?>
     </div>

     <div class="align-items-center">
       <div class="col-12 text-center">
         <h1 class="display-4 fw-bold mb-3" id="pageHeaderTitle">
           <?php echo esc_html($category_name); ?>
         </h1>
         <p class="lead" id="pageHeaderLead">
           <?php echo esc_html($category_desc); ?>
         </p>
       </div>
     </div>
   </div>
 </section>

 <!-- Product Grid -->
 <section class="product-grid py-5 bg-light" id="productGrid">

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

        ?>
           <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
             <div class="product-grid-item  h-100" style="cursor: pointer;">
               <a href="<?php echo esc_url($product_url); ?>">
                 <div class="card">
                   <img src="${product.image}" class="card-img-top" alt="name of product">

                   <div class="card-body p-3">
                     <h5 class="card-title"> <?php echo esc_html(get_the_title()); ?></h5>

                     <p class="card-text small text-muted mb-2">Formula: ${product.formula}</p>
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

 <?php
  get_footer();
  ?>