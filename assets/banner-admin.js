( function () {
	function toggleFields() {
		var checked = document.querySelector( 'input[name="acf[field_mks_countdown_type]"]:checked' );
		var display = checked && 'countdown' === checked.value ? 'none' : '';

		document.getElementById( 'postimagediv' ).style.display = display;
		document.getElementById( 'banner_caption' ).style.display = display;
	}

	document.addEventListener( 'DOMContentLoaded', toggleFields );
	document.addEventListener( 'change', function ( event ) {
		if ( 'acf[field_mks_countdown_type]' === event.target.name ) {
			toggleFields();
		}
	} );
} )();
