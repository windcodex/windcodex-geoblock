/* global jQuery, geoblock_admin */
( function ( $ ) {
	'use strict';

	// ── Cached elements ───────────────────────────────────────────────────────
	var $saveBtn   = $( '#gg-save-btn' );
	var $resetBtn  = $( '#gg-reset-btn' );
	var $toast     = $( '#gg-toast' );
	var $form      = $( '#gg-settings-form' );
	var toastTimer = null;
	var $breadcrumbCurrent = $( '#gg-breadcrumb-current' );
	var $footerBar = $( '#gg-footer-bar' );

	function updateTabUi( target, label ) {
		if ( $breadcrumbCurrent.length ) {
			$breadcrumbCurrent.text( label || '' );
		}
		if ( $footerBar.length ) {
			var visible = ( $footerBar.data( 'tab-visible' ) || '' ).toString().split( ',' );
			var show = visible.indexOf( target ) !== -1;
			$footerBar.toggle( show );
		}
	}

	function initCommonSettingsAccordion() {
		$( '.gg-tab-panel[data-panel="general"] .gg-card, .gg-tab-panel[data-panel="advanced"] .gg-card' ).each( function ( cardIndex ) {
			var $card = $( this );
			if ( $card.data( 'ggAccordionInit' ) ) {
				return;
			}

			var $header = $card.find( '.gg-card-header' ).first();
			if ( ! $header.length ) {
				return;
			}

			$card.data( 'ggAccordionInit', true ).addClass( 'gg-card-accordion-enabled' );

			var $actions = $header.find( '.gg-card-header-actions' ).first();
			if ( ! $actions.length ) {
				$actions = $( '<div class="gg-card-header-actions"></div>' ).appendTo( $header );
			}

			var $toggle = $actions.find( '[data-card-accordion-toggle]' ).first();
			if ( ! $toggle.length ) {
				$toggle = $( '<button type="button" class="gg-card-accordion-toggle" data-card-accordion-toggle aria-expanded="true"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>' );
				$actions.append( $toggle );
			}

			var $content = $card.find( '.gg-card-accordion-content' ).first();
			if ( ! $content.length ) {
				$content = $( '<div class="gg-card-accordion-content"></div>' );
				$header.nextAll().appendTo( $content );
				$header.after( $content );
			}

			var panelName = $card.closest( '.gg-tab-panel' ).data( 'panel' ) || 'panel';
			var storageKey = 'geoblock_free_card_collapsed_' + panelName + '_' + cardIndex;

			function setCollapsed( collapsed, skipAnimation ) {
				$card.toggleClass( 'is-collapsed', !! collapsed );
				$toggle.attr( 'aria-expanded', collapsed ? 'false' : 'true' );

				if ( skipAnimation ) {
					$content.toggle( ! collapsed );
					return;
				}
				if ( collapsed ) {
					$content.stop( true, true ).slideUp( 160 );
				} else {
					$content.stop( true, true ).slideDown( 180 );
				}
			}

			var isCollapsed = false;
			try {
				isCollapsed = window.localStorage.getItem( storageKey ) === '1';
			} catch ( e ) {
				isCollapsed = false;
			}
			setCollapsed( isCollapsed, true );

			function toggleAccordion() {
				var collapsed = ! $card.hasClass( 'is-collapsed' );
				setCollapsed( collapsed, false );
				try {
					window.localStorage.setItem( storageKey, collapsed ? '1' : '0' );
				} catch ( e ) {
					/* no-op */
				}
			}

			$header.on( 'click', function () {
				toggleAccordion();
			} );
			$toggle.on( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				toggleAccordion();
			} );
		} );
	}

	// ── Tab switching ─────────────────────────────────────────────────────────
	$( '.gg-tab-btn' ).on( 'click', function () {
		var target = $( this ).data( 'tab' );
		var label  = $( this ).data( 'breadcrumb' ) || $( this ).text().trim();

		$( '.gg-tab-btn' ).removeClass( 'gg-tab-active' ).attr( 'aria-selected', 'false' );
		$( this ).addClass( 'gg-tab-active' ).attr( 'aria-selected', 'true' );

		$( '.gg-tab-panel' ).removeClass( 'gg-tab-panel-active' );
		$( '[data-panel="' + target + '"]' ).addClass( 'gg-tab-panel-active' );
		updateTabUi( target, label );
	} );

	var $activeTab = $( '.gg-tab-btn.gg-tab-active' );
	if ( $activeTab.length ) {
		updateTabUi( $activeTab.data( 'tab' ), $activeTab.data( 'breadcrumb' ) || $activeTab.text().trim() );
	}
	initCommonSettingsAccordion();

	// ── Radio card selection ──────────────────────────────────────────────────
	$( '.gg-radio-card' ).on( 'click', function () {
		var $card = $( this );
		var mode  = $card.find( '.gg-radio-input' ).val();

		$( '.gg-radio-card' ).removeClass( 'gg-selected' );
		$card.addClass( 'gg-selected' );

		if ( mode === 'hide' || mode === 'catalog_only' ) {
			$( '#gg-row-message' ).addClass( 'gg-hidden' );
			$( '#gg-row-message-pos' ).addClass( 'gg-hidden' );
		} else {
			$( '#gg-row-message' ).removeClass( 'gg-hidden' );
			$( '#gg-row-message-pos' ).removeClass( 'gg-hidden' );
		}

		// Show redirect sub-option only under Hide mode
		if ( mode === 'hide' ) {
			$( '#gg-sub-redirect' ).removeClass( 'gg-hidden' );
		} else {
			$( '#gg-sub-redirect' ).addClass( 'gg-hidden' );
		}

		// Show catalog purchasable sub-option only under catalog_only mode
		if ( mode === 'catalog_only' ) {
			$( '#gg-sub-catalog' ).removeClass( 'gg-hidden' );
		} else {
			$( '#gg-sub-catalog' ).addClass( 'gg-hidden' );
		}
	} );

	// ── AJAX Save ─────────────────────────────────────────────────────────────
	$saveBtn.on( 'click', function () {
		var settings = collectFormData();
		var needsRedirectUrl = ( settings.restriction_mode === 'hide' && settings.redirect_enabled === 'yes' );
		var redirectUrl = ( settings.redirect_url || '' ).trim();
		if ( needsRedirectUrl && ! redirectUrl ) {
			showToast( '&#10005; ' + geoblock_admin.i18n.redirect_url_required, 'error' );
			$( '#gg-sub-url-field' ).removeClass( 'gg-hidden' );
			$( '#geoblock_redirect_url' ).trigger( 'focus' );
			return;
		}
		//setBusy( true );
		showToast( '', 'saving' );

		$.ajax( {
			url:    geoblock_admin.ajax_url,
			method: 'POST',
			data: {
				action:   'geoblock_save_settings',
				nonce:    geoblock_admin.save_nonce,
				settings: settings,
			},
		} )
		.done( function ( response ) {
			if ( response.success ) {
				showToast( '&#10003; ' + geoblock_admin.i18n.saved, 'success' );
			} else {
				var msg = ( response.data && response.data.message ) ? response.data.message : geoblock_admin.i18n.save_error;
				showToast( '&#10005; ' + msg, 'error' );
			}
		} )
		.fail( function () {
			showToast( '&#10005; ' + geoblock_admin.i18n.save_error, 'error' );
		} )
		.always( function () {
			setBusy( false );
		} );
	} );

	// ── AJAX Reset ────────────────────────────────────────────────────────────
	$resetBtn.on( 'click', function () {
		if ( ! window.confirm( geoblock_admin.i18n.reset_confirm ) ) {
			return;
		}

		//setBusy( true );
		showToast( '', 'saving' );

		$.ajax( {
			url:    geoblock_admin.ajax_url,
			method: 'POST',
			data: {
				action: 'geoblock_reset_settings',
				nonce:  geoblock_admin.reset_nonce,
			},
		} )
		.done( function ( response ) {
			if ( response.success ) {
				applySettingsToForm( response.data.settings );
				showToast( '&#8635; ' + geoblock_admin.i18n.reset_done, 'reset' );
			} else {
				showToast( '&#10005; ' + geoblock_admin.i18n.reset_error, 'error' );
			}
		} )
		.fail( function () {
			showToast( '&#10005; ' + geoblock_admin.i18n.reset_error, 'error' );
		} )
		.always( function () {
			setBusy( false );
		} );
	} );

	// ── Collect form values ───────────────────────────────────────────────────
	function collectFormData() {
		var data = {};
		var arr  = $form.serializeArray();

		$.each( arr, function ( i, field ) {
			var match = field.name.match( /geoblock_settings\[(\w+)\]/ );
			if ( match ) {
				data[ match[1] ] = field.value;
			}
		} );

		// Unchecked checkboxes are not included in serializeArray — add 'no' fallbacks.
		if ( ! data.debug_mode )            { data.debug_mode            = 'no'; }
		if ( ! data.force_geolocation )     { data.force_geolocation     = 'no'; }
		if ( ! data.redirect_enabled )      { data.redirect_enabled      = 'no'; }
		if ( ! data.catalog_purchasable )   { data.catalog_purchasable   = 'no'; }

		return data;
	}

	// ── Apply settings back to form after reset ───────────────────────────────
	function applySettingsToForm( settings ) {
		if ( ! settings ) { return; }

		if ( settings.restriction_mode ) {
			$( '.gg-radio-card' ).removeClass( 'gg-selected' );
			$( '.gg-radio-input[value="' + settings.restriction_mode + '"]' )
				.closest( '.gg-radio-card' )
				.addClass( 'gg-selected' )
				.find( '.gg-radio-input' )
				.prop( 'checked', true );

			var isHideMode = ( settings.restriction_mode === 'hide' );
			var isHideOrCatalog = ( settings.restriction_mode === 'hide' || settings.restriction_mode === 'catalog_only' );

			// Show/hide message + message position rows.
			if ( isHideOrCatalog ) {
				$( '#gg-row-message' ).addClass( 'gg-hidden' );
				$( '#gg-row-message-pos' ).addClass( 'gg-hidden' );
			} else {
				$( '#gg-row-message' ).removeClass( 'gg-hidden' );
				$( '#gg-row-message-pos' ).removeClass( 'gg-hidden' );
			}

			// Show/hide redirect sub-option.
			if ( isHideMode ) {
				$( '#gg-sub-redirect' ).removeClass( 'gg-hidden' );
			} else {
				$( '#gg-sub-redirect' ).addClass( 'gg-hidden' );
			}
		}

		if ( typeof settings.custom_message !== 'undefined' ) {
			$( '#geoblock_custom_message' ).val( settings.custom_message );
		}

		if ( typeof settings.redirect_url !== 'undefined' ) {
			$( '#geoblock_redirect_url' ).val( settings.redirect_url );
			var hasRedirect = ( settings.redirect_url && settings.redirect_url.length > 0 );
			$( '#gg-redirect-toggle' ).prop( 'checked', hasRedirect );
			if ( hasRedirect ) {
				$( '#gg-sub-url-field' ).removeClass( 'gg-hidden' );
			} else {
				$( '#gg-sub-url-field' ).addClass( 'gg-hidden' );
			}
		}

		if ( settings.message_position ) {
			$( '.gg-position-card' ).removeClass( 'gg-pos-selected' );
			$( 'input[name="geoblock_settings[message_position]"][value="' + settings.message_position + '"]' )
				.closest( '.gg-position-card' ).addClass( 'gg-pos-selected' );
		}

		var forceGeoOn = ( settings.force_geolocation === 'yes' );
		$( '#gg-force-geo-toggle' ).prop( 'checked', forceGeoOn );

		var debugOn = ( settings.debug_mode === 'yes' );
		$( '#gg-debug-toggle' ).prop( 'checked', debugOn );

		// Show/hide catalog sub-option on reset
		if ( settings.restriction_mode === 'catalog_only' ) {
			$( '#gg-sub-catalog' ).removeClass( 'gg-hidden' );
		} else {
			$( '#gg-sub-catalog' ).addClass( 'gg-hidden' );
		}
		var catalogPurchasableOn = ( settings.catalog_purchasable === 'yes' );
		$( '#gg-catalog-purchasable-toggle' ).prop( 'checked', catalogPurchasableOn );
	}

	// ── Busy state ────────────────────────────────────────────────────────────
	function setBusy( busy ) {
		$saveBtn.prop( 'disabled', busy );
		$resetBtn.prop( 'disabled', busy );

		if ( busy ) {
			$saveBtn.find( '.gg-btn-icon' ).html( '<span class="gg-spinner"></span>' );
			$saveBtn.find( '.gg-btn-label' ).text( geoblock_admin.i18n.saving );
		} else {
			$saveBtn.find( '.gg-btn-icon' ).html( '&#128190;' );
			$saveBtn.find( '.gg-btn-label' ).text( 'Save Settings' );
		}
	}

	// ── Toast ─────────────────────────────────────────────────────────────────
	function showToast( message, type ) {
		clearTimeout( toastTimer );

		if ( type === 'saving' ) {
			$toast.html( '<span class="gg-spinner" style="border-top-color:var(--gg-accent);border-color:var(--gg-accent-mid);"></span> ' + geoblock_admin.i18n.saving );
			$toast.attr( 'class', 'gg-toast gg-toast-show' );
			return;
		}

		var cls = 'gg-toast gg-toast-show gg-toast-';
		if ( type === 'error' )       { cls += 'error'; }
		else if ( type === 'reset' )  { cls += 'reset'; }
		else                          { cls += 'success'; }

		$toast.attr( 'class', cls ).html( message );

		toastTimer = setTimeout( function () {
			$toast.removeClass( 'gg-toast-show' );
		}, 3500 );
	}

	// ── Select2 on product meta box ───────────────────────────────────────────
	if ( $.fn.select2 && $( '#geoblock_countries_select' ).length ) {
		var $select = $( '#geoblock_countries_select' );

		$select.select2( {
			placeholder:      geoblock_admin.i18n.select_countries,
			allowClear:       true,
			width:            '100%',
			language: {
				noResults: function () {
					return geoblock_admin.i18n.no_results;
				},
			},
		} );

		$select.on( 'change', function () {
			var mode = $( 'input[name="geoblock_mode"]:checked' ).val() || 'exclude';
			updateRuleSummary( mode );
		} );


	}

	// ── Redirect toggle — show/hide URL field ──────────────────────────────────
	$( '#gg-redirect-toggle' ).on( 'change', function () {
		if ( $( this ).is( ':checked' ) ) {
			$( '#gg-sub-url-field' ).removeClass( 'gg-hidden' );
		} else {
			$( '#gg-sub-url-field' ).addClass( 'gg-hidden' );
			$( '#geoblock_redirect_url' ).val( '' );
		}
	} );

	// ── Message position card interactivity ──────────────────────────────────────
	$( '.gg-position-card' ).on( 'click', function () {
		$( '.gg-position-card' ).removeClass( 'gg-pos-selected' );
		$( this ).addClass( 'gg-pos-selected' );
		$( this ).find( '.gg-position-radio' ).prop( 'checked', true );
	} );

	// ── Rule pill interactivity ───────────────────────────────────────────────
	$( '.gg-rule-radio' ).on( 'change', function () {
		var value = $( this ).val();
		$( '.gg-rule-pill' ).removeClass( 'gg-pill-active' );
		$( this ).closest( '.gg-rule-pill' ).addClass( 'gg-pill-active' );
		updateRuleSummary( value );
	} );

	$( '.gg-rule-pill' ).on( 'click', function () {
		$( this ).find( '.gg-rule-radio' ).prop( 'checked', true ).trigger( 'change' );
	} );

	// ── Rule summary ─────────────────────────────────────────────────────────
	function updateRuleSummary( mode ) {
		var $summary     = $( '#gg-rule-summary' );
		if ( ! $summary.length ) { return; }

		var selected     = $( '#geoblock_countries_select' ).val() || [];
		var allCountries = geoblock_admin.countries || {};

		if ( ! selected.length ) {
			$summary.attr( 'class', 'gg-rule-summary gg-summary-none' );
			$summary.html(
				'<span class="gg-summary-icon">\u2705</span>' +
				'<span class="gg-summary-text">' + geoblock_admin.i18n.no_restrictions + '</span>'
			);
			return;
		}

		function formatCountryLabel( name, code ) {
			var label = name || code;
			var codeTag = ' (' + code + ')';
			if ( label.toUpperCase().indexOf( codeTag.toUpperCase() ) === -1 ) {
				label += codeTag;
			}
			return label;
		}

		var names = selected.map( function ( code ) {
			return formatCountryLabel( allCountries[ code ], code );
		} ).join( ', ' );

		var icon, cls, text;

		if ( mode === 'include' ) {
			icon = '\u2705';
			cls  = 'gg-rule-summary gg-summary-include';
			text = geoblock_admin.i18n.include_text.replace( '%s', '<strong>' + escHtml( names ) + '</strong>' );
		} else {
			icon = '\uD83D\uDEAB';
			cls  = 'gg-rule-summary gg-summary-exclude';
			text = geoblock_admin.i18n.exclude_text.replace( '%s', '<strong>' + escHtml( names ) + '</strong>' );
		}

		$summary.attr( 'class', cls ).html(
			'<span class="gg-summary-icon">' + icon + '</span>' +
			'<span class="gg-summary-text">' + text + '</span>'
		);
	}

	function escHtml( str ) {
		return $( '<div>' ).text( str ).html();
	}

	// Init rule summary on load.
	var savedMode = $( 'input[name="geoblock_mode"]:checked' ).val();
	if ( savedMode ) { updateRuleSummary( savedMode ); }

} )( jQuery );
