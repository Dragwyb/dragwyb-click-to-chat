import { useState, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function NewTicketModal({
	isOpen,
	onClose,
	categories = [],
	onTicketCreated,
	onShowNotice,
}) {
	const [newTicketData, setNewTicketData] = useState({
		subject: '',
		customer_name: '',
		customer_email: '',
		category_id: '',
		priority: 'normal',
		message: '',
		attachments: [],
	});
	const [creatingTicket, setCreatingTicket] = useState(false);
	const [modalEditorMode, setModalEditorMode] = useState('visual');
	const modalMessageInputRef = useRef(null);

	if (!isOpen) return null;

	const applyFormatting = (tagType) => {
		const textarea = modalMessageInputRef.current;
		if (!textarea) return;
		const start = textarea.selectionStart || 0;
		const end = textarea.selectionEnd || 0;
		const text = newTicketData.message || '';
		const selected = text.substring(start, end) || 'sample text';
		let replacement = '';
		if (tagType === 'bold') replacement = `<b>${selected}</b>`;
		else if (tagType === 'italic') replacement = `<i>${selected}</i>`;
		else if (tagType === 'underline') replacement = `<u>${selected}</u>`;
		else if (tagType === 'link') replacement = `<a href="https://example.com">${selected}</a>`;
		else if (tagType === 'ul') replacement = `\n<ul>\n  <li>${selected}</li>\n</ul>\n`;
		else if (tagType === 'ol') replacement = `\n<ol>\n  <li>${selected}</li>\n</ol>\n`;
		else if (tagType === 'quote') replacement = `\n<blockquote>${selected}</blockquote>\n`;
		else if (tagType === 'code') replacement = `<code>${selected}</code>`;

		const updated = text.substring(0, start) + replacement + text.substring(end);
		setNewTicketData((prev) => ({ ...prev, message: updated }));
		setTimeout(() => {
			if (textarea) {
				textarea.focus();
				textarea.setSelectionRange(start + replacement.length, start + replacement.length);
			}
		}, 50);
	};

	const handleAttachFiles = () => {
		if (window.wp && window.wp.media) {
			const frame = window.wp.media({
				title: __('Select or Upload Support Files', 'dragwyb-click-to-chat'),
				button: { text: __('Attach Files', 'dragwyb-click-to-chat') },
				multiple: true,
			});
			frame.on('select', () => {
				const selection = frame.state().get('selection').toJSON();
				const newAttachments = selection.map((file) => ({
					id: file.id,
					url: file.url,
					name: file.filename || file.title || 'Attachment',
					type: file.mime || file.type || '',
				}));
				setNewTicketData((prev) => ({
					...prev,
					attachments: [...(prev.attachments || []), ...newAttachments],
				}));
			});
			frame.open();
		} else {
			const input = document.createElement('input');
			input.type = 'file';
			input.multiple = true;
			input.onchange = (e) => {
				const files = Array.from(e.target.files);
				const fileNames = files.map((f) => ({ name: f.name, url: '', type: f.type }));
				setNewTicketData((prev) => ({
					...prev,
					attachments: [...(prev.attachments || []), ...fileNames],
				}));
			};
			input.click();
		}
	};

	const handleRemoveAttachment = (indexToRemove) => {
		setNewTicketData((prev) => ({
			...prev,
			attachments: (prev.attachments || []).filter((_, idx) => idx !== indexToRemove),
		}));
	};

	const handleCreateTicketSubmit = async (e) => {
		if (e) e.preventDefault();
		if (!newTicketData.subject.trim() || !newTicketData.message.trim()) {
			onShowNotice(__('Subject and message are required.', 'dragwyb-click-to-chat'), 'error');
			return;
		}
		setCreatingTicket(true);
		try {
			const payload = {
				...newTicketData,
				initial_message: newTicketData.message,
			};
			const res = await apiFetch({
				path: '/dctc-ai/v1/support/tickets',
				method: 'POST',
				data: payload,
			});
			if (res?.success) {
				setNewTicketData({
					subject: '',
					customer_name: '',
					customer_email: '',
					category_id: '',
					priority: 'normal',
					message: '',
					attachments: [],
				});
				onShowNotice(__('Ticket created successfully!', 'dragwyb-click-to-chat'), 'success');
				if (onTicketCreated) {
					onTicketCreated(res.ticket?.id);
				}
				onClose();
			}
		} catch (err) {
			console.error('Error creating ticket:', err);
			onShowNotice(__('Failed to create ticket.', 'dragwyb-click-to-chat'), 'error');
		} finally {
			setCreatingTicket(false);
		}
	};

	return (
		<div className="dctc-sc-modal-backdrop" onClick={onClose}>
			<div className="dctc-sc-modal-dialog" onClick={(e) => e.stopPropagation()}>
				<div className="dctc-sc-modal-header">
					<h3>
						<span className="dashicons dashicons-plus"></span>
						{__('Create New Support Ticket', 'dragwyb-click-to-chat')}
					</h3>
					<button
						type="button"
						className="dctc-sc-modal-close-btn"
						onClick={onClose}
					>
						&times;
					</button>
				</div>

				<form onSubmit={handleCreateTicketSubmit} className="dctc-sc-modal-form">
					<div className="dctc-sc-modal-body">
						<div className="dctc-sc-modal-form-group">
							<label>{__('Subject *', 'dragwyb-click-to-chat')}</label>
							<input
								type="text"
								required
								placeholder={__('Enter a support issue title...', 'dragwyb-click-to-chat')}
								value={newTicketData.subject}
								onChange={(e) => setNewTicketData({ ...newTicketData, subject: e.target.value })}
							/>
						</div>

						<div className="dctc-sc-modal-form-row">
							<div className="dctc-sc-modal-form-group">
								<label>{__('Customer Name', 'dragwyb-click-to-chat')}</label>
								<input
									type="text"
									placeholder={__('e.g. John Doe', 'dragwyb-click-to-chat')}
									value={newTicketData.customer_name}
									onChange={(e) => setNewTicketData({ ...newTicketData, customer_name: e.target.value })}
								/>
							</div>
							<div className="dctc-sc-modal-form-group">
								<label>{__('Customer Email', 'dragwyb-click-to-chat')}</label>
								<input
									type="email"
									placeholder={__('customer@example.com', 'dragwyb-click-to-chat')}
									value={newTicketData.customer_email}
									onChange={(e) => setNewTicketData({ ...newTicketData, customer_email: e.target.value })}
								/>
							</div>
						</div>

						<div className="dctc-sc-modal-form-row">
							<div className="dctc-sc-modal-form-group">
								<label>{__('Category', 'dragwyb-click-to-chat')}</label>
								<select
									value={newTicketData.category_id}
									onChange={(e) => setNewTicketData({ ...newTicketData, category_id: e.target.value })}
								>
									<option value="">{__('General / Technical', 'dragwyb-click-to-chat')}</option>
									{categories.map((cat) => (
										<option key={cat.id} value={cat.id}>{cat.name}</option>
									))}
								</select>
							</div>
							<div className="dctc-sc-modal-form-group">
								<label>{__('Priority', 'dragwyb-click-to-chat')}</label>
								<select
									value={newTicketData.priority}
									onChange={(e) => setNewTicketData({ ...newTicketData, priority: e.target.value })}
								>
									<option value="low">{__('Low', 'dragwyb-click-to-chat')}</option>
									<option value="normal">{__('Normal', 'dragwyb-click-to-chat')}</option>
									<option value="high">{__('High', 'dragwyb-click-to-chat')}</option>
									<option value="urgent">{__('Urgent', 'dragwyb-click-to-chat')}</option>
								</select>
							</div>
						</div>

						<div className="dctc-sc-modal-form-group">
							<div className="dctc-sc-wysiwyg-wrapper">
								<div className="dctc-sc-wysiwyg-header-tabs">
									<label style={{ fontWeight: 600, color: '#334155', margin: 0 }}>{__('Message *', 'dragwyb-click-to-chat')}</label>
									<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
										<div className="dctc-sc-wysiwyg-mode-switch">
											<button
												type="button"
												className={`dctc-sc-editor-mode-btn ${modalEditorMode === 'visual' ? 'active' : ''}`}
												onClick={() => setModalEditorMode('visual')}
											>
												{__('Visual', 'dragwyb-click-to-chat')}
											</button>
											<button
												type="button"
												className={`dctc-sc-editor-mode-btn ${modalEditorMode === 'text' ? 'active' : ''}`}
												onClick={() => setModalEditorMode('text')}
											>
												{__('Text', 'dragwyb-click-to-chat')}
											</button>
										</div>
										<button
											type="button"
											className="dctc-sc-add-media-btn"
											onClick={handleAttachFiles}
											title={__('Add Media / Files', 'dragwyb-click-to-chat')}
										>
											<span className="dashicons dashicons-admin-media"></span>
											<span>{__('Add Media', 'dragwyb-click-to-chat')}</span>
										</button>
									</div>
								</div>

								{modalEditorMode === 'visual' && (
									<div className="dctc-sc-wysiwyg-toolbar">
										<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('bold')} title={__('Bold', 'dragwyb-click-to-chat')}>
											<strong>B</strong>
										</button>
										<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('italic')} title={__('Italic', 'dragwyb-click-to-chat')}>
											<em>I</em>
										</button>
										<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('underline')} title={__('Underline', 'dragwyb-click-to-chat')}>
											<u>U</u>
										</button>
										<span className="dctc-sc-wysiwyg-divider"></span>
										<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('link')} title={__('Insert Link', 'dragwyb-click-to-chat')}>
											<span className="dashicons dashicons-admin-links"></span>
										</button>
										<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('ul')} title={__('Bullet List', 'dragwyb-click-to-chat')}>
											<span className="dashicons dashicons-editor-ul"></span>
										</button>
										<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('ol')} title={__('Numbered List', 'dragwyb-click-to-chat')}>
											<span className="dashicons dashicons-editor-ol"></span>
										</button>
										<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('quote')} title={__('Blockquote', 'dragwyb-click-to-chat')}>
											<span className="dashicons dashicons-editor-quote"></span>
										</button>
										<button type="button" className="dctc-sc-wysiwyg-btn" onClick={() => applyFormatting('code')} title={__('Code Block', 'dragwyb-click-to-chat')}>
											<span className="dashicons dashicons-editor-code"></span>
										</button>
									</div>
								)}

								<textarea
									ref={modalMessageInputRef}
									rows="5"
									required
									placeholder={__('Briefly describe the problem details...', 'dragwyb-click-to-chat')}
									value={newTicketData.message}
									onChange={(e) => setNewTicketData({ ...newTicketData, message: e.target.value })}
									className={`dctc-sc-wysiwyg-textarea ${modalEditorMode === 'text' ? 'text-mode-font' : ''}`}
								/>

								{newTicketData.attachments && newTicketData.attachments.length > 0 && (
									<div className="dctc-sc-attachment-chips-wrap">
										{newTicketData.attachments.map((file, fIdx) => (
											<div key={fIdx} className="dctc-sc-attachment-chip">
												<span className="dashicons dashicons-media-default"></span>
												<span className="chip-name" title={file.name}>{file.name}</span>
												<button
													type="button"
													className="chip-remove"
													onClick={() => handleRemoveAttachment(fIdx)}
													title={__('Remove file', 'dragwyb-click-to-chat')}
												>
													&times;
												</button>
											</div>
										))}
									</div>
								)}
							</div>
						</div>
					</div>

					<div className="dctc-sc-modal-footer-bar">
						<button
							type="button"
							className="dctc-sc-btn-cancel"
							onClick={onClose}
						>
							{__('Cancel', 'dragwyb-click-to-chat')}
						</button>
						<button
							type="submit"
							disabled={creatingTicket}
							className="dctc-sc-btn-submit"
						>
							{creatingTicket ? __('Creating...', 'dragwyb-click-to-chat') : __('Create Ticket', 'dragwyb-click-to-chat')}
						</button>
					</div>
				</form>
			</div>
		</div>
	);
}
