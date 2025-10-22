<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إدارة الطلبات - لوحة التحكم</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
            direction: rtl;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            color: #495057;
            padding: 15px 10px;
        }
        
        .table td {
            padding: 12px 10px;
            vertical-align: middle;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 20px;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
            color: white;
        }
        
        .btn-refresh {
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            border: none;
            border-radius: 8px;
            color: white;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.4);
            color: white;
        }
        
        .status-pending { background-color: #ffc107; }
        .status-paid { background-color: #28a745; }
        .status-shipped { background-color: #17a2b8; }
        .status-delivered { background-color: #6f42c1; }
        .status-cancelled { background-color: #dc3545; }
        .status-awaiting_payment { background-color: #fd7e14; }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .search-box {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .search-box:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shopping-cart me-2"></i>
                لوحة التحكم - إدارة الطلبات
            </a>
            <button class="btn btn-refresh" onclick="loadOrders()">
                <i class="fas fa-sync-alt me-2"></i>
                تحديث البيانات
            </button>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stats-card text-center">
                    <div class="stats-number" id="totalOrders">0</div>
                    <div>إجمالي الطلبات</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card text-center">
                    <div class="stats-number" id="pendingOrders">0</div>
                    <div>قيد الانتظار</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card text-center">
                    <div class="stats-number" id="paidOrders">0</div>
                    <div>مدفوعة</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card text-center">
                    <div class="stats-number" id="shippedOrders">0</div>
                    <div>مشحونة</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card text-center">
                    <div class="stats-number" id="deliveredOrders">0</div>
                    <div>مسلمة</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card text-center">
                    <div class="stats-number" id="cancelledOrders">0</div>
                    <div>ملغية</div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filter-section">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">البحث</label>
                    <input type="text" class="form-control search-box" id="searchInput" placeholder="البحث برقم الطلب، اسم العميل، أو رقم الهاتف...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">حالة الطلب</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">جميع الحالات</option>
                        <option value="pending">قيد الانتظار</option>
                        <option value="awaiting_payment">في انتظار الدفع</option>
                        <option value="paid">مدفوع</option>
                        <option value="shipped">مشحون</option>
                        <option value="delivered">مسلم</option>
                        <option value="cancelled">ملغي</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="dateFrom">
                </div>
                <div class="col-md-2">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="dateTo">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" onclick="applyFilters()">
                        <i class="fas fa-filter me-2"></i>
                        تطبيق الفلاتر
                    </button>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    قائمة الطلبات
                </h5>
            </div>
            <div class="card-body p-0">
                <!-- Loading Spinner -->
                <div class="loading" id="loadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2">جاري تحميل البيانات...</p>
                </div>

                <!-- Alert Messages -->
                <div id="alertContainer"></div>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>رقم الهاتف</th>
                                <th>المبلغ الإجمالي</th>
                                <th>الحالة</th>
                                <th>تاريخ الطلب</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <!-- Orders will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center p-3">
                    <div id="paginationInfo"></div>
                    <nav>
                        <ul class="pagination mb-0" id="paginationLinks">
                            <!-- Pagination links will be loaded here -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد الحذف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف هذا الطلب؟</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>تحذير:</strong> هذا الإجراء لا يمكن التراجع عنه!
                    </div>
                    <div id="orderDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-2"></i>
                        حذف الطلب
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global variables
        let currentPage = 1;
        let currentOrderId = null;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadOrders();
            
            // Add event listeners
            document.getElementById('searchInput').addEventListener('input', debounce(applyFilters, 500));
            document.getElementById('statusFilter').addEventListener('change', applyFilters);
            document.getElementById('dateFrom').addEventListener('change', applyFilters);
            document.getElementById('dateTo').addEventListener('change', applyFilters);
        });

        // Debounce function for search input
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Load orders from API
        async function loadOrders(page = 1) {
            showLoading(true);
            hideAlert();

            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: 15
                });

                // Add filters
                const search = document.getElementById('searchInput').value;
                const status = document.getElementById('statusFilter').value;
                const dateFrom = document.getElementById('dateFrom').value;
                const dateTo = document.getElementById('dateTo').value;

                if (search) params.append('search', search);
                if (status) params.append('status', status);
                if (dateFrom) params.append('date_from', dateFrom);
                if (dateTo) params.append('date_to', dateTo);

                const response = await fetch(`/api/v1/temp-orders?${params}`);
                const data = await response.json();

                if (data.success) {
                    displayOrders(data.data.orders);
                    displayStatistics(data.data.summary);
                    displayPagination(data.data.orders);
                    currentPage = page;
                } else {
                    showAlert('خطأ في تحميل البيانات: ' + data.message, 'danger');
                }
            } catch (error) {
                console.error('Error loading orders:', error);
                showAlert('حدث خطأ في الاتصال بالخادم', 'danger');
            } finally {
                showLoading(false);
            }
        }

        // Display orders in table
        function displayOrders(ordersData) {
            const tbody = document.getElementById('ordersTableBody');
            tbody.innerHTML = '';

            if (!ordersData.data || ordersData.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد طلبات لعرضها</p>
                        </td>
                    </tr>
                `;
                return;
            }

            ordersData.data.forEach(order => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <strong>${order.order_number}</strong>
                    </td>
                    <td>${order.customer_name}</td>
                    <td>${order.customer_phone}</td>
                    <td>
                        <strong>${parseFloat(order.total_amount).toFixed(3)} ${order.currency}</strong>
                    </td>
                    <td>
                        <span class="badge status-${order.status}">
                            ${getStatusText(order.status)}
                        </span>
                    </td>
                    <td>${formatDate(order.created_at)}</td>
                    <td>
                        <button class="btn btn-delete btn-sm" onclick="confirmDelete(${order.id}, '${order.order_number}', '${order.customer_name}')">
                            <i class="fas fa-trash"></i>
                            حذف
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Display statistics
        function displayStatistics(summary) {
            document.getElementById('totalOrders').textContent = summary.total_orders || 0;
            document.getElementById('pendingOrders').textContent = summary.pending_orders || 0;
            document.getElementById('paidOrders').textContent = summary.paid_orders || 0;
            document.getElementById('shippedOrders').textContent = summary.shipped_orders || 0;
            document.getElementById('deliveredOrders').textContent = summary.delivered_orders || 0;
            document.getElementById('cancelledOrders').textContent = summary.cancelled_orders || 0;
        }

        // Display pagination
        function displayPagination(ordersData) {
            const paginationInfo = document.getElementById('paginationInfo');
            const paginationLinks = document.getElementById('paginationLinks');

            // Update info
            const from = ordersData.from || 0;
            const to = ordersData.to || 0;
            const total = ordersData.total || 0;
            paginationInfo.innerHTML = `عرض ${from} إلى ${to} من أصل ${total} طلب`;

            // Update pagination links
            paginationLinks.innerHTML = '';

            if (ordersData.last_page > 1) {
                // Previous button
                if (ordersData.current_page > 1) {
                    paginationLinks.innerHTML += `
                        <li class="page-item">
                            <a class="page-link" href="#" onclick="loadOrders(${ordersData.current_page - 1})">السابق</a>
                        </li>
                    `;
                }

                // Page numbers
                for (let i = Math.max(1, ordersData.current_page - 2); i <= Math.min(ordersData.last_page, ordersData.current_page + 2); i++) {
                    paginationLinks.innerHTML += `
                        <li class="page-item ${i === ordersData.current_page ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="loadOrders(${i})">${i}</a>
                        </li>
                    `;
                }

                // Next button
                if (ordersData.current_page < ordersData.last_page) {
                    paginationLinks.innerHTML += `
                        <li class="page-item">
                            <a class="page-link" href="#" onclick="loadOrders(${ordersData.current_page + 1})">التالي</a>
                        </li>
                    `;
                }
            }
        }

        // Apply filters
        function applyFilters() {
            loadOrders(1);
        }

        // Confirm delete
        function confirmDelete(orderId, orderNumber, customerName) {
            currentOrderId = orderId;
            document.getElementById('orderDetails').innerHTML = `
                <strong>رقم الطلب:</strong> ${orderNumber}<br>
                <strong>العميل:</strong> ${customerName}
            `;
            deleteModal.show();
        }

        // Delete order
        document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
            if (!currentOrderId) return;

            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الحذف...';
            btn.disabled = true;

            try {
                const response = await fetch(`/api/v1/temp-orders/${currentOrderId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    deleteModal.hide();
                    showAlert('تم حذف الطلب بنجاح', 'success');
                    loadOrders(currentPage);
                } else {
                    showAlert('خطأ في حذف الطلب: ' + data.message, 'danger');
                }
            } catch (error) {
                console.error('Error deleting order:', error);
                showAlert('حدث خطأ في حذف الطلب', 'danger');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
                currentOrderId = null;
            }
        });

        // Utility functions
        function getStatusText(status) {
            const statusMap = {
                'pending': 'قيد الانتظار',
                'awaiting_payment': 'في انتظار الدفع',
                'paid': 'مدفوع',
                'shipped': 'مشحون',
                'delivered': 'مسلم',
                'cancelled': 'ملغي'
            };
            return statusMap[status] || status;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function showLoading(show) {
            document.getElementById('loadingSpinner').style.display = show ? 'block' : 'none';
        }

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show m-3" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }

        function hideAlert() {
            document.getElementById('alertContainer').innerHTML = '';
        }
    </script>
</body>
</html>