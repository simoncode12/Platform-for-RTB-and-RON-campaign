<?php
/**
 * AdStart RTB & RON Platform
 * Landing Page
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdStart - RTB & RON Advertising Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .landing-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="landing-card">
                    <div class="text-center p-5">
                        <h1 class="display-4 mb-4">
                            <i class="fas fa-chart-line text-primary me-3"></i>
                            AdStart Platform
                        </h1>
                        <h3 class="text-muted mb-5">RTB & RON Advertising Platform</h3>
                        
                        <div class="row mb-5">
                            <div class="col-md-4 mb-4">
                                <div class="text-center">
                                    <i class="fas fa-bullhorn feature-icon text-primary"></i>
                                    <h5>RTB Campaigns</h5>
                                    <p class="text-muted">Real-time bidding for external traffic sources</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="text-center">
                                    <i class="fas fa-network-wired feature-icon text-success"></i>
                                    <h5>RON Campaigns</h5>
                                    <p class="text-muted">Run of network campaigns with custom creatives</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="text-center">
                                    <i class="fas fa-chart-bar feature-icon text-warning"></i>
                                    <h5>Analytics</h5>
                                    <p class="text-muted">Real-time reporting and performance tracking</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="/admin/login.php" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Access Admin Panel
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="/api/rtb/request.php?test=1" class="btn btn-outline-primary btn-lg w-100" target="_blank">
                                    <i class="fas fa-cog me-2"></i>
                                    Test API
                                </a>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <small class="text-muted">
                                Platform Status: <span class="badge bg-success">Online</span> | 
                                Version: 1.0 | 
                                Server Time: <?php echo date('Y-m-d H:i:s T'); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>