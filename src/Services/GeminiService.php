<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Client minimal de l'API Gemini (Google AI Studio), repris de Filmi :
 * transport injectable pour les tests, cle jamais exposee au navigateur.
 *
 * La cle part dans l'en-tete x-goog-api-key et non dans l'URL : une URL finit dans
 * les messages d'erreur et les journaux, un en-tete non.
 */
final class GeminiService
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /** Code d'une LlmException qui vaut la peine d'etre retentee (surcharge, delai depasse). */
    private const TRANSIENT = 503;

    /** @var callable(string, array<int, string>, string): string */
    private $transport;

    public function __construct(
        private string $apiKey,
        private string $model = 'gemini-flash-lite-latest',
        ?callable $transport = null,
        private string $fallbackModel = 'gemini-3.1-flash-lite'
    ) {
        $this->transport = $transport ?? [$this, 'curlPost'];
    }

    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '';
    }

    /**
     * Demande une reponse JSON contrainte par un schema et la renvoie decodee.
     *
     * @param array<string, mixed> $schema schema de reponse (sous-ensemble OpenAPI)
     * @return array<string, mixed>
     */
    public function generateJson(string $system, string $user, array $schema, float $temperature = 0.4): array
    {
        if (!$this->isConfigured()) {
            throw new LlmException("Aucune clé Gemini configurée.");
        }

        $body = json_encode([
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
                'temperature' => $temperature,
            ],
        ], JSON_UNESCAPED_UNICODE);

        // Les modeles « flash » saturent regulierement chez Google (503 ou silence).
        // Une surcharge est passagere : on retente une fois avec le modele de repli.
        $models = array_values(array_unique(array_filter([$this->model, $this->fallbackModel])));
        $raw = '';
        foreach ($models as $i => $model) {
            try {
                $raw = $this->call($model, $body);
                break;
            } catch (LlmException $e) {
                if ($e->getCode() !== self::TRANSIENT || $i === count($models) - 1) {
                    throw $e;
                }
            }
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new LlmException('Réponse Gemini illisible.');
        }

        $blocked = $decoded['promptFeedback']['blockReason'] ?? null;
        if ($blocked !== null) {
            throw new LlmException('Gemini a refusé la demande (' . (string) $blocked . ').');
        }

        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new LlmException("Gemini n'a rien renvoyé d'exploitable.");
        }

        $payload = json_decode($text, true);
        if (!is_array($payload)) {
            throw new LlmException('Gemini a renvoyé un JSON invalide.');
        }

        return $payload;
    }

    private function call(string $model, string $body): string
    {
        $url = self::API_BASE . rawurlencode($model) . ':generateContent';

        try {
            return ($this->transport)($url, [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $this->apiKey,
            ], $body);
        } catch (LlmException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new LlmException('Appel Gemini en échec : ' . $this->redact($e->getMessage()));
        }
    }

    private function curlPost(string $url, array $headers, string $body): string
    {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            // Deux essais maximum (modele puis repli) restent sous la limite de describe_item.php.
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        unset($handle);

        if ($response === false) {
            throw new LlmException('Réseau indisponible : ' . $this->redact($error), self::TRANSIENT);
        }
        if ($status === 429) {
            throw new LlmException("Le quota gratuit de Gemini est atteint pour le moment. Réessaie plus tard.");
        }
        if ($status === 400 || $status === 403) {
            throw new LlmException("Gemini a refusé la requête ({$status}) : clé invalide ou non autorisée ?");
        }
        if ($status >= 400) {
            throw new LlmException("Gemini a répondu {$status}.", $status === 500 || $status === 503 ? self::TRANSIENT : 0);
        }

        return (string) $response;
    }

    private function redact(string $message): string
    {
        return $this->apiKey === ''
            ? $message
            : str_replace($this->apiKey, '[clef masquée]', $message);
    }
}
