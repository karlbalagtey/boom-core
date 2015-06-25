function boomChunk(page_id, type, slotname) {
	this.page_id = page_id;
	this.slotname = slotname;
	this.type = type;
	this.urlPrefix = '/cms/chunk/';

	/**
	 * To remove a chunk save it with no data.
	 *
	 * @param string template
	 * @returns {jqXHR}
	 */
	boomChunk.prototype.delete = function(template) {
		return this.save([]);
	};

	boomChunk.prototype.save = function(data) {
		data.slotname = this.slotname;
		data.type = this.type;

		return $.post(this.urlPrefix + 'save/' + this.page_id, data);
	};
}