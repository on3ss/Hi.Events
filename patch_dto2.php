<?php
$file = 'backend/app/Services/Application/Handlers/Account/Payment/Razorpay/DTO/CreateRazorpayLinkedAccountDTO.php';
$content = file_get_contents($file);
$content = str_replace('public readonly RazorpayPlatform|null $platform = null,', '', $content);
file_put_contents($file, $content);

$file = 'backend/app/Services/Application/Handlers/Account/Payment/Razorpay/DTO/RazorpayLinkedAccountDTO.php';
$content = file_get_contents($file);
$content = str_replace('public readonly ?RazorpayPlatform $platform,', '', $content);
file_put_contents($file, $content);
