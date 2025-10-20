<?php

require_once 'vendor/autoload.php';

use App\Services\VisitTrackingService;
use Illuminate\Http\Request;

// Initialize Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$service = new VisitTrackingService();

// Test different social media platforms
$testCases = [
    // Facebook
    ['url' => 'https://facebook.com/page', 'expected' => 'facebook'],
    ['url' => 'https://m.facebook.com/', 'expected' => 'facebook'],
    ['url' => 'https://www.facebook.com/share', 'expected' => 'facebook'],
    ['url' => 'https://fb.com/page', 'expected' => 'facebook'],
    
    // Twitter/X
    ['url' => 'https://twitter.com/user', 'expected' => 'twitter'],
    ['url' => 'https://x.com/user', 'expected' => 'twitter'],
    ['url' => 'https://mobile.twitter.com/user', 'expected' => 'twitter'],
    
    // Instagram
    ['url' => 'https://instagram.com/user', 'expected' => 'instagram'],
    ['url' => 'https://www.instagram.com/p/post', 'expected' => 'instagram'],
    
    // LinkedIn
    ['url' => 'https://linkedin.com/in/user', 'expected' => 'linkedin'],
    ['url' => 'https://www.linkedin.com/company/test', 'expected' => 'linkedin'],
    
    // YouTube
    ['url' => 'https://youtube.com/watch', 'expected' => 'youtube'],
    ['url' => 'https://www.youtube.com/channel/test', 'expected' => 'youtube'],
    ['url' => 'https://youtu.be/video', 'expected' => 'youtube'],
    
    // TikTok
    ['url' => 'https://tiktok.com/@user', 'expected' => 'tiktok'],
    ['url' => 'https://www.tiktok.com/video', 'expected' => 'tiktok'],
    
    // WhatsApp
    ['url' => 'https://wa.me/123456789', 'expected' => 'whatsapp'],
    ['url' => 'https://web.whatsapp.com/', 'expected' => 'whatsapp'],
    
    // Telegram
    ['url' => 'https://t.me/channel', 'expected' => 'telegram'],
    ['url' => 'https://telegram.me/user', 'expected' => 'telegram'],
    
    // Pinterest
    ['url' => 'https://pinterest.com/pin', 'expected' => 'pinterest'],
    ['url' => 'https://www.pinterest.com/board', 'expected' => 'pinterest'],
    
    // Snapchat
    ['url' => 'https://snapchat.com/add', 'expected' => 'snapchat'],
    ['url' => 'https://www.snapchat.com/discover', 'expected' => 'snapchat'],
    
    // Reddit
    ['url' => 'https://reddit.com/r/test', 'expected' => 'reddit'],
    ['url' => 'https://www.reddit.com/user/test', 'expected' => 'reddit'],
    
    // Google (search)
    ['url' => 'https://google.com/search', 'expected' => 'search'],
    ['url' => 'https://www.google.com/search?q=test', 'expected' => 'search'],
    
    // Direct access
    ['url' => '', 'expected' => 'direct'],
    ['url' => null, 'expected' => 'direct'],
    
    // Other domains
    ['url' => 'https://example.com/page', 'expected' => 'other'],
    ['url' => 'https://news.website.com/article', 'expected' => 'other'],
];

echo "Testing Social Media Referer Detection\n";
echo "=====================================\n\n";

$passed = 0;
$failed = 0;

foreach ($testCases as $index => $test) {
    // Create a mock request
    $request = new Request();
    $request->merge(['referrer_url' => $test['url']]);
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('extractVisitData');
    $method->setAccessible(true);
    
    $result = $method->invoke($service, $request);
    $actualType = $result['referer_type'];
    
    $status = $actualType === $test['expected'] ? '✅ PASS' : '❌ FAIL';
    
    if ($actualType === $test['expected']) {
        $passed++;
    } else {
        $failed++;
    }
    
    echo sprintf(
        "%s Test %d: %s\n   Expected: %s, Got: %s\n   URL: %s\n\n",
        $status,
        $index + 1,
        $actualType === $test['expected'] ? 'SUCCESS' : 'FAILED',
        $test['expected'],
        $actualType,
        $test['url'] ?: '(empty)'
    );
}

echo "=====================================\n";
echo "Test Results:\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed > 0) {
    echo "\n⚠️  Some tests failed. Please check the determineRefererType method.\n";
} else {
    echo "\n🎉 All tests passed! Social media detection is working correctly.\n";
}