<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwilioWhatsAppService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TwilioWhatsAppController extends Controller
{
    private TwilioWhatsAppService $twilioService;

    public function __construct(TwilioWhatsAppService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Test Twilio connection
     */
    public function testConnection()
    {
        $result = $this->twilioService->testConnection();

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Send a WhatsApp message
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'required|string|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->twilioService->sendMessage(
                $request->phone,
                $request->message
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => [
                        'sid' => $result->sid,
                        'status' => $result->status,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Twilio is not configured',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a template message
     */
    public function sendTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'template_sid' => 'required|string',
            'variables' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->twilioService->sendTemplateMessage(
                $request->phone,
                $request->template_sid,
                $request->variables ?? []
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Template message sent successfully',
                    'data' => [
                        'sid' => $result->sid,
                        'status' => $result->status,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Twilio is not configured',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send template: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send order notification
     */
    public function sendOrderNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'type' => 'required|in:confirmation,shipping,delivery',
            'template_sid' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $order = Order::with('orderItems')->findOrFail($request->order_id);
            $phone = $order->customer_phone;
            $templateSid = $request->template_sid;

            $result = match ($request->type) {
                'confirmation' => $templateSid 
                    ? $this->twilioService->sendOrderConfirmationTemplate($phone, $order, $templateSid)
                    : $this->twilioService->sendOrderConfirmation($phone, $order),
                'shipping' => $templateSid 
                    ? $this->twilioService->sendShippingUpdateTemplate($phone, $order, $templateSid)
                    : $this->twilioService->sendShippingUpdate($phone, $order),
                'delivery' => $templateSid 
                    ? $this->twilioService->sendDeliveryNotificationTemplate($phone, $order, $templateSid)
                    : $this->twilioService->sendDeliveryNotification($phone, $order),
            };

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order notification sent successfully',
                    'data' => [
                        'sid' => $result->sid,
                        'status' => $result->status,
                        'order_number' => $order->order_number,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Twilio is not configured',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get service status
     */
    public function status()
    {
        return response()->json([
            'success' => true,
            'configured' => $this->twilioService->isConfigured(),
            'from_number' => config('twilio.whatsapp_from'),
        ]);
    }
}
