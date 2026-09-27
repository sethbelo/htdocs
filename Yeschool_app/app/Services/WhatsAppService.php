<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp message to a parent.
     *
     * @param string $phone The parent's phone number (e.g. +243812345678)
     * @param string $message The message content
     * @return array ['success' => bool, 'log' => string]
     */
    public static function sendMessage(string $phone, string $message): array
    {
        $enabled = Setting::get('whatsapp_api_enabled', 'false') === 'true';
        $apiUrl = Setting::get('whatsapp_api_url', '');
        $apiToken = Setting::get('whatsapp_api_token', '');
        
        // Normalize phone number (remove spaces, dashes, ensure leading + or similar if needed)
        $normalizedPhone = preg_replace('/[^0-9+]/', '', $phone);
        if (empty($normalizedPhone)) {
            return [
                'success' => false,
                'log' => "Numéro de téléphone invalide ou vide."
            ];
        }

        // If not enabled (Simulation Mode)
        if (!$enabled) {
            $simLog = "[" . date('Y-m-d H:i:s') . "] SIMULATION WHATSAPP pour {$normalizedPhone} : \"{$message}\"";
            Log::channel('single')->info($simLog);
            
            // Append to a global simulation log stored in settings for easy viewing in the dashboard
            $existingLogs = json_decode(Setting::get('whatsapp_simulation_logs', '[]'), true) ?? [];
            array_unshift($existingLogs, [
                'date' => date('Y-m-d H:i:s'),
                'phone' => $normalizedPhone,
                'message' => $message,
                'status' => 'simulated'
            ]);
            // Keep last 100 logs
            $existingLogs = array_slice($existingLogs, 0, 100);
            Setting::set('whatsapp_simulation_logs', json_encode($existingLogs));

            return [
                'success' => true,
                'log' => "Simulé avec succès. Message : \"{$message}\""
            ];
        }

        // If enabled (Actual API Call)
        if (empty($apiUrl)) {
            return [
                'success' => false,
                'log' => "L'URL de l'API WhatsApp n'est pas configurée dans les réglages."
            ];
        }

        try {
            // Prepare payload according to generic API standards (compatible with Evolution API / UltraMsg / Twilio style)
            // By default, let's design it to support standard JSON POST with authorization header
            $headers = [];
            if (!empty($apiToken)) {
                // If it's a bearer token or evolution-api custom header
                if (str_contains(strtolower($apiUrl), 'evolution')) {
                    $headers['apikey'] = $apiToken;
                } else {
                    $headers['Authorization'] = 'Bearer ' . $apiToken;
                }
            }

            // Standard payload: number + text
            // Evolution API standard payload uses "number" and "text" inside "message"
            // We support a flexible structure
            $payload = [
                'number' => $normalizedPhone,
                'to' => $normalizedPhone, // fallback
                'phone' => $normalizedPhone, // fallback
                'message' => $message,
                'text' => $message // fallback for some APIs
            ];

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($apiUrl, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'log' => "API WhatsApp : Réponse " . $response->status() . " - " . $response->body()
                ];
            } else {
                return [
                    'success' => false,
                    'log' => "Échec de l'API WhatsApp : Code " . $response->status() . " - " . $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error("Erreur de connexion WhatsApp API: " . $e->getMessage());
            return [
                'success' => false,
                'log' => "Exception de connexion : " . $e->getMessage()
            ];
        }
    }

    /**
     * Build the message template for attendance alert.
     *
     * @param string $parentName
     * @param string $studentName
     * @param string $className
     * @param string $status ('absent' or 'late')
     * @param string $date
     * @param string $reason
     * @return string
     */
    public static function buildTemplate(string $parentName, string $studentName, string $className, string $status, string $date, string $reason = ''): string
    {
        $statusTranslated = $status === 'absent' ? 'absent(e)' : ($status === 'late' ? 'en retard' : 'présent(e)');
        $reasonText = empty($reason) ? 'Non spécifié' : $reason;

        $defaultTemplate = "Bonjour {parent_name}, nous vous informons que votre enfant {student_name} de la classe {class_name} a été marqué(e) {status} ce {date}. Motif: {reason}. Merci de nous contacter pour plus d'informations.";
        $template = Setting::get('whatsapp_message_template', $defaultTemplate);

        return strtr($template, [
            '{parent_name}' => $parentName ?: 'Parent',
            '{student_name}' => $studentName,
            '{class_name}' => $className,
            '{status}' => $statusTranslated,
            '{date}' => $date,
            '{reason}' => $reasonText
        ]);
    }
}
