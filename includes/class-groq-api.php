<?php
/**
 * AI Provider API Wrapper
 *
 * Supports Groq and OpenAI (both use OpenAI-compatible Chat Completions API).
 *
 * @package IAPOST_Groq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IAPOSTGROQ_Groq_API {

	private string $api_key;
	private string $model;
	private string $endpoint;
	private string $provider;

	public function __construct() {
		$this->provider = (string) get_option( 'iapostgroq_provider', 'groq' );
		$this->model    = (string) get_option( 'iapostgroq_model', 'llama-3.3-70b-versatile' );

		if ( 'openai' === $this->provider ) {
			$this->api_key  = (string) get_option( 'iapostgroq_openai_api_key', '' );
			$this->endpoint = 'https://api.openai.com/v1/chat/completions';
		} else {
			$this->api_key  = (string) get_option( 'iapostgroq_api_key', '' );
			$this->endpoint = 'https://api.groq.com/openai/v1/chat/completions';
		}
	}

	/**
	 * Available models grouped by provider.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function available_models(): array {
		return array(
			'groq' => array(
				'llama-3.3-70b-versatile'       => 'LLaMA 3.3 70B Versatile (recomendado)',
				'llama-3.1-70b-versatile'       => 'LLaMA 3.1 70B Versatile',
				'llama-3.1-8b-instant'          => 'LLaMA 3.1 8B Instant (rápido)',
				'llama3-70b-8192'               => 'LLaMA 3 70B',
				'llama3-8b-8192'                => 'LLaMA 3 8B',
				'mixtral-8x7b-32768'            => 'Mixtral 8x7B 32768',
				'gemma2-9b-it'                  => 'Gemma2 9B IT',
				'gemma-7b-it'                   => 'Gemma 7B IT',
				'deepseek-r1-distill-llama-70b' => 'DeepSeek R1 Distill LLaMA 70B',
				'qwen-qwq-32b'                  => 'Qwen QwQ 32B',
			),
			'openai' => array(
				'gpt-4o'        => 'GPT-4o (recomendado)',
				'gpt-4o-mini'   => 'GPT-4o Mini (rápido / económico)',
				'gpt-4-turbo'   => 'GPT-4 Turbo',
				'gpt-3.5-turbo' => 'GPT-3.5 Turbo (económico)',
			),
		);
	}

	/**
	 * Send a chat completion request.
	 *
	 * @param array $messages
	 * @param int   $max_tokens
	 * @param float $temperature
	 * @return array{text: string, usage: array}
	 * @throws RuntimeException
	 */
	public function chat( array $messages, int $max_tokens = 2000, float $temperature = 0.7 ): array {
		$provider_label = ( 'openai' === $this->provider ) ? 'OpenAI' : 'Groq';

		if ( empty( $this->api_key ) ) {
			throw new RuntimeException( $provider_label . ' API key not configured.' );
		}

		// Groq uses max_completion_tokens; OpenAI Chat Completions uses max_tokens.
		$tokens_param = ( 'openai' === $this->provider ) ? 'max_tokens' : 'max_completion_tokens';

		$payload = json_encode( array(
			'model'       => $this->model,
			'messages'    => $messages,
			$tokens_param => $max_tokens,
			'temperature' => $temperature,
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

		$raw         = curl_exec( $ch );
		$curl_err    = curl_error( $ch );
		$http_code   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		curl_close( $ch );

		if ( $curl_err ) {
			throw new RuntimeException( 'cURL error: ' . $curl_err );
		}

		$body = substr( $raw, $header_size );
		$data = json_decode( $body, true );

		if ( $http_code !== 200 ) {
			$headers_raw = substr( $raw, 0, $header_size );
			$retry_after = '';
			if ( preg_match( '/retry-after:\s*(\d+)/i', $headers_raw, $m ) ) {
				$retry_after = ' (retry-after: ' . $m[1] . 's)';
			}
			$error_msg = isset( $data['error']['message'] )
				? $data['error']['message']
				: 'HTTP ' . $http_code;
			throw new RuntimeException( $provider_label . ' API error: ' . $error_msg . $retry_after );
		}

		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			throw new RuntimeException( 'Unexpected ' . $provider_label . ' API response structure.' );
		}

		return array(
			'text'  => $data['choices'][0]['message']['content'],
			'usage' => $data['usage'] ?? array(),
		);
	}

	/**
	 * Test API connectivity with the currently configured provider.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function test_connection(): array {
		$provider_label = ( 'openai' === $this->provider ) ? 'OpenAI' : 'Groq';
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
				'message' => $provider_label . ' — Conexión exitosa. Respuesta: ' . esc_html( $result['text'] ),
			);
		} catch ( RuntimeException $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}
}
