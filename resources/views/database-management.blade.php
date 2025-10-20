<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة قاعدة البيانات - مؤقت</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            margin: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }

        .content {
            padding: 30px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            font-size: 1.2rem;
            color: #666;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .table-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .table-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .table-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .table-count {
            color: #7f8c8d;
            margin-bottom: 15px;
        }

        .table-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .table-data {
            margin-top: 30px;
            display: none;
        }

        .table-data.active {
            display: block;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #e9ecef;
        }

        .data-table th {
            background: #34495e;
            color: white;
            font-weight: bold;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            text-align: center;
        }

        .close {
            color: #aaa;
            float: left;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            display: none;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .refresh-btn {
            position: fixed;
            bottom: 30px;
            left: 30px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            transform: scale(1.1);
            background: #229954;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ إدارة قاعدة البيانات</h1>
            <p>أداة مؤقتة لعرض وإدارة جداول قاعدة البيانات</p>
        </div>

        <div class="warning">
            ⚠️ تحذير: هذه أداة مؤقتة للتطوير فقط. استخدمها بحذر!
        </div>

        <div class="content">
            <div class="alert alert-success" id="successAlert"></div>
            <div class="alert alert-error" id="errorAlert"></div>

            <div class="loading" id="loading">
                🔄 جاري تحميل الجداول...
            </div>

            <div class="tables-grid" id="tablesGrid" style="display: none;">
                <!-- سيتم ملء الجداول هنا بواسطة JavaScript -->
            </div>

            <div class="table-data" id="tableData">
                <h3 id="tableTitle"></h3>
                <div id="tableInfo"></div>
                <table class="data-table" id="dataTable" style="display: none;">
                    <thead id="tableHead"></thead>
                    <tbody id="tableBody"></tbody>
                </table>
                <div class="pagination" id="pagination"></div>
            </div>
        </div>
    </div>

    <!-- Modal للتأكيد -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>تأكيد العملية</h3>
            <p id="confirmMessage"></p>
            <div style="margin-top: 20px;">
                <button class="btn btn-danger" id="confirmBtn">تأكيد</button>
                <button class="btn btn-primary" id="cancelBtn" style="margin-right: 10px;">إلغاء</button>
            </div>
        </div>
    </div>

    <button class="refresh-btn" onclick="loadTables()" title="تحديث">🔄</button>

    <script>
        // متغيرات عامة
        let currentTable = null;
        let currentOffset = 0;
        let currentLimit = 50;

        // تحميل الجداول عند بدء الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            loadTables();
        });

        // دالة تحميل قائمة الجداول
        async function loadTables() {
            showLoading(true);
            try {
                const response = await fetch('/api/v1/temp-db/tables');
                const data = await response.json();
                
                if (data.success) {
                    displayTables(data.tables);
                } else {
                    showError('خطأ في تحميل الجداول: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في الاتصال: ' + error.message);
            }
            showLoading(false);
        }

        // دالة عرض الجداول
        function displayTables(tables) {
            const grid = document.getElementById('tablesGrid');
            grid.innerHTML = '';
            
            tables.forEach(table => {
                const card = document.createElement('div');
                card.className = 'table-card';
                card.innerHTML = `
                    <div class="table-name">${table.name}</div>
                    <div class="table-count">📊 ${table.count} سجل</div>
                    <div class="table-actions">
                        <button class="btn btn-primary" onclick="viewTable('${table.name}')">
                            👁️ عرض
                        </button>
                        <button class="btn btn-warning" onclick="confirmTruncate('${table.name}')">
                            🗑️ تفريغ
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });
            
            grid.style.display = 'grid';
        }

        // دالة عرض بيانات جدول معين
        async function viewTable(tableName, offset = 0) {
            currentTable = tableName;
            currentOffset = offset;
            
            showLoading(true);
            try {
                const response = await fetch(`/api/v1/temp-db/tables/${tableName}?limit=${currentLimit}&offset=${offset}`);
                const data = await response.json();
                
                if (data.success) {
                    displayTableData(data);
                } else {
                    showError('خطأ في تحميل بيانات الجدول: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في الاتصال: ' + error.message);
            }
            showLoading(false);
        }

        // دالة عرض بيانات الجدول
        function displayTableData(data) {
            const tableDataDiv = document.getElementById('tableData');
            const tableTitle = document.getElementById('tableTitle');
            const tableInfo = document.getElementById('tableInfo');
            const dataTable = document.getElementById('dataTable');
            const tableHead = document.getElementById('tableHead');
            const tableBody = document.getElementById('tableBody');
            
            tableTitle.textContent = `جدول: ${data.table_name}`;
            tableInfo.innerHTML = `
                <p><strong>إجمالي السجلات:</strong> ${data.total_count}</p>
                <p><strong>السجلات المعروضة:</strong> ${data.current_count}</p>
                <button class="btn btn-primary" onclick="hideTableData()" style="margin-top: 10px;">
                    ← العودة للجداول
                </button>
            `;
            
            // إنشاء رأس الجدول
            tableHead.innerHTML = '';
            const headerRow = document.createElement('tr');
            data.columns.forEach(column => {
                const th = document.createElement('th');
                th.textContent = column;
                headerRow.appendChild(th);
            });
            tableHead.appendChild(headerRow);
            
            // إنشاء محتوى الجدول
            tableBody.innerHTML = '';
            data.data.forEach(row => {
                const tr = document.createElement('tr');
                data.columns.forEach(column => {
                    const td = document.createElement('td');
                    td.textContent = row[column] || '';
                    tr.appendChild(td);
                });
                tableBody.appendChild(tr);
            });
            
            // إنشاء أزرار التنقل
            createPagination(data);
            
            dataTable.style.display = 'table';
            tableDataDiv.classList.add('active');
        }

        // دالة إنشاء أزرار التنقل
        function createPagination(data) {
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            
            const totalPages = Math.ceil(data.total_count / currentLimit);
            const currentPage = Math.floor(currentOffset / currentLimit) + 1;
            
            // زر السابق
            if (currentOffset > 0) {
                const prevBtn = document.createElement('button');
                prevBtn.className = 'btn btn-primary';
                prevBtn.textContent = '← السابق';
                prevBtn.onclick = () => viewTable(currentTable, currentOffset - currentLimit);
                pagination.appendChild(prevBtn);
            }
            
            // معلومات الصفحة
            const pageInfo = document.createElement('span');
            pageInfo.textContent = `صفحة ${currentPage} من ${totalPages}`;
            pageInfo.style.padding = '8px 16px';
            pagination.appendChild(pageInfo);
            
            // زر التالي
            if (currentOffset + currentLimit < data.total_count) {
                const nextBtn = document.createElement('button');
                nextBtn.className = 'btn btn-primary';
                nextBtn.textContent = 'التالي →';
                nextBtn.onclick = () => viewTable(currentTable, currentOffset + currentLimit);
                pagination.appendChild(nextBtn);
            }
        }

        // دالة إخفاء بيانات الجدول
        function hideTableData() {
            document.getElementById('tableData').classList.remove('active');
            document.getElementById('dataTable').style.display = 'none';
        }

        // دالة تأكيد تفريغ الجدول
        function confirmTruncate(tableName) {
            showConfirmModal(
                `هل أنت متأكد من تفريغ جدول "${tableName}"؟\nسيتم حذف جميع البيانات نهائياً!`,
                () => truncateTable(tableName)
            );
        }

        // دالة تفريغ الجدول
        async function truncateTable(tableName) {
            showLoading(true);
            try {
                const response = await fetch(`/api/v1/temp-db/tables/${tableName}/truncate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(data.message);
                    loadTables(); // إعادة تحميل الجداول
                } else {
                    showError('خطأ في تفريغ الجدول: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في الاتصال: ' + error.message);
            }
            showLoading(false);
        }

        // دالة عرض modal التأكيد
        function showConfirmModal(message, callback) {
            const modal = document.getElementById('confirmModal');
            const confirmMessage = document.getElementById('confirmMessage');
            const confirmBtn = document.getElementById('confirmBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const closeBtn = document.querySelector('.close');
            
            confirmMessage.textContent = message;
            modal.style.display = 'block';
            
            confirmBtn.onclick = () => {
                modal.style.display = 'none';
                callback();
            };
            
            cancelBtn.onclick = closeBtn.onclick = () => {
                modal.style.display = 'none';
            };
        }

        // دوال المساعدة
        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        }

        function showSuccess(message) {
            const alert = document.getElementById('successAlert');
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => alert.style.display = 'none', 5000);
        }

        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => alert.style.display = 'none', 5000);
        }

        // إغلاق modal عند النقر خارجها
        window.onclick = function(event) {
            const modal = document.getElementById('confirmModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>