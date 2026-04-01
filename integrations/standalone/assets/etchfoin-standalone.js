/* Etchmail Standalone Form — Frontend Submission */
(function () {
	'use strict';

	var DEBUG = (window.etchfoinStandalone && window.etchfoinStandalone.debug);

	function log() {
		if (!DEBUG) return;
		var args = ['[Etchmail]'].concat(Array.prototype.slice.call(arguments));
		console.log.apply(console, args);
	}

	document.addEventListener('DOMContentLoaded', function () {
		var forms = document.querySelectorAll('.etchmail-form form');
		log('Forms found:', forms.length);
		if (!forms.length) {
			log('No .etchmail-form form elements on page');
		}
		forms.forEach(function (form) { initForm(form); });
	});

	function initForm(form) {
		log('Initialising form', form.id || '(no id)');
		log('ajaxUrl:', (window.etchfoinStandalone && window.etchfoinStandalone.ajaxUrl) || 'MISSING');
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			log('Submit event fired');
			submitForm(form);
		});
	}

	function submitForm(form) {
		var btn     = form.querySelector('.etchmail-form__submit');
		var msgEl   = form.querySelector('.etchmail-form__message');
		var inputs  = form.querySelectorAll('.etchmail-form__input');

		// Client-side validation
		var valid = true;
		inputs.forEach(function (input) {
			input.removeAttribute('aria-invalid');
			if (input.required && !input.value.trim()) {
				input.setAttribute('aria-invalid', 'true');
				valid = false;
				log('Validation fail — required field empty:', input.name);
			}
			if (input.type === 'email' && input.value && !isValidEmail(input.value)) {
				input.setAttribute('aria-invalid', 'true');
				valid = false;
				log('Validation fail — invalid email:', input.name);
			}
		});

		if (!valid) {
			showMessage(msgEl, 'error', 'Please fill in all required fields.');
			return;
		}

		// Disable button, show loading
		var originalText = btn.textContent;
		btn.disabled = true;
		btn.innerHTML = '<span class="etchmail-form__spinner"></span> Sending…';
		showMessage(msgEl, '', '');

		// Build FormData
		var data = new FormData(form);
		data.append('action', 'etchfoin_standalone_submit');

		// Debug: log all FormData entries
		if (DEBUG) {
			log('FormData entries:');
			data.forEach(function (value, key) {
				log('  ' + key + ' = ' + (key === '_etchmail_nonce' ? value.substring(0, 6) + '...' : value));
			});
		}

		var ajaxUrl = (window.etchfoinStandalone && window.etchfoinStandalone.ajaxUrl) || '/wp-admin/admin-ajax.php';
		log('Sending XHR POST to', ajaxUrl);

		var xhr = new XMLHttpRequest();
		xhr.open('POST', ajaxUrl);
		xhr.onload = function () {
			btn.disabled = false;
			btn.textContent = originalText;

			log('Response status:', xhr.status);
			log('Response body:', xhr.responseText.substring(0, 500));

			var response;
			try { response = JSON.parse(xhr.responseText); } catch (e) {
				log('Failed to parse JSON response:', e.message);
				response = null;
			}

			if (xhr.status === 200 && response && response.success) {
				log('SUCCESS');
				showMessage(msgEl, 'success', msgEl.dataset.success || 'Thank you for subscribing!');
				form.reset();
				inputs.forEach(function (input) { input.removeAttribute('aria-invalid'); });

				// Auto-close popup on success if configured
				var popup = form.closest('.etchmail-popup');
				if (popup && popup.dataset.hideOnSubmit === 'yes') {
					setTimeout(function () { hidePopup(popup, true); }, 1500);
				}
			} else {
				var msg = (response && response.data && response.data.message) || msgEl.dataset.error || 'Something went wrong.';
				log('FAIL:', msg);
				showMessage(msgEl, 'error', msg);
			}
		};
		xhr.onerror = function () {
			btn.disabled = false;
			btn.textContent = originalText;
			log('XHR onerror — network failure');
			showMessage(msgEl, 'error', msgEl.dataset.error || 'Something went wrong.');
		};
		xhr.send(data);
	}

	function showMessage(el, type, text) {
		el.textContent = text;
		el.className = 'etchmail-form__message';
		if (type) {
			el.classList.add('etchmail-form__message--' + type);
		}
	}

	function isValidEmail(email) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
	}

	/* ==============================================================
	   POPUP / MODAL LOGIC
	   ============================================================== */

	function getCookie(name) {
		var match = document.cookie.match(new RegExp('(^| )' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]+)'));
		return match ? match[2] : null;
	}

	function setCookie(name, value, days) {
		var d = new Date();
		d.setTime(d.getTime() + (days * 86400000));
		document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
	}

	function showPopup(popup) {
		if (popup.classList.contains('etchmail-popup--open')) return;
		log('Opening popup');
		popup.classList.remove('etchmail-popup--closing');
		popup.style.display = 'flex';
		popup.classList.add('etchmail-popup--open');
		document.body.style.overflow = 'hidden';

		// Focus the close button for accessibility
		var closeBtn = popup.querySelector('.etchmail-popup__close');
		if (closeBtn) closeBtn.focus();
	}

	function hidePopup(popup, skipCookie) {
		if (!popup.classList.contains('etchmail-popup--open')) return;
		log('Closing popup');
		popup.classList.add('etchmail-popup--closing');

		// Set show-once cookie
		if (!skipCookie && popup.dataset.showOnce === 'yes') {
			var days = parseInt(popup.dataset.cookieDays, 10) || 30;
			var cookieName = 'etchmail_popup_' + (popup.dataset.listUid || 'default');
			setCookie(cookieName, '1', days);
			log('Set show-once cookie for', days, 'days');
		}

		setTimeout(function () {
			popup.classList.remove('etchmail-popup--open', 'etchmail-popup--closing');
			popup.style.display = 'none';
			document.body.style.overflow = '';
		}, 200);
	}

	function initPopups() {
		var popups = document.querySelectorAll('.etchmail-popup');
		log('Popups found:', popups.length);
		popups.forEach(function (popup) {
			// Move to body so no ancestor stacking context can clip it
			document.body.appendChild(popup);
			setupPopup(popup);
		});
	}

	function setupPopup(popup) {
		var showOnce    = popup.dataset.showOnce === 'yes';
		var cookieDays  = parseInt(popup.dataset.cookieDays, 10) || 30;
		var cookieName  = 'etchmail_popup_' + (popup.dataset.listUid || 'default');
		var delay       = parseFloat(popup.dataset.delay) || 0;
		var exitIntent  = popup.dataset.exitIntent === 'yes';
		var scrollPct   = parseInt(popup.dataset.scroll, 10) || 0;
		var closeOnOver = popup.dataset.closeOverlay === 'yes';

		// Check show-once cookie
		if (showOnce && getCookie(cookieName)) {
			log('Popup suppressed — show-once cookie exists');
			return;
		}

		var shown = false;
		function triggerShow() {
			if (shown) return;
			shown = true;
			showPopup(popup);
		}

		// Delay trigger
		if (delay > 0) {
			log('Popup will show after', delay, 's delay');
			setTimeout(triggerShow, delay * 1000);
		}

		// Exit-intent trigger
		if (exitIntent) {
			log('Exit-intent trigger enabled');
			document.addEventListener('mouseout', function onMouseOut(e) {
				if (e.clientY < 10 && !e.relatedTarget && null === e.toElement) {
					triggerShow();
					document.removeEventListener('mouseout', onMouseOut);
				}
			});
		}

		// Scroll trigger
		if (scrollPct > 0) {
			log('Scroll trigger enabled at', scrollPct, '%');
			window.addEventListener('scroll', function onScroll() {
				var scrollTop   = window.pageYOffset || document.documentElement.scrollTop;
				var docHeight   = document.documentElement.scrollHeight - document.documentElement.clientHeight;
				var scrolled    = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
				if (scrolled >= scrollPct) {
					triggerShow();
					window.removeEventListener('scroll', onScroll);
				}
			});
		}

		// If no delay, no exit intent, no scroll → show immediately
		if (delay <= 0 && !exitIntent && scrollPct <= 0) {
			triggerShow();
		}

		// Close button
		var closeBtn = popup.querySelector('.etchmail-popup__close');
		if (closeBtn) {
			closeBtn.addEventListener('click', function () { hidePopup(popup); });
		}

		// Close on overlay click
		var overlay = popup.querySelector('.etchmail-popup__overlay');
		if (overlay && closeOnOver) {
			overlay.addEventListener('click', function () { hidePopup(popup); });
		}

		// Close on ESC key
		document.addEventListener('keydown', function (e) {
			if (('Escape' === e.key || 27 === e.keyCode) && popup.classList.contains('etchmail-popup--open')) {
				hidePopup(popup);
			}
		});
	}

	// Init popups after DOM ready
	document.addEventListener('DOMContentLoaded', initPopups);

})();
