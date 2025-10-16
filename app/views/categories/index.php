<h2>Categories for <?= htmlspecialchars($contest['name']) ?></h2>
<p><a href="/contests">Back</a></p>
<a href="/contests/<?= urlencode($contest['id']) ?>/categories/new">+ New Category</a>
<table>
	<tr><th>Name</th><th>Subcategories</th></tr>
	<?php foreach ($categories as $cat): ?>
		<tr>
			<td><?= htmlspecialchars($cat['name']) ?></td>
			<td><a href="/categories/<?= urlencode($cat['id']) ?>/subcategories">Manage</a></td>
		</tr>
	<?php endforeach; ?>
</table>


