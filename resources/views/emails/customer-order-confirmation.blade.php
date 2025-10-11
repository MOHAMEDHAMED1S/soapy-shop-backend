<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد طلبك</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #374151;
            line-height: 1.6;
            direction: rtl;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .order-info {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border-right: 4px solid #667eea;
        }
        
        .order-info h2 {
            color: #1f2937;
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .order-info h2::before {
            content: "📦";
            margin-left: 10px;
            font-size: 24px;
        }
        
        .order-details {
            display: grid;
            gap: 12px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #4b5563;
        }
        
        .detail-value {
            font-weight: 700;
            color: #1f2937;
        }
        
        .order-items {
            margin: 30px 0;
        }
        
        .order-items h3 {
            color: #1f2937;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .order-items h3::before {
            content: "🛍️";
            margin-left: 10px;
            font-size: 20px;
        }
        
        .item {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .item-details {
            font-size: 14px;
            color: #6b7280;
        }
        
        .item-price {
            font-weight: 700;
            color: #059669;
            font-size: 16px;
        }
        
        .tracking-section {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
        }
        
        .tracking-section h3 {
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .tracking-section h3::before {
            content: "🚚";
            margin-left: 10px;
            font-size: 24px;
        }
        
        .tracking-button {
            display: inline-block;
            background-color: white;
            color: #059669;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        
        .tracking-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .footer {
            background-color: #1f2937;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .footer h4 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .footer p {
            opacity: 0.8;
            margin-bottom: 10px;
        }
        
        .social-links {
            margin-top: 20px;
        }
        
        .social-links a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-size: 18px;
        }
        
        .total-section {
            background-color: #fef3c7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-right: 4px solid #f59e0b;
        }
        
        .total-amount {
            font-size: 24px;
            font-weight: 700;
            color: #92400e;
            text-align: center;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 0;
                box-shadow: none;
            }
            
            .header, .content, .footer {
                padding: 20px;
            }
            
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🧼 Soapy Bubbles</h1>
            <p>شكراً لك على طلبك! تم تأكيد دفعتك بنجاح</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Order Information -->
            <div class="order-info">
                <h2>معلومات الطلب</h2>
                <div class="order-details">
                    <div class="detail-row">
                        <span class="detail-label">رقم الطلب:</span>
                        <span class="detail-value">#{{ $order->order_number }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">تاريخ الطلب:</span>
                        <span class="detail-value">{{ $order->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">حالة الطلب:</span>
                        <span class="detail-value" style="color: #059669;">✅ مدفوع</span>
                    </div>
                    @if($order->customer)
                    <div class="detail-row">
                        <span class="detail-label">اسم العميل:</span>
                        <span class="detail-value">{{ $order->customer->name }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Order Items -->
            <div class="order-items">
                <h3>المنتجات المطلوبة</h3>
                @foreach($order->orderItems as $item)
                <div class="item">
                    <div class="item-info">
                        <div class="item-name">{{ $item->product->name }}</div>
                        <div class="item-details">
                            الكمية: {{ $item->quantity }} × {{ number_format($item->price, 2) }} ريال
                        </div>
                    </div>
                    <div class="item-price">
                        {{ number_format($item->quantity * $item->price, 2) }} ريال
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Total Section -->
            <div class="total-section">
                <div class="total-amount">
                    المجموع الكلي: {{ number_format($order->total_amount, 2) }} ريال سعودي
                </div>
            </div>

            <!-- Tracking Section -->
            <div class="tracking-section">
                <h3>تتبع طلبك</h3>
                <p>يمكنك تتبع حالة طلبك في أي وقت من خلال الرابط أدناه</p>
                <a href="{{ $trackingUrl }}" class="tracking-button">
                    تتبع الطلب الآن
                </a>
                <p style="margin-top: 15px; font-size: 14px; opacity: 0.9;">
                    رقم التتبع: {{ $order->order_number }}
                </p>
            </div>

            <!-- Additional Info -->
            <div style="background-color: #eff6ff; border-radius: 8px; padding: 20px; margin: 20px 0; border-right: 4px solid #3b82f6;">
                <h4 style="color: #1e40af; margin-bottom: 10px;">📞 هل تحتاج مساعدة؟</h4>
                <p style="color: #1f2937;">
                    إذا كان لديك أي استفسار حول طلبك، لا تتردد في التواصل معنا:
                </p>
                <p style="color: #1f2937; margin-top: 10px;">
                    📧 البريد الإلكتروني: support@soapy-bubbles.com<br>
                    📱 الهاتف: +966 50 123 4567
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <h4>🧼 Soapy Bubbles</h4>
            <p>متجرك المفضل لمنتجات العناية والتنظيف الطبيعية</p>
            <p>شكراً لاختيارك Soapy Bubbles!</p>
            
            <div class="social-links">
                <a href="#">📘</a>
                <a href="#">📷</a>
                <a href="#">🐦</a>
                <a href="#">📱</a>
            </div>
            
            <p style="margin-top: 20px; font-size: 12px; opacity: 0.7;">
                © 2024 Soapy Bubbles. جميع الحقوق محفوظة.
            </p>
        </div>
    </div>
</body>
</html>