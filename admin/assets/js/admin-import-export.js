/**
 * Settings import / export validation.
 */
( function () {
	'use strict';

	var exportForm = document.getElementById( 'dctc-ie-export-form' );
	var importForm = document.getElementById( 'dctc-ie-import-form' );

	if ( exportForm ) {
		exportForm.addEventListener( 'submit', function ( e ) {
			var checked = exportForm.querySelectorAll(
				'input[name="dctc_modules[]"]:checked'
			);
			if ( ! checked.length ) {
				e.preventDefault();
				window.alert(
					'Please select at least one module to export (Channels and/or AI Assistant).'
				);
			}
		} );
	}

	if ( importForm ) {
		importForm.addEventListener( 'submit', function ( e ) {
			var checked = importForm.querySelectorAll(
				'input[name="dctc_import_modules[]"]:checked'
			);
			if ( ! checked.length ) {
				e.preventDefault();
				window.alert(
					'Please select at least one module to import (Channels and/or AI Assistant).'
				);
				return;
			}

			var fileInput = document.getElementById( 'dctc_import_file' );
			if ( ! fileInput || ! fileInput.files || ! fileInput.files.length ) {
				e.preventDefault();
				window.alert( 'Please choose a file to import.' );
				return;
			}

			if (
				! window.confirm(
					'Import will overwrite the selected settings on this site. API keys will not be changed. Continue?'
				)
			) {
				e.preventDefault();
			}
		} );
	}
} )();
