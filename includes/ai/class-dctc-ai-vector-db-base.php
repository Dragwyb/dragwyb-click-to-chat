<?php
/**
 * DCTC AI Vector DB Base
 *
 * Abstract base class for vector database implementations.
 * Defines the interface all vector DB adapters must implement.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_AI_Vector_DB_Base
 */
abstract class DCTC_AI_Vector_DB_Base {

	/**
	 * Configuration
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 * Constructor
	 *
	 * @param array $config Configuration for the vector DB.
	 */
	public function __construct( $config = [] ) {
		$this->config = $config;
		$this->validate_config();
	}

	/**
	 * Validate Configuration
	 *
	 * Override in child classes to validate provider-specific config.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	protected function validate_config() {
		return true;
	}

	/**
	 * Store Embedding
	 *
	 * Stores a single embedding vector.
	 *
	 * @param string $chunk_id   Unique chunk ID.
	 * @param array  $embedding  Embedding vector.
	 * @param string $document_id Document ID (metadata).
	 * @return bool|WP_Error Success or error
	 */
	abstract public function store_embedding( $chunk_id, $embedding, $document_id = '' );

	/**
	 * Bulk Store Embeddings
	 *
	 * Stores multiple embeddings efficiently.
	 *
	 * @param array $embeddings Array of [chunk_id => embedding, ...].
	 * @return bool|WP_Error Success or error
	 */
	abstract public function bulk_store_embeddings( $embeddings );

	/**
	 * Search Embeddings
	 *
	 * Searches for similar embeddings.
	 *
	 * @param array $query_embedding Query embedding vector.
	 * @param int   $limit           Maximum results to return.
	 * @return array Array of results [chunk_id, similarity, ...]
	 */
	abstract public function search( $query_embedding, $limit = 5 );

	/**
	 * Get Embedding
	 *
	 * Retrieves a stored embedding.
	 *
	 * @param string $chunk_id Chunk ID.
	 * @return array|null Embedding or null if not found
	 */
	abstract public function get_embedding( $chunk_id );

	/**
	 * Delete Embedding
	 *
	 * Deletes a stored embedding.
	 *
	 * @param string $chunk_id Chunk ID.
	 * @return bool|WP_Error Success or error
	 */
	abstract public function delete_embedding( $chunk_id );

	/**
	 * Bulk Delete
	 *
	 * Deletes multiple embeddings.
	 *
	 * @param array $chunk_ids Array of chunk IDs.
	 * @return bool|WP_Error Success or error
	 */
	abstract public function bulk_delete( $chunk_ids );

	/**
	 * Test Connection
	 *
	 * Tests connectivity to the vector DB.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	abstract public function test_connection();

	/**
	 * Get Statistics
	 *
	 * Returns vector DB statistics.
	 *
	 * @return array Statistics
	 */
	abstract public function get_statistics();
}
