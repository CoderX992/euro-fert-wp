<?php get_header(); ?>

<main id="primary" class="site-main">
  <div class=container>
    <?php
    if (have_posts()) :
      while (have_posts()) :
        the_post();
        the_content();
      endwhile;
    else :
      echo '<p>No content found.</p>';
    endif;
    ?>
  </div>
</main>

<?php get_footer(); ?>