/**
 * Support Center - Dynamic Taxonomies & Classification Hub
 *
 * Supports default taxonomies (Categories, Tags, Products) and unlimited
 * custom user-defined taxonomies with term management.
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const COLOR_PRESETS = [ '#4F46E5', '#7C3AED', '#2563EB', '#059669', '#D97706', '#E11D48', '#0891B2', '#475569' ];
const EMOJI_PRESETS = [ '📁', '🏷️', '📦', '🏢', '💻', '🌐', '⚙️', '🎯', '💡', '🔧', '👥', '⭐', '🛒', '💳', '🚀' ];
const PRESET_SKILLS = [ 'technical', 'billing', 'sales', 'returns', 'woocommerce', 'api', 'shipping', 'account' ];

const generateSlug = ( text ) => {
	return ( text || '' )
		.toString()
		.toLowerCase()
		.trim()
		.replace( /\s+/g, '-' )
		.replace( /[^\w\-]+/g, '' )
		.replace( /\-\-+/g, '-' );
};

export default function TaxonomiesView( {
	categories = [],
	tags = [],
	onRefresh,
	onShowNotice,
	userPermissions = {},
} ) {
	const canManage = !! ( userPermissions.is_admin || userPermissions.manage_categories !== false || userPermissions.manage_tags !== false );

	// Registered Taxonomies List
	const [ taxonomies, setTaxonomies] = useState( [
		{ slug: 'category', name: __( 'Categories', 'dragwyb-click-to-chat' ), icon: '📁', color: '#4F46E5', is_system: true },
		{ slug: 'tag', name: __( 'Tags', 'dragwyb-click-to-chat' ), icon: '🏷️', color: '#D97706', is_system: true },
		{ slug: 'product', name: __( 'Products', 'dragwyb-click-to-chat' ), icon: '📦', color: '#059669', is_system: true },
	] );

	const [ activeTaxSlug, setActiveTaxSlug ] = useState( 'category' );
	const [ customTerms, setCustomTerms ] = useState( [] );
	const [ termsLoading, setTermsLoading ] = useState( false );

	// Products state
	const [ products, setProducts ] = useState( [] );
	const [ productsLoading, setProductsLoading ] = useState( false );
	const [ syncingWc, setSyncingWc ] = useState( false );

	// -------------------------------------------------------------
	// MODAL STATES
	// -------------------------------------------------------------
	// 1. New / Edit Taxonomy Modal
	const [ isTaxModalOpen, setIsTaxModalOpen ] = useState( false );
	const [ editingTax, setEditingTax ] = useState( null );
	const [ taxForm, setTaxForm ] = useState( {
		name: '',
		slug: '',
		icon: '📑',
		color: '#6366F1',
		description: '',
	} );

	// 2. Category Modal
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

	// 3. Tag Modal
	const [ isTagModalOpen, setIsTagModalOpen ] = useState( false );
	const [ editingTag, setEditingTag ] = useState( null );
	const [ tagForm, setTagForm ] = useState( {
		id: 0,
		name: '',
		slug: '',
		color: '#4F46E5',
	} );

	// 4. Product Modal
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

	// 5. Custom Term Modal
	const [ isTermModalOpen, setIsTermModalOpen ] = useState( false );
	const [ editingTerm, setEditingTerm ] = useState( null );
	const [ termForm, setTermForm ] = useState( {
		id: 0,
		name: '',
		slug: '',
		color: '#4F46E5',
		description: '',
	} );

	const [ saving, setSaving ] = useState( false );

	// -------------------------------------------------------------
	// FETCH TAXONOMIES & TERMS
	// -------------------------------------------------------------
	const fetchTaxonomies = async () => {
		try {
			const data = await apiFetch( { path: '/dctc-ai/v1/support/taxonomies' } );
			if ( data?.success && Array.isArray( data.taxonomies ) ) {
				setTaxonomies( data.taxonomies );
			}
		} catch ( err ) {
			console.error( 'Error fetching taxonomies:', err );
		}
	};

	const fetchTermsForTaxonomy = async ( taxSlug ) => {
		if ( taxSlug === 'category' || taxSlug === 'tag' ) return;
		if ( taxSlug === 'product' ) {
			fetchProducts();
			return;
		}

		setTermsLoading( true );
		try {
			const data = await apiFetch( { path: `/dctc-ai/v1/support/taxonomies/${ taxSlug }/terms` } );
			if ( data?.success ) {
				setCustomTerms( data.terms || [] );
			}
		} catch ( err ) {
			console.error( `Error fetching terms for ${ taxSlug }:`, err );
		} finally {
			setTermsLoading( false );
		}
	};

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
		fetchTaxonomies();
	}, [] );

	useEffect( () => {
		fetchTermsForTaxonomy( activeTaxSlug );
	}, [ activeTaxSlug ] );

	const activeTax = taxonomies.find( ( t ) => t.slug === activeTaxSlug ) || taxonomies[ 0 ];

	// -------------------------------------------------------------
	// TAXONOMY HANDLERS (ADD / EDIT / DELETE TAXONOMY)
	// -------------------------------------------------------------
	const handleOpenAddTaxonomy = () => {
		setEditingTax( null );
		setTaxForm( {
			name: '',
			slug: '',
			icon: '📑',
			color: '#6366F1',
			description: '',
		} );
		setIsTaxModalOpen( true );
	};

	const handleOpenEditTaxonomy = ( tax ) => {
		setEditingTax( tax );
		setTaxForm( {
			name: tax.name || '',
			slug: tax.slug || '',
			icon: tax.icon || '📑',
			color: tax.color || '#6366F1',
			description: tax.description || '',
		} );
		setIsTaxModalOpen( true );
	};

	const handleSaveTaxonomy = async ( e ) => {
		e.preventDefault();
		if ( ! taxForm.name.trim() ) return;

		setSaving( true );
		try {
			const data = await apiFetch( {
				path: '/dctc-ai/v1/support/taxonomies',
				method: 'POST',
				data: taxForm,
			} );

			if ( data?.success ) {
				setIsTaxModalOpen( false );
				await fetchTaxonomies();
				if ( data.taxonomy?.slug ) {
					setActiveTaxSlug( data.taxonomy.slug );
				}
				if ( onShowNotice ) {
					onShowNotice(
						editingTax
							? __( 'Taxonomy updated successfully.', 'dragwyb-click-to-chat' )
							: __( 'New Taxonomy added successfully. You can now add terms inside it.', 'dragwyb-click-to-chat' ),
						'success'
					);
				}
			} else {
				alert( data?.message || __( 'Could not save taxonomy.', 'dragwyb-click-to-chat' ) );
			}
		} catch ( err ) {
			console.error( 'Error saving taxonomy:', err );
			alert( __( 'Failed to save taxonomy.', 'dragwyb-click-to-chat' ) );
		} finally {
			setSaving( false );
		}
	};

	const handleDeleteTaxonomy = async ( taxSlug ) => {
		if ( ! window.confirm( __( `Are you sure you want to delete the "${ taxSlug }" taxonomy and all its terms?`, 'dragwyb-click-to-chat' ) ) ) {
			return;
		}

		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/taxonomies/${ taxSlug }`,
				method: 'DELETE',
			} );
			if ( data?.success ) {
				await fetchTaxonomies();
				setActiveTaxSlug( 'category' );
				if ( onShowNotice ) onShowNotice( __( 'Taxonomy deleted.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error deleting taxonomy:', err );
		}
	};

	// -------------------------------------------------------------
	// CATEGORY HANDLERS
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

			const data = await apiFetch( {
				path: '/dctc-ai/v1/support/categories',
				method: 'POST',
				data: { ...catForm, required_skills: skillsArr },
			} );

			if ( data?.success ) {
				setIsCatModalOpen( false );
				if ( onRefresh ) onRefresh();
				if ( onShowNotice ) onShowNotice( __( 'Category saved successfully.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error saving category:', err );
		} finally {
			setSaving( false );
		}
	};

	const handleDeleteCategory = async ( id ) => {
		if ( ! window.confirm( __( 'Delete this category?', 'dragwyb-click-to-chat' ) ) ) return;
		try {
			const data = await apiFetch( { path: `/dctc-ai/v1/support/categories/${ id }`, method: 'DELETE' } );
			if ( data?.success ) {
				if ( onRefresh ) onRefresh();
				if ( onShowNotice ) onShowNotice( __( 'Category deleted.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error deleting category:', err );
		}
	};

	// -------------------------------------------------------------
	// TAG HANDLERS
	// -------------------------------------------------------------
	const handleOpenAddTag = () => {
		setEditingTag( null );
		setTagForm( { id: 0, name: '', slug: '', color: '#4F46E5' } );
		setIsTagModalOpen( true );
	};

	const handleOpenEditTag = ( tag ) => {
		setEditingTag( tag );
		setTagForm( { id: tag.id, name: tag.name || '', slug: tag.slug || '', color: tag.color || '#4F46E5' } );
		setIsTagModalOpen( true );
	};

	const handleSaveTag = async ( e ) => {
		e.preventDefault();
		if ( ! tagForm.name.trim() ) return;
		setSaving( true );
		try {
			const data = await apiFetch( { path: '/dctc-ai/v1/support/tags', method: 'POST', data: tagForm } );
			if ( data?.success ) {
				setIsTagModalOpen( false );
				if ( onRefresh ) onRefresh();
				if ( onShowNotice ) onShowNotice( __( 'Tag saved successfully.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error saving tag:', err );
		} finally {
			setSaving( false );
		}
	};

	const handleDeleteTag = async ( id ) => {
		if ( ! window.confirm( __( 'Delete this tag?', 'dragwyb-click-to-chat' ) ) ) return;
		try {
			const data = await apiFetch( { path: `/dctc-ai/v1/support/tags/${ id }`, method: 'DELETE' } );
			if ( data?.success ) {
				if ( onRefresh ) onRefresh();
				if ( onShowNotice ) onShowNotice( __( 'Tag deleted.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error deleting tag:', err );
		}
	};

	// -------------------------------------------------------------
	// PRODUCT HANDLERS
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
			const data = await apiFetch( { path: '/dctc-ai/v1/support/products', method: 'POST', data: prodForm } );
			if ( data?.success ) {
				setIsProdModalOpen( false );
				fetchProducts();
				if ( onShowNotice ) onShowNotice( __( 'Product saved.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error saving product:', err );
		} finally {
			setSaving( false );
		}
	};

	const handleDeleteProduct = async ( id ) => {
		if ( ! window.confirm( __( 'Delete this product from support catalog?', 'dragwyb-click-to-chat' ) ) ) return;
		try {
			const data = await apiFetch( { path: `/dctc-ai/v1/support/products/${ id }`, method: 'DELETE' } );
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
			const data = await apiFetch( { path: '/dctc-ai/v1/support/products/sync-wc', method: 'POST' } );
			if ( data?.success ) {
				setProducts( data.products || [] );
				if ( onShowNotice ) onShowNotice( __( `Synced ${ data.synced_count || 0 } WooCommerce products.`, 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error syncing WC products:', err );
		} finally {
			setSyncingWc( false );
		}
	};

	// -------------------------------------------------------------
	// CUSTOM TERM HANDLERS (FOR USER-CREATED TAXONOMIES)
	// -------------------------------------------------------------
	const handleOpenAddTerm = () => {
		setEditingTerm( null );
		setTermForm( { id: 0, name: '', slug: '', color: activeTax?.color || '#4F46E5', description: '' } );
		setIsTermModalOpen( true );
	};

	const handleOpenEditTerm = ( term ) => {
		setEditingTerm( term );
		setTermForm( {
			id: term.id,
			name: term.name || '',
			slug: term.slug || '',
			color: term.color || activeTax?.color || '#4F46E5',
			description: term.description || '',
		} );
		setIsTermModalOpen( true );
	};

	const handleSaveTerm = async ( e ) => {
		e.preventDefault();
		if ( ! termForm.name.trim() ) return;

		setSaving( true );
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/taxonomies/${ activeTaxSlug }/terms`,
				method: 'POST',
				data: termForm,
			} );

			if ( data?.success ) {
				setIsTermModalOpen( false );
				fetchTermsForTaxonomy( activeTaxSlug );
				if ( onShowNotice ) onShowNotice( __( 'Term saved successfully.', 'dragwyb-click-to-chat' ), 'success' );
			} else {
				alert( data?.message || __( 'Could not save term.', 'dragwyb-click-to-chat' ) );
			}
		} catch ( err ) {
			console.error( 'Error saving custom term:', err );
		} finally {
			setSaving( false );
		}
	};

	const handleDeleteTerm = async ( termId ) => {
		if ( ! window.confirm( __( 'Are you sure you want to delete this term?', 'dragwyb-click-to-chat' ) ) ) return;
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/taxonomies/${ activeTaxSlug }/terms/${ termId }`,
				method: 'DELETE',
			} );
			if ( data?.success ) {
				fetchTermsForTaxonomy( activeTaxSlug );
				if ( onShowNotice ) onShowNotice( __( 'Term deleted.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error deleting term:', err );
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
						{ __( 'Manage built-in and custom dynamic taxonomies, classify tickets, and route conversations condition-based.', 'dragwyb-click-to-chat' ) }
					</p>
				</div>
			</div>

			{ /* Taxonomies Navigation Bar with "+ Add Taxonomy" Button */ }
			<div className="dctc-sc-subtabs-nav" style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
				<div style={ { display: 'flex', flexWrap: 'wrap', gap: '8px', alignItems: 'center' } }>
					{ taxonomies.map( ( tax ) => (
						<button
							key={ tax.slug }
							type="button"
							className={ `dctc-sc-subtab-btn ${ activeTaxSlug === tax.slug ? 'active' : '' }` }
							onClick={ () => setActiveTaxSlug( tax.slug ) }
						>
							<span>{ tax.icon || '📑' }</span>
							<span>{ tax.name }</span>
							{ tax.slug === 'category' && <span className="dctc-sc-pill-count">({ categories.length })</span> }
							{ tax.slug === 'tag' && <span className="dctc-sc-pill-count">({ tags.length })</span> }
							{ tax.slug === 'product' && <span className="dctc-sc-pill-count">({ products.length })</span> }
							{ ! tax.is_system && customTerms.length > 0 && activeTaxSlug === tax.slug && (
								<span className="dctc-sc-pill-count">({ customTerms.length })</span>
							) }
						</button>
					) ) }
				</div>

				{ canManage && (
					<button
						type="button"
						className="button button-primary dctc-sc-add-tax-btn"
						onClick={ handleOpenAddTaxonomy }
						title={ __( 'Create a new custom taxonomy', 'dragwyb-click-to-chat' ) }
					>
						<span className="dashicons dashicons-plus-alt2" style={ { verticalAlign: 'middle', marginRight: '4px' } }></span>
						{ __( 'Add Taxonomy', 'dragwyb-click-to-chat' ) }
					</button>
				) }
			</div>

			{ /* =============================================================
			     PANE 1: CATEGORIES
			   ============================================================= */ }
			{ activeTaxSlug === 'category' && (
				<div className="dctc-sc-tab-pane">
					<div className="dctc-sc-toolbar-row">
						<div className="dctc-sc-toolbar-info">
							<span className="dashicons dashicons-info"></span>
							<span>{ __( 'Primary routing taxonomy: controls dynamic display of Product & Tags in the support ticket form.', 'dragwyb-click-to-chat' ) }</span>
						</div>
						{ canManage && (
							<button type="button" className="button button-primary" onClick={ handleOpenAddCat }>
								<span className="dashicons dashicons-plus-alt2" style={ { verticalAlign: 'middle', marginRight: '4px' } }></span>
								{ __( 'Add Category', 'dragwyb-click-to-chat' ) }
							</button>
						) }
					</div>

					<table className="wp-list-table widefat fixed striped dctc-sc-table">
						<thead>
							<tr>
								<th style={ { width: '22%' } }>{ __( 'Category Name', 'dragwyb-click-to-chat' ) }</th>
								<th style={ { width: '15%' } }>{ __( 'Slug', 'dragwyb-click-to-chat' ) }</th>
								<th style={ { width: '12%' } }>{ __( 'Priority', 'dragwyb-click-to-chat' ) }</th>
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
										{ __( 'No categories found.', 'dragwyb-click-to-chat' ) }
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
											{ canManage && (
												<div style={ { display: 'inline-flex', gap: '6px' } }>
													<button type="button" className="button button-small" onClick={ () => handleOpenEditCat( cat ) }>
														{ __( 'Edit', 'dragwyb-click-to-chat' ) }
													</button>
													<button type="button" className="button button-small button-link-delete" onClick={ () => handleDeleteCategory( cat.id ) }>
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

			{ /* =============================================================
			     PANE 2: TAGS
			   ============================================================= */ }
			{ activeTaxSlug === 'tag' && (
				<div className="dctc-sc-tab-pane">
					<div className="dctc-sc-toolbar-row">
						<div className="dctc-sc-toolbar-info">
							<span className="dashicons dashicons-tag"></span>
							<span>{ __( 'Visual tags for fast identification and badge design across tickets.', 'dragwyb-click-to-chat' ) }</span>
						</div>
						{ canManage && (
							<button type="button" className="button button-primary" onClick={ handleOpenAddTag }>
								<span className="dashicons dashicons-plus-alt2" style={ { verticalAlign: 'middle', marginRight: '4px' } }></span>
								{ __( 'Add Tag', 'dragwyb-click-to-chat' ) }
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
										{ __( 'No tags found. Click "Add Tag" to create one.', 'dragwyb-click-to-chat' ) }
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
											{ canManage && (
												<div style={ { display: 'inline-flex', gap: '6px' } }>
													<button type="button" className="button button-small" onClick={ () => handleOpenEditTag( tag ) }>
														{ __( 'Edit', 'dragwyb-click-to-chat' ) }
													</button>
													<button type="button" className="button button-small button-link-delete" onClick={ () => handleDeleteTag( tag.id ) }>
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

			{ /* =============================================================
			     PANE 3: PRODUCTS
			   ============================================================= */ }
			{ activeTaxSlug === 'product' && (
				<div className="dctc-sc-tab-pane">
					<div className="dctc-sc-toolbar-row">
						<div className="dctc-sc-toolbar-info">
							<span className="dashicons dashicons-cart"></span>
							<span>{ __( 'Product items catalog for support ticket tagging and auto-classification.', 'dragwyb-click-to-chat' ) }</span>
						</div>
						<div style={ { display: 'flex', gap: '8px' } }>
							<button
								type="button"
								className="button"
								onClick={ handleSyncWooCommerce }
								disabled={ syncingWc }
							>
								<span className={ `dashicons dashicons-update ${ syncingWc ? 'rotating' : '' }` } style={ { verticalAlign: 'middle', marginRight: '4px' } }></span>
								{ syncingWc ? __( 'Syncing...', 'dragwyb-click-to-chat' ) : __( 'Sync WooCommerce Products', 'dragwyb-click-to-chat' ) }
							</button>
							{ canManage && (
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
											{ __( 'No products in catalog. Click "Sync WooCommerce Products" or "Add Product".', 'dragwyb-click-to-chat' ) }
										</td>
									</tr>
								) : (
									products.map( ( prod ) => (
										<tr key={ prod.id }>
											<td><strong>{ prod.name }</strong></td>
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
												{ canManage && (
													<div style={ { display: 'inline-flex', gap: '6px' } }>
														<button type="button" className="button button-small" onClick={ () => handleOpenEditProd( prod ) }>
															{ __( 'Edit', 'dragwyb-click-to-chat' ) }
														</button>
														<button type="button" className="button button-small button-link-delete" onClick={ () => handleDeleteProduct( prod.id ) }>
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

			{ /* =============================================================
			     PANE 4: CUSTOM USER-DEFINED TAXONOMY TERMS
			   ============================================================= */ }
			{ ! activeTax?.is_system && (
				<div className="dctc-sc-tab-pane">
					<div className="dctc-sc-taxonomy-banner">
						<div className="dctc-sc-tax-banner-left">
							<h4>
								<span>{ activeTax?.icon || '📑' }</span> { activeTax?.name }{ ' ' }
								<code>({ activeTax?.slug })</code>
							</h4>
							<p>{ activeTax?.description || __( 'Custom support taxonomy.', 'dragwyb-click-to-chat' ) }</p>
						</div>
						<div className="dctc-sc-tax-banner-actions">
							{ canManage && (
								<>
									<button
										type="button"
										className="button button-small"
										onClick={ () => handleOpenEditTaxonomy( activeTax ) }
									>
										<span className="dashicons dashicons-edit"></span> { __( 'Edit Taxonomy', 'dragwyb-click-to-chat' ) }
									</button>
									<button
										type="button"
										className="button button-small button-link-delete"
										onClick={ () => handleDeleteTaxonomy( activeTax.slug ) }
									>
										<span className="dashicons dashicons-trash"></span> { __( 'Delete Taxonomy', 'dragwyb-click-to-chat' ) }
									</button>
									<button
										type="button"
										className="button button-primary"
										onClick={ handleOpenAddTerm }
									>
										<span className="dashicons dashicons-plus-alt2" style={ { verticalAlign: 'middle', marginRight: '4px' } }></span>
										{ __( `Add ${ activeTax?.name } Term`, 'dragwyb-click-to-chat' ) }
									</button>
								</>
							) }
						</div>
					</div>

					{ termsLoading ? (
						<div className="dctc-sc-loading-state" style={ { padding: '40px' } }>
							<span className="spinner is-active"></span> { __( 'Loading terms...', 'dragwyb-click-to-chat' ) }
						</div>
					) : (
						<table className="wp-list-table widefat fixed striped dctc-sc-table">
							<thead>
								<tr>
									<th style={ { width: '35%' } }>{ __( 'Term Name', 'dragwyb-click-to-chat' ) }</th>
									<th style={ { width: '25%' } }>{ __( 'Slug', 'dragwyb-click-to-chat' ) }</th>
									<th style={ { width: '25%' } }>{ __( 'Description', 'dragwyb-click-to-chat' ) }</th>
									<th style={ { width: '15%', textAlign: 'right' } }>{ __( 'Actions', 'dragwyb-click-to-chat' ) }</th>
								</tr>
							</thead>
							<tbody>
								{ customTerms.length === 0 ? (
									<tr>
										<td colSpan="4" style={ { textAlign: 'center', padding: '30px' } }>
											{ __( `No terms in "${ activeTax?.name }". Click "Add ${ activeTax?.name } Term" to create one.`, 'dragwyb-click-to-chat' ) }
										</td>
									</tr>
								) : (
									customTerms.map( ( term ) => (
										<tr key={ term.id }>
											<td>
												<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
													<span className="dctc-sc-color-bullet" style={ { backgroundColor: term.color || activeTax?.color || '#4F46E5' } }></span>
													<strong>{ term.name }</strong>
												</div>
											</td>
											<td><code>{ term.slug }</code></td>
											<td>{ term.description || '—' }</td>
											<td style={ { textAlign: 'right' } }>
												{ canManage && (
													<div style={ { display: 'inline-flex', gap: '6px' } }>
														<button type="button" className="button button-small" onClick={ () => handleOpenEditTerm( term ) }>
															{ __( 'Edit', 'dragwyb-click-to-chat' ) }
														</button>
														<button type="button" className="button button-small button-link-delete" onClick={ () => handleDeleteTerm( term.id ) }>
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

			{ /* =============================================================
			     MODAL 1: ADD / EDIT CUSTOM TAXONOMY
			   ============================================================= */ }
			{ isTaxModalOpen && (
				<div className="dctc-sc-modal-backdrop" onClick={ () => setIsTaxModalOpen( false ) }>
					<div className="dctc-sc-modal-card" onClick={ ( e ) => e.stopPropagation() }>
						{ /* Header */ }
						<div className="dctc-sc-modal-top-header">
							<div className="dctc-sc-modal-icon-badge" style={ { background: `linear-gradient(135deg, ${ taxForm.color || '#4f46e5' } 0%, #6366f1 100%)` } }>
								<span style={ { fontSize: '20px', lineHeight: 1 } }>{ taxForm.icon || '📑' }</span>
							</div>
							<div className="dctc-sc-modal-title-wrap">
								<h3 className="dctc-sc-modal-title">
									{ editingTax ? __( 'Edit Custom Taxonomy', 'dragwyb-click-to-chat' ) : __( 'Add New Support Taxonomy', 'dragwyb-click-to-chat' ) }
								</h3>
								<p className="dctc-sc-modal-desc">
									{ __( 'Define custom classification dimensions and route support queries with dynamic terms.', 'dragwyb-click-to-chat' ) }
								</p>
							</div>
							<button
								type="button"
								className="dctc-sc-modal-close-btn"
								onClick={ () => setIsTaxModalOpen( false ) }
								title={ __( 'Close', 'dragwyb-click-to-chat' ) }
							>
								✕
							</button>
						</div>

						{ /* Form Body */ }
						<form onSubmit={ handleSaveTaxonomy } className="dctc-sc-modal-form">
							<div className="dctc-sc-modal-body-scroll">
								
								{ /* Taxonomy Name */ }
								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-tag"></span>
										{ __( 'Taxonomy Name', 'dragwyb-click-to-chat' ) }
										<span className="dctc-sc-required-star">*</span>
									</label>
									<input
										type="text"
										required
										className="dctc-sc-custom-input"
										placeholder="e.g. Departments, Hardware, Platforms"
										value={ taxForm.name }
										onChange={ ( e ) => {
											const val = e.target.value;
											setTaxForm( {
												...taxForm,
												name: val,
												slug: ! editingTax && ( ! taxForm.slug || taxForm.slug === generateSlug( taxForm.name ) ) ? generateSlug( val ) : taxForm.slug,
											} );
										} }
									/>
									<span className="dctc-sc-field-hint">{ __( 'Plural name displayed as tab in the navigation bar.', 'dragwyb-click-to-chat' ) }</span>
								</div>

								{ /* Slug & Icon */ }
								<div className="dctc-sc-form-grid-2">
									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-admin-links"></span>
											{ __( 'Slug Identifier', 'dragwyb-click-to-chat' ) }
										</label>
										<input
											type="text"
											className="dctc-sc-custom-input"
											placeholder="e.g. department"
											value={ taxForm.slug }
											onChange={ ( e ) => setTaxForm( { ...taxForm, slug: generateSlug( e.target.value ) } ) }
										/>
									</div>

									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-smiley"></span>
											{ __( 'Icon / Emoji', 'dragwyb-click-to-chat' ) }
										</label>
										<input
											type="text"
											className="dctc-sc-custom-input"
											placeholder="🏢"
											value={ taxForm.icon }
											onChange={ ( e ) => setTaxForm( { ...taxForm, icon: e.target.value } ) }
										/>
									</div>
								</div>

								{ /* Quick Emoji Chips */ }
								<div className="dctc-sc-form-group" style={ { marginTop: '-6px' } }>
									<span className="dctc-sc-preset-label">{ __( 'Quick Emoji Selection:', 'dragwyb-click-to-chat' ) }</span>
									<div className="dctc-sc-emoji-chips-list">
										{ EMOJI_PRESETS.map( ( em ) => (
											<button
												key={ em }
												type="button"
												className={ `dctc-sc-emoji-chip-btn ${ taxForm.icon === em ? 'is-active' : '' }` }
												onClick={ () => setTaxForm( { ...taxForm, icon: em } ) }
											>
												{ em }
											</button>
										) ) }
									</div>
								</div>

								{ /* Color Theme */ }
								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-art"></span>
										{ __( 'Color Theme', 'dragwyb-click-to-chat' ) }
									</label>
									<div className="dctc-sc-color-picker-wrap">
										<div className="dctc-sc-color-swatches-row">
											{ COLOR_PRESETS.map( ( c ) => (
												<button
													key={ c }
													type="button"
													className={ `dctc-sc-color-swatch ${ taxForm.color === c ? 'is-selected' : '' }` }
													style={ { backgroundColor: c } }
													onClick={ () => setTaxForm( { ...taxForm, color: c } ) }
													title={ c }
												/>
											) ) }
										</div>
										<div className="dctc-sc-native-color-wrap">
											<input
												type="color"
												className="dctc-sc-native-color-btn"
												value={ taxForm.color || '#4F46E5' }
												onChange={ ( e ) => setTaxForm( { ...taxForm, color: e.target.value } ) }
											/>
											<input
												type="text"
												className="dctc-sc-custom-input"
												style={ { width: '120px' } }
												value={ taxForm.color }
												onChange={ ( e ) => setTaxForm( { ...taxForm, color: e.target.value } ) }
											/>
										</div>
									</div>
								</div>

								{ /* Description */ }
								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-editor-paragraph"></span>
										{ __( 'Description (Optional)', 'dragwyb-click-to-chat' ) }
									</label>
									<textarea
										rows="2"
										className="dctc-sc-custom-textarea"
										placeholder={ __( 'What is this taxonomy used for?', 'dragwyb-click-to-chat' ) }
										value={ taxForm.description }
										onChange={ ( e ) => setTaxForm( { ...taxForm, description: e.target.value } ) }
									/>
								</div>

							</div>

							{ /* Pinned Footer */ }
							<div className="dctc-sc-modal-footer-bar">
								<button type="button" className="dctc-sc-btn-cancel" onClick={ () => setIsTaxModalOpen( false ) }>
									{ __( 'Cancel', 'dragwyb-click-to-chat' ) }
								</button>
								<button type="submit" className="dctc-sc-btn-submit" disabled={ saving }>
									{ saving ? (
										<>
											<span className="spinner is-active" style={ { margin: 0, float: 'none' } }></span>
											{ __( 'Saving...', 'dragwyb-click-to-chat' ) }
										</>
									) : (
										<>
											<span className="dashicons dashicons-saved" style={ { fontSize: '17px', lineHeight: '1' } }></span>
											{ editingTax ? __( 'Update Taxonomy', 'dragwyb-click-to-chat' ) : __( 'Create Taxonomy', 'dragwyb-click-to-chat' ) }
										</>
									) }
								</button>
							</div>
						</form>
					</div>
				</div>
			) }

			{ /* =============================================================
			     MODAL 2: ADD / EDIT CATEGORY
			   ============================================================= */ }
			{ isCatModalOpen && (
				<div className="dctc-sc-modal-backdrop" onClick={ () => setIsCatModalOpen( false ) }>
					<div className="dctc-sc-modal-card is-wide" onClick={ ( e ) => e.stopPropagation() }>
						
						{ /* Header */ }
						<div className="dctc-sc-modal-top-header">
							<div className="dctc-sc-modal-icon-badge" style={ { background: `linear-gradient(135deg, ${ catForm.color || '#4f46e5' } 0%, #6366f1 100%)` } }>
								<span className="dashicons dashicons-category"></span>
							</div>
							<div className="dctc-sc-modal-title-wrap">
								<h3 className="dctc-sc-modal-title">
									{ editingCat ? __( 'Edit Support Category', 'dragwyb-click-to-chat' ) : __( 'Add New Category', 'dragwyb-click-to-chat' ) }
								</h3>
								<p className="dctc-sc-modal-desc">
									{ __( 'Primary routing category: dynamically triggers support ticket fields and agent specialization.', 'dragwyb-click-to-chat' ) }
								</p>
							</div>
							<button
								type="button"
								className="dctc-sc-modal-close-btn"
								onClick={ () => setIsCatModalOpen( false ) }
								title={ __( 'Close', 'dragwyb-click-to-chat' ) }
							>
								✕
							</button>
						</div>

						{ /* Form Body */ }
						<form onSubmit={ handleSaveCategory } className="dctc-sc-modal-form">
							<div className="dctc-sc-modal-body-scroll">
								
								{ /* Category Name & Slug */ }
								<div className="dctc-sc-form-grid-2">
									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-category"></span>
											{ __( 'Category Name', 'dragwyb-click-to-chat' ) }
											<span className="dctc-sc-required-star">*</span>
										</label>
										<input
											type="text"
											required
											className="dctc-sc-custom-input"
											placeholder="e.g. WooCommerce & Orders"
											value={ catForm.name }
											onChange={ ( e ) => {
												const val = e.target.value;
												setCatForm( {
													...catForm,
													name: val,
													slug: ! editingCat && ( ! catForm.slug || catForm.slug === generateSlug( catForm.name ) ) ? generateSlug( val ) : catForm.slug,
												} );
											} }
										/>
									</div>

									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-admin-links"></span>
											{ __( 'Category Slug', 'dragwyb-click-to-chat' ) }
										</label>
										<input
											type="text"
											className="dctc-sc-custom-input"
											placeholder="e.g. woocommerce-orders"
											value={ catForm.slug }
											onChange={ ( e ) => setCatForm( { ...catForm, slug: generateSlug( e.target.value ) } ) }
										/>
									</div>
								</div>

								{ /* Color & Default Priority */ }
								<div className="dctc-sc-form-grid-2">
									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-art"></span>
											{ __( 'Category Color', 'dragwyb-click-to-chat' ) }
										</label>
										<div className="dctc-sc-color-picker-wrap">
											<div className="dctc-sc-color-swatches-row">
												{ COLOR_PRESETS.map( ( c ) => (
													<button
														key={ c }
														type="button"
														className={ `dctc-sc-color-swatch ${ catForm.color === c ? 'is-selected' : '' }` }
														style={ { backgroundColor: c } }
														onClick={ () => setCatForm( { ...catForm, color: c } ) }
														title={ c }
													/>
												) ) }
											</div>
											<div className="dctc-sc-native-color-wrap">
												<input
													type="color"
													className="dctc-sc-native-color-btn"
													value={ catForm.color || '#4F46E5' }
													onChange={ ( e ) => setCatForm( { ...catForm, color: e.target.value } ) }
												/>
												<input
													type="text"
													className="dctc-sc-custom-input"
													style={ { width: '110px' } }
													value={ catForm.color }
													onChange={ ( e ) => setCatForm( { ...catForm, color: e.target.value } ) }
												/>
											</div>
										</div>
									</div>

									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-flag"></span>
											{ __( 'Default Ticket Priority', 'dragwyb-click-to-chat' ) }
										</label>
										<div className="dctc-sc-select-wrapper">
											<select
												className="dctc-sc-custom-select"
												value={ catForm.default_priority }
												onChange={ ( e ) => setCatForm( { ...catForm, default_priority: e.target.value } ) }
											>
												<option value="low">🟢 { __( 'Low Priority', 'dragwyb-click-to-chat' ) }</option>
												<option value="normal">🔵 { __( 'Normal Priority', 'dragwyb-click-to-chat' ) }</option>
												<option value="high">🟠 { __( 'High Priority', 'dragwyb-click-to-chat' ) }</option>
												<option value="urgent">🔴 { __( 'Urgent Priority', 'dragwyb-click-to-chat' ) }</option>
											</select>
										</div>
										<span className="dctc-sc-field-hint">{ __( 'Assigned automatically when a ticket is filed under this category.', 'dragwyb-click-to-chat' ) }</span>
									</div>
								</div>

								{ /* Support Form Dynamic Visibility Card */ }
								<div className="dctc-sc-visibility-container">
									<div className="dctc-sc-visibility-header">
										<h4>
											<span className="dashicons dashicons-randomize" style={ { color: '#6366f1' } }></span>
											{ __( 'Support Form Dynamic Condition Rules', 'dragwyb-click-to-chat' ) }
										</h4>
										<p>{ __( 'Toggle dynamic fields in the [dragwyb_support] ticket form whenever a user chooses this category.', 'dragwyb-click-to-chat' ) }</p>
									</div>

									{ /* Switch 1: Product Selector */ }
									<div
										className="dctc-sc-toggle-card"
										onClick={ () => setCatForm( { ...catForm, show_product: catForm.show_product ? 0 : 1 } ) }
									>
										<div className="dctc-sc-toggle-info">
											<span className="dctc-sc-toggle-icon">📦</span>
											<div className="dctc-sc-toggle-text-wrap">
												<span className="dctc-sc-toggle-main-title">{ __( 'Show Product Dropdown in Ticket Form', 'dragwyb-click-to-chat' ) }</span>
												<span className="dctc-sc-toggle-sub-hint">{ __( 'Lets users select their purchased WooCommerce or custom product.', 'dragwyb-click-to-chat' ) }</span>
											</div>
										</div>
										<label className="dctc-sc-switch-control" onClick={ ( e ) => e.stopPropagation() }>
											<input
												type="checkbox"
												checked={ !! catForm.show_product }
												onChange={ ( e ) => setCatForm( { ...catForm, show_product: e.target.checked ? 1 : 0 } ) }
											/>
											<span className="dctc-sc-switch-slider"></span>
										</label>
									</div>

									{ /* Switch 2: Tags Selector */ }
									<div
										className="dctc-sc-toggle-card"
										onClick={ () => setCatForm( { ...catForm, show_tags: catForm.show_tags ? 0 : 1 } ) }
									>
										<div className="dctc-sc-toggle-info">
											<span className="dctc-sc-toggle-icon">🏷️</span>
											<div className="dctc-sc-toggle-text-wrap">
												<span className="dctc-sc-toggle-main-title">{ __( 'Show Tags Option in Ticket Form', 'dragwyb-click-to-chat' ) }</span>
												<span className="dctc-sc-toggle-sub-hint">{ __( 'Displays issue tags to help users classify their support topic.', 'dragwyb-click-to-chat' ) }</span>
											</div>
										</div>
										<label className="dctc-sc-switch-control" onClick={ ( e ) => e.stopPropagation() }>
											<input
												type="checkbox"
												checked={ !! catForm.show_tags }
												onChange={ ( e ) => setCatForm( { ...catForm, show_tags: e.target.checked ? 1 : 0 } ) }
											/>
											<span className="dctc-sc-switch-slider"></span>
										</label>
									</div>

									{ /* Switch 3: AI Assistant */ }
									<div
										className="dctc-sc-toggle-card"
										onClick={ () => setCatForm( { ...catForm, ai_allowed: catForm.ai_allowed ? 0 : 1 } ) }
									>
										<div className="dctc-sc-toggle-info">
											<span className="dctc-sc-toggle-icon">🤖</span>
											<div className="dctc-sc-toggle-text-wrap">
												<span className="dctc-sc-toggle-main-title">{ __( 'Allow AI Assistant Immediate Responses', 'dragwyb-click-to-chat' ) }</span>
												<span className="dctc-sc-toggle-sub-hint">{ __( 'Enable AI Chatbot to draft solutions or answer questions in this category.', 'dragwyb-click-to-chat' ) }</span>
											</div>
										</div>
										<label className="dctc-sc-switch-control" onClick={ ( e ) => e.stopPropagation() }>
											<input
												type="checkbox"
												checked={ !! catForm.ai_allowed }
												onChange={ ( e ) => setCatForm( { ...catForm, ai_allowed: e.target.checked ? 1 : 0 } ) }
											/>
											<span className="dctc-sc-switch-slider"></span>
										</label>
									</div>
								</div>

								{ /* Required Skills Pillbox */ }
								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-awards"></span>
										{ __( 'Required Agent Skills (Auto-routing matching)', 'dragwyb-click-to-chat' ) }
									</label>
									<input
										type="text"
										className="dctc-sc-custom-input"
										placeholder="e.g. technical, billing, returns"
										value={ catForm.required_skills }
										onChange={ ( e ) => setCatForm( { ...catForm, required_skills: e.target.value } ) }
									/>
									<div className="dctc-sc-preset-skills-wrapper" style={ { marginTop: '4px' } }>
										<span className="dctc-sc-preset-label">{ __( 'Quick Add Skills:', 'dragwyb-click-to-chat' ) }</span>
										<div className="dctc-sc-preset-chips">
											{ PRESET_SKILLS.map( ( skill ) => {
												const activeSkills = ( catForm.required_skills || '' )
													.split( ',' )
													.map( ( s ) => s.trim() )
													.filter( Boolean );
												const isActive = activeSkills.includes( skill );
												return (
													<button
														key={ skill }
														type="button"
														className={ `dctc-sc-skill-chip ${ isActive ? 'is-selected' : '' }` }
														onClick={ () => {
															let nextSkills;
															if ( isActive ) {
																nextSkills = activeSkills.filter( ( s ) => s !== skill );
															} else {
																nextSkills = [ ...activeSkills, skill ];
															}
															setCatForm( { ...catForm, required_skills: nextSkills.join( ', ' ) } );
														} }
													>
														<span className="dctc-sc-chip-icon">{ isActive ? '✓' : '+' }</span>
														{ skill }
													</button>
												);
											} ) }
										</div>
									</div>
								</div>

								{ /* Category Description */ }
								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-editor-paragraph"></span>
										{ __( 'Category Description (Optional)', 'dragwyb-click-to-chat' ) }
									</label>
									<textarea
										rows="2"
										className="dctc-sc-custom-textarea"
										placeholder={ __( 'Describe what inquiries belong here...', 'dragwyb-click-to-chat' ) }
										value={ catForm.description }
										onChange={ ( e ) => setCatForm( { ...catForm, description: e.target.value } ) }
									/>
								</div>

							</div>

							{ /* Pinned Footer */ }
							<div className="dctc-sc-modal-footer-bar">
								<button type="button" className="dctc-sc-btn-cancel" onClick={ () => setIsCatModalOpen( false ) }>
									{ __( 'Cancel', 'dragwyb-click-to-chat' ) }
								</button>
								<button type="submit" className="dctc-sc-btn-submit" disabled={ saving }>
									{ saving ? (
										<>
											<span className="spinner is-active" style={ { margin: 0, float: 'none' } }></span>
											{ __( 'Saving...', 'dragwyb-click-to-chat' ) }
										</>
									) : (
										<>
											<span className="dashicons dashicons-saved" style={ { fontSize: '17px', lineHeight: '1' } }></span>
											{ editingCat ? __( 'Update Category', 'dragwyb-click-to-chat' ) : __( 'Create Category', 'dragwyb-click-to-chat' ) }
										</>
									) }
								</button>
							</div>
						</form>
					</div>
				</div>
			) }

			{ /* =============================================================
			     MODAL 3: ADD / EDIT TAG
			   ============================================================= */ }
			{ isTagModalOpen && (
				<div className="dctc-sc-modal-backdrop" onClick={ () => setIsTagModalOpen( false ) }>
					<div className="dctc-sc-modal-card" onClick={ ( e ) => e.stopPropagation() }>
						
						{ /* Header */ }
						<div className="dctc-sc-modal-top-header">
							<div className="dctc-sc-modal-icon-badge" style={ { background: `linear-gradient(135deg, ${ tagForm.color || '#D97706' } 0%, #f59e0b 100%)` } }>
								<span className="dashicons dashicons-tag"></span>
							</div>
							<div className="dctc-sc-modal-title-wrap">
								<h3 className="dctc-sc-modal-title">{ editingTag ? __( 'Edit Support Tag', 'dragwyb-click-to-chat' ) : __( 'Add New Tag', 'dragwyb-click-to-chat' ) }</h3>
								<p className="dctc-sc-modal-desc">{ __( 'Tags allow customers and agents to pinpoint precise sub-topics and issue badges.', 'dragwyb-click-to-chat' ) }</p>
							</div>
							<button type="button" className="dctc-sc-modal-close-btn" onClick={ () => setIsTagModalOpen( false ) } title={ __( 'Close', 'dragwyb-click-to-chat' ) }>
								✕
							</button>
						</div>

						{ /* Form Body */ }
						<form onSubmit={ handleSaveTag } className="dctc-sc-modal-form">
							<div className="dctc-sc-modal-body-scroll">
								
								<div className="dctc-sc-form-grid-2">
									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-tag"></span>
											{ __( 'Tag Name', 'dragwyb-click-to-chat' ) }
											<span className="dctc-sc-required-star">*</span>
										</label>
										<input
											type="text"
											required
											className="dctc-sc-custom-input"
											placeholder="e.g. Refund Request"
											value={ tagForm.name }
											onChange={ ( e ) => {
												const val = e.target.value;
												setTagForm( {
													...tagForm,
													name: val,
													slug: ! editingTag && ( ! tagForm.slug || tagForm.slug === generateSlug( tagForm.name ) ) ? generateSlug( val ) : tagForm.slug,
												} );
											} }
										/>
									</div>

									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-admin-links"></span>
											{ __( 'Slug', 'dragwyb-click-to-chat' ) }
										</label>
										<input
											type="text"
											className="dctc-sc-custom-input"
											placeholder="e.g. refund-request"
											value={ tagForm.slug }
											onChange={ ( e ) => setTagForm( { ...tagForm, slug: generateSlug( e.target.value ) } ) }
										/>
									</div>
								</div>

								{ /* Color Swatches */ }
								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-art"></span>
										{ __( 'Badge Color', 'dragwyb-click-to-chat' ) }
									</label>
									<div className="dctc-sc-color-picker-wrap">
										<div className="dctc-sc-color-swatches-row">
											{ COLOR_PRESETS.map( ( c ) => (
												<button
													key={ c }
													type="button"
													className={ `dctc-sc-color-swatch ${ tagForm.color === c ? 'is-selected' : '' }` }
													style={ { backgroundColor: c } }
													onClick={ () => setTagForm( { ...tagForm, color: c } ) }
													title={ c }
												/>
											) ) }
										</div>
										<div className="dctc-sc-native-color-wrap">
											<input
												type="color"
												className="dctc-sc-native-color-btn"
												value={ tagForm.color || '#4F46E5' }
												onChange={ ( e ) => setTagForm( { ...tagForm, color: e.target.value } ) }
											/>
											<input
												type="text"
												className="dctc-sc-custom-input"
												style={ { width: '110px' } }
												value={ tagForm.color }
												onChange={ ( e ) => setTagForm( { ...tagForm, color: e.target.value } ) }
											/>
										</div>
									</div>
								</div>

							</div>

							{ /* Pinned Footer */ }
							<div className="dctc-sc-modal-footer-bar">
								<button type="button" className="dctc-sc-btn-cancel" onClick={ () => setIsTagModalOpen( false ) }>
									{ __( 'Cancel', 'dragwyb-click-to-chat' ) }
								</button>
								<button type="submit" className="dctc-sc-btn-submit" disabled={ saving }>
									{ saving ? __( 'Saving...', 'dragwyb-click-to-chat' ) : ( editingTag ? __( 'Update Tag', 'dragwyb-click-to-chat' ) : __( 'Create Tag', 'dragwyb-click-to-chat' ) ) }
								</button>
							</div>
						</form>
					</div>
				</div>
			) }

			{ /* =============================================================
			     MODAL 4: ADD / EDIT PRODUCT
			   ============================================================= */ }
			{ isProdModalOpen && (
				<div className="dctc-sc-modal-backdrop" onClick={ () => setIsProdModalOpen( false ) }>
					<div className="dctc-sc-modal-card" onClick={ ( e ) => e.stopPropagation() }>
						
						{ /* Header */ }
						<div className="dctc-sc-modal-top-header">
							<div className="dctc-sc-modal-icon-badge" style={ { background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)' } }>
								<span className="dashicons dashicons-cart"></span>
							</div>
							<div className="dctc-sc-modal-title-wrap">
								<h3 className="dctc-sc-modal-title">{ editingProd ? __( 'Edit Product', 'dragwyb-click-to-chat' ) : __( 'Add Product to Support Catalog', 'dragwyb-click-to-chat' ) }</h3>
								<p className="dctc-sc-modal-desc">{ __( 'Products can be selected by customers during ticket creation for issue context.', 'dragwyb-click-to-chat' ) }</p>
							</div>
							<button type="button" className="dctc-sc-modal-close-btn" onClick={ () => setIsProdModalOpen( false ) } title={ __( 'Close', 'dragwyb-click-to-chat' ) }>
								✕
							</button>
						</div>

						{ /* Form Body */ }
						<form onSubmit={ handleSaveProduct } className="dctc-sc-modal-form">
							<div className="dctc-sc-modal-body-scroll">
								
								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-products"></span>
										{ __( 'Product Name', 'dragwyb-click-to-chat' ) }
										<span className="dctc-sc-required-star">*</span>
									</label>
									<input
										type="text"
										required
										className="dctc-sc-custom-input"
										placeholder="e.g. Pro Membership Plan / Wireless Keyboard"
										value={ prodForm.name }
										onChange={ ( e ) => {
											const val = e.target.value;
											setProdForm( {
												...prodForm,
												name: val,
												slug: ! editingProd && ( ! prodForm.slug || prodForm.slug === generateSlug( prodForm.name ) ) ? generateSlug( val ) : prodForm.slug,
											} );
										} }
									/>
								</div>

								<div className="dctc-sc-form-grid-2">
									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-barcode"></span>
											{ __( 'SKU / Model Code', 'dragwyb-click-to-chat' ) }
										</label>
										<input
											type="text"
											className="dctc-sc-custom-input"
											placeholder="e.g. PRO-01"
											value={ prodForm.sku }
											onChange={ ( e ) => setProdForm( { ...prodForm, sku: e.target.value } ) }
										/>
									</div>

									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-money-alt"></span>
											{ __( 'Price ($)', 'dragwyb-click-to-chat' ) }
										</label>
										<input
											type="number"
											step="0.01"
											className="dctc-sc-custom-input"
											placeholder="29.99"
											value={ prodForm.price }
											onChange={ ( e ) => setProdForm( { ...prodForm, price: e.target.value } ) }
										/>
									</div>
								</div>

								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-category"></span>
										{ __( 'Primary Category Association', 'dragwyb-click-to-chat' ) }
									</label>
									<div className="dctc-sc-select-wrapper">
										<select
											className="dctc-sc-custom-select"
											value={ prodForm.category_id || 0 }
											onChange={ ( e ) => setProdForm( { ...prodForm, category_id: parseInt( e.target.value, 10 ) || 0 } ) }
										>
											<option value="0">{ __( '— All / General Products —', 'dragwyb-click-to-chat' ) }</option>
											{ categories.map( ( c ) => (
												<option key={ c.id } value={ c.id }>
													{ c.name }
												</option>
											) ) }
										</select>
									</div>
									<span className="dctc-sc-field-hint">{ __( 'Optional: link this product to a specific support category.', 'dragwyb-click-to-chat' ) }</span>
								</div>

							</div>

							{ /* Pinned Footer */ }
							<div className="dctc-sc-modal-footer-bar">
								<button type="button" className="dctc-sc-btn-cancel" onClick={ () => setIsProdModalOpen( false ) }>
									{ __( 'Cancel', 'dragwyb-click-to-chat' ) }
								</button>
								<button type="submit" className="dctc-sc-btn-submit" disabled={ saving }>
									{ saving ? __( 'Saving...', 'dragwyb-click-to-chat' ) : ( editingProd ? __( 'Update Product', 'dragwyb-click-to-chat' ) : __( 'Save Product', 'dragwyb-click-to-chat' ) ) }
								</button>
							</div>
						</form>
					</div>
				</div>
			) }

			{ /* =============================================================
			     MODAL 5: ADD / EDIT CUSTOM TAXONOMY TERM
			   ============================================================= */ }
			{ isTermModalOpen && (
				<div className="dctc-sc-modal-backdrop" onClick={ () => setIsTermModalOpen( false ) }>
					<div className="dctc-sc-modal-card" onClick={ ( e ) => e.stopPropagation() }>
						
						{ /* Header */ }
						<div className="dctc-sc-modal-top-header">
							<div className="dctc-sc-modal-icon-badge" style={ { background: `linear-gradient(135deg, ${ termForm.color || activeTax?.color || '#4f46e5' } 0%, #6366f1 100%)` } }>
								<span style={ { fontSize: '20px', lineHeight: 1 } }>{ activeTax?.icon || '📑' }</span>
							</div>
							<div className="dctc-sc-modal-title-wrap">
								<h3 className="dctc-sc-modal-title">
									{ editingTerm
										? __( `Edit ${ activeTax?.name || 'Taxonomy' } Item`, 'dragwyb-click-to-chat' )
										: __( `Add New ${ activeTax?.name || 'Taxonomy' } Item`, 'dragwyb-click-to-chat' ) }
								</h3>
								<p className="dctc-sc-modal-desc">
									{ __( `Create a classified term under the ${ activeTax?.name } taxonomy.`, 'dragwyb-click-to-chat' ) }
								</p>
							</div>
							<button type="button" className="dctc-sc-modal-close-btn" onClick={ () => setIsTermModalOpen( false ) } title={ __( 'Close', 'dragwyb-click-to-chat' ) }>
								✕
							</button>
						</div>

						{ /* Form Body */ }
						<form onSubmit={ handleSaveTerm } className="dctc-sc-modal-form">
							<div className="dctc-sc-modal-body-scroll">
								
								<div className="dctc-sc-form-grid-2">
									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-tag"></span>
											{ __( 'Item Name', 'dragwyb-click-to-chat' ) }
											<span className="dctc-sc-required-star">*</span>
										</label>
										<input
											type="text"
											required
											className="dctc-sc-custom-input"
											placeholder={ `e.g. ${ activeTax?.name || 'Term' } Name` }
											value={ termForm.name }
											onChange={ ( e ) => {
												const val = e.target.value;
												setTermForm( {
													...termForm,
													name: val,
													slug: ! editingTerm && ( ! termForm.slug || termForm.slug === generateSlug( termForm.name ) ) ? generateSlug( val ) : termForm.slug,
												} );
											} }
										/>
									</div>

									<div className="dctc-sc-form-group">
										<label className="dctc-sc-field-label">
											<span className="dashicons dashicons-admin-links"></span>
											{ __( 'Slug', 'dragwyb-click-to-chat' ) }
										</label>
										<input
											type="text"
											className="dctc-sc-custom-input"
											placeholder="e.g. item-slug"
											value={ termForm.slug }
											onChange={ ( e ) => setTermForm( { ...termForm, slug: generateSlug( e.target.value ) } ) }
										/>
									</div>
								</div>

								{ /* Color Picker */ }
								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-art"></span>
										{ __( 'Color Theme', 'dragwyb-click-to-chat' ) }
									</label>
									<div className="dctc-sc-color-picker-wrap">
										<div className="dctc-sc-color-swatches-row">
											{ COLOR_PRESETS.map( ( c ) => (
												<button
													key={ c }
													type="button"
													className={ `dctc-sc-color-swatch ${ termForm.color === c ? 'is-selected' : '' }` }
													style={ { backgroundColor: c } }
													onClick={ () => setTermForm( { ...termForm, color: c } ) }
													title={ c }
												/>
											) ) }
										</div>
										<div className="dctc-sc-native-color-wrap">
											<input
												type="color"
												className="dctc-sc-native-color-btn"
												value={ termForm.color || '#4F46E5' }
												onChange={ ( e ) => setTermForm( { ...termForm, color: e.target.value } ) }
											/>
											<input
												type="text"
												className="dctc-sc-custom-input"
												style={ { width: '110px' } }
												value={ termForm.color }
												onChange={ ( e ) => setTermForm( { ...termForm, color: e.target.value } ) }
											/>
										</div>
									</div>
								</div>

								{ /* Description */ }
								<div className="dctc-sc-form-group">
									<label className="dctc-sc-field-label">
										<span className="dashicons dashicons-editor-paragraph"></span>
										{ __( 'Description (Optional)', 'dragwyb-click-to-chat' ) }
									</label>
									<textarea
										rows="2"
										className="dctc-sc-custom-textarea"
										placeholder={ __( 'Optional description for this item...', 'dragwyb-click-to-chat' ) }
										value={ termForm.description }
										onChange={ ( e ) => setTermForm( { ...termForm, description: e.target.value } ) }
									/>
								</div>

							</div>

							{ /* Pinned Footer */ }
							<div className="dctc-sc-modal-footer-bar">
								<button type="button" className="dctc-sc-btn-cancel" onClick={ () => setIsTermModalOpen( false ) }>
									{ __( 'Cancel', 'dragwyb-click-to-chat' ) }
								</button>
								<button type="submit" className="dctc-sc-btn-submit" disabled={ saving }>
									{ saving ? __( 'Saving...', 'dragwyb-click-to-chat' ) : ( editingTerm ? __( 'Update Item', 'dragwyb-click-to-chat' ) : __( 'Save Item', 'dragwyb-click-to-chat' ) ) }
								</button>
							</div>
						</form>
					</div>
				</div>
			) }
		</div>
	);
}
