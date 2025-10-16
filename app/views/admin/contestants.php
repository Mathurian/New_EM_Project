<h2>Contestants</h2>
<p><a href="/admin">Back</a></p>
<form method="post" action="/admin/contestants">
	<label>Name <input name="name" required /></label>
	<label>Email <input type="email" name="email" /></label>
	<label>Gender
		<select name="gender">
			<option value="">—</option>
			<option>Female</option>
			<option>Male</option>
			<option>Non-binary</option>
			<option>Prefer not to say</option>
		</select>
	</label>
	<button type="submit">Add Contestant</button>
</form>

<table>
	<tr><th>Name</th><th>Email</th><th>Gender</th><th></th></tr>
	<?php foreach ($rows as $r): ?>
		<tr>
			<td><?= htmlspecialchars($r['name']) ?></td>
			<td><?= htmlspecialchars($r['email'] ?? '') ?></td>
			<td><?= htmlspecialchars($r['gender'] ?? '') ?></td>
			<td>
				<form method="post" action="/admin/contestants/delete" onsubmit="return confirm('Delete contestant?')">
					<input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>" />
					<button type="submit">Delete</button>
				</form>
			</td>
		</tr>
	<?php endforeach; ?>
</table>


