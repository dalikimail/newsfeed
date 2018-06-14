<div class="row">
	<div class="col">
		<form method="get" action="index.php">
			<h5>Зарегистрировать нового пользователя</h5>
			<p>
				<input type="hidden" name="page" value="registration">
				Имя: <input type="text" name="name" placeholder="Имя" autocomplete="off">
				Пароль: <input type="password" name="password" placeholder="Пароль" autocomplete="off">
				Адрес: <input type="text" name="email" placeholder="Электронный адрес" autocomplete="off">
			</p>
			<p><button class="btn btn-outline-info my-2 my-sm-0" type="submit">Зарегистрироваться</button></p>
			<p>{{MODEL-INFO}}</p>
		</form>
	</div>
</div>