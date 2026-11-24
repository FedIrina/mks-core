( function () {
	'use strict';

	var timers = [];
	var interval = null;

	function pad( value ) {
		return String( value ).padStart( 2, '0' );
	}

	function setUnit( timer, unit, value ) {
		if ( timer.units[ unit ] ) {
			timer.units[ unit ].textContent = pad( value );
		}
	}

	function tick() {
		var now = Date.now();
		var active = false;

		timers.forEach( function ( timer ) {
			if ( timer.done ) {
				return;
			}

			var remaining = Math.floor( ( timer.until - now ) / 1000 );

			if ( remaining <= 0 ) {
				timer.done = true;
				remaining = 0;
			} else {
				active = true;
			}

			setUnit( timer, 'days', Math.floor( remaining / 86400 ) );
			setUnit( timer, 'hours', Math.floor( ( remaining % 86400 ) / 3600 ) );
			setUnit( timer, 'minutes', Math.floor( ( remaining % 3600 ) / 60 ) );
			setUnit( timer, 'seconds', remaining % 60 );
		} );

		if ( ! active && interval !== null ) {
			window.clearInterval( interval );
			interval = null;
		}
	}

	function start() {
		timers.forEach( function ( timer ) {
			timer.units = {
				days: timer.el.querySelector( '[data-unit="days"]' ),
				hours: timer.el.querySelector( '[data-unit="hours"]' ),
				minutes: timer.el.querySelector( '[data-unit="minutes"]' ),
				seconds: timer.el.querySelector( '[data-unit="seconds"]' )
			};
			timer.done = false;
		} );

		tick();

		if ( timers.some( function ( timer ) {
			return ! timer.done;
		} ) ) {
			interval = window.setInterval( tick, 1000 );
		}
	}

	document.querySelectorAll( '.mks-countdown[data-until]' ).forEach( function ( el ) {
		var until = Date.parse( el.getAttribute( 'data-until' ) );

		if ( ! isNaN( until ) ) {
			timers.push( {
				el: el,
				until: until,
				units: {},
				done: false
			} );
		}
	} );

	if ( ! timers.length ) {
		return;
	}

	start();

	document.addEventListener( 'visibilitychange', function () {
		if ( ! document.hidden ) {
			tick();
		}
	} );
}() );
