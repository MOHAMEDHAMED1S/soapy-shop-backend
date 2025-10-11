<x-mail::message>
# طلب جديد مدفوع

مرحباً {{ $admin->name }},

تم استلام طلب جديد مدفوع في المتجر.

## تفاصيل الطلب

**رقم الطلب:** {{ $order->order_number }}  
**المبلغ الإجمالي:** {{ number_format($order->total_amount, 3) }} {{ $order->currency }}  
**تاريخ الطلب:** {{ $order->created_at->format('Y-m-d H:i') }}  
**حالة الطلب:** {{ $order->status }}

## معلومات العميل

**الاسم:** {{ $order->customer_name }}  
**البريد الإلكتروني:** {{ $order->customer_email }}  
**الهاتف:** {{ $order->customer_phone }}

## عنوان الشحن

{{ $order->shipping_address['street'] ?? '' }}, {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['governorate'] ?? '' }}

<x-mail::button :url="config('app.admin_url') . '/orders/' . $order->id">
عرض تفاصيل الطلب
</x-mail::button>

يرجى مراجعة الطلب واتخاذ الإجراءات اللازمة.

شكراً لك،<br>
{{ config('app.name') }}
</x-mail::message>
