<?php
/**
 * Groq API Wrapper
 *
 * @package IAPOST_Groq
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IAPOSTGROQ_Groq_API {

    private string $api_key;
    private string $model;
    private string $endpoint = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct() {
        $this->api_key = (string) get_option( 'iapostgroq_api_key', '' );
        $this->model   = (string) get_option( 'iapostgroq_model', 'llama-3.3-70b-versatile' );
    }

    /**
     * Send a chat completion request to Groq.
     *
     * @param array $messages
     * @param int   $max_tokens
     * @param float $temperature
     * @return array{text: string, usage: array}
     * @throws RuntimeException
     */
    public function chat( array $messages, int $max_tokens = 2000, float $temperature = 0.7 ): array {
        if ( empty( $this->api_key ) ) {
            throw new RuntimeException( 'Groq API key not configured.' );
        }

        $payload = json_encode( array(
            'model'                => $this->model,
            'messages'             => $messages,
            'max_completion_tokens' => $max_tokens,
            'temperature'          => $temperature,
        ) );

        $ch = curl_init();
        curl_setopt_array( $ch, array(
            CURLOPT_URL            => $this->endpoint,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key,
            ),
        ) );

        $raw      = curl_exec( $ch );
        $curl_err = curl_error( $ch );
        $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
        curl_close( $ch );

        if ( $curl_err ) {
            throw new RuntimeException( 'cURL error: ' . $curl_err );
        }

        $body = substr( $raw, $header_size );
        $data = json_decode( $body, true );

        if ( $http_code !== 200 ) {
            $headers_raw  = substr( $raw, 0, $header_size );
            $retry_after  = '';
            if ( preg_match( '/retry-after:\s*(\d+)/i', $headers_raw, $m ) ) {
                $retry_after = ' (retry-after: ' . $m[1] . 's)';
            }
            $error_msg = isset( $data['error']['message'] )
                ? $data['error']['message']
                : 'HTTP ' . $http_code;
            throw new RuntimeException( 'Groq API error: ' . $error_msg . $retry_after );
        }

        if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
            throw new RuntimeException( 'Unexpected Groq API response structure.' );
        }

        return array(
            'text'  => $data['choices'][0]['message']['content'],
            'usage' => $data['usage'] ?? array(),
        );
    }

    /**
     * Test API connectivity.
     *
     * @return array{success: bool, message: string}
     */
    public function test_connection(): array {
        try {
            $result = $this->chat(
                array(
                    array( 'role' => 'user', 'content' => 'Di ok' ),
                ),
                50,
                0.1
            );
            return array(
                'success' => true,
                'message' => 'Conexión exitosa. Respuesta: ' . esc_html( $result['text'] ),
            );
        } catch ( RuntimeException $e ) {
            return array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
    }
}
