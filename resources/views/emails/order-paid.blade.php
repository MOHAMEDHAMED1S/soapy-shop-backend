<x-mail::message>
# طلب جديد مدفوع

مرحباً {{ $admin->name }},

تم استلام طلب جديد مدفوع في المتجر.

## تفاصيل الطلب

**رقم الطلب:** {{ $order->order_number }}  
**المبلغ الإجمالي:** {{ $order->total_amount }} {{ $order->currency ?? 'KWD' }}  
**تاريخ الطلب:** {{ $order->created_at->format('Y-m-d H:i') }}  

## معلومات العميل

**الاسم:** {{ $order->customer_name }}  
**البريد الإلكتروني:** {{ $order->customer_email }}  
**الهاتف:** {{ $order->customer_phone }}

شكراً لك،<br>
{{ config('app.name') }}
</x-mail::message>
