function boomDialog(options) {
	this.deferred = new $.Deferred().always(function() {
		$(top.window).trigger('boom:dialog:close');
	});

	this.options = $.extend({
		width: 'auto',
		cancelButton : true,
		closeButton : true,
		autoOpen: true,
		modal: true,
		resizable: false,
		draggable: false,
		closeOnEscape: false,
		buttons : [],
		dialogClass : 'b-dialog',
		boomDialog: this
	}, options);

	boomDialog.prototype.always = function(callback) {
		this.deferred.always(callback);

		return this;
	};

 	boomDialog.prototype.cancelButton = {
		text: 'Cancel',
		class: 'b-button-cancel',
		click: function() {
			var boomDialog = $(this).dialog('option', 'boomDialog');
			boomDialog.cancel();
		}
	};

	boomDialog.prototype.cancel = function() {
		this.deferred.rejectWith(this.dialog);

		this.cleanup();
	};

	boomDialog.prototype.cleanup = function() {
		if (this.contents) {
			this.contents.remove();
			this.contents = null;
		}
	};

	boomDialog.prototype.closeButton = {
		text: 'Okay',
		class: 'b-button-close',
		click: function() {
			var boomDialog = $(this).dialog('option', 'boomDialog');
			boomDialog.close();
		}
	};

	boomDialog.prototype.close = function() {
		this.deferred.resolveWith(this.dialog);
		
		this.cleanup();
	};

	boomDialog.prototype.done = function(callback) {
		this.deferred.done(callback);

		return this;
	};

	boomDialog.prototype.fail = function(callback) {
		this.deferred.fail(callback);

		return this;
	};

	boomDialog.prototype.init = function() {
		var boomDialog = this;

		$(top.window).trigger('boom:dialog:open');

		this
			.contents
			.dialog(this.options)
			.ui();

		$(document).on('keydown', function(e) {
			if (e.which === $.ui.keyCode.ESCAPE) {
				boomDialog.cancel();
				e.stopPropagation();
			}
		});
	};

	boomDialog.prototype.open = function() {
		var self = this,
			$div = $('<div></div>');

		if (this.options.id) {
			$div.attr('id', this.options.id);
		}

		this.contents = $div.appendTo($(document).contents().find('body'));

		this.options.closeButton && this.options.buttons.push(this.closeButton);
		this.options.cancelButton && this.options.buttons.push(this.cancelButton);

		if (this.options.url && this.options.url.length) {
			if (this.contents.hasClass('ui-dialog-content')) {
				this.contents.dialog('open');
			} else {
				setTimeout(function() {
					self.contents.load(self.options.url, function(response, status, xhr) {
						if (xhr.status === 200) {
							self.init();

							if ($.isFunction(self.options.onLoad)) {
								self.options.onLoad.apply(self.dialog);
							}
						} else {
							self.deferred.reject(response, xhr.status);
						} 
					});
				}, 100);
			}

		} else if (this.options.msg.length) {
			setTimeout(function() {
				self.contents.html(self.options.msg);
				self.init();

				if ($.isFunction(self.options.onLoad)) {
					self.options.onLoad(self);
				}
			}, 100);
		}
	};

	this.open();
};