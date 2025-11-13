<?php
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $apellidos = $_POST["apellidos"];
    $correo = $_POST["correo"];
    $contrasena = $_POST["contrasena"];
    $rol = "Paciente"; // valor por defecto

    // Verificar si el correo ya existe
    $sqlCheck = "SELECT id_usuario FROM Usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sqlCheck);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "El correo ya está registrado.";
    } else {
        // Encriptar la contraseña
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $sql = "INSERT INTO Usuarios (nombre, apellidos, correo, contrasena, rol, estado_cuenta)
                VALUES (?, ?, ?, ?, ?, 'Pendiente')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $nombre, $apellidos, $correo, $hash, $rol);

        if ($stmt->execute()) {
            echo "Registro exitoso. Ya puedes iniciar sesión.";
        } else {
            echo "Error al registrar: " . $stmt->error;
        }
    }

    $stmt->close();
    $conn->close();
}
?>
