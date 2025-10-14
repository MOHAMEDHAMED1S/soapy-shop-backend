<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تأكيد طلبك</title>
</head>
<body style="margin:0; padding:0; background-color:#ffffff; font-family:'Tahoma', Arial, sans-serif; direction:rtl; text-align:right;">

  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; padding:30px 0;">
    <tr>
      <td align="center">

        <!-- البطاقة -->
        <table width="600" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e0e0e0; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.05);">

          <!-- الرأس -->
          <tr>
            <td align="center" style="padding:25px 0; border-bottom:2px solid #000;">
              <img src="https://soapy-bubbles.com/logo.png" alt="{{ config('app.name', 'Soapy Bubbles') }}" width="140" style="display:block;">
              <h1 style="margin:15px 0 5px; font-size:24px; color:#000;">تأكيد طلبك</h1>
              <p style="margin:0; color:#555; font-size:15px;">شكراً لك على طلبك. إليك تفاصيل طلبك.</p>
            </td>
          </tr>

          <!-- المحتوى -->
          <tr>
            <td style="padding:30px; color:#333; font-size:15px; line-height:1.8;">

              <!-- معلومات الطلب -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:25px; background-color:#f9f9f9; border:1px solid #e0e0e0;">
                <tr>
                  <td style="padding:20px;">
                    <h2 style="margin:0 0 15px; font-size:18px; color:#000; border-bottom:1px solid #e0e0e0; padding-bottom:8px;">معلومات الطلب</h2>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="padding:6px 0; font-weight:bold; width:150px;">رقم الطلب:</td>
                        <td style="padding:6px 0;">{{ $order->order_number }}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; font-weight:bold;">تاريخ الطلب:</td>
                        <td style="padding:6px 0;">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; font-weight:bold;">حالة الطلب:</td>
                        <td style="padding:6px 0;">
                          @switch($order->status)
                              @case('pending') في الانتظار @break
                              @case('paid') مدفوع @break
                              @case('shipped') تم الشحن @break
                              @case('delivered') تم التسليم @break
                              @case('cancelled') ملغي @break
                              @default {{ $order->status }}
                          @endswitch
                        </td>
                      </tr>
                      @if($order->customer)
                        <tr>
                          <td style="padding:6px 0; font-weight:bold;">اسم العميل:</td>
                          <td style="padding:6px 0;">{{ $order->customer->name }}</td>
                        </tr>
                      @elseif($order->customer_name)
                        <tr>
                          <td style="padding:6px 0; font-weight:bold;">اسم العميل:</td>
                          <td style="padding:6px 0;">{{ $order->customer_name }}</td>
                        </tr>
                      @endif
                    </table>
                  </td>
                </tr>
              </table>

              <!-- المنتجات المطلوبة -->
              @if($order->orderItems && $order->orderItems->count() > 0)
              <h3 style="font-size:18px; color:#000; border-bottom:1px solid #e0e0e0; padding-bottom:8px; margin-bottom:15px;">المنتجات المطلوبة</h3>
              @foreach($order->orderItems as $item)
              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px; border:1px solid #e0e0e0;">
                <tr>
                  <td style="padding:15px;">
                    <div style="font-weight:bold; color:#000; margin-bottom:5px; font-size:14px;">{{ $item->product->title ?? 'منتج غير محدد' }}</div>
                    <div style="color:#666; font-size:13px;">الكمية: {{ $item->quantity }} × {{ number_format($item->product_price, 2) }} {{ $order->currency ?? 'KWD' }}</div>
                  </td>
                  <td align="left" style="padding:15px; font-weight:bold; color:#000; font-size:14px; white-space:nowrap;">
                    {{ number_format($item->quantity * $item->product_price, 2) }} {{ $order->currency ?? 'KWD' }}
                  </td>
                </tr>
              </table>
              @endforeach
              @endif

              <!-- المجموع الكلي -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:25px 0; background-color:#f9f9f9; border:2px solid #000;">
                <tr>
                  <td align="center" style="padding:20px;">
                    <div style="font-size:16px; color:#333;">المجموع الكلي</div>
                    <div style="font-size:26px; font-weight:bold; color:#000;">
                      {{ number_format($order->total_amount, 2) }} {{ $order->currency ?? 'KWD' }}
                    </div>
                  </td>
                </tr>
              </table>

              <!-- تتبع الطلب -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:25px; background-color:#f9f9f9; border:1px solid #e0e0e0;">
                <tr>
                  <td align="center" style="padding:25px;">
                    <h3 style="font-size:16px; color:#000; margin-bottom:10px;">تتبع طلبك</h3>
                    <p style="font-size:14px; color:#555; margin:0 0 15px;">يمكنك تتبع حالة طلبك في أي وقت من خلال الرابط التالي:</p>
                    <a href="{{ $trackingUrl ?? config('app.url') . '/track/' . $order->order_number }}" style="background-color:#000; color:#fff; text-decoration:none; padding:12px 25px; border-radius:6px; font-weight:bold; display:inline-block;">تتبع الطلب</a>
                    <p style="margin-top:15px; font-size:14px; color:#777;">رقم التتبع: {{ $order->order_number }}</p>
                  </td>
                </tr>
              </table>

              <!-- الدعم -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f9f9f9; border:1px solid #e0e0e0;">
                <tr>
                  <td style="padding:20px;">
                    <h4 style="font-size:16px; color:#000; margin-bottom:10px;">هل تحتاج مساعدة؟</h4>
                    <p style="font-size:14px; color:#333; margin:0 0 10px;">إذا كان لديك أي استفسار حول طلبك، لا تتردد في التواصل معنا:</p>
                    <p style="background-color:#fff; border:1px solid #e0e0e0; padding:12px; border-radius:6px; font-size:14px; margin:0;">
                      البريد الإلكتروني: <a href="mailto:info@soapy-bubbles.com" style="color:#000; text-decoration:none;">info@soapy-bubbles.com</a>
                    </p>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- الفوتر -->
          <tr>
            <td align="center" style="background-color:#000; color:#fff; padding:25px;">
              <h4 style="margin:0 0 10px; font-size:16px;">شكراً لاختيارك {{ config('app.name', 'Soapy Bubbles') }}</h4>
              <p style="margin:0; font-size:14px; color:#ccc;">© {{ date('Y') }} {{ config('app.name', 'Soapy Bubbles') }} — جميع الحقوق محفوظة.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>