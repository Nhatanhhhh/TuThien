<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
  header('Location: ../login.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard - HopeLink</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/header.css">
  <link rel="stylesheet" href="../styles/dashboard_staff.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <div class="sidebar">
    <div class="logo">
      <a href="../index.php"
        style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: white;">
        <img src="../images/hopelink-logo.svg" alt="HopeLink Logo">
        <h1>HopeLink</h1>
      </a>
    </div>
    <div class="nav-item active" onclick="window.location.href='dashboard.php'">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </div>
    <div class="nav-item" onclick="window.location.href='manage_events.php'">
      <i class="fas fa-calendar-alt"></i> Quản lý sự kiện
    </div>
    <div class="nav-item" onclick="window.location.href='manage_participants.php'">
      <i class="fas fa-users"></i> Quản lý người tham gia
    </div>
    <div class="nav-item" onclick="logout()">
      <i class="fas fa-sign-out-alt"></i> Đăng xuất
    </div>
  </div>

  <div class="main-content">
    <h2 class="section-title">Staff Dashboard</h2>
    <div class="dashboard-grid">
      <div class="dashboard-card">
        <h3><i class="fas fa-calendar-check"></i> Chuyến đi đang diễn ra</h3>
        <div class="stat-number" id="active-events">0</div>
        <p>Chuyến đi đang hoạt động</p>
      </div>
      <div class="dashboard-card">
        <h3><i class="fas fa-users"></i> Người tham gia</h3>
        <div class="stat-number" id="total-participants">0</div>
        <p>Tổng số người tham gia</p>
      </div>
      <div class="dashboard-card">
        <h3><i class="fas fa-ticket-alt"></i> Vé đã bán</h3>
        <div class="stat-number" id="total-tickets">0</div>
        <p>Tổng số vé đã bán</p>
      </div>
      <div class="dashboard-card">
        <h3><i class="fas fa-hand-holding-heart"></i> Tổng quyên góp</h3>
        <div class="stat-number" id="total-donations">0</div>
        <p>Tổng số tiền quyên góp</p>
      </div>
    </div>

    <div class="charts-container">
      <div class="chart-container">
        <div class="chart-header">
          <h3>Thống kê vé bán</h3>
          <div class="time-filter">
            <select id="ticketsTimeSelect" class="time-select">
              <option value="day">Theo ngày</option>
              <option value="month">Theo tháng</option>
              <option value="year">Theo năm</option>
            </select>
          </div>
        </div>
        <div class="chart-wrapper">
          <canvas id="ticketsChart"></canvas>
        </div>
      </div>
      <div class="chart-container">
        <div class="chart-header">
          <h3>Thống kê quyên góp</h3>
          <div class="time-filter">
            <select id="donationsTimeSelect" class="time-select">
              <option value="day">Theo ngày</option>
              <option value="month">Theo tháng</option>
              <option value="year">Theo năm</option>
            </select>
          </div>
        </div>
        <div class="chart-wrapper">
          <canvas id="donationsChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <script>
    let ticketsChart = null;
    let donationsChart = null;

    function formatDate(dateStr, timeFilter) {
      const [year, month, day] = dateStr.split('-');
      switch (timeFilter) {
        case 'day':
          return `${day}/${month}/${year}`;
        case 'year':
          return year;
        default:
          return `${month}/${year}`;
      }
    }

    async function loadTicketsStats(timeFilter = 'day') {
      const response = await fetch(`get_dashboard_stats.php?time_filter=${timeFilter}`);
      const data = await response.json();
      if (data.success) {
        document.getElementById('active-events').textContent = data.active_events;
        document.getElementById('total-participants').textContent = data.total_participants;
        document.getElementById('total-tickets').textContent = data.total_tickets;
        document.getElementById('total-donations').textContent = new Intl.NumberFormat('vi-VN', {
          style: 'currency',
          currency: 'VND'
        }).format(data.total_donations);

        // Chỉ cập nhật dropdown của vé bán
        document.getElementById('ticketsTimeSelect').value = timeFilter;

        // Vẽ lại biểu đồ vé bán
        const ticketsCtx = document.getElementById('ticketsChart').getContext('2d');
        const timeLabels = data.ticket_stats.map(item => formatDate(item.time_period, timeFilter));
        const ticketCounts = data.ticket_stats.map(item => item.ticket_count);

        if (ticketsChart) ticketsChart.destroy();

        ticketsChart = new Chart(ticketsCtx, {
          type: 'bar',
          data: {
            labels: timeLabels,
            datasets: [{
              label: 'Số vé đã bán',
              data: ticketCounts,
              backgroundColor: 'rgba(54, 162, 235, 0.5)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
              padding: {
                top: 10,
                right: 10,
                bottom: 10,
                left: 10
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  stepSize: 1
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            },
            plugins: {
              legend: {
                display: false
              }
            }
          }
        });
      }
    }

    async function loadDonationsStats(timeFilter = 'day') {
      const response = await fetch(`get_dashboard_stats.php?time_filter=${timeFilter}`);
      const data = await response.json();
      if (data.success) {
        // Vẽ lại biểu đồ quyên góp
        const donationsCtx = document.getElementById('donationsChart').getContext('2d');
        const donationLabels = data.donation_stats.map(item => formatDate(item.time_period, timeFilter));
        const donationAmounts = data.donation_stats.map(item => item.total_amount);

        if (donationsChart) donationsChart.destroy();

        donationsChart = new Chart(donationsCtx, {
          type: 'bar',
          data: {
            labels: donationLabels,
            datasets: [{
              label: 'Số tiền quyên góp',
              data: donationAmounts,
              backgroundColor: 'rgba(75, 192, 192, 0.5)',
              borderColor: 'rgba(75, 192, 192, 1)',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
              padding: {
                top: 10,
                right: 10,
                bottom: 10,
                left: 10
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function (value) {
                    return new Intl.NumberFormat('vi-VN', {
                      style: 'currency',
                      currency: 'VND',
                      maximumFractionDigits: 0
                    }).format(value);
                  }
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            },
            plugins: {
              legend: {
                display: false
              }
            }
          }
        });

        // Chỉ cập nhật dropdown của quyên góp
        document.getElementById('donationsTimeSelect').value = timeFilter;
      }
    }

    // Gắn sự kiện cho từng dropdown
    document.getElementById('ticketsTimeSelect').addEventListener('change', function() {
      loadTicketsStats(this.value);
    });
    document.getElementById('donationsTimeSelect').addEventListener('change', function() {
      loadDonationsStats(this.value);
    });

    // Khi trang vừa load, hiển thị mặc định
    document.addEventListener('DOMContentLoaded', () => {
      loadTicketsStats('day');
      loadDonationsStats('day');
    });

    function logout() {
      const formData = new FormData();
      formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>');
      fetch('../logout.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            window.location.href = data.redirect;
          } else {
            alert(data.message || 'Đăng xuất thất bại. Vui lòng thử lại.');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Đã xảy ra lỗi khi đăng xuất.');
        });
    }
  </script>
</body>

</html>