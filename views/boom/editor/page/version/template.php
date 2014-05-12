<form id="b-page-version-template" name="pageversion-template">
	<?= Form::hidden('csrf', Security::token()) ?>
	<div class="b-template">
		<p>Changing the template of the page will change how the content of the page is visually displayed.</p>
		<p>Although some content may not be visible with certain templates, the content will remain with the page and become visible if the template is changed back.</p>
	
		<label for="template_id">Template:</label>
		<select id='template' name='template_id'>
			<? foreach ($templates as $t): ?>
				<option value='<?= $t->id ?>' data-description='<?= $tshortUrl ?>' data-count='<?= $t->page_count() ?>'<? if ($template_id == $t->id): ?> selected='selected'<? endif ?>><?= $t->name ?></option>
			<? endforeach ?>
		</select>

		<div id='description'><strong>Template description:</strong><p></p></div>
		<div id='count'><strong>Pages using this template:</strong><p></p></div>
	</div>
</form>