<div class="row">
	<div class="col">
		<form method="get" action="index.php">
			<h5>Найти новости по тегу (через запятую)</h5>
			<p>
				<input type="hidden" name="page" value="tagged">
				<input class="form-control mr-sm-2" type="text" name="tags" placeholder="Поиск по тегам">
			</p>
			<p><button class="btn btn-outline-info my-2 my-sm-0" type="submit">Поиск</button></p>
			<p>{{MODEL-INFO}}</p>
		</form>
	</div>
</div>