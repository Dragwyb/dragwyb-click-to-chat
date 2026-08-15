<?php
/**
 * Settings — Import / Export page.
 *
 * Classic WordPress settings layout: postboxes + form-table.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap dctc-settings-page">
	<h1><?php esc_html_e( 'Settings', 'dragwyb-click-to-chat' ); ?></h1>
	<p class="dctc-settings-page__intro">
		<?php esc_html_e( 'Back up or restore Channels and AI Assistant configuration. Chat history, knowledge-base embeddings, and API keys are never included in exports.', 'dragwyb-click-to-chat' ); ?>
	</p>

	<div class="dctc-settings-page__columns">
		<div class="dctc-settings-box postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php esc_html_e( 'Export', 'dragwyb-click-to-chat' ); ?></h2>
			</div>
			<div class="inside">
				<p class="dctc-settings-box__desc">
					<?php esc_html_e( 'Download a portable backup of the selected modules.', 'dragwyb-click-to-chat' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="dctc-ie-export-form">
					<input type="hidden" name="action" value="dctc_export_settings" />
					<?php wp_nonce_field( 'dctc_import_export' ); ?>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Include', 'dragwyb-click-to-chat' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span><?php esc_html_e( 'Modules to export', 'dragwyb-click-to-chat' ); ?></span>
									</legend>
									<label for="dctc_export_channels">
										<input name="dctc_modules[]" type="checkbox" id="dctc_export_channels" value="channels" checked="checked" />
										<?php esc_html_e( 'Channels', 'dragwyb-click-to-chat' ); ?>
									</label>
									<br />
									<label for="dctc_export_ai">
										<input name="dctc_modules[]" type="checkbox" id="dctc_export_ai" value="ai" checked="checked" />
										<?php esc_html_e( 'AI Assistant', 'dragwyb-click-to-chat' ); ?>
									</label>
									<p class="description">
										<?php esc_html_e( 'Widget settings, chatbot config, display options, and knowledge text. API keys are excluded.', 'dragwyb-click-to-chat' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Format', 'dragwyb-click-to-chat' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span><?php esc_html_e( 'Export format', 'dragwyb-click-to-chat' ); ?></span>
									</legend>
									<label for="dctc_format_json">
										<input name="dctc_format" type="radio" id="dctc_format_json" value="json" checked="checked" />
										JSON
										<span class="description dctc-settings-inline-hint"><?php esc_html_e( '— recommended for re-import', 'dragwyb-click-to-chat' ); ?></span>
									</label>
									<br />
									<label for="dctc_format_csv">
										<input name="dctc_format" type="radio" id="dctc_format_csv" value="csv" />
										CSV
										<span class="description dctc-settings-inline-hint"><?php esc_html_e( '— spreadsheet compatible', 'dragwyb-click-to-chat' ); ?></span>
									</label>
									<br />
									<label for="dctc_format_sql">
										<input name="dctc_format" type="radio" id="dctc_format_sql" value="sql" />
										SQL
										<span class="description dctc-settings-inline-hint"><?php esc_html_e( '— wp_options statements for phpMyAdmin', 'dragwyb-click-to-chat' ); ?></span>
									</label>
								</fieldset>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Download export', 'dragwyb-click-to-chat' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>

		<div class="dctc-settings-box postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php esc_html_e( 'Import', 'dragwyb-click-to-chat' ); ?></h2>
			</div>
			<div class="inside">
				<p class="dctc-settings-box__desc">
					<?php esc_html_e( 'Restore settings from a previous export file.', 'dragwyb-click-to-chat' ); ?>
				</p>

				<div class="notice notice-info inline dctc-settings-notice">
					<p>
						<?php esc_html_e( 'API keys are not imported. Existing OpenAI, Google, and Pinecone keys on this site are left unchanged.', 'dragwyb-click-to-chat' ); ?>
					</p>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" id="dctc-ie-import-form">
					<input type="hidden" name="action" value="dctc_import_settings" />
					<?php wp_nonce_field( 'dctc_import_export' ); ?>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Apply to', 'dragwyb-click-to-chat' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span><?php esc_html_e( 'Modules to import', 'dragwyb-click-to-chat' ); ?></span>
									</legend>
									<label for="dctc_import_channels">
										<input name="dctc_import_modules[]" type="checkbox" id="dctc_import_channels" value="channels" checked="checked" />
										<?php esc_html_e( 'Channels', 'dragwyb-click-to-chat' ); ?>
									</label>
									<br />
									<label for="dctc_import_ai">
										<input name="dctc_import_modules[]" type="checkbox" id="dctc_import_ai" value="ai" checked="checked" />
										<?php esc_html_e( 'AI Assistant', 'dragwyb-click-to-chat' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="dctc_import_file"><?php esc_html_e( 'File', 'dragwyb-click-to-chat' ); ?></label>
							</th>
							<td>
								<input
									type="file"
									name="dctc_import_file"
									id="dctc_import_file"
									accept=".json,.csv,.sql,application/json,text/csv,application/sql"
									required
								/>
								<p class="description">
									<?php esc_html_e( 'Accepted types: .json, .csv, .sql. Maximum size: 2 MB.', 'dragwyb-click-to-chat' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary" id="dctc-ie-import-btn">
							<?php esc_html_e( 'Import settings', 'dragwyb-click-to-chat' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>
	</div>
</div>
