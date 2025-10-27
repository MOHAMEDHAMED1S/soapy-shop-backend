<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhatsAppController extends Controller
{
    /**
     * Get all WhatsApp settings
     */
    public function index()
    {
        try {
            $settings = WhatsAppSetting::orderBy('key')->get()->map(function ($setting) {
                return [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'value' => WhatsAppSetting::parseValue($setting->value, $setting->type),
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'is_active' => $setting->is_active,
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving WhatsApp settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific setting
     */
    public function show($key)
    {
        try {
            $setting = WhatsAppSetting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'value' => WhatsAppSetting::parseValue($setting->value, $setting->type),
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'is_active' => $setting->is_active,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update WhatsApp settings
     */
    public function update(Request $request, $key)
    {
        try {
            $setting = WhatsAppSetting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'value' => 'required',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $setting->update([
                'value' => WhatsAppSetting::formatValue($request->value, $setting->type),
                'is_active' => $request->has('is_active') ? $request->is_active : $setting->is_active,
            ]);

            // Clear cache
            WhatsAppSetting::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => [
                    'key' => $setting->key,
                    'value' => WhatsAppSetting::parseValue($setting->value, $setting->type),
                    'is_active' => $setting->is_active,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update multiple settings at once
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updated = [];

            foreach ($request->settings as $settingData) {
                $setting = WhatsAppSetting::where('key', $settingData['key'])->first();

                if ($setting) {
                    $setting->update([
                        'value' => WhatsAppSetting::formatValue($settingData['value'], $setting->type),
                        'is_active' => $settingData['is_active'] ?? $setting->is_active,
                    ]);

                    $updated[] = $setting->key;
                }
            }

            // Clear cache
            WhatsAppSetting::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => [
                    'updated_count' => count($updated),
                    'updated_keys' => $updated
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle WhatsApp globally
     */
    public function toggleGlobal()
    {
        try {
            $setting = WhatsAppSetting::where('key', 'whatsapp_enabled')->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }

            $currentValue = WhatsAppSetting::parseValue($setting->value, 'boolean');
            $newValue = !$currentValue;

            $setting->update([
                'value' => WhatsAppSetting::formatValue($newValue, 'boolean')
            ]);

            // Clear cache
            WhatsAppSetting::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp ' . ($newValue ? 'enabled' : 'disabled') . ' successfully',
                'data' => [
                    'whatsapp_enabled' => $newValue
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling WhatsApp',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle admin notifications
     */
    public function toggleAdminNotifications()
    {
        try {
            $setting = WhatsAppSetting::where('key', 'admin_notification_enabled')->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }

            $currentValue = WhatsAppSetting::parseValue($setting->value, 'boolean');
            $newValue = !$currentValue;

            $setting->update([
                'value' => WhatsAppSetting::formatValue($newValue, 'boolean')
            ]);

            // Clear cache
            WhatsAppSetting::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Admin notifications ' . ($newValue ? 'enabled' : 'disabled'),
                'data' => [
                    'admin_notification_enabled' => $newValue
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling admin notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle delivery notifications
     */
    public function toggleDeliveryNotifications()
    {
        try {
            $setting = WhatsAppSetting::where('key', 'delivery_notification_enabled')->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }

            $currentValue = WhatsAppSetting::parseValue($setting->value, 'boolean');
            $newValue = !$currentValue;

            $setting->update([
                'value' => WhatsAppSetting::formatValue($newValue, 'boolean')
            ]);

            // Clear cache
            WhatsAppSetting::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Delivery notifications ' . ($newValue ? 'enabled' : 'disabled'),
                'data' => [
                    'delivery_notification_enabled' => $newValue
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling delivery notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test WhatsApp connection
     */
    public function test(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'message' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $whatsappService = app(\App\Services\WhatsAppService::class);
            $baseUrl = WhatsAppSetting::getBaseUrl();
            $phone = $request->phone;
            $message = $request->message ?? "This is a test message from Soapy Shop";

            // استخدام asForm() لإرسال البيانات كـ form-data
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->asForm()
                ->post("{$baseUrl}/api/send/message", [
                    'to' => $phone,
                    'message' => $message,  // استخدام 'message' بدلاً من 'body'
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test message sent successfully',
                    'data' => $response->json()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test message',
                    'error' => $response->body()
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending test message',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

