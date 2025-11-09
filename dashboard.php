<!-- Replace all previous PHP/HTML with this updated version -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Garage - Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    /* Global Reset */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #fffaf5;
      color: #1f2937;
      min-height: 100vh;
    }

    .dashboard {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 270px;
      background: #fff;
      border-right: 3px solid #f97316;
      box-shadow: 5px 0 15px rgba(0, 0, 0, 0.05);
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      z-index: 10;
      transition: all 0.3s ease-in-out;
    }

    .sidebar-header {
      background: linear-gradient(90deg, #f97316, #ea580c);
      padding: 2rem;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 80px;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 12px;
      color: white;
      font-size: 20px;
      font-weight: bold;
    }

    .logo i {
      font-size: 24px;
    }

    .sidebar-nav {
      padding: 2rem 1rem;
    }

    .nav-item {
      display: flex;
      align-items: center;
      padding: 1rem;
      margin-bottom: 8px;
      border-radius: 12px;
      transition: all 0.2s;
      color: #1f2937;
      font-weight: 500;
      cursor: pointer;
      background: white;
    }

    .nav-item i {
      margin-right: 1rem;
      color: #ea580c;
    }

    .nav-item:hover, .nav-item.active {
      background: #f97316;
      color: white;
    }

    .nav-item.active i {
      color: white;
    }

    /* Main Content */
    .main-content {
      margin-left: 270px;
      width: calc(100% - 270px);
    }

    .header {
      background: white;
      padding: 1.5rem 2rem;
      border-bottom: 2px solid #f97316;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 5;
    }

    .page-title h1 {
      font-size: 24px;
      background: linear-gradient(90deg, #f97316, #ea580c);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .user-avatar {
      width: 45px;
      height: 45px;
      background: linear-gradient(135deg, #ea580c, #f97316);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
    }

    .content {
      padding: 2rem;
    }

    .welcome-banner {
      background: linear-gradient(90deg, #f97316, #ea580c);
      border-radius: 20px;
      padding: 2rem;
      color: white;
      margin-bottom: 2rem;
      box-shadow: 0 12px 30px rgba(0,0,0,0.1);
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.5rem;
    }

    .stat-card {
      background: white;
      border: 2px solid #f97316;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 10px 20px rgba(0,0,0,0.05);
      transition: 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-4px);
    }

    .stat-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .stat-icon {
      font-size: 28px;
      background: linear-gradient(135deg, #ea580c, #f97316);
      color: white;
      padding: 12px;
      border-radius: 12px;
    }

    .stat-value {
      font-size: 28px;
      font-weight: bold;
      color: #ea580c;
    }

    .stat-title {
      font-size: 14px;
      color: #6b7280;
    }

    .stat-change {
      color: #10b981;
      font-size: 12px;
    }
  </style>
</head>
<body>
<?php
  $currentPage = $_GET['page'] ?? 'overview';
  $stats = [
    ['title' => 'Total Bookings', 'value' => '1,234', 'change' => '+12%', 'icon' => 'fas fa-calendar-alt'],
    ['title' => 'Active Customers', 'value' => '856', 'change' => '+8%', 'icon' => 'fas fa-users'],
    ['title' => 'Revenue', 'value' => '$45,678', 'change' => '+15%', 'icon' => 'fas fa-dollar-sign'],
    ['title' => 'Services Done', 'value' => '987', 'change' => '+5%', 'icon' => 'fas fa-car']
  ];
  $menuItems = [
    ['id' => 'overview', 'label' => 'Overview', 'icon' => 'fas fa-chart-line'],
    ['id' => 'bookings', 'label' => 'Bookings', 'icon' => 'fas fa-calendar-alt'],
    ['id' => 'customers', 'label' => 'Customers', 'icon' => 'fas fa-users'],
    ['id' => 'services', 'label' => 'Services', 'icon' => 'fas fa-car'],
    ['id' => 'reports', 'label' => 'Reports', 'icon' => 'fas fa-chart-bar'],
    ['id' => 'settings', 'label' => 'Settings', 'icon' => 'fas fa-cog']
  ];
?>

<div class="dashboard">
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-header">
      <div class="logo"><i class="fas fa-warehouse"></i>Garage</div>
    </div>
    <nav class="sidebar-nav">
      <?php foreach ($menuItems as $item): ?>
        <div class="nav-item <?php echo $currentPage === $item['id'] ? 'active' : ''; ?>" onclick="changePage('<?php echo $item['id']; ?>')">
          <i class="<?php echo $item['icon']; ?>"></i><?php echo $item['label']; ?>
        </div>
      <?php endforeach; ?>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Header -->
    <div class="header">
      <div class="page-title">
        <h1><?php echo ucfirst($currentPage); ?></h1>
      </div>
      <div class="user-avatar">G</div>
    </div>

    <!-- Content -->
    <div class="content">
      <?php if ($currentPage === 'overview'): ?>
        <div class="welcome-banner">
          <h2>Welcome back, Admin 🚗</h2>
          <p>Your garage operations are running smoothly.</p>
        </div>
        <div class="stats-grid">
          <?php foreach ($stats as $stat): ?>
            <div class="stat-card">
              <div class="stat-header">
                <div class="stat-icon"><i class="<?php echo $stat['icon']; ?>"></i></div>
                <div class="stat-value"><?php echo $stat['value']; ?></div>
              </div>
              <div class="stat-title"><?php echo $stat['title']; ?></div>
              <div class="stat-change"><i class="fas fa-arrow-up"></i> <?php echo $stat['change']; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div style="text-align:center; margin-top:3rem;">
          <i class="fas fa-tools" style="font-size: 48px; color: #f97316;"></i>
          <h2 style="margin-top: 1rem; background: linear-gradient(90deg, #f97316, #ea580c); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            <?php echo ucfirst($currentPage); ?> Coming Soon
          </h2>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  function changePage(pageId) {
    window.location.href = "?page=" + pageId;
  }
</script>
</body>
</html>
