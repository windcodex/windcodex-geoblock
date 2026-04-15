(function () {
	function ggHideVariationForm() {
		if ( ! window.geoblock_data || ! window.geoblock_data.hide_variation_form ) {
			return;
		}

		var form = document.querySelector( 'form.variations_form' );
		if ( ! form ) {
			return;
		}

		// Hide the variation controls and the button area.
		var vars = form.querySelector( 'table.variations' );
		if ( vars ) {
			vars.style.display = 'none';
		}

		var wrap = form.querySelector( '.single_variation_wrap' );
		if ( wrap ) {
			wrap.style.display = 'none';
		}

		var reset = form.querySelector( '.reset_variations' );
		if ( reset ) {
			reset.style.display = 'none';
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', ggHideVariationForm );
	} else {
		ggHideVariationForm();
	}
})();
