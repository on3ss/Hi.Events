<?php
$file = 'backend/app/Services/Application/Handlers/Account/Payment/Razorpay/DTO/RazorpayLinkedAccountDTO.php';
$content = file_get_contents($file);
$content = str_replace('        public readonly ?RazorpayPlatform $platform = null,', '', $content);
file_put_contents($file, $content);
