/**
 * form-validation.js – Validation côté client des formulaires
 */
(function () {
	'use strict';

	function showError(input, msg) {
		input.setAttribute('aria-invalid', 'true');
		var err = input.parentNode.querySelector('.field-error');
		if (err) {
			err.textContent = msg;
		} else {
			var span = document.createElement('span');
			span.className = 'field-error';
			span.setAttribute('role', 'alert');
			span.textContent = msg;
			input.parentNode.appendChild(span);
		}
	}

	function clearError(input) {
		input.removeAttribute('aria-invalid');
		var err = input.parentNode.querySelector('.field-error');
		if (err) err.textContent = '';
	}

	function validateInput(input) {
		var val = input.value.trim();
		if (input.hasAttribute('required') && !val) {
			showError(input, 'Ce champ est obligatoire.');
			return false;
		}
		if (input.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
			showError(input, 'Adresse e-mail invalide.');
			return false;
		}
		if (input.minLength > 0 && val.length < input.minLength) {
			showError(input, 'Minimum ' + input.minLength + ' caracteres requis.');
			return false;
		}
		clearError(input);
		return true;
	}

	function attachValidation(form) {
		if (!form) return;
		var inputs = Array.from(form.querySelectorAll('input, textarea, select'));
		inputs.forEach(function (input) {
			input.addEventListener('blur', function () { validateInput(input); });
			input.addEventListener('input', function () {
				if (input.getAttribute('aria-invalid')) validateInput(input);
			});
		});
		form.addEventListener('submit', function (e) {
			var valid = inputs.map(validateInput).every(Boolean);
			if (!valid) e.preventDefault();
		});
	}

	attachValidation(document.querySelector('#login-form'));
	attachValidation(document.querySelector('#create-account-form'));
	attachValidation(document.querySelector('.contact-form form'));
	attachValidation(document.querySelector('form[action="creer_offre.php"]'));
	attachValidation(document.querySelector('.postuler-form'));

})();
