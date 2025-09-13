<?php
session_start();
if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? '';

// Kiểm tra vai trò admin
$isAdmin = $role === 'admin';

error_log("Session Debug - isLoggedIn: " . ($isLoggedIn ? 'true' : 'false'));
error_log("Session Debug - username: " . $username);
error_log("Session Debug - email: " . $email);
error_log("Session Debug - role: " . $role);

if (!$isLoggedIn) {
  error_log("User not logged in, dropdown will not render.");
}

// Check if the user is accessing the root URL, but not index.php
$requestUri = trim($_SERVER['REQUEST_URI'], '/');
$basePath = 'TuThien';
$showError = ($requestUri === $basePath || $requestUri === "$basePath/") && basename($_SERVER['PHP_SELF']) !== 'index.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HopeLink - Kết nối Tình nguyện viên & Tổ chức Từ thiện</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/header.css">
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
          <?php if ($role === 'admin'): ?>
            <a href="admin/dashboard.php" class="admin-btn">
              <i class="fas fa-user-shield"></i> Quản lý
            </a>
          <?php elseif ($role === 'staff'): ?>
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
                <a href="#" class="dropdown-item" onclick="handleLogout(event, '<?php echo $_SESSION['csrf_token']; ?>')"><i
                    class="fas fa-sign-out-alt"></i> Đăng xuất</a>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main>
    <section id="hero">
      <div class="hero-content">
        <h2><i class="fas fa-heart" style="font-size: 24px;"></i> Lan tỏa yêu thương, kết nối cộng đồng</h2>
        <p>Tham gia HopeLink để lan tỏa yêu thương, hỗ trợ cộng đồng và tạo nên sự thay đổi tích cực.</p>
        <a href="charity_travel.php" class="explore-btn"><i class="fas fa-arrow-right"></i> Khám phá ngay</a>
      </div>
    </section>

    <section id="carousel" style="padding: 100px 20px;">
      <h2 class="section-title"><i class="fas fa-star" style="font-size: 24px;"></i> Câu chuyện truyền cảm hứng</h2>
      <div class="carousel">
        <div class="carousel-track">
          <div class="carousel-item">
            <img src="images/batch_trai-nghiem-nen-thu-o-tay-nguyen-cong-chieng.jpg1.jpg" alt="Chuyện 1">
            <h3>Hành trình trao yêu thương</h3>
            <p>Chuyến đi từ thiện đã mang lại niềm vui và hy vọng cho hàng trăm trẻ em vùng cao.</p>
          </div>
          <div class="carousel-item">
            <img src="images/R.jpg" alt="Chuyện 2">
            <h3>Ngày hội xanh</h3>
            <p>Hơn 500 tình nguyện viên cùng nhau trồng 1000 cây xanh, góp phần bảo vệ môi trường.</p>
          </div>
          <div class="carousel-item">
            <img src="images/5-cach-phat-trien-ban-than.jpg" alt="Chuyện 3">
            <h3>Tri thức cho tương lai</h3>
            <p>Chương trình trao tặng sách đã mang tri thức đến với trẻ em vùng sâu vùng xa.</p>
          </div>
          <div class="carousel-item">
            <img src="images/hienmau12-11-2022_20221112165453.jpg" alt="Chuyện 4">
            <h3>Hỗ trợ y tế cộng đồng</h3>
            <p>Chương trình khám sức khỏe miễn phí đã giúp hàng nghìn người dân vùng khó khăn.</p>
          </div>
          <div class="carousel-item">
            <img src="images/5d5137d5-1850-4dc1-b373-13c47a14c63c.jpg" alt="Chuyện 5">
            <h3>Xây dựng trường học</h3>
            <p>Dự án xây trường học mang lại cơ hội học tập cho trẻ em ở vùng sâu vùng xa.</p>
          </div>
        </div>
        <button class="carousel-btn prev"><i class="fas fa-chevron-left"></i></button>
        <button class="carousel-btn next"><i class="fas fa-chevron-right"></i></button>
      </div>
    </section>

    <section id="events" style="padding: 100px 20px; background: #e6fffa;">
      <h2 class="section-title"><i class="fas fa-calendar-alt" style="font-size: 24px;"></i> Sự kiện sắp tới</h2>
      <div class="events-grid">
        <?php
        // Kết nối database và lấy dữ liệu sự kiện
        require_once 'config.php';

        // Lấy 3 sự kiện sắp tới (ngày >= ngày hiện tại, đang hoạt động, chưa xóa)
        $stmt = $pdo->query("SELECT * FROM trips WHERE date >= CURDATE() AND is_active = 1 AND is_deleted = 0 ORDER BY date ASC LIMIT 3");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($events) > 0) {
          foreach ($events as $event) {
            // Xử lý đường dẫn hình ảnh
            $imagePath = !empty($event['image']) ? htmlspecialchars($event['image']) : 'images/default-event.jpg';

            // Format ngày tháng
            $eventDate = date('d/m/Y', strtotime($event['date']));
            $eventTime = date('H:i', strtotime($event['time']));

            echo '
        <div class="card">
          <img src="' . $imagePath . '" alt="' . htmlspecialchars($event['name']) . '">
          <div class="card-content">
            <h3>' . htmlspecialchars($event['name']) . '</h3>
            <p>' . htmlspecialchars($event['description']) . '</p>
            <div class="event-details">
              <span><i class="fas fa-calendar"></i> ' . $eventDate . '</span>
              <span><i class="fas fa-clock"></i> ' . $eventTime . '</span>
              <span><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($event['location']) . '</span>
            </div>
            <a href="' . ($role === 'admin' ? 'admin/manage_events.php' : ($role === 'staff' ? 'staff/manage_events.php' : ($isLoggedIn ? 'trip_details.php?trip_id=' . $event['id'] : 'login.php'))) . '" class="event-join-btn">
              <i class="fas fa-arrow-right"></i> ' . 
              ($role === 'admin' ? 'Quản lý sự kiện' : 
               ($role === 'staff' ? 'Quản lý sự kiện' : 
                ($isLoggedIn ? 'Tham gia ngay' : 'Đăng nhập để tham gia'))) . 
            '</a>
          </div>
        </div>';
          }
        } else {
          echo '<p>Hiện không có sự kiện nào sắp diễn ra.</p>';
        }
        ?>
      </div>
    </section>

    <section id="news" style="padding: 100px 20px;">
      <h2 class="section-title"><i class="fas fa-newspaper" style="font-size: 24px;"></i> Tin tức mới nhất</h2>
      <div class="news-grid">
        <div class="card">
          <img src="images/batch_trai-nghiem-nen-thu-o-tay-nguyen-cong-chieng.jpg1.jpg" alt="Tin tức 1">
          <div class="card-content">
            <h3>HopeLink đạt cột mốc 1000 tình nguyện viên</h3>
            <p>Chúng tôi tự hào thông báo rằng HopeLink đã kết nối thành công 1000 tình nguyện viên với các tổ chức từ
              thiện trên cả nước.</p>
            <p><strong>Ngày đăng:</strong> 01/11/2025</p>
          </div>
        </div>
        <div class="card">
          <img src="images/iii.png" alt="Tin tức 2">
          <div class="card-content">
            <h3>Chương trình từ thiện mùa đông 2025</h3>
            <p>Chuẩn bị cho mùa đông ấm áp với chương trình quyên góp quần áo và chăn ấm cho người vô gia cư.</p>
            <p><strong>Ngày đăng:</strong> 05/11/2025</p>
          </div>
        </div>
        <div class="card">
          <img src="images/uuu.jpg" alt="Tin tức 3">
          <div class="card-content">
            <h3>Hợp tác với tổ chức quốc tế</h3>
            <p>HopeLink ký kết hợp tác với tổ chức từ thiện quốc tế để mở rộng các chương trình hỗ trợ cộng đồng.</p>
            <p><strong>Ngày đăng:</strong> 10/11/2025</p>
          </div>
        </div>
      </div>
    </section>
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
        <a href="#hero">Trang chủ</a>
        <a href="#events">Sự kiện</a>
        <a href="charity_travel.php">Chuyến đi từ thiện</a>
        <a href="#lienhe">Liên hệ</a>
      </div>
      <div class="footer-section">
        <h3>Liên hệ</h3>
        <p>Email: <a href="/cdn-cgi/l/email-protection#b99a96978d989a8db99196899c95909792d7968b9e">[email protected]</a>
        </p>
        <p>Hotline: 1800-1234</p>
        <p>Địa chỉ: 123 Đường Từ Thiện, Hà Nội</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 HopeLink. Mọi quyền được bảo lưu.</p>
    </div>
  </footer>

  <div id="notification-modal" class="modal">
    <div class="modal-content">
      <i id="modal-icon" class="modal-icon"></i>
      <p id="modal-message"></p>
      <button class="modal-close-btn">Đóng</button>
    </div>
  </div>

  <div id="login-required-modal" class="modal" style="display: none;">
    <div class="modal-content">
      <i class="fas fa-lock modal-icon"></i>
      <h3>Yêu cầu đăng nhập</h3>
      <p>Vui lòng đăng nhập để tiếp tục sử dụng tính năng này.</p>
      <div class="modal-buttons">
        <a href="login.php" class="modal-btn primary">Đăng nhập</a>
        <button class="modal-btn secondary modal-close-btn">Đóng</button>
      </div>
    </div>
  </div>

  <div id="error-modal" class="modal" style="display: none;">
    <div class="error-modal-content">
      <i class="fas fa-face-sad-tear error-icon"></i>
      <p id="error-message">Oops! Có vẻ bạn lạc đường rồi 😅</p>
      <p>Nhấn nút bên dưới để khám phá HopeLink nhé!</p>
      <a href="/TuThien/index.php" class="error-btn"><i class="fas fa-home"></i> Đi đến Trang chủ</a>
    </div>
  </div>

  <script>
    // Show error modal if accessing root URL
    <?php if ($showError): ?>
      document.getElementById('error-modal').style.display = 'flex';
      document.body.style.background = '#f7fafc';
      document.querySelector('header').style.display = 'none';
      document.querySelector('main').style.display = 'none';
      document.querySelector('footer').style.display = 'none';
    <?php endif; ?>

    // Function to show login required modal
    function showLoginRequiredModal() {
      const modal = document.getElementById('login-required-modal');
      modal.style.display = 'flex';
      
      // Close modal when clicking close button
      const closeBtn = modal.querySelector('.modal-close-btn');
      closeBtn.onclick = () => {
        modal.style.display = 'none';
      };
      
      // Close modal when clicking outside
      window.onclick = (event) => {
        if (event.target === modal) {
          modal.style.display = 'none';
        }
      };
    }

    // Add click handlers for protected links
    document.addEventListener('DOMContentLoaded', function() {
      const protectedLinks = document.querySelectorAll('a[href="donation.php"], a[href="profile.php"], a[href="donation_history.php"]');
      protectedLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          <?php if (!$isLoggedIn): ?>
            e.preventDefault();
            showLoginRequiredModal();
          <?php endif; ?>
        });
      });
    });

    // Carousel functionality
    const track = document.querySelector('.carousel-track');
    const items = document.querySelectorAll('.carousel-item');
    const prevBtn = document.querySelector('.carousel-btn.prev');
    const nextBtn = document.querySelector('.carousel-btn.next');
    let currentIndex = 0;

    function updateCarousel() {
      const itemWidth = items[0].offsetWidth + 20; // Including gap
      track.style.transform = `translateX(-${currentIndex * itemWidth}px)`;
    }

    nextBtn.addEventListener('click', () => {
      if (currentIndex < 4) { // Move to next story until story 5
        currentIndex++;
      } else {
        currentIndex = 0; // Loop back to story 1
      }
      updateCarousel();
    });

    prevBtn.addEventListener('click', () => {
      if (currentIndex > 0) { // Move to previous story
        currentIndex--;
      } else {
        currentIndex = 4; // Loop back to story 5
      }
      updateCarousel();
    });

    // Auto-play carousel
    let autoPlay = setInterval(() => {
      if (currentIndex < 4) { // Stop at index 4 (story 5)
        currentIndex++;
      } else {
        currentIndex = 0; // Reset to story 1
      }
      updateCarousel();
    }, 3000);

    // Pause on hover
    document.querySelector('.carousel').addEventListener('mouseenter', () => clearInterval(autoPlay));
    document.querySelector('.carousel').addEventListener('mouseleave', () => {
      autoPlay = setInterval(() => {
        if (currentIndex < 4) {
          currentIndex++;
        } else {
          currentIndex = 0;
        }
        updateCarousel();
      }, 3000);
    });

    // Animation for cards
    const cards = document.querySelectorAll('.card');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = 1;
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.2 });

    cards.forEach(card => {
      card.style.opacity = 0;
      card.style.transform = 'translateY(30px)';
      card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
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

    // Show notification modal
    function showNotification(message, isSuccess) {
      const modal = document.getElementById('notification-modal');
      const modalIcon = document.getElementById('modal-icon');
      const modalMessage = document.getElementById('modal-message');
      const modalCloseBtn = document.querySelector('.modal-close-btn');

      modalMessage.textContent = message;
      modalIcon.className = `modal-icon ${isSuccess ? 'success fas fa-check-circle' : 'error fas fa-exclamation-circle'}`;
      modal.style.display = 'flex';

      modalCloseBtn.onclick = () => {
        modal.style.display = 'none';
      };
    }

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
          showNotification('Lỗi', result.message || 'Đăng xuất thất bại. Vui lòng thử lại.', false);
        }
      } catch (error) {
        console.error('Logout error:', error);
        showNotification('Lỗi', 'Đã xảy ra lỗi khi đăng xuất.', false);
      }
    }

    // Hàm cập nhật số dư
    async function updateUserBalance() {
      try {
        const response = await fetch('config.php?get_user_info=1');
        const result = await response.json();

        if (result.success && result.user.balance !== undefined) {
          const balanceElement = document.getElementById('user-balance');
          // Format số tiền theo định dạng tiền tệ
          balanceElement.textContent = formatCurrency(result.user.balance);
        }
      } catch (error) {
        console.error('Error updating balance:', error);
      }
    }

    // Hàm format tiền tệ
    function formatCurrency(amount) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
      }).format(amount);
    }

    // Gọi hàm khi trang được tải
    document.addEventListener('DOMContentLoaded', function () {
      updateUserBalance();

      // Cập nhật lại khi dropdown được mở
      const userBtn = document.querySelector('.user-btn');
      if (userBtn) {
        userBtn.addEventListener('click', updateUserBalance);
      }
    });
  </script>
</body>

</html>