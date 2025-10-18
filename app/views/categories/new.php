<h2>New Category</h2>
<p><a href="/contests/<?= urlencode($contest['id']) ?>/categories">Back</a></p>
<form method="post" action="/contests/<?= urlencode($contest['id']) ?>/categories">
	<label>Name <input name="name" required /></label>
	<button type="submit">Create</button>
</form>


