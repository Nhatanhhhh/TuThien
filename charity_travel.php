<?php
session_start();
if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';

// Kiểm tra vai trò admin
$isAdmin = $role === 'admin';
$isStaff = $role === 'staff';

// Lấy danh sách chuyến đi từ bảng trips
require_once 'config.php';
$stmt = $pdo->query("SELECT * FROM trips WHERE date >= CURDATE() AND is_active = 1 ORDER BY date ASC");
$upcomingTrips = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM trips WHERE date < CURDATE() ORDER BY date DESC");
$pastTrips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chuyến Du Lịch Từ Thiện - HopeLink</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/header.css">
  <link rel="stylesheet" href="styles/charity_travel.css">
</head>

<body>
  <header>
    <div class="header-content">
      <div class="logo">
        <img src="images/hopelink-logo.svg" alt="HopeLink Logo">
        <h1>HopeLink</h1>
      </div>
      <nav>
        <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
        <div class="dropdown">
          <a href="#events" class="dropdown-toggle"><i class="fas fa-hands-helping"></i> Hoạt động</a>
          <div class="dropdown-menu">
            <a href="charity_travel.php"><i class="fas fa-hands-helping"></i> Chuyến du lịch từ thiện</a>
          </div>
        </div>
        <a href="#lienhe"><i class="fas fa-envelope"></i> Liên hệ</a>
        <a href="donation_history.php"><i class="fas fa-history"></i> Lịch sử chuyến đi</a>
      </nav>
      <div class="auth-buttons">
        <?php if (!$isLoggedIn): ?>
          <a href="login.php" class="join-btn" id="login-btn"><i class="fas fa-user-plus"></i> Tham gia ngay</a>
        <?php else: ?>
          <?php if ($isAdmin): ?>
            <a href="admin/dashboard.php" class="admin-btn">
              <i class="fas fa-user-shield"></i> Quản lý
            </a>
          <?php elseif ($isStaff): ?>
            <a href="staff/dashboard.php" class="admin-btn">
              <i class="fas fa-user-shield"></i> Quản lý
            </a>
          <?php else: ?>
            <div class="user-info" id="user-info">
              <button class="user-btn">
                <span class="greeting"><i class="fas fa-user"></i> <?php echo htmlspecialchars($username); ?></span>
              </button>
              <div class="user-dropdown">
                <div class="user-header">
                  <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                  <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($_SESSION['email'] ?? 'email@example.com'); ?></div>
                  </div>
                </div>
                <div class="user-stats">
                  <div class="stat-item">
                    <span class="stat-value">0</span>
                    <span class="stat-label">Chuyến đi</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-value" id="user-balance">$0</span>
                    <span class="stat-label">Quỹ từ thiện</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-value">0</span>
                    <span class="stat-label">Giờ tình nguyện</span>
                  </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="donation.php" class="dropdown-item"><i class="fas fa-wallet"></i> Nạp tiền vào quỹ</a>
                <a href="profile.php" class="dropdown-item"><i class="fas fa-user-circle"></i> Thông tin tài khoản</a>
                <a href="donation_history.php" class="dropdown-item"><i class="fas fa-history"></i> Lịch sử chuyến đi</a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item" onclick="handleLogout(event, '<?php echo $_SESSION['csrf_token']; ?>')">
                  <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main>
    <div class="container">
      <div class="tabs">
        <div class="tab active" onclick="switchTab('upcoming')">Sự kiện sắp tới</div>
        <div class="tab" onclick="switchTab('past')">Sự kiện đã qua</div>
      </div>

      <div id="trip-list">
        <div id="upcoming" class="tab-content active">
          <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Sự kiện sắp tới</h2>
          <?php if (empty($upcomingTrips)): ?>
            <div class="empty-state">Hiện tại chưa có sự kiện sắp tới nào.</div>
          <?php else: ?>
            <div class="events-grid">
              <?php foreach ($upcomingTrips as $trip): ?>
                <div class="event-card">
                  <img
                    src="<?php echo htmlspecialchars($trip['image'] ?? 'https://via.placeholder.com/320x200.png?text=' . urlencode($trip['name'])); ?>"
                    alt="<?php echo htmlspecialchars($trip['name']); ?>" class="event-image">
                  <div class="event-content">
                    <h3 class="event-title"><?php echo htmlspecialchars($trip['name']); ?></h3>
                    <p class="event-info">
                      <i class="fas fa-calendar"></i>
                      <?php echo htmlspecialchars(date('d/m/Y', strtotime($trip['date']))); ?>
                      <?php echo htmlspecialchars($trip['time']); ?>
                    </p>
                    <p class="event-info">
                      <i class="fas fa-map-marker-alt"></i>
                      <?php echo htmlspecialchars($trip['location']); ?>
                    </p>
                    <p class="event-description"><?php echo htmlspecialchars($trip['description']); ?></p>
                    <div class="event-actions">
                      <?php if (!$isAdmin): ?>
                        <?php if ($isLoggedIn): ?>
                          <a href="http://localhost:85/TuThien/trip_details.php?trip_id=<?php echo htmlspecialchars($trip['id']); ?>"
                            class="event-join-btn">Đăng ký tham gia</a>
                        <?php else: ?>
                          <a href="login.php" class="event-join-btn">Đăng nhập để đăng ký</a>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <div id="past" class="tab-content">
          <h2 class="section-title"><i class="fas fa-history"></i> Sự kiện đã qua</h2>
          <?php if (empty($pastTrips)): ?>
            <div class="empty-state">Hiện tại chưa có sự kiện nào đã qua.</div>
          <?php else: ?>
            <div class="events-grid">
              <?php foreach ($pastTrips as $trip): ?>
                <div class="event-card">
                  <img
                    src="<?php echo htmlspecialchars($trip['image'] ?? 'https://via.placeholder.com/320x200.png?text=' . urlencode($trip['name'])); ?>"
                    alt="<?php echo htmlspecialchars($trip['name']); ?>" class="event-image">
                  <div class="event-content">
                    <h3 class="event-title"><?php echo htmlspecialchars($trip['name']); ?></h3>
                    <p class="event-info">
                      <i class="fas fa-calendar"></i>
                      <?php echo htmlspecialchars(date('d/m/Y', strtotime($trip['date']))); ?>
                      <?php echo htmlspecialchars($trip['time']); ?>
                    </p>
                    <p class="event-info">
                      <i class="fas fa-map-marker-alt"></i>
                      <?php echo htmlspecialchars($trip['location']); ?>
                    </p>
                    <p class="event-description"><?php echo htmlspecialchars($trip['description']); ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <footer>
    <div class="footer-content">
      <div class="footer-section">
        <h3>Về HopeLink</h3>
        <p>HopeLink là nền tảng kết nối tình nguyện viên với các tổ chức từ thiện, lan tỏa yêu thương và xây dựng cộng
          đồng bền vững.</p>
      </div>
      <div class="footer-section">
        <h3>Liên kết nhanh</h3>
        <a href="index.php">Trang chủ</a>
        <a href="index.php#events">Sự kiện</a>
        <a href="charity_travel.php">Chuyến đi từ thiện</a>
        <a href="#lienhe">Liên hệ</a>
      </div>
      <div class="footer-section">
        <h3>Liên hệ</h3>
        <p>Email: <a href="mailto:support@hopelink.org">support@hopelink.org</a></p>
        <p>Hotline: 1800-1234</p>
        <p>Địa chỉ: 123 Đường Từ Thiện, Hà Nội</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 HopeLink. Mọi quyền được bảo lưu.</p>
    </div>
  </footer>

  <script>
    function switchTab(tabId) {
      document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
      document.querySelector(`.tab[onclick="switchTab('${tabId}')"]`).classList.add('active');
      document.getElementById(tabId).classList.add('active');
    }

    // Animation for event cards
    const eventCards = document.querySelectorAll('.event-card');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, { threshold: 0.2 });

    eventCards.forEach(card => {
      observer.observe(card);
    });

    // Toggle dropdown on mobile
    const dropdown = document.querySelector('.dropdown');
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    dropdownToggle.addEventListener('click', (e) => {
      if (window.innerWidth <= 768) {
        e.preventDefault();
        dropdown.classList.toggle('active');
      }
    });

    // User dropdown toggle
    document.addEventListener('DOMContentLoaded', function () {
      const userBtn = document.querySelector('.user-btn');
      const userInfo = document.querySelector('.user-info');

      if (userBtn && userInfo) {
        userBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          userInfo.classList.toggle('active');
        });

        document.addEventListener('click', function (e) {
          if (!userInfo.contains(e.target)) {
            userInfo.classList.remove('active');
          }
        });
      }
    });

    // Handle logout
    async function handleLogout(event, csrfToken) {
      event.preventDefault();
      try {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        const response = await fetch('logout.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
        if (result.success) {
          window.location.href = result.redirect;
        } else {
          alert('Đăng xuất thất bại. Vui lòng thử lại.');
        }
      } catch (error) {
        alert('Đã xảy ra lỗi khi đăng xuất.');
      }
    }

    // Update user balance
    async function updateUserBalance() {
      try {
        const response = await fetch('config.php?get_user_info=1');
        const result = await response.json();
        if (result.success && result.user.balance !== undefined) {
          const balanceElement = document.getElementById('user-balance');
          balanceElement.textContent = formatCurrency(result.user.balance);
        }
      } catch (error) {
        console.error('Error updating balance:', error);
      }
    }

    function formatCurrency(amount) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
      }).format(amount);
    }

    document.addEventListener('DOMContentLoaded', function () {
      updateUserBalance();
      const userBtn = document.querySelector('.user-btn');
      if (userBtn) {
        userBtn.addEventListener('click', updateUserBalance);
      }
    });
  </script>
</body>

</html>