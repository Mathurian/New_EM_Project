<h2>Contests for <?= htmlspecialchars($contest['name']) ?></h2>
<p><a href="/contests">Back</a></p>
<a href="/contests/<?= urlencode($contest['id']) ?>/categories/new">+ New Contest</a>
<table>
	<tr><th>Name</th><th>Categories</th></tr>
	<?php foreach ($categories as $cat): ?>
		<tr>
			<td><?= htmlspecialchars($cat['name']) ?></td>
			<td><a href="/categories/<?= urlencode($cat['id']) ?>/subcategories">Manage</a></td>
		</tr>
	<?php endforeach; ?>
</table>


