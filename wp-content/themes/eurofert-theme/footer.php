    <!-- Footer Design updated v1.1 -->


    <footer class="footer">
        <div class="footer-container">
            <div class="footer-upper">
                <div class="footer-top-left-col">
                    <!-- Column 1: logo and tagline -->
                    <div class="content-wrapper">
                        <img class="footer-logo" src="<?php echo get_template_directory_uri(); ?>/images/eurofert-logo.png" alt="Eurofert Logo">

                        <p class="footer-subtitle">
                            Sustainable agricultural solutions for modern farming needs. Trusted by farmers worldwide since 1985.
                        </p>
                    </div>
                </div>

                <!-- Column 2: featured products -->
                <div class="footer-center-col">
                    <div class="upper-list-wrapper">
                        <h4 class="footer-heading">
                            <span class="heading-text">Featured Products</span>
                        </h4>

                        <ul class="footer-links footer-product-links">
                            <li><a href="#"><span>Colfert
                                        Essential KTS</span></a></li>
                            <li><a href="#"><span>Colfert
                                        Power</span></a></li>
                            <li><a href="#"><span>Colfert
                                        Special A-Z Plus</span></a></li>
                            <li><a href="#"><span>Colfert
                                        Trace 16</span></a></li>
                            <li><a href="#"><span>Colfert Combi</span></a></li>
                        </ul>
                    </div>
                </div>
                <!-- Column 3: quick links and footer-social-col -->
                <div class="footer-end-col">
                    <div class="upper-list-wrapper">
                        <h4 class="footer-heading">
                            <span class="heading-text">Quick Links</span>
                        </h4>
                        <ul class="footer-links footer-quick-links">
                            <li><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
                            <li><a href="<?php echo esc_url(home_url('/services')); ?>">Services</a></li>
                            <li><a href="<?php echo esc_url(home_url('/about')); ?>">About Us</a></li>
                            <li><a href="<?php echo esc_url(home_url('/product-categories')); ?>">Product Categories Overview</a></li>
                        </ul>

                    </div>
                </div>
            </div>

            <hr class="footer-separator" aria-hidden="true" />

            <div class="footer-lower">
                <div class="footer-copyright-col">
                    <p class="copyright-text text-muted">
                        <strong>&copy; 2025 Eurofert. All rights reserved.</strong>
                    </p>
                </div>

                <div class="footer-legal-col">
                    <ul class="footer-legal-list">
                        <li>
                            <a href="#" class="footer-legal-link text-muted">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="#" class="footer-legal-link text-muted">Terms of Service</a>
                        </li>
                        <li>
                            <a href="#" class="footer-legal-link text-muted">Cookie Policy</a>
                        </li>
                    </ul>

                </div>
                <div class="footer-social-col">
                    <div class="footer-social-wrapper">
                        <a href="#" class="social-link" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                        <a href="#" class="social-link" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                        <a href="#" class="social-link" aria-label="LinkedIn"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                        <a href="#" class="social-link" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                    </div>

                </div>


            </div>
        </div>

    </footer>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top" aria-label="Back to Top">
        <i class="fas fa-arrow-up"></i>
    </a>
    <?php wp_footer(); ?>
    </body>


    </html>