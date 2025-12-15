<?php
require __DIR__ . '/../app/Mailer.php';

try {
    $mail = makeMailer();

    // Debug solo para probar
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';

    $mail->addAddress('arturo.rosales.c@hotmail.com');
    $mail->Subject = 'Test PHPMailer (manual) + InfinityFree';
    $mail->Body    = '<b>Si llegó este correo, Gmail + PHPMailer funcionan ✅</b>';
    $mail->AltBody = 'Si llegó este correo, Gmail + PHPMailer funcionan';

    $mail->send();
    echo "✅ CORREO ENVIADO";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
