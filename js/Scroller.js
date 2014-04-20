/**
 * The BiliScroller plugin
 * 
 * This plugin can be used to automatically wrap long portions of text into a given viewport.
 * When a user touches the edges of the viewport, the text will scroll in the given direction.
 * 
 * REQUIREMENTS
 *  - This is created and tested with the latest jQuery but will possibly work on older jQuery versions.
 *  
 * VERSION
 * 	- v1.0
 * 
 * AUTHOR
 *  - Robin van Baalen <robin@neverwoods.com>
 *  
 * USAGE
 *  - The options are optional
 *  
 *  When the DOM is ready, call:
 *  
 *    var scrollerApi = new BiliScroller({
 *        speed: 10,
 *        viewport: 200,
 *        overlayWidth: 30
 *    });
 *  
 * REQUIRED CSS
 * 
 *    .bili-scroller {
 *        width: auto;
 *        position: relative;
 *        overflow: hidden;
 *        height: 20px;
 *    }
 *    
 *    .bili-scroller-wrap {
 *        width: auto;
 *        overflow: visible;
 *        position: absolute;
 *    }
 */
var BiliScroller = (function (window, $) {

    function BiliScroller (opts) {
        this.container = $(".bili-scroller");
        this.wrap = $(".bili-scroller-wrap", this.container);
        this.wrapperWidth = 0;
        
        this.defaults = {
            viewport: 100, // px
            speed: 100,
            overlayWidth: "15" // %
        }
        
        if (opts) {        
            this.defaults = $.extend(this.defaults, opts);
        }
        
        return this.init();
    };
    
    BiliScroller.prototype.init = function () {
        var self = this;
        
        this.wrapperWidth = this.wrap.outerWidth(true);
        this.wrap.css("width", this.wrapperWidth);
        
        this.container.css("width", this.defaults.viewport);
        
        this.appendLeftOverlay();
        this.appendRightOverlay();
        
        this.container.on("mouseenter", ".biliscroller-overlay", function () {
            self.scroll($(this));
        });
        this.container.on("mouseleave", ".biliscroller-overlay", function () {
            self.stopScroll();
        });
    };
    
    BiliScroller.prototype.scroll = function ($element) {
        var self = this;
        var direction = $element.hasClass("left") ? "left" : "right";

        this.interval = setInterval(function () {
            var offset = parseInt(self.wrap.css("left")) || 0;
            var newOffset = (direction == "left") ? (offset + 10) : (offset - 10);
            
            if (direction == "left" && offset >= 0) {
                return;
            }
            
            if (direction == "right" && offset <= (self.container.width() - self.wrap.width())) {
                return;
            }
            
            self.wrap.stop().animate({"left": newOffset}, {"duration": self.defaults.speed});
        }, self.defaults.speed);
    };
    
    BiliScroller.prototype.stopScroll = function () {
        clearInterval(this.interval);
    };
    
    BiliScroller.prototype.appendLeftOverlay = function() {
        this.appendOverlay("left");
    };
    
    BiliScroller.prototype.appendRightOverlay = function() {
        this.appendOverlay("right");
    };
    
    BiliScroller.prototype.appendOverlay = function(position) {
        var self = this;
        var $overlay = $("<div>");
        var style = {
            "height": self.container.height(),
            "position": "absolute",
            "width": self.defaults.overlayWidth + "%"
        }
        
        if (position == "right") {
            style.right = 0;
        }
        if (position == "left") {
            style.left = 0;
        }
        
        $overlay
            .addClass("biliscroller-overlay " + position)
            .css(style);
        
        this.container.append($overlay);
    };
    
    return BiliScroller;
    
})(window, jQuery);