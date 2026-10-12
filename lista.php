<?php
// Intermediario del formulario de la lista de espera.
// Recibe el alta desde la landing y la reenvía a n8n por dentro del servidor, para que la dirección de n8n no
// aparezca nunca en la página. Esa dirección vive en la variable de entorno N8N_WEBHOOK_URL (EasyPanel).

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');

function responder($codigo, $motivo) {
    http_response_code($codigo);
    echo json_encode(['ok' => false, 'motivo' => $motivo]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responder(405, 'metodo_no_permitido');
}

$destino = getenv('N8N_WEBHOOK_URL');
if (!$destino) {
    responder(503, 'configuracion_pendiente');
}

// Como mucho 8 KB: el formulario manda bastante menos. Si llega algo más largo o que no es JSON, se rechaza.
$cuerpo = file_get_contents('php://input', false, null, 0, 8192);
if ($cuerpo === false || $cuerpo === '' || json_decode($cuerpo) === null) {
    responder(400, 'datos_no_validos');
}

// Se reenvía el navegador del visitante: n8n descarta los bots por su firma.
$navegador = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);
if ($navegador === '') {
    $navegador = 'CryptoWolfLanding/1.0';
}

$ch = curl_init($destino);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $cuerpo,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'User-Agent: ' . $navegador],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 25,
]);
$respuesta = curl_exec($ch);
$codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Si el flujo de n8n está apagado (404) o no responde, la landing dice «La lista aún no está abierta».
if ($respuesta === false || $codigo === 404) {
    responder(503, 'configuracion_pendiente');
}
if (json_decode($respuesta) === null) {
    responder(502, 'error_interno');
}

http_response_code(($codigo >= 200 && $codigo < 300) ? 200 : 502);
echo $respuesta;
