/**
 * Library to animate APNG sprites.
 *
 * @author Robin van Baalen
 * @version 1.0
 * 
 * CHANGELOG
 * 	1.0
 * 		Initial release
 * 	1.0.1
 * 		Store initialized APNG object on target element
 * 
 */
var Bili = Bili || {};

Bili.APNG = (function ($) {

	function APNG (options) {
		this.defaults = {
			elementId: null,
			speed: 7,
			totalFrames: 37,
			frameWidth: 220,

			imageTimeout: false,
			frameIndex: 0,
			Xpos: 0,
			preloaderTimeout: false,
			secondsBetweenFrames: 0
		};

		this.settings = $.extend(this.defaults, options);

		if (this.settings.elementId === null || typeof this.settings.imageSrc === "undefined") {
			throw new Error("Failed to initialize APNG animation. Required settings not set.", 1);
		}

		return this.init();
	};

	APNG.prototype.init = function () {
		var self = this;

		clearTimeout(this.settings.imageTimeout);

		this.settings.imageTimeout = 0;

		var objImage = new Image();
		objImage.onload = function () {
			self.settings.imageTimeout = setTimeout(function () {
				self.start();
			}, 0);
		};

		objImage.onerror = function () {
			alert("Could not load sprite.");
		};

		objImage.src = this.settings.imageSrc;
		
		$("#" + self.defaults.elementId).data("bili.apng", this);
	};

	APNG.prototype.start = function () {
		var s = this.settings;
		var loaderImage = document.getElementById(s.elementId);
		var self = this;

		loaderImage.style.backgroundImage = "url(" + s.imageSrc + ")";
		loaderImage.style.width = s.width + "px";
		loaderImage.style.height = s.height + "px";
		
		var FPS = Math.round(100 / s.speed);
		s.secondsBetweenFrames = 1 / FPS;
		
		s.preloaderTimeout = setTimeout(function () {
			self.continueAnimation();
		}, s.secondsBetweenFrames / 1000);
	};

	APNG.prototype.continueAnimation = function () {
		var self = this;
		var s = this.settings;
		var loaderImage = document.getElementById(s.elementId);
		
		if (loaderImage === null || loaderImage.length == 0) {
			// Stop animation when loader image is not available.
			this.stop();
		}

		s.Xpos += s.frameWidth;
		//increase the index so we know which frame of our animation we are currently on
		s.frameIndex += 1;
		 
		if (s.frameIndex >= s.totalFrames) {
			s.Xpos = 0;
			s.frameIndex = 0;
		}
		
		if (loaderImage) {
			loaderImage.style.backgroundPosition = (-s.Xpos) + 'px 0';
		}
		
		s.preloaderTimeout = setTimeout(function () {
			self.continueAnimation();
		}, s.secondsBetweenFrames * 1000);
	};

	APNG.prototype.stop = function () {
		var s = this.settings;

		clearTimeout(s.preloaderTimeout);
		s.preloaderTimeout = false;
	};

	return APNG;

})(jQuery);