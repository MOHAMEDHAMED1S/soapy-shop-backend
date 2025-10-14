<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إشعار طلب جديد مدفوع</title>
</head>
<body style="margin:0; padding:0; background-color:#ffffff; font-family:'Tahoma', Arial, sans-serif; direction:rtl; text-align:right;">

  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; padding:30px 0;">
    <tr>
      <td align="center">

        <!-- البطاقة -->
        <table width="600" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e0e0e0; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); overflow:hidden;">

          <!-- الرأس -->
          <tr>
            <td align="center" style="padding:25px 0; border-bottom:1px solid #f0f0f0;">
              <img src="https://soapy-bubbles.com/logo.png" alt="{{ config('app.name', 'Soapy Bubbles') }}" width="140" style="display:block;">
            </td>
          </tr>

          <!-- العنوان -->
          <tr>
            <td style="padding:25px 30px 10px 30px;">
              <h2 style="margin:0; font-size:22px; color:#000;">تم استلام طلب جديد مدفوع</h2>
            </td>
          </tr>

          <!-- التفاصيل -->
          <tr>
            <td style="padding:10px 30px 20px 30px; color:#333; font-size:15px; line-height:1.7;">
              <p style="margin:0 0 10px;">مرحبًا {{ $admin->name ?? 'المدير' }},</p>
              <p style="margin:0 0 15px;">
                تم استلام طلب جديد في <strong>{{ config('app.name', 'Soapy Bubbles') }}</strong>، والتفاصيل كالتالي:
              </p>

              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:15px; border-collapse:collapse;">
                <tr>
                  <td style="padding:8px 0; font-weight:bold; width:150px;">رقم الطلب:</td>
                  <td style="padding:8px 0;">{{ $order->order_number }}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0; font-weight:bold;">تاريخ الإنشاء:</td>
                  <td style="padding:8px 0;">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0; font-weight:bold;">عدد المنتجات:</td>
                  <td style="padding:8px 0;">{{ $order->orderItems->sum('quantity') }}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0; font-weight:bold;">المجموع الكلي:</td>
                  <td style="padding:8px 0;">{{ number_format($order->total_amount, 2) }} {{ $order->currency ?? 'KWD' }}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0; font-weight:bold;">اسم العميل:</td>
                  <td style="padding:8px 0;">{{ $order->customer_name ?? ($order->customer->name ?? 'غير محدد') }}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0; font-weight:bold;">البريد الإلكتروني:</td>
                  <td style="padding:8px 0;">{{ $order->customer_email ?? ($order->customer->email ?? 'غير محدد') }}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0; font-weight:bold;">رقم الهاتف:</td>
                  <td style="padding:8px 0;">{{ $order->customer_phone ?? ($order->customer->phone ?? 'غير محدد') }}</td>
                </tr>
              </table>

              <!-- زر عرض الطلب -->
              <div style="text-align:center; margin-top:25px;">
                <a href="{{ config('app.admin_url', '#') }}/orders/{{ $order->id }}" 
                   style="background-color:#000; color:#fff; text-decoration:none; padding:12px 28px; border-radius:6px; display:inline-block; font-weight:bold;">
                  عرض تفاصيل الطلب
                </a>
              </div>
            </td>
          </tr>

          <!-- الفوتر -->
          <tr>
            <td align="center" style="background-color:#f9f9f9; padding:18px; border-top:1px solid #eaeaea; font-size:13px; color:#777;">
              © {{ date('Y') }} {{ config('app.name', 'Soapy Bubbles') }} — جميع الحقوق محفوظة.
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>