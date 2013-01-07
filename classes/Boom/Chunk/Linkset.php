<?php defined('SYSPATH') OR die('No direct script access.');
/**
* @package	BoomCMS
* @category	Chunks
* @author	Rob Taylor
* @copyright	Hoop Associates
*
*/
class Boom_Chunk_Linkset extends Chunk
{
	protected $_default_template = 'quicklinks';

	protected $_type = 'linkset';

	protected function _show()
	{
		return View::factory("site/slots/linkset/$this->_template", array(
			'title'		=>	$this->_chunk->title,
			'links'	=>	$this->_chunk->links(),
		));
	}

	public function _show_default()
	{
		return View::factory("site/slots/default/linkset/$this->_template");
	}

	public function has_content()
	{
		return count($this->_chunk->links()) > 0;
	}
}