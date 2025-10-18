<h2>Judges</h2>
<p><a href="/admin">Back</a></p>
<form method="post" action="/admin/judges">
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
	<label><input type="checkbox" name="is_head_judge" value="1" /> Head Judge</label>
	<label><input type="checkbox" name="create_user" value="1" /> Create login for this judge</label>
	<label>Password <input type="password" name="password" /></label>
	<button type="submit">Add Judge</button>
</form>

<table>
    <tr><th>Name</th><th>Email</th><th>Gender</th><th>Head Judge</th><th></th></tr>
	<?php foreach ($rows as $r): ?>
		<tr>
			<td><?= htmlspecialchars($r['name']) ?></td>
			<td><?= htmlspecialchars($r['email'] ?? '') ?></td>
			<td><?= htmlspecialchars($r['gender'] ?? '') ?></td>
            <td><?= !empty($r['is_head_judge']) ? 'Yes' : 'No' ?></td>
			<td>
                <form method="post" action="/admin/judges/<?= htmlspecialchars($r['id']) ?>/update" style="display:inline-block; margin-right:8px;">
                    <input type="hidden" name="name" value="<?= htmlspecialchars($r['name']) ?>" />
                    <input type="hidden" name="email" value="<?= htmlspecialchars($r['email'] ?? '') ?>" />
                    <input type="hidden" name="gender" value="<?= htmlspecialchars($r['gender'] ?? '') ?>" />
                    <input type="hidden" name="is_head_judge" value="<?= !empty($r['is_head_judge']) ? '0' : '1' ?>" />
                    <button type="submit"><?= !empty($r['is_head_judge']) ? 'Unset Head' : 'Set Head' ?></button>
                </form>
                <form method="post" action="/admin/judges/delete" onsubmit="return confirm('Delete judge?')" style="display:inline-block;">
					<input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>" />
					<button type="submit">Delete</button>
				</form>
			</td>
		</tr>
	<?php endforeach; ?>
</table>


