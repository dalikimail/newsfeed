<div class="row">
	<div class="col">
		<form method="get" action="index.php">
			<h5>Опубликовать новый пост</h5>
			<p>
				<input type="hidden" name="page" value="newpost">
				<input type="text" name="title" placeholder="Заголовок" autocomplete="off">
			</p>
			<p>
				<textarea name="content"></textarea>
			</p>
			<p>
				<input type="text" name="taglist" placeholder="Список тегов" autocomplete="off">
			</p>
			<p><button class="btn btn-outline-info my-2 my-sm-0" type="submit">Опубликовать пост</button></p>
			<p>{{MODEL-INFO}}</p>
		</form>
	</div>
</div>