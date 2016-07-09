/* https://gist.github.com/mistic100/37c95fab77b5626c5623 */

/**
 * $(source).simulateDragDrop({
 *      dropTarget: $(destination),
 *      start: function() {},
 *      done: function() {},
 *      dragStartDelay: 0,
 *      dropDelay: 0,
 *      dragEndDelay: 0
 * })
 */
(function($) {
  $.fn.simulateDragDrop = function(options) {
    return this.each(function() {
      new $.simulateDragDrop(this, options);
    });
  };
  
  $.simulateDragDrop = function(elem, options) {
    var that = this;
    
    this.options = options;
    this.elem = elem;
    
    if (this.options.start) {
      this.options.start.call(this.elem);
    }
    
    setTimeout(function() {
      that.dragstart();
    }, this.options.dragStartDelay || 0);
  };
  
  $.extend($.simulateDragDrop.prototype, {
    dragstart: function() {
      var that = this;
      
      var event = this.createEvent('dragstart');
      this.dispatchEvent(this.elem, 'dragstart', event);
      
      setTimeout(function() {
        that.drop(event);
      }, this.options.dropDelay || 0);
    },
    drop: function(event) {
      var that = this;
      
      var dropEvent = this.createEvent('drop');
      dropEvent.dataTransfer = event.dataTransfer;
      this.dispatchEvent($(this.options.dropTarget)[0], 'drop', dropEvent);
      
      setTimeout(function() {
        that.dragend(event);
      }, this.options.dragEndDelay || 0);
    },
    dragend: function(event) {
      var dragEndEvent = this.createEvent('dragend');
      dragEndEvent.dataTransfer = event.dataTransfer;
      this.dispatchEvent(this.elem, 'dragend', dragEndEvent);
      
      if (this.options.done) {
        this.options.done.call(this.elem);
      }
    },
    createEvent: function(type) {
      var event = document.createEvent('CustomEvent');
      event.initCustomEvent(type, true, true, null);
      event.dataTransfer = {
        data: {},
        setData: function(type, val) {
          this.data[type] = val;
        },
        getData: function(type) {
          return this.data[type];
        }
      };
      return event;
    },
    dispatchEvent: function(elem, type, event) {
      if (elem.dispatchEvent) {
        elem.dispatchEvent(event);
      }
      else if (elem.fireEvent) {
        elem.fireEvent('on' + type, event);
      }
    }
  });
})(jQuery);