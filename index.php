<?php
header('Content-Type: application/json; charset=utf-8');

// 1. ضع هنا رابط ملف الـ M3U الحقيقي الخاص بك (من جيت هب أو أي مكان)
$m3u_url = "https://raw.githubusercontent.com/mosasa6468-max/playlist/refs/heads/main/playlist.m3u";

// 2. بيانات الدخول التي ستكتبها في تطبيق الـ IPTV
$allowed_username = "sport";
$allowed_password = "zone";

$username = isset($_GET['username']) ? $_GET['username'] : '';
$password = isset($_GET['password']) ? $_GET['password'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// التحقق من الحساب
if ($username !== $allowed_username || $password !== $allowed_password) {
    die(json_encode(["user_info" => ["auth" => 0]]));
}

// دالة ذكية لقراءة ملف الـ M3U
function parseM3u($url) {
    $content = @file_get_contents($url);
    if (!$content) return [];
    
    $lines = explode("\n", $content);
    $channels = [];
    $current_cat = "عام";
    $channel_name = "";
    $icon = "";
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#EXTINF:') === 0) {
            preg_match('/group-title="([^"]+)"/', $line, $cat_match);
            preg_match('/tvg-logo="([^"]+)"/', $line, $logo_match);
            preg_match('/,(.+)$/', $line, $name_match);
            
            $current_cat = isset($cat_match[1]) ? $cat_match[1] : "عام";
            $icon = isset($logo_match[1]) ? $logo_match[1] : "";
            $channel_name = isset($name_match[1]) ? trim($name_match[1]) : "قناة غير معروفة";
        } elseif (filter_var($line, FILTER_VALIDATE_URL)) {
            $channels[] = [
                "name" => $channel_name,
                "url" => $line,
                "category" => $current_cat,
                "icon" => $icon
            ];
        }
    }
    return $channels;
}

// الرد على التطبيقات (Xtream API)
$channels = parseM3u($m3u_url);

$categories = array_unique(array_column($channels, 'category'));
$cat_mapping = [];
$cat_id = 1;
foreach ($categories as $cat) {
    $cat_mapping[$cat] = (string)$cat_id++;
}

if ($action == 'get_live_categories') {
    $response = [];
    foreach ($cat_mapping as $cat_name => $id) {
        $response[] = ["category_id" => $id, "category_name" => $cat_name, "parent_id" => "0"];
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} 
elseif ($action == 'get_live_streams') {
    $response = [];
    $stream_id = 1;
    foreach ($channels as $ch) {
        $response[] = [
            "num" => $stream_id,
            "name" => $ch['name'],
            "stream_id" => (string)$stream_id++,
            "stream_icon" => $ch['icon'],
            "url" => $ch['url'],
            "category_id" => $cat_mapping[$ch['category']]
        ];
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} 
else {
    // اللوحة الأساسية
    echo json_encode([
        "user_info" => [
            "auth" => 1,
            "status" => "Active",
            "exp_date" => "1798752000"
        ],
        "server_info" => [
            "url" => $_SERVER['HTTP_HOST'],
            "port" => "80"
        ]
    ]);
}
?>