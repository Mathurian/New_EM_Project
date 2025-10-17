<h2>New Person</h2>
<p><a href="/people">Back</a></p>
<form method="post" action="/contestants" enctype="multipart/form-data">
	<fieldset>
		<legend>Add Contestant</legend>
		<label>Name <input name="name" required /></label>
		<label>Email <input name="email" /></label>
		<label>Gender (optional) <input name="gender" placeholder="Enter custom gender or leave blank" /></label>
		<label>Pronouns (optional) <input name="pronouns" placeholder="e.g., they/them, she/her, he/him, ze/zir, or custom" /></label>
		<label>Contestant Number <input type="number" name="contestant_number" min="1" /></label>
		<label>Bio <textarea name="bio" rows="4" cols="50" placeholder="Tell us about yourself..."></textarea></label>
		<label>Profile Image <input type="file" name="image" accept="image/*" /></label>
		<button type="submit">Add Contestant</button>
	</fieldset>
</form>
<form method="post" action="/judges" enctype="multipart/form-data">
	<fieldset>
		<legend>Add Judge</legend>
		<label>Name <input name="name" required /></label>
		<label>Email <input name="email" /></label>
		<label>Gender (optional) <input name="gender" placeholder="Enter custom gender or leave blank" /></label>
		<label>Pronouns (optional) <input name="pronouns" placeholder="e.g., they/them, she/her, he/him, ze/zir, or custom" /></label>
		<label>Bio <textarea name="bio" rows="4" cols="50" placeholder="Tell us about yourself..."></textarea></label>
		<label>Profile Image <input type="file" name="image" accept="image/*" /></label>
		<button type="submit">Add Judge</button>
	</fieldset>
</form>


