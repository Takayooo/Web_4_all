/**
 * carousel.js – Carrousel infini des annonces
 */
(function () {
	var track = document.getElementById('carousel-track');
	var prev = document.getElementById('carousel-prev');
	var next = document.getElementById('carousel-next');
	var pauseEl = document.getElementById('carousel-pause');

	if (!track) return;

	var paused = false;
	var timer = null;
	var isAnimating = false;
	var ANIM_MS = 520;

	function getStepWidth() {
		var first = track.children[0];
		if (!first) return 0;
		var gap = parseFloat(window.getComputedStyle(track).columnGap || window.getComputedStyle(track).gap || '0') || 0;
		return first.getBoundingClientRect().width + gap;
	}

	function moveRight() {
		if (isAnimating || track.children.length < 2) return;
		isAnimating = true;
		var step = getStepWidth();
		var last = track.lastElementChild;
		track.style.transition = 'none';
		track.insertBefore(last, track.firstElementChild);
		track.style.transform = 'translateX(' + (-step) + 'px)';
		track.offsetHeight;
		track.style.transition = 'transform ' + ANIM_MS + 'ms ease';
		track.style.transform = 'translateX(0px)';
		window.setTimeout(function () {
			track.style.transition = 'none';
			track.style.transform = 'translateX(0px)';
			isAnimating = false;
		}, ANIM_MS + 30);
	}

	function moveLeft() {
		if (isAnimating || track.children.length < 2) return;
		isAnimating = true;
		var step = getStepWidth();
		track.style.transition = 'transform ' + ANIM_MS + 'ms ease';
		track.style.transform = 'translateX(' + (-step) + 'px)';
		window.setTimeout(function () {
			track.style.transition = 'none';
			track.appendChild(track.firstElementChild);
			track.style.transform = 'translateX(0px)';
			isAnimating = false;
		}, ANIM_MS + 30);
	}

	function forward() {
		/* Avance d'une annonce: la carte suivante entre depuis la droite */
		moveLeft();
	}

	function startTimer() {
		clearInterval(timer);
		timer = window.setInterval(function () {
			if (!paused) forward();
		}, 2600);
	}

	if (prev) prev.addEventListener('click', function () {
		moveRight();
		if (!paused) startTimer();
	});

	if (next) next.addEventListener('click', function () {
		forward();
		if (!paused) startTimer();
	});

	if (pauseEl) {
		pauseEl.addEventListener('click', function () {
			paused = !paused;
			pauseEl.textContent = paused ? '\u25b6 Reprendre' : '\u23f8 Pause';
			if (paused) {
				clearInterval(timer);
			} else {
				startTimer();
			}
		});
	}

	window.addEventListener('resize', function () {
		track.style.transition = 'none';
		track.style.transform = 'translateX(0px)';
	});

	startTimer();
})();
