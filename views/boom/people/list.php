<div class="boom-tabs">
	<div class="ui-helper-right" style="padding: .4em .6em 0 0;">
		<?
			if (isset($pagination)):
				echo "<div class='ui-helper-left'>", $pagination, "</div>";
			endif;
		?>

		<select id="boom-tagmanager-order-select" class="ui-helper-left" style="width: 98px">
			<optgroup label="Direction">
				<option value="asc" <? if ($order == 'asc') echo "selected='selected'"; ?>>A - Z</option>
				<option value="desc" <? if ($order == 'desc') echo "selected='selected'"; ?>>Z - A</option>
			</optgroup>
		</select>
		</select>
	</div>
	<ul>
		<li><a href="#b-items-view-thumbs" class="boom-button" data-icon="ui-icon-boom-thumbnails">Thumbnails</a></li>
		<li><a href="#b-items-view-list" class="boom-button" data-icon="ui-icon-boom-list">List</a></li>
	</ul>

	<div id="b-items-view-list" class="ui-helper-left">
		<table>
			<? foreach ($people as $person): ?>
				<tr>
					<td width="10" class="ui-helper-reset">
						<input type="checkbox" class="b-items-select-checkbox ui-helper-reset" id="person-list-<?=$person->id?>" />
					</td>
					<td>
						<a href="#person/<?=$person->id?>"><img src="<?= URL::gravatar($person->emailaddress, array('s' => 16)) ?>" /> <?= $person->name ?></a>
					</td>
					<td>
						<span class='tags'>
							<?
								foreach($person->groups->find_all() as $group):
									echo "<a rel=​'ajax' name='#tag/", $group->pk(), "' href='#tag/", $group->pk(), "'>", $group->name, " &raquo;</a>";
								endforeach
							?>​
						</span>
					</td>
				</tr>
			<? endforeach ?>
		</table>
	</div>

	<div id="b-items-view-thumbs" class="b-items-thumbs ui-helper-left">
		<? foreach ($people as $person): ?>
			<div class="boom-tagmanager-people b-items-thumbs ui-helper-clearfix">
				<div class="thumb ui-corner-all">

					<input type="checkbox" class="b-items-select-checkbox ui-helper-reset" id="person-thumb-<?=$person->id?>" />

					<a href="#person/<?=$person->id?>">
						<img src="<?= URL::gravatar($person->email, array('s' => 80), Request::$initial->secure()) ?>" />
						<span class="caption"><?=$person->name ?></span>
						<span class="caption-overlay"></span>
					</a>
				</div>
			</div>
		<? endforeach; ?>
	</div>

	<div style="padding: .5em 0 .5em .5em;border-color:#ccc;border-width:1px 0 0 0;" class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
		<div class="ui-helper-right" style="margin: .5em .5em 0 0">
			Total people: <?= Num::format($total, 0) ?>
		</div>
	</div>
</div>
