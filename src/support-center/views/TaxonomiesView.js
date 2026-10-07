/**
 * Support Center - Dynamic Taxonomies & Classification Hub
 *
 * Supports default taxonomies (Categories, Tags, Products) and unlimited
 * custom user-defined taxonomies with term management.
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import TaxonomyModal from '../components/taxonomies/TaxonomyModal';
import CategoryModal from '../components/taxonomies/CategoryModal';
import TagModal from '../components/taxonomies/TagModal';
import ProductModal from '../components/taxonomies/ProductModal';
import CustomTermModal from '../components/taxonomies/CustomTermModal';

const COLOR_PRESETS = [ '#4F46E5', '#7C3AED', '#2563EB', '#059669', '#D97706', '#E11D48', '#0891B2', '#475569' ];
const DASHICON_PRESETS = [
	'dashicons-category',
	'dashicons-tag',
	'dashicons-products',
	'dashicons-groups',
	'dashicons-networking',
	'dashicons-location-alt',
	'dashicons-admin-settings',
	'dashicons-shield',
	'dashicons-portfolio',
	'dashicons-flag',
	'dashicons-clipboard',
	'dashicons-chart-pie',
];
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
	const [ taxonomies, setTaxonomies ] = useState( [
		{ slug: 'category', name: __( 'Categories', 'dragwyb-click-to-chat' ), icon_dashicon: 'dashicons-category', color: '#4F46E5', is_system: true },
		{ slug: 'tag', name: __( 'Tags', 'dragwyb-click-to-chat' ), icon_dashicon: 'dashicons-tag', color: '#D97706', is_system: true },
		{ slug: 'product', name: __( 'Products', 'dragwyb-click-to-chat' ), icon_dashicon: 'dashicons-products', color: '#059669', is_system: true },
	] );

	const [ activeTaxSlug, setActiveTaxSlug ] = useState( 'category' );
	const [ customTerms, setCustomTerms ] = useState( [] );
	const [ termsLoading, setTermsLoading ] = useState( false );

	// Products state
	const [ products, setProducts ] = useState( [] );
	const [ productsLoading, setProductsLoading ] = useState( false );
	const [ syncingWc, setSyncingWc ] = useState( false );

	// Modals state
	const [ isTaxModalOpen, setIsTaxModalOpen ] = useState( false );
	const [ editingTax, setEditingTax ] = useState( null );
	const [ taxForm, setTaxForm ] = useState( {
		name: '',
		slug: '',
		icon_type: 'preset',
		icon_dashicon: 'dashicons-category',
		image_url: '',
		color: '#4F46E5',
		description: '',
	} );

	const [ isCatModalOpen, setIsCatModalOpen ] = useState( false );
	const [ editingCat, setEditingCat ] = useState( null );
	const [ catForm, setCatForm ] = useState( {
		id: 0,
		name: '',
		slug: '',
		color: '#4F46E5',
		default_priority: 'normal',
		sub_taxonomies: [ 'product', 'tag' ],
		required_skills: '',
		description: '',
	} );
	const [ draggedSubTaxSlug, setDraggedSubTaxSlug ] = useState( null );
	const [ dropTargetSubTaxSlug, setDropTargetSubTaxSlug ] = useState( null );

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
		category_id: 0,
	} );

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
			if ( data?.success && Array.isArray( data.terms ) ) {
				setCustomTerms( data.terms );
			} else {
				setCustomTerms( [] );
			}
		} catch ( err ) {
			console.error( 'Error fetching terms:', err );
			setCustomTerms( [] );
		} finally {
			setTermsLoading( false );
		}
	};

	const fetchProducts = async () => {
		setProductsLoading( true );
		try {
			const data = await apiFetch( { path: '/dctc-ai/v1/support/products' } );
			if ( data?.success && Array.isArray( data.products ) ) {
				setProducts( data.products );
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
		if ( activeTaxSlug === 'product' ) {
			fetchProducts();
		} else if ( activeTaxSlug !== 'category' && activeTaxSlug !== 'tag' ) {
			fetchTermsForTaxonomy( activeTaxSlug );
		}
	}, [ activeTaxSlug ] );

	const handleOpenMediaUploader = () => {
		if ( typeof wp === 'undefined' || ! wp.media ) {
			alert( __( 'WordPress Media library is not available in this view.', 'dragwyb-click-to-chat' ) );
			return;
		}

		const mediaFrame = wp.media( {
			title: __( 'Select or Upload Taxonomy Icon / Image', 'dragwyb-click-to-chat' ),
			button: { text: __( 'Use as Taxonomy Icon', 'dragwyb-click-to-chat' ) },
			multiple: false,
			library: { type: 'image' },
		} );

		mediaFrame.on( 'select', () => {
			const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			if ( attachment && attachment.url ) {
				setTaxForm( ( prev ) => ( {
					...prev,
					image_url: attachment.url,
					icon_type: 'custom',
				} ) );
			}
		} );

		mediaFrame.open();
	};

	const handleOpenAddTaxonomy = () => {
		setEditingTax( null );
		setTaxForm( {
			name: '',
			slug: '',
			icon_type: 'preset',
			icon_dashicon: 'dashicons-category',
			image_url: '',
			color: '#4F46E5',
			description: '',
		} );
		setIsTaxModalOpen( true );
	};

	const handleOpenEditTaxonomy = ( tax ) => {
		setEditingTax( tax );
		setTaxForm( {
			name: tax.name || '',
			slug: tax.slug || '',
			icon_type: tax.image_url ? 'custom' : 'preset',
			icon_dashicon: tax.icon_dashicon || tax.icon || 'dashicons-category',
			image_url: tax.image_url || '',
			color: tax.color || '#4F46E5',
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
				data: {
					...taxForm,
					is_editing: !! editingTax,
					original_slug: editingTax ? editingTax.slug : '',
				},
			} );

			if ( data?.success ) {
				setIsTaxModalOpen( false );
				fetchTaxonomies();
				if ( taxForm.slug ) setActiveTaxSlug( taxForm.slug );
				if ( onShowNotice ) onShowNotice( __( 'Taxonomy saved successfully.', 'dragwyb-click-to-chat' ), 'success' );
			} else {
				alert( data?.message || __( 'Could not save taxonomy.', 'dragwyb-click-to-chat' ) );
			}
		} catch ( err ) {
			console.error( 'Error saving taxonomy:', err );
			alert( err?.message || __( 'An error occurred while saving taxonomy.', 'dragwyb-click-to-chat' ) );
		} finally {
			setSaving( false );
		}
	};

	const handleDeleteTaxonomy = async ( slug ) => {
		if ( ! window.confirm( __( 'Are you sure you want to delete this entire taxonomy and its terms?', 'dragwyb-click-to-chat' ) ) ) return;
		try {
			const data = await apiFetch( {
				path: `/dctc-ai/v1/support/taxonomies/${ slug }`,
				method: 'DELETE',
			} );
			if ( data?.success ) {
				setActiveTaxSlug( 'category' );
				fetchTaxonomies();
				if ( onShowNotice ) onShowNotice( __( 'Taxonomy deleted.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error deleting taxonomy:', err );
		}
	};

	const handleOpenAddCat = () => {
		setEditingCat( null );
		setCatForm( {
			id: 0,
			name: '',
			slug: '',
			color: '#4F46E5',
			default_priority: 'normal',
			sub_taxonomies: [ 'product', 'tag' ],
			required_skills: '',
			description: '',
		} );
		setIsCatModalOpen( true );
	};

	const handleOpenEditCat = ( cat ) => {
		setEditingCat( cat );
		const subTax = Array.isArray( cat.sub_taxonomies ) && cat.sub_taxonomies.length > 0
			? cat.sub_taxonomies
			: [
					...( Number( cat.show_product ?? 1 ) ? [ 'product' ] : [] ),
					...( Number( cat.show_tags ?? 1 ) ? [ 'tag' ] : [] ),
			  ];
		setCatForm( {
			id: cat.id,
			name: cat.name || '',
			slug: cat.slug || '',
			color: cat.color || '#4F46E5',
			default_priority: cat.default_priority || 'normal',
			sub_taxonomies: subTax,
			required_skills: cat.required_skills || '',
			description: cat.description || '',
		} );
		setIsCatModalOpen( true );
	};

	const handleToggleSubTaxonomy = ( subSlug ) => {
		const current = Array.isArray( catForm.sub_taxonomies ) ? [ ...catForm.sub_taxonomies ] : [];
		const exists = current.includes( subSlug );
		const next = exists ? current.filter( ( s ) => s !== subSlug ) : [ ...current, subSlug ];
		setCatForm( { ...catForm, sub_taxonomies: next } );
	};

	const handleMoveSubTaxonomy = ( subSlug, direction ) => {
		const current = Array.isArray( catForm.sub_taxonomies ) ? [ ...catForm.sub_taxonomies ] : [];
		const index = current.indexOf( subSlug );
		if ( index === -1 ) return;
		const targetIndex = direction === 'up' ? index - 1 : index + 1;
		if ( targetIndex < 0 || targetIndex >= current.length ) return;
		const item = current.splice( index, 1 )[ 0 ];
		current.splice( targetIndex, 0, item );
		setCatForm( { ...catForm, sub_taxonomies: current } );
	};

	const handleSubTaxDragStart = ( e, subSlug ) => {
		setDraggedSubTaxSlug( subSlug );
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData( 'text/plain', subSlug );
	};

	const handleSubTaxDragOver = ( e, subSlug ) => {
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
		if ( dropTargetSubTaxSlug !== subSlug ) {
			setDropTargetSubTaxSlug( subSlug );
		}
	};

	const handleSubTaxDrop = ( e, targetSubSlug ) => {
		e.preventDefault();
		const draggedSlug = draggedSubTaxSlug || e.dataTransfer.getData( 'text/plain' );
		if ( ! draggedSlug || draggedSlug === targetSubSlug ) {
			setDraggedSubTaxSlug( null );
			setDropTargetSubTaxSlug( null );
			return;
		}

		const current = Array.isArray( catForm.sub_taxonomies ) ? [ ...catForm.sub_taxonomies ] : [];
		const fromIndex = current.indexOf( draggedSlug );
		const toIndex = current.indexOf( targetSubSlug );
		if ( fromIndex === -1 || toIndex === -1 ) {
			setDraggedSubTaxSlug( null );
			setDropTargetSubTaxSlug( null );
			return;
		}

		const item = current.splice( fromIndex, 1 )[ 0 ];
		current.splice( toIndex, 0, item );
		setCatForm( { ...catForm, sub_taxonomies: current } );
		setDraggedSubTaxSlug( null );
		setDropTargetSubTaxSlug( null );
	};

	const handleSaveCategory = async ( e ) => {
		e.preventDefault();
		if ( ! catForm.name.trim() ) return;
		setSaving( true );
		try {
			const payload = {
				...catForm,
				show_product: ( catForm.sub_taxonomies || [] ).includes( 'product' ) ? 1 : 0,
				show_tags: ( catForm.sub_taxonomies || [] ).includes( 'tag' ) ? 1 : 0,
			};
			const data = await apiFetch( { path: '/dctc-ai/v1/support/categories', method: 'POST', data: payload } );
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
		if ( ! window.confirm( __( 'Are you sure you want to delete this category?', 'dragwyb-click-to-chat' ) ) ) return;
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

	const handleOpenAddTag = () => {
		setEditingTag( null );
		setTagForm( { id: 0, name: '', slug: '', color: '#D97706' } );
		setIsTagModalOpen( true );
	};

	const handleOpenEditTag = ( tag ) => {
		setEditingTag( tag );
		setTagForm( { id: tag.id, name: tag.name || '', slug: tag.slug || '', color: tag.color || '#D97706' } );
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
				if ( onShowNotice ) onShowNotice( __( 'Tag saved.', 'dragwyb-click-to-chat' ), 'success' );
			}
		} catch ( err ) {
			console.error( 'Error saving tag:', err );
		} finally {
			setSaving( false );
		}
	};

	const handleDeleteTag = async ( id ) => {
		if ( ! window.confirm( __( 'Are you sure you want to delete this tag?', 'dragwyb-click-to-chat' ) ) ) return;
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

	const handleOpenAddProd = () => {
		setEditingProd( null );
		setProdForm( { id: 0, name: '', slug: '', sku: '', category_id: 0 } );
		setIsProdModalOpen( true );
	};

	const handleOpenEditProd = ( prod ) => {
		setEditingProd( prod );
		setProdForm( {
			id: prod.id,
			name: prod.name || '',
			slug: prod.slug || '',
			sku: prod.sku || '',
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

	const activeTax = taxonomies.find( ( t ) => t.slug === activeTaxSlug ) || taxonomies[ 0 ];

	return (
		<div className="dctc-sc-panel-box">
			{/* Header */}
			<div className="dctc-sc-panel-header">
				<div className="dctc-sc-panel-icon-wrap icon-purple">
					<span className="dashicons dashicons-category"></span>
				</div>
				<div style={{ flex: 1 }}>
					<h3>{__('Support Taxonomies & Routing Engine', 'dragwyb-click-to-chat')}</h3>
					<p className="dctc-sc-panel-sub">
						{__('Manage built-in and custom dynamic taxonomies, classify tickets, and route conversations condition-based.', 'dragwyb-click-to-chat')}
					</p>
				</div>
			</div>

			{/* Taxonomies Navigation Bar with "+ Add Taxonomy" Button */}
			<div className="dctc-sc-subtabs-nav" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
				<div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', alignItems: 'center' }}>
					{taxonomies.map((tax) => (
						<button
							key={tax.slug}
							type="button"
							className={`dctc-sc-subtab-btn ${activeTaxSlug === tax.slug ? 'active' : ''}`}
							onClick={() => setActiveTaxSlug(tax.slug)}
						>
							{tax.image_url ? (
								<img
									src={tax.image_url}
									alt=""
									style={{ width: '16px', height: '16px', borderRadius: '3px', objectFit: 'cover', verticalAlign: 'middle', marginRight: '4px' }}
								/>
							) : (
								<span
									className={`dashicons ${tax.icon_dashicon || tax.icon || 'dashicons-category'}`}
									style={{ fontSize: '15px', width: '15px', height: '15px', verticalAlign: 'middle', marginRight: '4px' }}
								></span>
							)}
							<span>{tax.name}</span>
							{tax.slug === 'category' && <span className="dctc-sc-pill-count">({categories.length})</span>}
							{tax.slug === 'tag' && <span className="dctc-sc-pill-count">({tags.length})</span>}
							{tax.slug === 'product' && <span className="dctc-sc-pill-count">({products.length})</span>}
							{!tax.is_system && customTerms.length > 0 && activeTaxSlug === tax.slug && (
								<span className="dctc-sc-pill-count">({customTerms.length})</span>
							)}
						</button>
					))}
				</div>

				{canManage && (
					<button
						type="button"
						className="button button-primary dctc-sc-add-tax-btn"
						onClick={handleOpenAddTaxonomy}
						title={__('Create a new custom taxonomy', 'dragwyb-click-to-chat')}
					>
						<span className="dashicons dashicons-plus-alt2" style={{ verticalAlign: 'middle', marginRight: '4px' }}></span>
						{__('Add Taxonomy', 'dragwyb-click-to-chat')}
					</button>
				)}
			</div>

			{/* PANE 1: CATEGORIES */}
			{activeTaxSlug === 'category' && (
				<div className="dctc-sc-tab-pane">
					<div className="dctc-sc-toolbar-row">
						<div className="dctc-sc-toolbar-info">
							<span className="dashicons dashicons-info"></span>
							<span>{__('Primary routing taxonomy: controls dynamic display of Product & Tags in the support ticket form.', 'dragwyb-click-to-chat')}</span>
						</div>
						{canManage && (
							<button type="button" className="button button-primary" onClick={handleOpenAddCat}>
								<span className="dashicons dashicons-plus-alt2" style={{ verticalAlign: 'middle', marginRight: '4px' }}></span>
								{__('Add Category', 'dragwyb-click-to-chat')}
							</button>
						)}
					</div>

					<table className="wp-list-table widefat fixed striped dctc-sc-table">
						<thead>
							<tr>
								<th style={{ width: '24%' }}>{__('Category Name', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '18%' }}>{__('Slug', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '14%' }}>{__('Default Priority', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '30%' }}>{__('Sub-Field Taxonomies (Ticket Form Order)', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '14%', textAlign: 'right' }}>{__('Actions', 'dragwyb-click-to-chat')}</th>
							</tr>
						</thead>
						<tbody>
							{categories.length === 0 ? (
								<tr>
									<td colSpan="5" style={{ textAlign: 'center', padding: '30px' }}>
										{__('No categories found.', 'dragwyb-click-to-chat')}
									</td>
								</tr>
							) : (
								categories.map((cat) => {
									const catSubTax = Array.isArray(cat.sub_taxonomies) && cat.sub_taxonomies.length > 0
										? cat.sub_taxonomies
										: [
												...(Number(cat.show_product ?? 1) ? ['product'] : []),
												...(Number(cat.show_tags ?? 1) ? ['tag'] : []),
										  ];
									return (
										<tr key={cat.id}>
											<td>
												<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
													<span className="dctc-sc-color-bullet" style={{ backgroundColor: cat.color || '#4F46E5' }}></span>
													<strong>{cat.name}</strong>
												</div>
											</td>
											<td><code>{cat.slug}</code></td>
											<td>
												<span className={`dctc-sc-badge ${getPriorityBadgeClass(cat.default_priority)}`}>
													{cat.default_priority}
												</span>
											</td>
											<td>
												{catSubTax.length === 0 ? (
													<span style={{ color: '#94a3b8', fontSize: '12px' }}>{__('None (Standard fields only)', 'dragwyb-click-to-chat')}</span>
												) : (
													<div style={{ display: 'flex', flexWrap: 'wrap', gap: '4px', alignItems: 'center' }}>
														{catSubTax.map((subSlug, sIdx) => {
															const taxDef = taxonomies.find((t) => t.slug === subSlug);
															const taxName = taxDef ? taxDef.name : subSlug;
															return (
																<span
																	key={subSlug}
																	className="dctc-sc-badge-tag"
																	style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}
																>
																	<span style={{ fontSize: '10px', color: '#6366f1', fontWeight: 700 }}>#{sIdx + 1}</span>
																	{taxName}
																</span>
															);
														})}
													</div>
												)}
											</td>
											<td style={{ textAlign: 'right' }}>
												{canManage && (
													<div style={{ display: 'inline-flex', gap: '6px' }}>
														<button type="button" className="button button-small" onClick={() => handleOpenEditCat(cat)}>
															{__('Edit', 'dragwyb-click-to-chat')}
														</button>
														<button type="button" className="button button-small button-link-delete" onClick={() => handleDeleteCategory(cat.id)}>
															{__('Delete', 'dragwyb-click-to-chat')}
														</button>
													</div>
												)}
											</td>
										</tr>
									);
								})
							)}
						</tbody>
					</table>
				</div>
			)}

			{/* PANE 2: TAGS */}
			{activeTaxSlug === 'tag' && (
				<div className="dctc-sc-tab-pane">
					<div className="dctc-sc-toolbar-row">
						<div className="dctc-sc-toolbar-info">
							<span className="dashicons dashicons-tag"></span>
							<span>{__('Visual tags for fast identification and badge design across tickets.', 'dragwyb-click-to-chat')}</span>
						</div>
						{canManage && (
							<button type="button" className="button button-primary" onClick={handleOpenAddTag}>
								<span className="dashicons dashicons-plus-alt2" style={{ verticalAlign: 'middle', marginRight: '4px' }}></span>
								{__('Add Tag', 'dragwyb-click-to-chat')}
							</button>
						)}
					</div>

					<table className="wp-list-table widefat fixed striped dctc-sc-table">
						<thead>
							<tr>
								<th style={{ width: '35%' }}>{__('Tag Name & Badge', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '30%' }}>{__('Slug', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '20%' }}>{__('Color Code', 'dragwyb-click-to-chat')}</th>
								<th style={{ width: '15%', textAlign: 'right' }}>{__('Actions', 'dragwyb-click-to-chat')}</th>
							</tr>
						</thead>
						<tbody>
							{tags.length === 0 ? (
								<tr>
									<td colSpan="4" style={{ textAlign: 'center', padding: '30px' }}>
										{__('No tags found. Click "Add Tag" to create one.', 'dragwyb-click-to-chat')}
									</td>
								</tr>
							) : (
								tags.map((tag) => (
									<tr key={tag.id}>
										<td>
											<span className="dctc-sc-badge dctc-sc-badge-tag" style={{ borderColor: tag.color, color: tag.color }}>
												<span className="dashicons dashicons-tag" style={{ fontSize: '12px', width: '12px', height: '12px', verticalAlign: 'middle', marginRight: '4px' }}></span>
												{tag.name}
											</span>
										</td>
										<td><code>{tag.slug}</code></td>
										<td>
											<span className="dctc-sc-color-pill-sample" style={{ backgroundColor: tag.color }}></span>
											<code>{tag.color}</code>
										</td>
										<td style={{ textAlign: 'right' }}>
											{canManage && (
												<div style={{ display: 'inline-flex', gap: '6px' }}>
													<button type="button" className="button button-small" onClick={() => handleOpenEditTag(tag)}>
														{__('Edit', 'dragwyb-click-to-chat')}
													</button>
													<button type="button" className="button button-small button-link-delete" onClick={() => handleDeleteTag(tag.id)}>
														{__('Delete', 'dragwyb-click-to-chat')}
													</button>
												</div>
											)}
										</td>
									</tr>
								))
							)}
						</tbody>
					</table>
				</div>
			)}

			{/* PANE 3: PRODUCTS */}
			{activeTaxSlug === 'product' && (
				<div className="dctc-sc-tab-pane">
					<div className="dctc-sc-toolbar-row">
						<div className="dctc-sc-toolbar-info">
							<span className="dashicons dashicons-cart"></span>
							<span>{__('Product items catalog for support ticket tagging and auto-classification.', 'dragwyb-click-to-chat')}</span>
						</div>
						<div style={{ display: 'flex', gap: '8px' }}>
							{!!window.dctc_support_data?.is_woocommerce_active && (
								<button
									type="button"
									className="button"
									onClick={handleSyncWooCommerce}
									disabled={syncingWc}
								>
									<span className={`dashicons dashicons-update ${syncingWc ? 'rotating' : ''}`} style={{ verticalAlign: 'middle', marginRight: '4px' }}></span>
									{syncingWc ? __('Syncing...', 'dragwyb-click-to-chat') : __('Sync WooCommerce Products', 'dragwyb-click-to-chat')}
								</button>
							)}
							{canManage && (
								<button type="button" className="button button-primary" onClick={handleOpenAddProd}>
									<span className="dashicons dashicons-plus-alt2" style={{ verticalAlign: 'middle', marginRight: '4px' }}></span>
									{__('Add Product', 'dragwyb-click-to-chat')}
								</button>
							)}
						</div>
					</div>

					{productsLoading ? (
						<div className="dctc-sc-loading-state" style={{ padding: '40px' }}>
							<span className="spinner is-active"></span> {__('Loading products catalog...', 'dragwyb-click-to-chat')}
						</div>
					) : (
						<table className="wp-list-table widefat fixed striped dctc-sc-table">
							<thead>
								<tr>
									<th style={{ width: '45%' }}>{__('Product Name', 'dragwyb-click-to-chat')}</th>
									<th style={{ width: '25%' }}>{__('SKU / Model Code', 'dragwyb-click-to-chat')}</th>
									<th style={{ width: '18%' }}>{__('Source', 'dragwyb-click-to-chat')}</th>
									<th style={{ width: '12%', textAlign: 'right' }}>{__('Actions', 'dragwyb-click-to-chat')}</th>
								</tr>
							</thead>
							<tbody>
								{products.length === 0 ? (
									<tr>
										<td colSpan="4" style={{ textAlign: 'center', padding: '30px' }}>
											{__('No products in catalog. Click "Sync WooCommerce Products" or "Add Product".', 'dragwyb-click-to-chat')}
										</td>
									</tr>
								) : (
									products.map((prod) => (
										<tr key={prod.id}>
											<td><strong>{prod.name}</strong></td>
											<td><code>{prod.sku || '—' }</code></td>
											<td>
												{prod.wc_product_id ? (
													<span className="dctc-sc-badge" style={{ background: '#EDE9FE', color: '#5B21B6' }}>WooCommerce</span>
												) : (
													<span className="dctc-sc-badge" style={{ background: '#F1F5F9', color: '#475569' }}>Custom</span>
												)}
											</td>
											<td style={{ textAlign: 'right' }}>
												{canManage && (
													<div style={{ display: 'inline-flex', gap: '6px' }}>
														<button type="button" className="button button-small" onClick={() => handleOpenEditProd(prod)}>
															{__('Edit', 'dragwyb-click-to-chat')}
														</button>
														<button type="button" className="button button-small button-link-delete" onClick={() => handleDeleteProduct(prod.id)}>
															{__('Delete', 'dragwyb-click-to-chat')}
														</button>
													</div>
												)}
											</td>
										</tr>
									))
								)}
							</tbody>
						</table>
					)}
				</div>
			)}

			{/* PANE 4: CUSTOM USER-DEFINED TAXONOMY TERMS */}
			{!activeTax?.is_system && (
				<div className="dctc-sc-tab-pane">
					<div className="dctc-sc-taxonomy-banner">
						<div className="dctc-sc-tax-banner-left">
							<h4>
								{activeTax?.image_url ? (
									<img
										src={activeTax.image_url}
										alt=""
										style={{ width: '20px', height: '20px', borderRadius: '4px', objectFit: 'cover', verticalAlign: 'middle', marginRight: '6px' }}
									/>
								) : (
									<span
										className={`dashicons ${activeTax?.icon_dashicon || activeTax?.icon || 'dashicons-category'}`}
										style={{ fontSize: '20px', width: '20px', height: '20px', verticalAlign: 'middle', marginRight: '6px' }}
									></span>
								)}
								{activeTax?.name}{' '}
								<code>({activeTax?.slug})</code>
							</h4>
							<p>{activeTax?.description || __('Custom support taxonomy.', 'dragwyb-click-to-chat')}</p>
						</div>
						<div className="dctc-sc-tax-banner-actions">
							{canManage && (
								<>
									<button
										type="button"
										className="button button-small"
										onClick={() => handleOpenEditTaxonomy(activeTax)}
									>
										<span className="dashicons dashicons-edit"></span> {__('Edit Taxonomy', 'dragwyb-click-to-chat')}
									</button>
									<button
										type="button"
										className="button button-small button-link-delete"
										onClick={() => handleDeleteTaxonomy(activeTax.slug)}
									>
										<span className="dashicons dashicons-trash"></span> {__('Delete Taxonomy', 'dragwyb-click-to-chat')}
									</button>
									<button
										type="button"
										className="button button-primary"
										onClick={handleOpenAddTerm}
									>
										<span className="dashicons dashicons-plus-alt2" style={{ verticalAlign: 'middle', marginRight: '4px' }}></span>
										{__(`Add ${activeTax?.name} Term`, 'dragwyb-click-to-chat')}
									</button>
								</>
							)}
						</div>
					</div>

					{termsLoading ? (
						<div className="dctc-sc-loading-state" style={{ padding: '40px' }}>
							<span className="spinner is-active"></span> {__('Loading terms...', 'dragwyb-click-to-chat')}
						</div>
					) : (
						<table className="wp-list-table widefat fixed striped dctc-sc-table">
							<thead>
								<tr>
									<th style={{ width: '35%' }}>{__('Term Name', 'dragwyb-click-to-chat')}</th>
									<th style={{ width: '25%' }}>{__('Slug', 'dragwyb-click-to-chat')}</th>
									<th style={{ width: '25%' }}>{__('Description', 'dragwyb-click-to-chat')}</th>
									<th style={{ width: '15%', textAlign: 'right' }}>{__('Actions', 'dragwyb-click-to-chat')}</th>
								</tr>
							</thead>
							<tbody>
								{customTerms.length === 0 ? (
									<tr>
										<td colSpan="4" style={{ textAlign: 'center', padding: '30px' }}>
											{__(`No terms in "${activeTax?.name}". Click "Add ${activeTax?.name} Term" to create one.`, 'dragwyb-click-to-chat')}
										</td>
									</tr>
								) : (
									customTerms.map((term) => (
										<tr key={term.id}>
											<td>
												<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
													<span className="dctc-sc-color-bullet" style={{ backgroundColor: term.color || activeTax?.color || '#4F46E5' }}></span>
													<strong>{term.name}</strong>
												</div>
											</td>
											<td><code>{term.slug}</code></td>
											<td>{term.description || '—'}</td>
											<td style={{ textAlign: 'right' }}>
												{canManage && (
													<div style={{ display: 'inline-flex', gap: '6px' }}>
														<button type="button" className="button button-small" onClick={() => handleOpenEditTerm(term)}>
															{__('Edit', 'dragwyb-click-to-chat')}
														</button>
														<button type="button" className="button button-small button-link-delete" onClick={() => handleDeleteTerm(term.id)}>
															{__('Delete', 'dragwyb-click-to-chat')}
														</button>
													</div>
												)}
											</td>
										</tr>
									))
								)}
							</tbody>
						</table>
					)}
				</div>
			)}

			{/* MODAL 1: ADD / EDIT CUSTOM TAXONOMY */}
			<TaxonomyModal
				open={isTaxModalOpen}
				editingTax={editingTax}
				taxForm={taxForm}
				setTaxForm={setTaxForm}
				onSave={handleSaveTaxonomy}
				onClose={() => setIsTaxModalOpen(false)}
				saving={saving}
				generateSlug={generateSlug}
				DASHICON_PRESETS={DASHICON_PRESETS}
				COLOR_PRESETS={COLOR_PRESETS}
				onOpenMedia={handleOpenMediaUploader}
			/>

			{/* MODAL 2: ADD / EDIT CATEGORY */}
			<CategoryModal
				open={isCatModalOpen}
				editingCat={editingCat}
				catForm={catForm}
				setCatForm={setCatForm}
				onSave={handleSaveCategory}
				onClose={() => setIsCatModalOpen(false)}
				saving={saving}
				generateSlug={generateSlug}
				COLOR_PRESETS={COLOR_PRESETS}
				PRESET_SKILLS={PRESET_SKILLS}
				taxonomies={taxonomies}
				draggedSubTaxSlug={draggedSubTaxSlug}
				setDraggedSubTaxSlug={setDraggedSubTaxSlug}
				dropTargetSubTaxSlug={dropTargetSubTaxSlug}
				setDropTargetSubTaxSlug={setDropTargetSubTaxSlug}
				handleSubTaxDragStart={handleSubTaxDragStart}
				handleSubTaxDragOver={handleSubTaxDragOver}
				handleSubTaxDrop={handleSubTaxDrop}
				handleToggleSubTaxonomy={handleToggleSubTaxonomy}
				handleMoveSubTaxonomy={handleMoveSubTaxonomy}
			/>

			{/* MODAL 3: ADD / EDIT TAG */}
			<TagModal
				open={isTagModalOpen}
				editingTag={editingTag}
				tagForm={tagForm}
				setTagForm={setTagForm}
				onSave={handleSaveTag}
				onClose={() => setIsTagModalOpen(false)}
				saving={saving}
				generateSlug={generateSlug}
				COLOR_PRESETS={COLOR_PRESETS}
			/>

			{/* MODAL 4: ADD / EDIT PRODUCT */}
			<ProductModal
				open={isProdModalOpen}
				editingProd={editingProd}
				prodForm={prodForm}
				setProdForm={setProdForm}
				onSave={handleSaveProduct}
				onClose={() => setIsProdModalOpen(false)}
				saving={saving}
				generateSlug={generateSlug}
				categories={categories}
			/>

			{/* MODAL 5: ADD / EDIT CUSTOM TAXONOMY TERM */}
			<CustomTermModal
				open={isTermModalOpen}
				editingTerm={editingTerm}
				activeTax={activeTax}
				termForm={termForm}
				setTermForm={setTermForm}
				onSave={handleSaveTerm}
				onClose={() => setIsTermModalOpen(false)}
				saving={saving}
				generateSlug={generateSlug}
				COLOR_PRESETS={COLOR_PRESETS}
			/>
		</div>
	);
}
