<?php
require_once __DIR__ . '/../../LocalAdminSystem/AdminSystem/vendor/autoload.php';
require_once __DIR__ . '/../Config/database.php';

function send_json($arr){header('Content-Type: application/json'); echo json_encode($arr); exit;}

$lrn = $_POST['lrn'] ?? '';
$otp  = $_POST['otp'] ?? '';

$stmt = $pdo->prepare("SELECT otp_code, otp_expiry FROM register WHERE lrn=:lrn LIMIT 1");
$stmt->execute([':lrn'=>$lrn]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student) send_json(['success'=>false,'msg'=>'Student not found.']);
if($student['otp_code'] !== $otp) send_json(['success'=>false,'msg'=>'Incorrect OTP.']);
if(strtotime($student['otp_expiry']) < time()) send_json(['success'=>false,'msg'=>'OTP expired.']);

$update = $pdo->prepare("UPDATE register SET email_verified=true, otp_code=null, otp_expiry=null WHERE lrn=:lrn");
$update->execute([':lrn'=>$lrn]);

send_json(['success'=>true,'msg'=>'Email verified successfully!']);