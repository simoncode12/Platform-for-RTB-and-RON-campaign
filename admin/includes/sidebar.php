<div class="col-md-3 col-lg-2 px-0">
    <div class="sidebar">
        <div class="p-4 text-center">
            <h4 class="text-white mb-0">
                <i class="fas fa-chart-line me-2"></i>
                AdStart
            </h4>
            <small class="text-white-50">RTB & RON Platform</small>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
            
            <div class="nav-section mt-3">
                <small class="text-white-50 px-3 text-uppercase">Campaigns</small>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rtb-sell.php' ? 'active' : ''; ?>" href="rtb-sell.php">
                    <i class="fas fa-bullhorn me-2"></i>
                    RTB Campaigns
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ron-campaign.php' ? 'active' : ''; ?>" href="ron-campaign.php">
                    <i class="fas fa-network-wired me-2"></i>
                    RON Campaigns
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'creative.php' ? 'active' : ''; ?>" href="creative.php">
                    <i class="fas fa-palette me-2"></i>
                    Creatives
                </a>
            </div>
            
            <div class="nav-section mt-3">
                <small class="text-white-50 px-3 text-uppercase">Traffic</small>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rtb-buy.php' ? 'active' : ''; ?>" href="rtb-buy.php">
                    <i class="fas fa-shopping-cart me-2"></i>
                    RTB Buy
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'zone.php' ? 'active' : ''; ?>" href="zone.php">
                    <i class="fas fa-map-marked-alt me-2"></i>
                    Zones
                </a>
            </div>
            
            <div class="nav-section mt-3">
                <small class="text-white-50 px-3 text-uppercase">Management</small>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'website.php' ? 'active' : ''; ?>" href="website.php">
                    <i class="fas fa-globe me-2"></i>
                    Websites
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'advertiser.php' ? 'active' : ''; ?>" href="advertiser.php">
                    <i class="fas fa-user-tie me-2"></i>
                    Advertisers
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'publisher.php' ? 'active' : ''; ?>" href="publisher.php">
                    <i class="fas fa-users me-2"></i>
                    Publishers
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'category.php' ? 'active' : ''; ?>" href="category.php">
                    <i class="fas fa-tags me-2"></i>
                    Categories
                </a>
            </div>
            
            <div class="nav-section mt-3">
                <a class="nav-link text-danger" href="login.php?logout=1">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Logout
                </a>
            </div>
        </nav>
    </div>
</div>