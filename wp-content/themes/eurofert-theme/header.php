<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>

</head>


<body <?php body_class('has-fixed-header'); ?>>

  <!-- Header -->
  <header class="header" id="header">

    <nav class="site-nav">
      <a class="brand-logo" href="<?php echo esc_url(home_url('/')); ?>">
        <img
          src="<?php echo esc_url(get_theme_file_uri('/images/eurofert-logo.png')); ?>"
          alt="Eurofert Logo"
          class="logo"
          loading="eager"
          width="185"
          height="auto" />
      </a>
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent"
        aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <!-- navbar collapse parent -->
      <div class="main-menu collapse navbar-collapse-container" id="navbarSupportedContent">
        <div class="menu-header">
          <h5 class="menu-title">Menu Navigation</h5>
        </div>

        <!-- Nav menu list-->
        <ul class="nav-menu">
          <li class="nav-item">
            <a class="nav-link active" href="index.html"><span>Home</span></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="services.html"><span>Services</span></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="about.html"><span>About Us</span></a>
          </li>

          <!-- Dropdown list Product Categories-->
          <li class="nav-item has-dropdown" data-toggle="dropdown">
            <a class="nav-link parent-link">
              <span class="link-title">Product Categories</span>
            </a>

            <!-- dropdown list Opener-->
            <span class="nav-opener" aria-label="Open submenu">
              <i class="fas fa-chevron-up arrow-icon"></i>
              <!-- arrow icon-->
            </span>

            <!-- Mobile and Desktop Categories Sub-menu -->
            <ul class="dropdown-menu submenu-items">
              <li class="submenu-item submenu-title">
                <!-- WP categories URL -->
                <a class="submenu__link" href="">
                  Product Categories Overview</a>
              </li>

              <div>

                <li class="submenu-item">
                  <a href="products.html?category=maxigrowEssentials" class="submenu__link">
                    MAXIGROW Essentials</a>
                </li>

                <li class="submenu-item">
                  <a href="#" class="submenu__link">
                    MaxiGrow Micronutrient
                  </a>
                </li>

                <li class="submenu-item">
                  <a href="products.html?category=maxigrowPower" class="submenu__link">MAXIGROW Power</a>
                </li>

                <li class="submenu-item">
                  <a href="products.html?category=maxigrowNPK" class="submenu__link">MAXIGROW NPK</a>
                </li>

                <li class="submenu-item">
                  <a href="products.html?category=maxigrowFoliar" class="submenu__link">MAXIGROW Foliar</a>
                </li>



                <li class="submenu-item">
                  <a href="products.html?category=maxigrowTrace" class="submenu__link">MAXIGROW Trace</a>
                </li>



                <li class="submenu-item">
                  <a href="products.html?category=maxigrowSpecialty" class="submenu__link">MAXIGROW Specialty</a>
                </li>

                <li class="submenu-item">
                  <a href="products.html?category=maxigrowTerra" class="submenu__link">MAXIGROW Terra</a>
                </li>
              </div>
            </ul>
          </li>
        </ul>
      </div>
      <!-- navbar collapse wrapper end-->
      <div
        class="drawer-backdrop"
        data-drawer-backdrop
        aria-hidden="true"></div>
    </nav>

  </header>