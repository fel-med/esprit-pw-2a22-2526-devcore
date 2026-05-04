<?php

class config
{
    private static $pdo = null;

    public static function getConnexion(): PDO
    {
        if (!isset(self::$pdo)) {
            try {
                self::$pdo = new PDO(
                    'mysql:host=localhost;dbname=cre8connect;charset=utf8',
                    'root',
                    '',
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (Exception $e) {
                die('Erreur connexion: ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}

/**
 * Appelle l'API OpenRouter et retourne le contenu texte brut.
 */
function callOpenRouter(string $prompt): ?string
{
    $apiKey = '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';
    $url    = 'https://openrouter.ai/api/v1/chat/completions';

    $data = [
        'model'       => 'deepseek/deepseek-chat-v3-0324:free',
        'messages'    => [
            [
                'role'    => 'system',
                'content' => 'Tu es un assistant expert en marketing digital, campagnes publicitaires '
                           . 'et contrats de collaboration entre marques et créateurs de contenu. '
                           . 'Réponds toujours en JSON valide.',
            ],
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ],
        'temperature' => 0.7,
        'max_tokens'  => 1500,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'HTTP-Referer: http://localhost/projet/Esprit-PW-2A22-2526-Devcore',
            'X-Title: Cre8Connect',
        ],
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $httpCode !== 200) {
        error_log("OpenRouter API Error: HTTP $httpCode — $curlErr");
        return null;
    }

    $decoded = json_decode($response, true);
    return $decoded['choices'][0]['message']['content'] ?? null;
}