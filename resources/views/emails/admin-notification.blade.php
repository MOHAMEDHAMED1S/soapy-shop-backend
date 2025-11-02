<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إشعار إداري - soapy bubbles</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        .priority {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .priority.urgent {
            background-color: #e74c3c;
            color: white;
        }
        .priority.high {
            background-color: #f39c12;
            color: white;
        }
        .priority.medium {
            background-color: #3498db;
            color: white;
        }
        .priority.low {
            background-color: #95a5a6;
            color: white;
        }
        .notification-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .notification-message {
            font-size: 16px;
            color: #34495e;
            margin-bottom: 25px;
            padding: 15px;
            background-color: #ecf0f1;
            border-radius: 5px;
        }
        .notification-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        .action-button {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
        }
        .action-button:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🧴 soapy bubbles</div>
            <div class="priority {{ $priority }}">{{ $priority }}</div>
        </div>

        <div class="notification-title">{{ $title }}</div>
        
        <div class="notification-message">
            {{ $message }}
        </div>

        @if(!empty($data))
        <div class="notification-details">
            <h3 style="margin-top: 0; color: #2c3e50;">تفاصيل الإشعار:</h3>
            
            @if(isset($data['order_number']))
            <div class="detail-row">
                <span class="detail-label">رقم الطلب:</span>
                <span class="detail-value">{{ $data['order_number'] }}</span>
            </div>
            @endif

            @if(isset($data['customer_name']))
            <div class="detail-row">
                <span class="detail-label">اسم العميل:</span>
                <span class="detail-value">{{ $data['customer_name'] }}</span>
            </div>
            @endif

            @if(isset($data['customer_phone']))
            <div class="detail-row">
                <span class="detail-label">رقم الهاتف:</span>
                <span class="detail-value">{{ $data['customer_phone'] }}</span>
            </div>
            @endif

            @if(isset($data['total_amount']))
            <div class="detail-row">
                <span class="detail-label">المبلغ الإجمالي:</span>
                <span class="detail-value">{{ $data['total_amount'] }} {{ $data['currency'] ?? 'KWD' }}</span>
            </div>
            @endif

            @if(isset($data['payment_method']))
            <div class="detail-row">
                <span class="detail-label">طريقة الدفع:</span>
                <span class="detail-value">{{ $data['payment_method'] }}</span>
            </div>
            @endif

            @if(isset($data['product_title']))
            <div class="detail-row">
                <span class="detail-label">اسم المنتج:</span>
                <span class="detail-value">{{ $data['product_title'] }}</span>
            </div>
            @endif

            @if(isset($data['status']))
            <div class="detail-row">
                <span class="detail-label">الحالة:</span>
                <span class="detail-value">{{ $data['status'] }}</span>
            </div>
            @endif
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/admin/notifications" class="action-button">
                عرض الإشعارات
            </a>
        </div>

        <div class="footer">
            <p>تم إرسال هذا الإشعار في: {{ $createdAt }}</p>
            <p>soapy bubbles - متجر الصابون الطبيعي</p>
            <p>هذا إشعار تلقائي، يرجى عدم الرد على هذا البريد الإلكتروني.</p>
        </div>
    </div>
</body>
</html>
