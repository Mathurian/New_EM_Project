<h2>Create Categories from Template: <?= htmlspecialchars($category['name']) ?></h2>
<p><a href="/categories/<?= urlencode($category['id']) ?>/subcategories">Back</a></p>

<?php if (empty($templates)): ?>
	<p>No templates available. <a href="/admin/templates/new">Create a template first</a></p>
<?php else: ?>
	<?php foreach ($templates as $template): ?>
		<div style="border: 1px solid #ccc; padding: 15px; margin: 10px 0;">
			<h3><?= htmlspecialchars($template['name']) ?></h3>
			<?php if (!empty($template['description'])): ?>
				<p><em><?= htmlspecialchars($template['description']) ?></em></p>
			<?php endif; ?>
			
			<?php 
			$subcategoryNames = [];
			if (!empty($template['subcategory_names'])) {
				$subcategoryNames = json_decode($template['subcategory_names'], true) ?: [];
			}
			?>
			
			<?php if (!empty($subcategoryNames)): ?>
				<p><strong>Categories:</strong></p>
				<ul>
					<?php foreach ($subcategoryNames as $name): ?>
						<li><?= htmlspecialchars($name) ?></li>
					<?php endforeach; ?>
				</ul>
				
				<form method="post" action="/categories/<?= urlencode($category['id']) ?>/subcategories/from-template">
					<input type="hidden" name="template_id" value="<?= htmlspecialchars($template['id']) ?>" />
					<label>Score Cap (optional)
						<input type="number" name="score_cap" min="0" step="0.1" />
					</label>
					<button type="submit">Create All Categories from This Template</button>
				</form>
			<?php else: ?>
				<p><em>No categories defined in this template.</em></p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
