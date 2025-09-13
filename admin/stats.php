<?php
session_start();
// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
// Đảm bảo CSRF token tồn tại
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê - HopeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container {
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #4a5568;
            color: #fff;
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        .summary-section {
            margin-top: 30px;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .summary-section h3 {
            margin-bottom: 15px;
            color: #2d3748;
        }

        .chart-container {
            margin-top: 20px;
            position: relative;
            height: 400px;
            width: 100%;
        }

        select {
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .export-buttons {
            margin-top: 10px;
        }

        .export-buttons button {
            padding: 8px 16px;
            margin-right: 10px;
            background-color: #4a5568;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .export-buttons button:hover {
            background-color: #2d3748;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <div class="nav-item" onclick="window.location.href='dashboard.php'">
            <i class="fas fa-users"></i> Quản lý tài khoản
        </div>
        <div class="nav-item" onclick="window.location.href='manage_events.php'">
            <i class="fas fa-calendar-alt"></i> Quản lý sự kiện
        </div>
        <div class="nav-item" onclick="window.location.href='manage_participants.php'">
            <i class="fas fa-plane"></i> Quản lý chuyến bay
        </div>
        <div class="nav-item active" onclick="window.location.href='stats.php'">
            <i class="fas fa-chart-bar"></i> Thống kê
        </div>
        <!-- Chuyển từ modal sang trang mới -->
        <div class="nav-item" onclick="window.location.href='add_staff_page.php'">
            <i class="fas fa-user-plus"></i> Thêm Nhân Viên
        </div>
        <div class="nav-item" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </div>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Thống kê Chuyến Đi</h1>
        </div>
        <div class="stats-container">
            <div class="table-container">
                <h2>Danh sách Chuyến Đi</h2>
                <div class="export-buttons" style="margin-bottom: 20px;">
                    <button onclick="exportToPDF()" style="background-color: #EF4036;">Xuất PDF</button>
                    <button onclick="exportToExcel()" style="background-color: #016E3C;">Xuất Excel</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Tên Chuyến Đi</th>
                            <th>Số Vé Tham Gia</th>
                            <th>Trạng Thái</th>
                            <th>Thời Gian Hủy</th>
                            <th>Lý Do Hủy</th>
                            <th>Hoàn Tiền</th>
                            <th>Số Tiền Hoàn</th>
                            <th>Người Nhận Hoàn Tiền</th>
                        </tr>
                    </thead>
                    <tbody id="trips-table-body">
                        <!-- Dữ liệu chuyến đi sẽ được thêm vào đây -->
                    </tbody>
                </table>
            </div>
            <div class="summary-section">
                <h2>Thống kê Theo Thời Gian</h2>
                <div id="yearly-stats">
                    <h3>Theo Năm</h3>
                    <div id="yearly-data"></div>
                </div>
                <div id="quarterly-stats">
                    <h3>Theo Quý</h3>
                    <div id="quarterly-data"></div>
                </div>
                <div id="monthly-stats">
                    <h3>Theo Tháng</h3>
                    <div id="monthly-data"></div>
                </div>
            </div>
            <div class="summary-section">
                <h2>Trực Quan Hóa Dữ Liệu</h2>
                <select id="chart-type" onchange="updateChart()">
                    <option value="pie">Biểu đồ tròn (Tiền quyên góp)</option>
                    <option value="bar">Biểu đồ cột (Số Vé Tham Gia)</option>
                    <option value="line">Biểu đồ đường (Xu hướng thời gian)</option>
                </select>
                <div class="chart-container">
                    <canvas id="myChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <script>
        let myChart;
        const colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEEAD',
            '#D4A5A5', '#9B59B6', '#3498DB', '#E74C3C', '#2ECC71'
        ];

        function loadTripStats() {
            fetch('get_trip_stats.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('trips-table-body');
                    tbody.innerHTML = '';
                    if (data.success && data.trips.length > 0) {
                        data.trips.forEach(trip => {
                            const status = trip.is_cancelled ? 'Đã hủy' : (trip.is_active ? 'Đang hoạt động' : 'Không hoạt động');
                            const refundStatus = trip.refund_status || 'Chưa hoàn tiền';
                            const refundAmount = trip.refunded_amount ? new Intl.NumberFormat('vi-VN').format(trip.refunded_amount) + ' VNĐ' : '0 VNĐ';
                            const recipients = trip.refunded_users.length > 0 ? [...new Set(trip.refunded_users)].join(', ') : 'Không có';
                            const cancelledAt = trip.cancelled_at || 'Chưa hủy';
                            const cancellationReason = trip.cancellation_reason || 'Không có';
                            tbody.innerHTML += `
                                <tr>
                                    <td>${trip.name}</td>
                                    <td>${trip.ticket_count || 0}</td>
                                    <td>${status}</td>
                                    <td>${cancelledAt}</td>
                                    <td>${cancellationReason}</td>
                                    <td>${refundStatus}</td>
                                    <td>${refundAmount}</td>
                                    <td>${recipients}</td>
                                </tr>
                            `;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="8">Không có dữ liệu chuyến đi.</td></tr>';
                    }
                    updateChartData(data.trips);
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('trips-table-body').innerHTML = '<tr><td colspan="8">Lỗi khi tải dữ liệu.</td></tr>';
                });
        }

        function loadTimeStats() {
            fetch('get_time_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const yearlyData = document.getElementById('yearly-data');
                        yearlyData.innerHTML = data.yearly.map(item => `
                            <div class="summary-item">Năm ${item.year}: ${item.trip_count} chuyến, Tổng tiền: ${formatCurrency(item.total_amount)} VNĐ</div>
                        `).join('');

                        const quarterlyData = document.getElementById('quarterly-data');
                        quarterlyData.innerHTML = data.quarterly.map(item => `
                            <div class="summary-item">Năm ${item.year} Quý ${item.quarter}: ${item.trip_count} chuyến, Tổng tiền: ${formatCurrency(item.total_amount)} VNĐ</div>
                        `).join('');

                        const monthlyData = document.getElementById('monthly-data');
                        monthlyData.innerHTML = data.monthly.map(item => `
                            <div class="summary-item">Tháng ${item.month}/${item.year}: ${item.trip_count} chuyến, Tổng tiền: ${formatCurrency(item.total_amount)} VNĐ</div>
                        `).join('');
                    } else {
                        console.error('Error loading time stats:', data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount);
        }

        function updateChartData(trips) {
            if (myChart) myChart.destroy();

            const chartType = document.getElementById('chart-type').value;
            const ctx = document.getElementById('myChart').getContext('2d');

            let labels, data, type, backgroundColors, borderColors;
            if (chartType === 'pie') {
                labels = trips.map(trip => trip.name);
                data = trips.map(trip => trip.total_amount || 0);
                type = 'pie';
                backgroundColors = colors.slice(0, trips.length);
                borderColors = backgroundColors;
            } else if (chartType === 'bar') {
                labels = trips.map(trip => trip.name);
                data = trips.map(trip => trip.ticket_count || 0);
                type = 'bar';
                backgroundColors = colors.slice(0, trips.length);
                borderColors = backgroundColors;
            } else if (chartType === 'line') {
                labels = trips.map(trip => trip.name);
                data = trips.map(trip => trip.total_amount || 0);
                type = 'line';
                backgroundColors = ['rgba(54, 162, 235, 0.2)'];
                borderColors = ['rgba(54, 162, 235, 1)'];
            }

            myChart = new Chart(ctx, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: chartType === 'pie' ? 'Tiền quyên góp (VNĐ)' : (chartType === 'bar' ? 'Số Vé Tham Gia' : 'Tổng tiền (VNĐ)'),
                        data: data,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 2,
                        fill: chartType === 'line' ? true : false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: type !== 'pie' ? {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: chartType === 'bar' ? 'Số Vé Tham Gia' : 'Tổng tiền (VNĐ)'
                            },
                            ticks: {
                                callback: function(value) {
                                    if (chartType !== 'bar') {
                                        return new Intl.NumberFormat('vi-VN').format(value);
                                    }
                                    return value;
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Chuyến đi'
                            }
                        }
                    } : {},
                    plugins: {
                        legend: {
                            display: type === 'pie',
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.dataset.label.includes('tiền')) {
                                        label += new Intl.NumberFormat('vi-VN').format(context.raw) + ' VNĐ';
                                    } else {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateChart() {
            fetch('get_trip_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) updateChartData(data.trips);
                })
                .catch(error => console.error('Error:', error));
        }

        function exportToPDF() {
            fetch('export_to_pdf.php')
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'thong_ke_chuyen_di.pdf';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                })
                .catch(error => console.error('Error exporting to PDF:', error));
        }

        function exportToExcel() {
            fetch('export_to_excel.php')
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'thong_ke_chuyen_di.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                })
                .catch(error => console.error('Error exporting to Excel:', error));
        }

        function logout() {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>');
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

        function showSection(section) {
            console.log(`Show section: ${section}`);
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadTripStats();
            loadTimeStats();
            updateChart();
        });
    </script>
</body>

</html>