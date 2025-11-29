<?php
require_once __DIR__ . '/../app/auth_check.php';
require_once __DIR__ . '/../vendor/autoload.php';

echo class_exists('\PHPMailer\PHPMailer\PHPMailer') ? "PHPMailer OK<br>" : "PHPMailer FAIL<br>";
echo class_exists('\Dompdf\Dompdf') ? "Dompdf OK<br>" : "Dompdf FAIL<br>";
echo class_exists('\Dotenv\Dotenv') ? "Dotenv OK<br>" : "Dotenv FAIL<br>";
echo class_exists('\Monolog\Logger') ? "Monolog OK<br>" : "Monolog FAIL<br>";
