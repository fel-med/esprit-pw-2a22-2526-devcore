<?php

// ── Charger .env ──────────────────────────────────────────────────────────────
function loadEnv(string $path): void
{
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}
loadEnv(__DIR__ . '/.env');

// ── Connexion DB ──────────────────────────────────────────────────────────────
class Config
{
    private static $pdo = null;

    public static function getConnexion(): PDO
    {
        if (!isset(self::$pdo)) {
            try {
                self::$pdo = new PDO(
                    'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost')
                    . ';dbname=' . ($_ENV['DB_NAME'] ?? 'cre8connect')
                    . ';charset=utf8',
                    $_ENV['DB_USER'] ?? 'root',
                    $_ENV['DB_PASS'] ?? '',
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

// ── Groq API ──────────────────────────────────────────────────────────────────
function callOpenRouter(string $prompt): ?string
{
    $apiKey = $_ENV['GROQ_API_KEY'] ?? '';

 HEAD
    // 🔴 Sécurité : ne pas appeler l'API si la clé est absente
    if (empty($apiKey)) {
        error_log('GROQ_API_KEY manquante dans .env');
        return null;

  public static function getConnexion()

  {

    if (!isset(self::$pdo)) {

      try {

        self::$pdo = new PDO(

          'mysql:host=localhost;dbname=cre8connect',

          'root',

          '',

          [

            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC

          ]

        );

      } catch (Exception $e) {

        die('Erreur: ' . $e->getMessage());

      }

 origin/main
    }

    $url  = 'https://api.groq.com/openai/v1/chat/completions';
    $data = [
        'model'       => 'llama-3.3-70b-versatile',
        'messages'    => [
            [
                'role'    => 'system',
                'content' => 'Tu es un assistant expert en marketing digital. Réponds toujours en JSON valide uniquement.',
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
        ],
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Groq API Error: HTTP $httpCode — " . $response);
        return null;
    }

    $decoded = json_decode($response, true);
    return $decoded['choices'][0]['message']['content'] ?? null;
}