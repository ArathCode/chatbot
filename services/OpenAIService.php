<?php
function interpretarMensaje($mensaje) {

    $prompt = "Eres un asistente de búsqueda de propiedades inmobiliarias. Analiza el mensaje y retorna JSON estricto (sin markdown, sin triple backticks).

Si el usuario busca PROPIEDADES (puede ser parcial):
- Si dice 'propiedades', 'inmuebles', 'terrenos', 'casas', 'departamentos' → extrae lo que mencionó
- Si menciona una ubicación específica → extrae esa ubicación
- Si NO menciona ubicación específica → devuelve null en ubicacion
- Si NO menciona tipo específico → devuelve null en tipo_propiedad

{
  \"tipo_accion\": \"buscar_propiedad\",
  \"tipo_propiedad\": \"tipo (casa/departamento/terreno/propiedad) o null\",
  \"ubicacion\": \"ubicación específica o null\"
}

Si el usuario quiere HABLAR CON EJECUTIVO (palabras clave: ejecutivo, asesor, contacto, agente, vendedor):
{
  \"tipo_accion\": \"contacto_ejecutivo\"
}

Mensaje: $mensaje

Responde SOLO el JSON, nada más.";

    $data = [
        "model" => "gpt-4o-mini",
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ]
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . OPENAI_API_KEY,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if ($response === false) {
        return ['error' => 'CURL ERROR: ' . curl_error($ch)];
    }

    curl_close($ch);

    $result = json_decode($response, true);

    // 🔥 VALIDACIÓN SEGURA
    if (!isset($result["choices"][0]["message"]["content"])) {
        return ['error' => 'Respuesta inválida de OpenAI', 'debug' => $result];
    }

    $content = trim($result["choices"][0]["message"]["content"]);
    
    // Limpiar markdown (si viene con ```)
    $content = preg_replace('/^```json\s*/i', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);
    $content = trim($content);
    
    // Intentar extraer JSON si está dentro de texto
    if (preg_match('/\{.*\}/s', $content, $matches)) {
        $content = $matches[0];
    }
    
    $decoded = json_decode($content, true);
    
    if ($decoded === null) {
        return ['error' => 'JSON inválido en respuesta', 'content' => $content];
    }
    
    return $decoded;
}