<?php
/**
 * Section 5: Import / Export
 * Portable backup and restore for Channels, AI Assistant, and Support Center settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dctc_support_settings = get_option( 'dctc_support_settings', array() );
$dctc_support_active   = ! empty( $dctc_support_settings['enabled'] );
?>

<div class="dctc-ie-section-wrap">
	<h2 class="dctc-section-title"><?php esc_html_e( 'Import & Export Settings', 'dragwyb-click-to-chat' ); ?></h2>
	<p style="color: #6b7280; font-size: 14px; margin-top: -10px; margin-bottom: 25px; line-height: 1.5;">
		<?php esc_html_e( 'Easily transfer your settings between staging and production sites. Sensitive API keys and token credentials are encrypted or omitted for security.', 'dragwyb-click-to-chat' ); ?>
	</p>

	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">

		<!-- Export Card -->
		<div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
			<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
				<div style="width: 40px; height: 40px; border-radius: 10px; background: #f3e8ff; display: flex; align-items: center; justify-content: center; font-size: 20px;">
					📦
				</div>
				<div>
					<h3 style="font-size: 17px; font-weight: 700; color: #111827; margin: 0;"><?php esc_html_e( 'Export Settings', 'dragwyb-click-to-chat' ); ?></h3>
					<span style="font-size: 12.5px; color: #6b7280;"><?php esc_html_e( 'Generate downloadable configuration backup', 'dragwyb-click-to-chat' ); ?></span>
				</div>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="dctc-ie-export-form" style="display: flex; flex-direction: column; flex: 1; justify-content: space-between;">
				<input type="hidden" name="action" value="dctc_export_settings" />
				<?php wp_nonce_field( 'dctc_import_export' ); ?>

				<div>
					<div style="margin: 18px 0 14px;">
						<label style="font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">
							<?php esc_html_e( 'Include Modules in Backup:', 'dragwyb-click-to-chat' ); ?>
						</label>
						<div style="display: flex; flex-direction: column; gap: 8px; background: #f9fafb; padding: 12px 14px; border-radius: 8px; border: 1px solid #f3f4f6;">
							<label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #1f2937; cursor: pointer;">
								<input name="dctc_modules[]" type="checkbox" id="dctc_export_channels" value="channels" checked="checked" style="accent-color: #8e44ad;" />
								<span>📱 <strong><?php esc_html_e( 'Channels', 'dragwyb-click-to-chat' ); ?></strong> <small style="color:#6b7280;">(Multi-channel buttons, layout & triggers)</small></span>
							</label>
							<label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #1f2937; cursor: pointer;">
								<input name="dctc_modules[]" type="checkbox" id="dctc_export_ai" value="ai" checked="checked" style="accent-color: #8e44ad;" />
								<span>🤖 <strong><?php esc_html_e( 'AI Assistant', 'dragwyb-click-to-chat' ); ?></strong> <small style="color:#6b7280;">(Bot identity, styling & knowledge base text)</small></span>
							</label>
						</div>
					</div>

					<div style="margin-bottom: 20px;">
						<label style="font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">
							<?php esc_html_e( 'File Format:', 'dragwyb-click-to-chat' ); ?>
						</label>
						<div style="display: flex; gap: 16px;">
							<label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer;">
								<input name="dctc_format" type="radio" id="dctc_format_json" value="json" checked="checked" style="accent-color: #8e44ad;" />
								<span><strong>JSON</strong> <small style="color:#8e44ad;">(Recommended)</small></span>
							</label>
							<label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer;">
								<input name="dctc_format" type="radio" id="dctc_format_csv" value="csv" style="accent-color: #8e44ad;" />
								<span>CSV</span>
							</label>
							<label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer;">
								<input name="dctc_format" type="radio" id="dctc_format_sql" value="sql" style="accent-color: #8e44ad;" />
								<span>SQL</span>
							</label>
						</div>
					</div>
				</div>

				<button type="submit" class="dctc-btn dctc-btn-primary" style="width: 100%; justify-content: center; padding: 10px 16px;">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
						<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
						<polyline points="7 10 12 15 17 10"></polyline>
						<line x1="12" y1="15" x2="12" y2="3"></line>
					</svg>
					<?php esc_html_e( 'Download Export File', 'dragwyb-click-to-chat' ); ?>
				</button>
			</form>
		</div>

		<!-- Import Card -->
		<div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
			<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
				<div style="width: 40px; height: 40px; border-radius: 10px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; font-size: 20px;">
					📥
				</div>
				<div>
					<h3 style="font-size: 17px; font-weight: 700; color: #111827; margin: 0;"><?php esc_html_e( 'Import & Restore', 'dragwyb-click-to-chat' ); ?></h3>
					<span style="font-size: 12.5px; color: #6b7280;"><?php esc_html_e( 'Restore settings from previously exported backup', 'dragwyb-click-to-chat' ); ?></span>
				</div>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" id="dctc-ie-import-form" style="display: flex; flex-direction: column; flex: 1; justify-content: space-between;">
				<input type="hidden" name="action" value="dctc_import_settings" />
				<?php wp_nonce_field( 'dctc_import_export' ); ?>

				<div>
					<div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 14px; margin: 10px 0 16px; font-size: 12.5px; color: #1e40af; line-height: 1.4;">
						🛡️ <strong><?php esc_html_e( 'Safe Import:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Your current API keys will remain untouched. Only configuration options are restored.', 'dragwyb-click-to-chat' ); ?>
					</div>

					<div style="margin-bottom: 14px;">
						<label style="font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">
							<?php esc_html_e( 'Apply to Modules:', 'dragwyb-click-to-chat' ); ?>
						</label>
						<div style="display: flex; gap: 16px; background: #f9fafb; padding: 10px 14px; border-radius: 8px; border: 1px solid #f3f4f6;">
							<label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer;">
								<input name="dctc_import_modules[]" type="checkbox" id="dctc_import_channels" value="channels" checked="checked" style="accent-color: #8e44ad;" />
								<span>📱 <?php esc_html_e( 'Channels', 'dragwyb-click-to-chat' ); ?></span>
							</label>
							<label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer;">
								<input name="dctc_import_modules[]" type="checkbox" id="dctc_import_ai" value="ai" checked="checked" style="accent-color: #8e44ad;" />
								<span>🤖 <?php esc_html_e( 'AI Assistant', 'dragwyb-click-to-chat' ); ?></span>
							</label>
						</div>
					</div>

					<div style="margin-bottom: 20px;">
						<label for="dctc_import_file" style="font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">
							<?php esc_html_e( 'Select Backup File (.json, .csv, .sql):', 'dragwyb-click-to-chat' ); ?>
						</label>
						<input
							type="file"
							name="dctc_import_file"
							id="dctc_import_file"
							accept=".json,.csv,.sql,application/json,text/csv,application/sql"
							required
							style="width: 100%; font-size: 13px; padding: 8px 12px; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 8px; cursor: pointer;"
						/>
					</div>
				</div>

				<button type="submit" class="dctc-btn dctc-btn-secondary" style="width: 100%; justify-content: center; padding: 10px 16px; color: #111827; border-color: #d1d5db;">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
						<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
						<polyline points="17 8 12 3 7 8"></polyline>
						<line x1="12" y1="3" x2="12" y2="15"></line>
					</svg>
					<?php esc_html_e( 'Import & Restore Settings', 'dragwyb-click-to-chat' ); ?>
				</button>
			</form>
		</div>

	</div>
</div>
