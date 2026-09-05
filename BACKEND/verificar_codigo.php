<?php
require_once 'conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['email'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');

    $stmt = $pdo->prepare("SELECT id, codigo_verificacion FROM turistas WHERE correo = ? AND estado = 'pendiente'");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && $usuario['codigo_verificacion'] === $codigo) {
        $update = $pdo->prepare("UPDATE turistas SET estado = 'activo', codigo_verificacion = NULL WHERE id = ?");
        $update->execute([$usuario['id']]);

        echo json_encode(["status" => "success", "message" => "Cuenta verificada con éxito. Ya puedes iniciar sesión."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Código incorrecto o cuenta ya verificada."]);
    }
}
?>