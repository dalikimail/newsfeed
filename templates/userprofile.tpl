<div class="container">
	<h5>Профиль пользователя {{USER-NAME}}</h5>
	<p>E-mail: {{USER-EMAIL}}</p>
	<p>Количество постов: <a href="index.php?page=userpost&amp;id={{USER-ID}}">{{USER-POSTCOUNT}}</a></p>
</div>
<div class="container">
	<h5>Действия пользователя</h5>
	<p>
		<a class="btn btn-success" href="index.php?page=newpost">Новый пост</a>
		<a class="btn btn-primary" href="index.php?page=userpost&amp;id={{USER-ID}}">Мои посты</a>
		<a class="btn btn-warning" href="index.php?page=logout">Выйти</a>
	</p>
</div>