<?php
// correo.php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . "/lib/PHPMailer/src/Exception.php";
require_once __DIR__ . "/lib/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/lib/PHPMailer/src/SMTP.php";

function enviarCorreo($para, $asunto, $html) {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = "smtp.gmail.com";
    $mail->SMTPAuth   = true;

    // ✅ CAMBIA ESTO
    $mail->Username   = "carlosoft0@gmail.com";
    $mail->Password   = "vsftvvfkguuylhob";

    $mail->Port       = 587;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->CharSet = "UTF-8";
    $mail->setFrom($mail->Username, "Plataforma Cursos");
    $mail->addAddress($para);

    $mail->isHTML(true);
    $mail->Subject = $asunto;
    $mail->Body    = $html;
    $mail->AltBody = strip_tags($html);

    return $mail->send();
}
