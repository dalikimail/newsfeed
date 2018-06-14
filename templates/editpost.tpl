<div class="row">
	<div class="col">
		<form method="get" action="index.php">
			<h5>Опубликовать новый пост</h5>
			<p>
				<input type="hidden" name="page" value="editpost">
				<input type="hidden" name="id" value="{{POST-ID}}">
				<input type="text" name="title" placeholder="Заголовок" autocomplete="off" value="{{POST-TITLE}}">
			</p>
			<p>
				<textarea name="content">{{POST-CONTENT}}</textarea>
			</p>
			<p>
				<input type="text" name="taglist" placeholder="Список тегов" autocomplete="off" value="{{POST-TAGS}}">
			</p>
			<p><button class="btn btn-outline-info my-2 my-sm-0" type="submit">Отредактировать пост</button></p>
			<p>{{MODEL-INFO}}</p>
		</form>
	</div>
</div>