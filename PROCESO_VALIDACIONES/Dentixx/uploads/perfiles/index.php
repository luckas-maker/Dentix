<?php
// Archivo de seguridad para carpeta de perfiles
header('HTTP/1.0 403 Forbidden');
header('Content-Type: text/plain; charset=utf-8');
exit('Acceso denegado - Área restringida');
?>