/**
 * Support Center - Categories, Tags & Products Taxonomies Hub
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function CategoriesView( {
	categories = [],
	tags = [],
	onRefresh,
	onShowNotice,
	userPermissions = {},
	initialSubTab = 'categories',
} ) {
	const canManageCategories = !! ( userPermissions.is_admin || userPermissions.manage_categories !== false );
	const canManageTags = !! ( userPermissions.is_admin || userPermissions.manage_tags !== false );

	const [ subTab, setSubTab ] = useState( initialSubTab ); // 'categories' | 'tags' | 'products'
	const [ products, setProducts ] = useState( [] );
	const [ productsLoading, setProductsLoading ] = useState( false );
	const [ syncingWc, setSyncingWc ] = useState( false );

	// Modals
	const [ isCatModalOpen, setIsCatModalOpen ] = useState( false );
	const [ editingCat, setEditingCat ] = useState( null );
	const [ catForm, setCatForm ] = useState( {
		id: 0,
		name: '',
		slug: '',
		color: '#4F46E5',
		default_priority: 'normal',
		show_product: 1,
		show_tags: 1,
		ai_allowed: 1,
		required_skills: '',
		description: '',
	} );

	const [ isTagModalOpen, setIsTagModalOpen ] = useState( false );
	const [ editingTag, setEditingTag ] = useState( null );
	const [ tagForm, setTagForm ] = useState( {
		id: 0,
		name: '',
		slug: '',
		color: '#4F46E5',
	} );

	const [ isProdModalOpen, setIsProdModalOpen ] = useState( false );
	const [ editingProd, setEditingProd ] = useState( null );
	const [ prodForm, setProdForm ] = useState( {
		id: 0,
		name: '',
		slug: '',
		sku: '',
		price: '',
		category_id: 0,
	} );

	const [ saving, setSaving ] = useState( false );

	// Fetch Products
	const fetchProducts = async () => {
		setProductsLoading( true );
		try {
			const data = await apiFetch( { path: '/dctc-ai/v1/support/products' } );
			if ( data?.success ) {
				setProducts( data.products || [] );
			}
		} catch ( err ) {
			console.error( 'Error fetching products:', err );
		} finally {
			setProductsLoading( false );
		}
	};

	useEffect( () => {
		if ( subTab === 'products' ) {
			fetchProducts();
		}
	}, [ subTab ] );

	// -------------------------------------------------------------
	// Category Handlers
	// -------------------------------------------------------------
	const handleOpenAddCat = () => {
		setEditingCat( null );
		setCatForm( {
			id: 0,
			name: '',
			slug: '',
			color: '#4F46E5',
			default_priority: 'normal',
			show_product: 1,
			show_tags: 1,
			ai_allowed: 1,
			required_skills: '',
			description: '',
		} );
		setIsCatModalOpen( true );
	};

	const handleOpenEditCat = ( cat ) => {
		setEditingCat( cat );
		setCatForm( {
			id: cat.id,
			name: cat.name || '',
			slug: cat.slug || '',
			color: cat.color || '#4F46E5',
			default_priority: cat.default_priority || 'normal',
			show_product: cat.show_product !== undefined ? Number( cat.show_product ) : 1,
			show_tags: cat.show_tags !== undefined ? Number( cat.show_tags ) : 1,
			ai_allowed: cat.ai_allowed !== undefined ? Number( cat.ai_allowed ) : 1,
			required_skills: Array.isArray( cat.required_skills ) ? cat.required_skills.join( ', ' ) : '',
			description: cat.description || '',
		} );
		setIsCatModalOpen( true );
	};

	const handleSaveCategory = async ( e ) => {
		e.preventDefault();
		if ( ! catForm.name.trim() ) return;

		setSaving( true );
		try {
			const skillsArr = catForm.required_skills
				.split( ',' )
				.map( ( s ) => s.trim() )
				.filter( Boolean );

			const payload = {
				...catForm,
				required_skills: skillsArr,
			};

			const data = await apiFetch( {
				path: '/dctc-ai/v1/support/categories',
				method: 'POST',
				data: payload,
			} );

			if ( data?.success ) {
				setIsCatModalOpen( false );
				if ( onRefresh ) onRefresh();
				if ( onShowNotice ) {
					onShowNotice(
						editingCat
							? __( 'Category updated successfully.', 'dragwyb-click-to-chat' )
							: __( 'New Category created successfully.', 'dragwyb-click-to-chat' ),
						'success'
					);
				}
			} else {
				alert( data?.message || __( 'Failed to save category.', 'dragwyb-click-to-chat' ) );
			}
		} catch ( err ) {
			console.error( 'Error saving category:', err );
			alert( __( 'Error saving category.', 'dragwyb-click-to-chat' ) );
		} finally {
			setSaving( false );
		}
	};

	const handleDeleteCategory = async ( catId ) => {
		if ( ! window.confirm( __( 'Are you sure you want to delete this category?', 'dragwyb-click-to-chat' ) ) ) {
			return;
		}

		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/categories/${ catId }`,
				method: 'DELETE',
			} );
			if ( data?.success ) {
				if ( onRefresh ) onRefresh();
				if ( onShowNotice ) onShowNotice( __( 'Category deleted.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error deleting category:', err );
		}
	};

	// -------------------------------------------------------------
	// Tag Handlers
	// -------------------------------------------------------------
	const handleOpenAddTag = () => {
		setEditingTag( null );
		setTagForm( { id: 0, name: '', slug: '', color: '#4F46E5' } );
		setIsTagModalOpen( true );
	};

	const handleOpenEditTag = ( tag ) => {
		setEditingTag( tag );
		setTagForm( {
			id: tag.id,
			name: tag.name || '',
			slug: tag.slug || '',
			color: tag.color || '#4F46E5',
		} );
		setIsTagModalOpen( true );
	};

	const handleSaveTag = async ( e ) => {
		e.preventDefault();
		if ( ! tagForm.name.trim() ) return;

		setSaving( true );
		try {
			const data = await apiFetch( {
				path: '/dctc-ai/v1/support/tags',
				method: 'POST',
				data: tagForm,
			} );

			if ( data?.success ) {
				setIsTagModalOpen( false );
				if ( onRefresh ) onRefresh();
				if ( onShowNotice ) {
					onShowNotice(
						editingTag
							? __( 'Tag updated successfully.', 'dragwyb-click-to-chat' )
							: __( 'New Tag created successfully.', 'dragwyb-click-to-chat' ),
						'success'
					);
				}
			} else {
				alert( data?.message || __( 'Failed to save tag.', 'dragwyb-click-to-chat' ) );
			}
		} catch ( err ) {
			console.error( 'Error saving tag:', err );
			alert( __( 'Error saving tag.', 'dragwyb-click-to-chat' ) );
		} finally {
			setSaving( false );
		}
	};

	const handleDeleteTag = async ( tagId ) => {
		if ( ! window.confirm( __( 'Are you sure you want to delete this tag?', 'dragwyb-click-to-chat' ) ) ) {
			return;
		}

		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/tags/${ tagId }`,
				method: 'DELETE',
			} );
			if ( data?.success ) {
				if ( onRefresh ) onRefresh();
				if ( onShowNotice ) onShowNotice( __( 'Tag deleted.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error deleting tag:', err );
		}
	};

	// -------------------------------------------------------------
	// Product Handlers
	// -------------------------------------------------------------
	const handleOpenAddProd = () => {
		setEditingProd( null );
		setProdForm( { id: 0, name: '', slug: '', sku: '', price: '', category_id: 0 } );
		setIsProdModalOpen( true );
	};

	const handleOpenEditProd = ( prod ) => {
		setEditingProd( prod );
		setProdForm( {
			id: prod.id,
			name: prod.name || '',
			slug: prod.slug || '',
			sku: prod.sku || '',
			price: prod.price || '',
			category_id: prod.category_id || 0,
		} );
		setIsProdModalOpen( true );
	};

	const handleSaveProduct = async ( e ) => {
		e.preventDefault();
		if ( ! prodForm.name.trim() ) return;

		setSaving( true );
		try {
			const data = await apiFetch( {
				path: '/dctc-ai/v1/support/products',
				method: 'POST',
				data: prodForm,
			} );

			if ( data?.success ) {
				setIsProdModalOpen( false );
				fetchProducts();
				if ( onShowNotice ) {
					onShowNotice(
						editingProd
							? __( 'Product updated.', 'dragwyb-click-to-chat' )
							: __( 'Product added to support catalog.', 'dragwyb-click-to-chat' ),
						'success'
					);
				}
			} else {
				alert( data?.message || __( 'Failed to save product.', 'dragwyb-click-to-chat' ) );
			}
		} catch ( err ) {
			console.error( 'Error saving product:', err );
		} finally {
			setSaving( false );
		}
	};

	const handleDeleteProduct = async ( prodId ) => {
		if ( ! window.confirm( __( 'Delete this product from support catalog?', 'dragwyb-click-to-chat' ) ) ) {
			return;
		}

		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/products/${ prodId }`,
				method: 'DELETE',
			} );
			if ( data?.success ) {
				fetchProducts();
				if ( onShowNotice ) onShowNotice( __( 'Product deleted.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error deleting product:', err );
		}
	};

	const handleSyncWooCommerce = async () => {
		setSyncingWc( true );
		try {
			const data = await apiFetch( {
				path: '/dctc-ai/v1/support/products/sync-wc',
				method: 'POST',
			} );
			if ( data?.success ) {
				setProducts( data.products || [] );
				if ( onShowNotice ) {
					onShowNotice(
						__( `Synced ${ data.synced_count || 0 } WooCommerce products to catalog.`, 'dragwyb-click-to-chat' ),
						'success'
					);
				}
			}
		} catch ( err ) {
			console.error( 'Error syncing WooCommerce products:', err );
		} finally {
			setSyncingWc( false );
		}
	};

	const getPriorityBadgeClass = ( priority ) => {
		switch ( priority ) {
			case 'urgent': return 'dctc-sc-badge-urgent';
			case 'high': return 'dctc-sc-badge-high';
			case 'normal': return 'dctc-sc-badge-normal';
			default: return 'dctc-sc-badge-low';
		}
	};

	return (
		<div className="dctc-sc-panel-box">
			{ /* Header */ }
			<div className="dctc-sc-panel-header">
				<div className="dctc-sc-panel-icon-wrap icon-purple">
					<span className="dashicons dashicons-category"></span>
				</div>
				<div style={ { flex: 1 } }>
					<h3>{ __( 'Support Taxonomies & Routing Engine', 'dragwyb-click-to-chat' ) }</h3>
					<p className="dctc-sc-panel-sub">
						{ __( 'Configure Categories, Tags, and Products. Control dynamic conditions for showing Product & Tag selectors in customer support forms.', 'dragwyb-click-to-chat' ) }
					</p>
				</div>
			</div>

			{ /* Sub-navigation Bar */ }
			<div className="dctc-sc-subtabs-nav">
				<button
					type="button"
					className={ `dctc-sc-subtab-btn ${ subTab === 'categories' ? 'active' : '' }` }
					onClick={ () => setSubTab( 'categories' ) }
				>
					📁 { __( 'Categories (Main Taxonomy)', 'dragwyb-click-to-chat' ) } ({ categories.length })
				</button>
				<button
					type="button"
					className={ `dctc-sc-subtab-btn ${ subTab === 'tags' ? 'active' : '' }` }
					onClick={ () => setSubTab( 'tags' ) }
				>
					🏷️ { __( 'Tags Taxonomy', 'dragwyb-click-to-chat' ) } ({ tags.length })
				</button>
				<button
					type="button"
					className={ `dctc-sc-subtab-btn ${ subTab === 'products' ? 'active' : '' }` }
					onClick={ () => setSubTab( 'products' ) }
				>
					📦 { __( 'Products Catalog', 'dragwyb-click-to-chat' ) } ({ products.length })
				</button>
			</div>

			{ /* -------------------------------------------------------------
			     SECTION 1: CATEGORIES
			   ------------------------------------------------------------- */ }
			{ subTab === 'categories' && (
				<div className="dctc-sc-tab-pane">
					<div className="dctc-sc-toolbar-row">
						<div className="dctc-sc-toolbar-info">
							<span className="dashicons dashicons-info"></span>
							<span>{ __( 'Each category controls whether Product & Tag selectors appear in the customer support ticket form.', 'dragwyb-click-to-chat' ) }</span>
						</div>
						{ canManageCategories && (
							<button type="button" className="button button-primary" onClick={ handleOpenAddCat }>
								<span className="dashicons dashicons-plus-alt2" style={ { verticalAlign: 'middle', marginRight: '4px' } }></span>
								{ __( 'Add New Category', 'dragwyb-click-to-chat' ) }
							</button>
						) }
					</div>

					<table className="wp-list-table widefat fixed striped dctc-sc-table">
						<thead>
							<tr>
								<th style={ { width: '22%' } }>{ __( 'Category Name', 'dragwyb-click-to-chat' ) }</th>
								<th style={ { width: '15%' } }>{ __( 'Slug', 'dragwyb-click-to-chat' ) }</th>
								<th style={ { width: '12%' } }>{ __( 'Default Priority', 'dragwyb-click-to-chat' ) }</th>
								<th style={ { width: '13%' } }>{ __( 'Show Product?', 'dragwyb-click-to-chat' ) }</th>
								<th style={ { width: '13%' } }>{ __( 'Show Tags?', 'dragwyb-click-to-chat' ) }</th>
								<th style={ { width: '12%' } }>{ __( 'AI Allowed', 'dragwyb-click-to-chat' ) }</th>
								<th style={ { width: '13%', textAlign: 'right' } }>{ __( 'Actions', 'dragwyb-click-to-chat' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ categories.length === 0 ? (
								<tr>
									<td colSpan="7" style={ { textAlign: 'center', padding: '30px' } }>
										{ __( 'No support categories found. Click "Add New Category" to create one.', 'dragwyb-click-to-chat' ) }
									</td>
								</tr>
							) : (
								categories.map( ( cat ) => (
									<tr key={ cat.id }>
										<td>
											<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
												<span className="dctc-sc-color-bullet" style={ { backgroundColor: cat.color || '#4F46E5' } }></span>
												<strong>{ cat.name }</strong>
											</div>
										</td>
										<td><code>{ cat.slug }</code></td>
										<td>
											<span className={ `dctc-sc-badge ${ getPriorityBadgeClass( cat.default_priority ) }` }>
												{ cat.default_priority }
											</span>
										</td>
										<td>
											{ cat.show_product ? (
												<span className="dctc-sc-badge" style={ { background: '#ECFDF5', color: '#047857' } }>✅ { __( 'Yes', 'dragwyb-click-to-chat' ) }</span>
											) : (
												<span className="dctc-sc-badge" style={ { background: '#F3F4F6', color: '#6B7280' } }>❌ { __( 'Hidden', 'dragwyb-click-to-chat' ) }</span>
											) }
										</td>
										<td>
											{ cat.show_tags ? (
												<span className="dctc-sc-badge" style={ { background: '#EEF2FF', color: '#4338CA' } }>✅ { __( 'Yes', 'dragwyb-click-to-chat' ) }</span>
											) : (
												<span className="dctc-sc-badge" style={ { background: '#F3F4F6', color: '#6B7280' } }>❌ { __( 'Hidden', 'dragwyb-click-to-chat' ) }</span>
											) }
										</td>
										<td>{ cat.ai_allowed ? __( '✅ AI Active', 'dragwyb-click-to-chat' ) : __( '🧑‍💼 Human Only', 'dragwyb-click-to-chat' ) }</td>
										<td style={ { textAlign: 'right' } }>
											{ canManageCategories && (
												<div style={ { display: 'inline-flex', gap: '6px' } }>
													<button
														type="button"
														className="button button-small"
														onClick={ () => handleOpenEditCat( cat ) }
													>
														{ __( 'Edit', 'dragwyb-click-to-chat' ) }
													</button>
													<button
														type="button"
														className="button button-small button-link-delete"
														onClick={ () => handleDeleteCategory( cat.id ) }
													>
														{ __( 'Delete', 'dragwyb-click-to-chat' ) }
													</button>
												</div>
											) }
										</td>
									</tr>
								) )
							) }
						</tbody>
					</table>
				</div>
			) }

			{ /* -------------------------------------------------------------
			     SECTION 2: TAGS
			   ------------------------------------------------------------- */ }
			{ subTab === 'tags' && (
				<div className="dctc-sc-tab-pane">
					<div className="dctc-sc-toolbar-row">
						<div className="dctc-sc-toolbar-info">
							<span className="dashicons dashicons-tag"></span>
							<span>{ __( 'Visual tags for fast classification, issue types (Refund, Shipping, Urgent), and routing.', 'dragwyb-click-to-chat' ) }</span>
						</div>
						{ canManageTags && (
							<button type="button" className="button button-primary" onClick={ handleOpenAddTag }>
								<span className="dashicons dashicons-plus-alt2" style={ { verticalAlign: 'middle', marginRight: '4px' } }></span>
								{ __( 'Add New Tag', 'dragwyb-click-to-chat' ) }
							</button>
						) }
					</div>

					<table className="wp-list-table widefat fixed striped dctc-sc-table">
						<thead>
							<tr>
								<th style={ { width: '35%' } }>{ __( 'Tag Name & Badge', 'dragwyb-click-to-chat' ) }</th>
								<th style={ { width: '30%' } }>{ __( 'Slug', 'dragwyb-click-to-chat' ) }</th>
								<th style={ { width: '20%' } }>{ __( 'Color Code', 'dragwyb-click-to-chat' ) }</th>
								<th style={ { width: '15%', textAlign: 'right' } }>{ __( 'Actions', 'dragwyb-click-to-chat' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ tags.length === 0 ? (
								<tr>
									<td colSpan="4" style={ { textAlign: 'center', padding: '30px' } }>
										{ __( 'No tags found. Click "Add New Tag" to create one.', 'dragwyb-click-to-chat' ) }
									</td>
								</tr>
							) : (
								tags.map( ( tag ) => (
									<tr key={ tag.id }>
										<td>
											<span className="dctc-sc-badge dctc-sc-badge-tag" style={ { borderColor: tag.color, color: tag.color } }>
												🏷️ { tag.name }
											</span>
										</td>
										<td><code>{ tag.slug }</code></td>
										<td>
											<span className="dctc-sc-color-pill-sample" style={ { backgroundColor: tag.color } }></span>
											<code>{ tag.color }</code>
										</td>
										<td style={ { textAlign: 'right' } }>
											{ canManageTags && (
												<div style={ { display: 'inline-flex', gap: '6px' } }>
													<button
														type="button"
														className="button button-small"
														onClick={ () => handleOpenEditTag( tag ) }
													>
														{ __( 'Edit', 'dragwyb-click-to-chat' ) }
													</button>
													<button
														type="button"
														className="button button-small button-link-delete"
														onClick={ () => handleDeleteTag( tag.id ) }
													>
														{ __( 'Delete', 'dragwyb-click-to-chat' ) }
													</button>
												</div>
											) }
										</td>
									</tr>
								) )
							) }
						</tbody>
					</table>
				</div>
			) }

			{ /* -------------------------------------------------------------
			     SECTION 3: PRODUCTS
			   ------------------------------------------------------------- */ }
			{ subTab === 'products' && (
				<div className="dctc-sc-tab-pane">
					<div className="dctc-sc-toolbar-row">
						<div className="dctc-sc-toolbar-info">
							<span className="dashicons dashicons-cart"></span>
							<span>{ __( 'Products available for customer selection and automated AI assistant classification.', 'dragwyb-click-to-chat' ) }</span>
						</div>
						<div style={ { display: 'flex', gap: '8px' } }>
							<button
								type="button"
								className="button"
								onClick={ handleSyncWooCommerce }
								disabled={ syncingWc }
								title={ __( 'Import published products from WooCommerce catalog', 'dragwyb-click-to-chat' ) }
							>
								<span className={ `dashicons dashicons-update ${ syncingWc ? 'rotating' : '' }` } style={ { verticalAlign: 'middle', marginRight: '4px' } }></span>
								{ syncingWc ? __( 'Syncing...', 'dragwyb-click-to-chat' ) : __( 'Sync WooCommerce Products', 'dragwyb-click-to-chat' ) }
							</button>
							{ canManageCategories && (
								<button type="button" className="button button-primary" onClick={ handleOpenAddProd }>
									<span className="dashicons dashicons-plus-alt2" style={ { verticalAlign: 'middle', marginRight: '4px' } }></span>
									{ __( 'Add Product', 'dragwyb-click-to-chat' ) }
								</button>
							) }
						</div>
					</div>

					{ productsLoading ? (
						<div className="dctc-sc-loading-state" style={ { padding: '40px' } }>
							<span className="spinner is-active"></span> { __( 'Loading products catalog...', 'dragwyb-click-to-chat' ) }
						</div>
					) : (
						<table className="wp-list-table widefat fixed striped dctc-sc-table">
							<thead>
								<tr>
									<th style={ { width: '40%' } }>{ __( 'Product Name', 'dragwyb-click-to-chat' ) }</th>
									<th style={ { width: '20%' } }>{ __( 'SKU', 'dragwyb-click-to-chat' ) }</th>
									<th style={ { width: '15%' } }>{ __( 'Price', 'dragwyb-click-to-chat' ) }</th>
									<th style={ { width: '15%' } }>{ __( 'Source', 'dragwyb-click-to-chat' ) }</th>
									<th style={ { width: '10%', textAlign: 'right' } }>{ __( 'Actions', 'dragwyb-click-to-chat' ) }</th>
								</tr>
							</thead>
							<tbody>
								{ products.length === 0 ? (
									<tr>
										<td colSpan="5" style={ { textAlign: 'center', padding: '30px' } }>
											{ __( 'No products in support catalog. Click "Sync WooCommerce Products" or "Add Product".', 'dragwyb-click-to-chat' ) }
										</td>
									</tr>
								) : (
									products.map( ( prod ) => (
										<tr key={ prod.id }>
											<td>
												<strong>{ prod.name }</strong>
											</td>
											<td><code>{ prod.sku || '—' }</code></td>
											<td>{ prod.price ? `$${ Number( prod.price ).toFixed( 2 ) }` : '—' }</td>
											<td>
												{ prod.wc_product_id ? (
													<span className="dctc-sc-badge" style={ { background: '#EDE9FE', color: '#5B21B6' } }>WooCommerce</span>
												) : (
													<span className="dctc-sc-badge" style={ { background: '#F1F5F9', color: '#475569' } }>Custom</span>
												) }
											</td>
											<td style={ { textAlign: 'right' } }>
												{ canManageCategories && (
													<div style={ { display: 'inline-flex', gap: '6px' } }>
														<button
															type="button"
															className="button button-small"
															onClick={ () => handleOpenEditProd( prod ) }
														>
															{ __( 'Edit', 'dragwyb-click-to-chat' ) }
														</button>
														<button
															type="button"
															className="button button-small button-link-delete"
															onClick={ () => handleDeleteProduct( prod.id ) }
														>
															{ __( 'Delete', 'dragwyb-click-to-chat' ) }
														</button>
													</div>
												) }
											</td>
										</tr>
									) )
								) }
							</tbody>
						</table>
					)}
				</div>
			) }

			{ /* -------------------------------------------------------------
			     MODAL 1: ADD / EDIT CATEGORY
			   ------------------------------------------------------------- */ }
			{ isCatModalOpen && (
				<div className="dctc-sc-modal-overlay">
					<div className="dctc-sc-modal-box">
						<div className="dctc-sc-modal-header">
							<h3>
								{ editingCat
									? __( 'Edit Support Category', 'dragwyb-click-to-chat' )
									: __( 'Add New Support Category', 'dragwyb-click-to-chat' ) }
							</h3>
							<button
								type="button"
								className="dctc-sc-modal-close"
								onClick={ () => setIsCatModalOpen( false ) }
							>
								&times;
							</button>
						</div>

						<form onSubmit={ handleSaveCategory } className="dctc-sc-modal-form">
							<div className="dctc-sc-form-grid-2">
								<div className="dctc-sc-form-group">
									<label>{ __( 'Category Name *', 'dragwyb-click-to-chat' ) }</label>
									<input
										type="text"
										required
										placeholder="e.g. WooCommerce & Orders"
										value={ catForm.name }
										onChange={ ( e ) => setCatForm( { ...catForm, name: e.target.value } ) }
										className="regular-text"
									/>
								</div>

								<div className="dctc-sc-form-group">
									<label>{ __( 'Slug', 'dragwyb-click-to-chat' ) }</label>
									<input
										type="text"
										placeholder="e.g. woocommerce-orders"
										value={ catForm.slug }
										onChange={ ( e ) => setCatForm( { ...catForm, slug: e.target.value } ) }
										className="regular-text"
									/>
								</div>
							</div>

							<div className="dctc-sc-form-grid-2">
								<div className="dctc-sc-form-group">
									<label>{ __( 'Category Color', 'dragwyb-click-to-chat' ) }</label>
									<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
										<input
											type="color"
											value={ catForm.color }
											onChange={ ( e ) => setCatForm( { ...catForm, color: e.target.value } ) }
											style={ { width: '42px', height: '36px', padding: '0', border: '1px solid #CBD5E1', borderRadius: '6px', cursor: 'pointer' } }
										/>
										<input
											type="text"
											value={ catForm.color }
											onChange={ ( e ) => setCatForm( { ...catForm, color: e.target.value } ) }
											style={ { width: '100px' } }
										/>
									</div>
								</div>

								<div className="dctc-sc-form-group">
									<label>{ __( 'Default Priority', 'dragwyb-click-to-chat' ) }</label>
									<select
										value={ catForm.default_priority }
										onChange={ ( e ) => setCatForm( { ...catForm, default_priority: e.target.value } ) }
									>
										<option value="low">{ __( 'Low', 'dragwyb-click-to-chat' ) }</option>
										<option value="normal">{ __( 'Normal', 'dragwyb-click-to-chat' ) }</option>
										<option value="high">{ __( 'High', 'dragwyb-click-to-chat' ) }</option>
										<option value="urgent">{ __( 'Urgent', 'dragwyb-click-to-chat' ) }</option>
									</select>
								</div>
							</div>

							{ /* Dynamic Form Display Options */ }
							<div className="dctc-sc-form-section-banner">
								<h4>{ __( 'Support Forum Dynamic Options', 'dragwyb-click-to-chat' ) }</h4>
								<p>{ __( 'Control which options are dynamically shown when a customer selects this category in the support form.', 'dragwyb-click-to-chat' ) }</p>

								<div className="dctc-sc-checkbox-field" style={ { marginTop: '10px' } }>
									<label>
										<input
											type="checkbox"
											checked={ !! catForm.show_product }
											onChange={ ( e ) => setCatForm( { ...catForm, show_product: e.target.checked ? 1 : 0 } ) }
										/>
										<strong>{ __( 'Show Product Selector in Support Form', 'dragwyb-click-to-chat' ) }</strong>
									</label>
									<span className="dctc-sc-field-desc">{ __( 'When selected, customer can pick the relevant product name.', 'dragwyb-click-to-chat' ) }</span>
								</div>

								<div className="dctc-sc-checkbox-field" style={ { marginTop: '8px' } }>
									<label>
										<input
											type="checkbox"
											checked={ !! catForm.show_tags }
											onChange={ ( e ) => setCatForm( { ...catForm, show_tags: e.target.checked ? 1 : 0 } ) }
										/>
										<strong>{ __( 'Show Tags Option in Support Form', 'dragwyb-click-to-chat' ) }</strong>
									</label>
									<span className="dctc-sc-field-desc">{ __( 'When selected, customer can specify tags and topics.', 'dragwyb-click-to-chat' ) }</span>
								</div>

								<div className="dctc-sc-checkbox-field" style={ { marginTop: '8px' } }>
									<label>
										<input
											type="checkbox"
											checked={ !! catForm.ai_allowed }
											onChange={ ( e ) => setCatForm( { ...catForm, ai_allowed: e.target.checked ? 1 : 0 } ) }
										/>
										<strong>{ __( 'Allow AI Assistant Responses', 'dragwyb-click-to-chat' ) }</strong>
									</label>
									<span className="dctc-sc-field-desc">{ __( 'Uncheck to route tickets directly to human staff specialists without AI answering.', 'dragwyb-click-to-chat' ) }</span>
								</div>
							</div>

							<div className="dctc-sc-form-group">
								<label>{ __( 'Required Staff Skills (Comma separated)', 'dragwyb-click-to-chat' ) }</label>
								<input
									type="text"
									placeholder="e.g. woocommerce, returns, billing"
									value={ catForm.required_skills }
									onChange={ ( e ) => setCatForm( { ...catForm, required_skills: e.target.value } ) }
									className="regular-text"
								/>
							</div>

							<div className="dctc-sc-form-group">
								<label>{ __( 'Description', 'dragwyb-click-to-chat' ) }</label>
								<textarea
									rows="3"
									placeholder={ __( 'Internal description for this category...', 'dragwyb-click-to-chat' ) }
									value={ catForm.description }
									onChange={ ( e ) => setCatForm( { ...catForm, description: e.target.value } ) }
								/>
							</div>

							<div className="dctc-sc-modal-actions">
								<button
									type="button"
									className="button"
									onClick={ () => setIsCatModalOpen( false ) }
								>
									{ __( 'Cancel', 'dragwyb-click-to-chat' ) }
								</button>
								<button
									type="submit"
									className="button button-primary"
									disabled={ saving }
								>
									{ saving ? __( 'Saving...', 'dragwyb-click-to-chat' ) : ( editingCat ? __( 'Update Category', 'dragwyb-click-to-chat' ) : __( 'Create Category', 'dragwyb-click-to-chat' ) ) }
								</button>
							</div>
						</form>
					</div>
				</div>
			) }

			{ /* -------------------------------------------------------------
			     MODAL 2: ADD / EDIT TAG
			   ------------------------------------------------------------- */ }
			{ isTagModalOpen && (
				<div className="dctc-sc-modal-overlay">
					<div className="dctc-sc-modal-box" style={ { maxWidth: '450px' } }>
						<div className="dctc-sc-modal-header">
							<h3>
								{ editingTag
									? __( 'Edit Support Tag', 'dragwyb-click-to-chat' )
									: __( 'Add New Support Tag', 'dragwyb-click-to-chat' ) }
							</h3>
							<button
								type="button"
								className="dctc-sc-modal-close"
								onClick={ () => setIsTagModalOpen( false ) }
							>
								&times;
							</button>
						</div>

						<form onSubmit={ handleSaveTag } className="dctc-sc-modal-form">
							<div className="dctc-sc-form-group">
								<label>{ __( 'Tag Name *', 'dragwyb-click-to-chat' ) }</label>
								<input
									type="text"
									required
									placeholder="e.g. Refund Request"
									value={ tagForm.name }
									onChange={ ( e ) => setTagForm( { ...tagForm, name: e.target.value } ) }
									className="regular-text"
								/>
							</div>

							<div className="dctc-sc-form-group">
								<label>{ __( 'Slug', 'dragwyb-click-to-chat' ) }</label>
								<input
									type="text"
									placeholder="e.g. refund-request"
									value={ tagForm.slug }
									onChange={ ( e ) => setTagForm( { ...tagForm, slug: e.target.value } ) }
									className="regular-text"
								/>
							</div>

							<div className="dctc-sc-form-group">
								<label>{ __( 'Badge Color', 'dragwyb-click-to-chat' ) }</label>
								<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
									<input
										type="color"
										value={ tagForm.color }
										onChange={ ( e ) => setTagForm( { ...tagForm, color: e.target.value } ) }
										style={ { width: '42px', height: '36px', padding: '0', border: '1px solid #CBD5E1', borderRadius: '6px', cursor: 'pointer' } }
									/>
									<input
										type="text"
										value={ tagForm.color }
										onChange={ ( e ) => setTagForm( { ...tagForm, color: e.target.value } ) }
										style={ { width: '100px' } }
									/>
								</div>
							</div>

							<div className="dctc-sc-modal-actions">
								<button
									type="button"
									className="button"
									onClick={ () => setIsTagModalOpen( false ) }
								>
									{ __( 'Cancel', 'dragwyb-click-to-chat' ) }
								</button>
								<button
									type="submit"
									className="button button-primary"
									disabled={ saving }
								>
									{ saving ? __( 'Saving...', 'dragwyb-click-to-chat' ) : ( editingTag ? __( 'Update Tag', 'dragwyb-click-to-chat' ) : __( 'Create Tag', 'dragwyb-click-to-chat' ) ) }
								</button>
							</div>
						</form>
					</div>
				</div>
			) }

			{ /* -------------------------------------------------------------
			     MODAL 3: ADD / EDIT PRODUCT
			   ------------------------------------------------------------- */ }
			{ isProdModalOpen && (
				<div className="dctc-sc-modal-overlay">
					<div className="dctc-sc-modal-box" style={ { maxWidth: '500px' } }>
						<div className="dctc-sc-modal-header">
							<h3>
								{ editingProd
									? __( 'Edit Support Product', 'dragwyb-click-to-chat' )
									: __( 'Add Product to Catalog', 'dragwyb-click-to-chat' ) }
							</h3>
							<button
								type="button"
								className="dctc-sc-modal-close"
								onClick={ () => setIsProdModalOpen( false ) }
							>
								&times;
							</button>
						</div>

						<form onSubmit={ handleSaveProduct } className="dctc-sc-modal-form">
							<div className="dctc-sc-form-group">
								<label>{ __( 'Product Name *', 'dragwyb-click-to-chat' ) }</label>
								<input
									type="text"
									required
									placeholder="e.g. Pro Membership Plan"
									value={ prodForm.name }
									onChange={ ( e ) => setProdForm( { ...prodForm, name: e.target.value } ) }
									className="regular-text"
								/>
							</div>

							<div className="dctc-sc-form-grid-2">
								<div className="dctc-sc-form-group">
									<label>{ __( 'SKU (Optional)', 'dragwyb-click-to-chat' ) }</label>
									<input
										type="text"
										placeholder="e.g. PRO-MEM-01"
										value={ prodForm.sku }
										onChange={ ( e ) => setProdForm( { ...prodForm, sku: e.target.value } ) }
										className="regular-text"
									/>
								</div>

								<div className="dctc-sc-form-group">
									<label>{ __( 'Price (Optional)', 'dragwyb-click-to-chat' ) }</label>
									<input
										type="number"
										step="0.01"
										placeholder="29.99"
										value={ prodForm.price }
										onChange={ ( e ) => setProdForm( { ...prodForm, price: e.target.value } ) }
										className="regular-text"
									/>
								</div>
							</div>

							<div className="dctc-sc-modal-actions">
								<button
									type="button"
									className="button"
									onClick={ () => setIsProdModalOpen( false ) }
								>
									{ __( 'Cancel', 'dragwyb-click-to-chat' ) }
								</button>
								<button
									type="submit"
									className="button button-primary"
									disabled={ saving }
								>
									{ saving ? __( 'Saving...', 'dragwyb-click-to-chat' ) : ( editingProd ? __( 'Update Product', 'dragwyb-click-to-chat' ) : __( 'Add Product', 'dragwyb-click-to-chat' ) ) }
								</button>
							</div>
						</form>
					</div>
				</div>
			) }
		</div>
	);
}
