<?php
require_once 'conexion.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';

    if (empty($nombre) || empty($apellido) || empty($correo) || empty($password_raw)) {
        echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM turistas WHERE correo = ?");
    $stmt->execute([$correo]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "El correo electrónico ya está registrado."]);
        exit;
    }

    $password_hash = password_hash($password_raw, PASSWORD_BCRYPT);
    $codigo_verificacion = rand(100000, 999999);

    $stmt = $pdo->prepare("INSERT INTO turistas (nombre, apellido, correo, password, codigo_verificacion, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
    
    if ($stmt->execute([$nombre, $apellido, $correo, $password_hash, $codigo_verificacion])) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'tu_correo@gmail.com'; // Tu correo real
            $mail->Password = 'tu_contraseña_de_aplicacion'; // Contraseña de aplicación de Google
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('tu_correo@gmail.com', 'Go Nic');
            $mail->addAddress($correo, "$nombre $apellido");

            $mail->isHTML(true);
            $mail->Subject = 'Codigo de verificacion - Go Nic';
            $mail->Body    = "Hola <b>$nombre</b>,<br>Tu código de verificación para Go Nic es: <h2>$codigo_verificacion</h2>";

            $mail->send();
            echo json_encode(["status" => "success", "message" => "Registro exitoso. Revisa tu correo para verificar tu cuenta."]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Usuario creado, pero falló el envío del correo: {$mail->ErrorInfo}"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Error al registrar en la base de datos."]);
    }
}
?>