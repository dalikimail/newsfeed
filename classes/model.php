<?php
/**
 *  Абстрактное описание класса
 */
abstract class AbstractModel
{
	protected $_connection;
	protected $_modelName;
	protected $_parameters;
	protected $_user;
	
	/**
	 *  @brief Модель получает данные для передачи их в ViewConstructor
	 *  
	 *  @param [in] $name string
	 *  @param [in] $parameters array
	 *  @return nothing / Exception
	 *  
	 *  @details В модели сохраняются имя модели, параметры, переданные ей, проверяется наличие сессии у пользователя
	 */
	function __construct( $name, $parameters )
	{
		try 
		{
			$this->_connection = new Database(StaticAccess::DBHOST, StaticAccess::DBUSER, StaticAccess::DBPASSWORD, StaticAccess::DBBASE);
		}
		catch ( Exception $e )
		{
			echo "Выброшено исключение: {$e->getMessage()}";
			die();
		}
		$this->_modelName = $name;
		$this->_parameters = $parameters;
		$this->_user = new User( $this->_connection );
		
		$cookie_id = array_key_exists( 'session_newsfeed_id', $_COOKIE ) ? $_COOKIE['session_newsfeed_id'] : 0;
		$cookie_session = array_key_exists( "session_newsfeed_{$cookie_id}", $_COOKIE ) ? $_COOKIE["session_newsfeed_{$cookie_id}"] : '0';
		$this->_user->checkSession( $cookie_session );
	}
	
	/**
	 *  @brief Геттеры
	 *  
	 *  @return mixed
	 */
	public function name() { return $this->_modelName; }
	public function action() { return $this->_parameters['action']; }
	public function value() { return $this->_parameters['value']; }
	public function user() { return $this->_user; }
	
	/**
	 *  @brief Подсчет пользователей на сайте
	 *  
	 *  @return int
	 *  
	 *  @details Используется в content.tpl
	 */
	public function userCount() 
	{
		$query = "SELECT COUNT(user_id) as cnt FROM users";
		$result = $this->_connection->query( $query );
		
		if ( $fetch = $this->_connection->fetch( $result ) )
		{
			return $fetch['cnt'];
		}
		else
		{
			return 0;
		}
	}
	
	/**
	 *  @brief Подсчет постов на сайте
	 *  
	 *  @return int
	 *  
	 *  @details Используется в content.tpl
	 */
	public function postCount() 
	{
		$query = "SELECT COUNT(post_id) as cnt FROM posts";
		$result = $this->_connection->query( $query );
		
		if ( $fetch = $this->_connection->fetch( $result ) )
		{
			return $fetch['cnt'];
		}
		else
		{
			return 0;
		}
	}

	/**
	 *  @brief Подсчет тегов на сайте
	 *  
	 *  @return int
	 *  
	 *  @details Используется в content.tpl
	 */
	public function tagCount() 
	{
		$query = "SELECT COUNT(tag_id) as cnt FROM tags";
		$result = $this->_connection->query( $query );
		
		if ( $fetch = $this->_connection->fetch( $result ) )
		{
			return $fetch['cnt'];
		}
		else
		{
			return 0;
		}
	}
}

/**
 *  Конкретная модель для работы с наличием авторизованного пользователя
 */
class ProfileModel extends AbstractModel
{
	private $_userName;
	private $_userId;
	private $_userEmail;
	private $_userPostCount;
	
	private $_postTitle;
	private $_postContent;
	private $_postTags;
	private $_postId;
	
	private $_modelInfo;
	
	/**
	 *  @brief Наполнение параметров модели
	 *  
	 *  @param [in] $name string
	 *  @param [in] $parameters array
	 *  
	 *  @details Проверяется авторизация пользователя, прежде чем выдается доступ к определенным действиям
	 */
	function __construct( $name, $parameters )
	{
		parent::__construct( $name, $parameters );
		$this->checkAuthorization();
		switch ($this->_parameters['action'])
		{
			case 'userprofile': $this->userProfileModel(); break;
			case 'admin': $this->adminModel(); break;
			case 'newpost': $this->newPostModel(); break;
			case 'editpost': $this->editPostModel(); break;
			case 'deletepost': $this->deletePostModel(); break;
		}
	}
	
	/**
	 *  @brief Геттеры
	 *  
	 *  @return mixed
	 */
	public function userName() { return $this->_userName; }
	public function userId() { return $this->_userId; }
	public function userEmail() { return $this->_userEmail; }
	public function userPostCount() { return $this->_userPostCount; }
	public function postTitle() { return $this->_postTitle; }
	public function postId() { return $this->_postId; }
	public function postContent() { return $this->_postContent; }
	public function postTags() { return $this->_postTags; }
	public function modelInfo() { return $this->_modelInfo; }
	
	/**
	 *  @brief Проверка авторизации
	 *  
	 *  @details Если пользователь не авторизован, то дальнейшая загрузка страницы прекращается
	 */
	private function checkAuthorization()
	{
		if ( !$this->user()->isLogged() )
		{
			echo "Доступ к запрашиваемой странице запрещен!";
			die();
		}
	}
	
	/**
	 *  @brief Удаление поста
	 *  
	 *  @return redirect / Exception
	 *  
	 *  @details Только автор поста или администратор (level > 0) могут удалять пост
	 */
	private function deletePostModel()
	{
		if ( (int)$this->_parameters['value']['postId'] > 0 )
		{
			$postId = (int)$this->_parameters['value']['postId'];
			
			try
			{
				$postData = $this->loadPost( $postId );
				
				if ( $postData['author_id'] == $this->user()->id() || $this->user()->level() > 0 )
				{
					$this->deletePost( $postId );
					header("Location: index.php?page=newsfeed");
				}
				else 
				{
					$this->_modelInfo = 'Недостаточно прав для доступа!';
				}	
			}
			catch ( Exception $e )
			{
				$this->_modelInfo = $e->getMessage();
			}
		}
		else 
		{
			$this->_modelInfo = 'Не указан id поста для удаления!';
		}
	}
	
	/**
	 *  @brief Редактирование поста
	 *  
	 *  @return nothing / Exception
	 *  
	 *  @details Пост может редактировать автор поста, либо администратор (level > 0). После редактирования изменяется время поста. 
	 */
	private function editPostModel()
	{
		if ( (int)$this->_parameters['value']['postId'] > 0 )
		{
			$postId = (int)$this->_parameters['value']['postId'];
			
			try
			{
				$postData = $this->loadPost( $postId );
				
				if ( $postData['author_id'] == $this->user()->id() || $this->user()->level() > 0 )
				{
					if ( mb_strlen($this->_parameters['value']['title']) > 0 && mb_strlen($this->_parameters['value']['content']) > 0 )
					{
						try 
						{
							$this->editPost( $this->_parameters['value']['title'], $this->_parameters['value']['content'], $this->_parameters['value']['tagList'], $postId );
						}
						catch ( Exception $e )
						{
							$this->_modelInfo = $e->getMessage();
						}	
					}
					else 
					{
						$this->_postId = $postId;
						$this->_postTitle = $postData['title'];
						$this->_postContent = $postData['content'];
						$this->_postTags = $this->tagsToString( $this->loadTags( $postData['post_id'] ) );
						$this->_modelInfo = '';
					}
				}
				else 
				{
					$this->_modelInfo = 'Недостаточно прав для доступа!';
				}	
			}
			catch ( Exception $e )
			{
				$this->_modelInfo = $e->getMessage();
			}
		}
		else 
		{
			$this->_modelInfo = 'Не указан id поста для редактирования!';
		}
	}
		
	
	/**
	 *  @brief Удалить пост
	 *  
	 *  @param [in] $postId int
	 *  
	 *  @details Пост и связи с тегами удаляются без проверки
	 */
	private function deletePost( $postId )
	{
		$query = "DELETE FROM tagged_posts WHERE post_id = {$postId}";
		$this->_connection->query( $query );
		
		$query = "DELETE FROM posts WHERE post_id = {$postId} LIMIT 1";
		$this->_connection->query( $query );
	}
	
	/**
	 *  @brief Отредактировать пост
	 *  
	 *  @param [in] $title string
	 *  @param [in] $content string
	 *  @param [in] $tagList string
	 *  @param [in] $postId int
	 *  @return redirect / Exception
	 *  
	 *  @details Пост редактируется, либо выбрасываются исключения.
	 */
	private function editPost( $title, $content, $tagList, $postId )
	{
		$postTitle = $this->sanitize( $title );
		$postContent = $this->sanitize( $content );
		$postTime = time();
		
		if ( mb_strlen($postTitle) > 0 && mb_strlen($postContent) > 0 )
		{
			$query = "UPDATE posts SET title = '{$postTitle}', content = '{$postContent}', date = {$postTime} WHERE post_id = {$postId} LIMIT 1";
			$result = $this->_connection->query( $query );
			
			if ( $result )
			{
				$this->assignTags( $tagList, $postId );
				header("Location: index.php?page=newsfeed");
			}
			else 
			{
				throw new Exception('Непредвиденная ошибка при редактировании поста!');
			}
		}
		else
		{
			throw new Exception('Недопустимые символы в названии поста или в его описании!');
		}
	}
	
	/**
	 *  @brief Склеить переданный набор тегов в строку
	 *  
	 *  @param [in] $tags array
	 *  @return string
	 */
	private function tagsToString( $tags )
	{
		return implode(', ', $tags);
	}
	
	/**
	 *  @brief Загрузить теги для поста
	 *  
	 *  @param [in] $postId int
	 *  @return array
	 *  
	 *  @details Если тегов нет, то вернется пустой массив
	 */
	private function loadTags( $postId )
	{
		$tags = [];
		
		$query = "SELECT name FROM tagged_posts TP JOIN tags T ON TP.tag_id = T.tag_id WHERE TP.post_id = {$postId}";
		$result = $this->_connection->query( $query );
		
		while ( $fetch = $this->_connection->fetch( $result ) )
		{
			$tags[] = $fetch['name'];
		}

		return $tags;
	}
	
	/**
	 *  @brief Загрузить пост
	 *  
	 *  @param [in] $postId int
	 *  @return array / Exception
	 */
	private function loadPost( $postId )
	{
		$query = "SELECT * FROM posts WHERE post_id = {$postId} LIMIT 1";
		$result = $this->_connection->query( $query );
		
		if ($fetch = $this->_connection->fetch( $result ) )
		{
			return $fetch;
		}
		else throw new Exception('Не удалось загрузить пост!');
	}
	
	/**
	 *  @brief Установка данных для просмотра профиля
	 */
	private function userProfileModel()
	{
		$this->_userName = $this->user()->name();
		$this->_userId = $this->user()->id();
		$this->_userEmail = $this->user()->email();
		$this->_userPostCount = $this->countPosts( $this->_userId );
	}
	
	/**
	 *  @brief Подсчет постов пользователя
	 *  
	 *  @param [in] $userId int
	 *  @return int
	 */
	private function countPosts( $userId )
	{
		$query = "SELECT COUNT(post_id) as cnt FROM posts WHERE author_id = {$userId}";
		$result = $this->_connection->query( $query );
		
		if ($fetch = $this->_connection->fetch( $result ) )
		{
			return $fetch['cnt'];
		}
		else return 0;
	}
	
	/**
	 *  @brief Публикация нового поста
	 */
	private function newPostModel()
	{
		if ( mb_strlen($this->_parameters['value']['title']) > 0 && mb_strlen($this->_parameters['value']['content']) > 0 )
		{
			try 
			{
				$this->newPost( $this->_parameters['value']['title'], $this->_parameters['value']['content'], $this->_parameters['value']['tagList'] );
			}
			catch ( Exception $e )
			{
				$this->_modelInfo = $e->getMessage();
			}
		}
		else $this->_modelInfo = '';
	}
	
	/**
	 *  @brief Опубликовать новый пост
	 *  
	 *  @param [in] $title string
	 *  @param [in] $content string
	 *  @param [in] $tagList string
	 *  @return redirect / Exception
	 */
	private function newPost( $title, $content, $tagList )
	{
		$postTitle = $this->sanitize( $title );
		$postContent = $this->sanitize( $content );
		$postTime = time();
		$postAuthor = $this->user()->id();
		
		if ( mb_strlen($postTitle) > 0 && mb_strlen($postContent) > 0 )
		{
			$query = "INSERT INTO posts(`title`, `content`, `date`, `author_id`) VALUES('{$postTitle}', '{$postContent}', {$postTime}, {$postAuthor})";
			$result = $this->_connection->query( $query );
			
			if ( $result )
			{
				$this->assignTags( $tagList, $this->_connection->insert_id() );
				header("Location: index.php?page=newsfeed");
			}
			else 
			{
				throw new Exception('Непредвиденная ошибка при добавлении поста!');
			}
		}
		else
		{
			throw new Exception('Недопустимые символы в названии поста или в его описании!');
		}
	}
	
	/**
	 *  @brief Присвоить теги посту
	 *  
	 *  @param [in] $tagList string
	 *  @param [in] $postId int
	 *  
	 *  @details Сперва удаляются все связи поста, затем производится попытка присвоить тег
	 */
	private function assignTags( $tagList, $postId )
	{
		$query = "DELETE FROM tagged_posts WHERE post_id = {$postId}";
		$this->_connection->query( $query );
		
		$tags = explode( ',', $tagList );
		
		foreach ( $tags as $tag )
		{
			preg_replace( '/([^a-z0-9\s])/i', '', $tag );
			preg_replace( '/\+/i', ' ', $tag );
			$sanitizedTag = trim( $tag );
			
			if ( mb_strlen( $sanitizedTag ) > 0 )
			{
				$this->assignTag( $sanitizedTag, $postId );
			}
		}
	}
	
	/**
	 *  @brief Присвоить тег посту
	 *  
	 *  @param [in] $tag string
	 *  @param [in] $postId int
	 *  @return result
	 *  
	 *  @details Сперва ищется тег в базе тегов, если он есть, то используется его tag_id. В противном случае будут созданы новые теги и связь будет создана с ним. Дубликаты исключаются на уровне базы.
	 */
	private function assignTag( $tag, $postId )
	{
		$query = "SELECT tag_id FROM tags WHERE name = '{$tag}' LIMIT 1";
		$result = $this->_connection->query( $query );
		
		if ( $fetch = $this->_connection->fetch( $result ) )
		{
			$query = "INSERT INTO tagged_posts(`post_id`, `tag_id`) VALUES({$postId}, {$fetch['tag_id']})";
			return $this->_connection->query( $query );
		}
		else
		{
			$query = "INSERT INTO tags(`name`) VALUES('{$tag}')";
			$result = $this->_connection->query( $query );
			
			if ( $result )
			{
				$tagId = $this->_connection->insert_id();
				$query = "INSERT INTO tagged_posts(`post_id`, `tag_id`) VALUES({$postId}, {$tagId})";
				return $this->_connection->query( $query );
			}
		}
	}
	
	/**
	 *  @brief Экранировать строку для БД
	 *  
	 *  @param [in] $string string
	 *  @return string
	 */
	private function sanitize( $string )
	{
		return $this->_connection->escape( $string );
	}
}

/**
 *  Конкретная модель для работы с простыми формами
 */
class StandardModel extends AbstractModel
{
	private $_modelInfo;
	
	/**
	 *  @brief Наполнение параметров модели
	 *  
	 *  @param [in] $name string
	 *  @param [in] $parameters array
	 */
	function __construct( $name, $parameters )
	{
		parent::__construct( $name, $parameters );
		switch ($this->_parameters['action'])
		{
			case 'search': $this->searchModel(); break;
			case 'error': $this->errorModel(); break;
			case 'login': $this->loginModel(); break;
			case 'registration': $this->registrationModel(); break;
			case 'logout': $this->logoutModel(); break;
			default: $this->defaultModel(); break;
		}
	}
	
	/**
	 *  @brief Геттер _modelInfo
	 *  
	 *  @return string
	 *  
	 *  @details Вспомогательная строка, в которую можно записать разную информацию для вывода
	 */
	public function modelInfo() { return $this->_modelInfo; }
	
	/**
	 *  @brief Установить в _modelInfo наиболее популярные теги
	 *  
	 *  @details TAGSCOUNTDEFAULT задано в static.php
	 */
	private function searchModel()
	{
		$this->_modelInfo = $this->loadPopularTags( StaticAccess::TAGSCOUNTDEFAULT );
	}
	
	/**
	 *  @brief Установить в _modelInfo ошибочное действие
	 *  
	 *  @details Вызывается если не получилось найти подходящий вывод для выбранного действия
	 */
	private function errorModel()
	{
		$this->_modelInfo = $this->action();
	}
	
	/**
	 *  @brief Вход на сайт
	 *  
	 *  @details Если переданы параметры, то будет произведена попытка входа на сайт, иначе вывод формы входа
	 */
	private function loginModel()
	{
		if ( mb_strlen($this->_parameters['value']['name']) > 0 && mb_strlen($this->_parameters['value']['password']) > 0 )
		{
			try 
			{
				$this->user()->login( $this->_parameters['value']['name'], $this->_parameters['value']['password'] );
			}
			catch ( Exception $e )
			{
				$this->_modelInfo = $e->getMessage();
			}
		}
		else $this->_modelInfo = '';
	}
	
	/**
	 *  @brief Регистрация на сайте
	 *  
	 *  @details Если переданы параметры, то будет произведена попытка регистрации на сайте, иначе вывод формы регистрации
	 */
	private function registrationModel()
	{
		if ( mb_strlen($this->_parameters['value']['name']) > 0 && mb_strlen($this->_parameters['value']['password']) > 0 &&  mb_strlen($this->_parameters['value']['email']) > 0 )
		{
			try 
			{
				$userId = $this->user()->createUser( $this->_parameters['value']['name'], $this->_parameters['value']['password'], $this->_parameters['value']['email'] );
				
				header("Location: index.php?page=login&name={$this->_parameters['value']['name']}&password={$this->_parameters['value']['password']}");
			}
			catch ( Exception $e )
			{
				$this->_modelInfo = $e->getMessage();
			}
		}
		else $this->_modelInfo = '';
	}
	
	/**
	 *  @brief Выход с сайта
	 */
	private function logoutModel()
	{
		$this->user()->logout();
		$this->_modelInfo = '';
	}
	
	/**
	 *  @brief Заглушка
	 */
	private function defaultModel()
	{
		$this->_modelInfo = '';
	}
	
	/**
	 *  @brief Загрузить популярные теги
	 *  
	 *  @param [in] $count int
	 *  @return string
	 *  
	 *  @details Теги будут склеены в строку
	 */
	private function loadPopularTags( $count )
	{
		$tagByCnt = [];
		
		$query = "SELECT T.name as name, count(post_id) as cnt FROM tags T LEFT JOIN tagged_posts TP ON T.tag_id = TP.tag_id GROUP BY T.name ORDER BY cnt DESC LIMIT {$count}";
		$result = $this->_connection->query( $query );
		
		if ( $this->_connection->rows( $result ) > 0 )
		{
			while ( $fetch = $this->_connection->fetch( $result ) )
			{
				$tagByCnt[] = $fetch['name'];
			}
		}
		
		$tagByCnt = implode( ', ', $tagByCnt );
		
		return $tagByCnt;
	}
}

/**
 *  Конкретная модель для работы с выводом постов
 */
class NewsFeedModel extends AbstractModel
{
	private $_posts;
	
	/**
	 *  @brief Наполнение параметров модели
	 *  
	 *  @param [in] $name string
	 *  @param [in] $parameters array
	 */
	function __construct( $name, $parameters )
	{
		parent::__construct( $name, $parameters );
		switch ($this->_parameters['action'])
		{
			case 'random': 
				$this->loadRandomPosts( $this->_parameters['value'] );
			break;
			case 'tagged': 
				$this->loadTaggedPosts( $this->_parameters['value'] );
			break;
			case 'userpost':
				$this->loadUserPosts( $this->_parameters['value'] );
			break;
			default: 
				$this->loadLastPosts( $this->_parameters['value'] );
			break;
		}
	}
	
	/**
	 *  @brief Геттер загруженных постов
	 *  
	 *  @return array
	 *  
	 *  @details Посты могут быть последними/случайными/с определенными тегами/пользовательскими
	 */
	public function posts() { return $this->_posts; }
	
	/**
	 *  @brief Загрузить посты пользователя
	 *  
	 *  @param [in] $userId int
	 */
	private function loadUserPosts( $userId )
	{
		$query = "SELECT P.*, U.name as name FROM posts P JOIN users U ON P.author_id = U.user_id WHERE P.author_id = {$userId} ORDER BY date DESC";
		$result = $this->_connection->query( $query );
		
		$this->fetchPosts( $result );
	}
	
	/**
	 *  @brief Загрузить посты с тегами
	 *  
	 *  @param [in] $tagString string
	 */
	private function loadTaggedPosts( $tagString )
	{
		$tagIds = $this->tagStringToIds( $tagString );
		
		$query = "SELECT P.*, COALESCE(U.name, CONCAT('Unknown_', CAST(P.author_id AS CHAR(20)))) as name FROM posts P LEFT JOIN users U ON P.author_id = U.user_id LEFT JOIN tagged_posts TP ON P.post_id = TP.post_id WHERE TP.tag_id IN ({$tagIds}) ORDER BY date DESC";
		$result = $this->_connection->query( $query );
		
		$this->fetchPosts( $result );
	}
	
	/**
	 *  @brief Загрузить случайные посты
	 *  
	 *  @param [in] $count int
	 *  
	 *  @details Будет производиться $tryLimitation попыток получить посты из базы данных, пока не наберется $numberOfPosts постов
	 */
	private function loadRandomPosts( $count )
	{
		$tryLimitation = 25;
		$numberOfPosts = $count;
		$ids = [];
		$posts = [];
		
		$query = "SELECT COUNT(1) as cnt, MIN(post_id) as minimum, MAX(post_id) as maximum FROM posts";
		$result = $this->_connection->query( $query );
		
		if ( $fetch = $this->_connection->fetch( $result ) )
		{
			$constraint = $fetch['cnt'];
			$numberOfPosts = min( $constraint, $numberOfPosts );
			$maximum = $fetch['maximum'];
			$minimum = $fetch['minimum'];
			
			while ( $tryLimitation > 0 && $numberOfPosts > 0 ) 
			{
				$found = false;
				while (!$found)
				{
					$random = mt_rand( $minimum, $maximum );
					if ( !in_array( $random, $ids ) )
					{
						$found = true;
						$ids[] = $random;
					}
				}
				
				$query = "SELECT post_id FROM posts WHERE post_id = {$random}";
				$result = $this->_connection->query( $query );
				
				if ($result)
				{
					$numberOfPosts--;
					$posts[] = $random;
				}
				
				$tryLimitation--;
			}
			
			if ( count( $posts ) > 0 )
			{
				$implode = implode( ',', $posts );
				
				$query = "SELECT P.*, COALESCE(U.name, CONCAT('Unknown_', CAST(P.author_id AS CHAR(20)))) as name FROM posts P LEFT JOIN users U ON P.author_id = U.user_id WHERE post_id IN ({$implode}) ORDER BY date DESC";
				$result = $this->_connection->query( $query );
				
				$this->fetchPosts( $result );
			}
		}
		else { throw new Exception('Не удалось найти случайные статьи!'); }
	}
	
	/**
	 *  @brief Загрузить последние посты
	 *  
	 *  @param [in] $count int
	 */
	private function loadLastPosts( $count )
	{
		$query = "SELECT P.*, COALESCE(U.name, CONCAT('Unexistant_', CAST(P.author_id AS CHAR(20)))) as name FROM posts P LEFT JOIN users U ON P.author_id = U.user_id ORDER BY date DESC LIMIT {$count}";
		$result = $this->_connection->query( $query );
		
		$this->fetchPosts( $result );
	}
	
	/**
	 *  @brief Разобрать результаты из БД в посты
	 *  
	 *  @param [in] $result mysqli_result
	 *  @return nothing / Exception
	 *  
	 *  @details _posts предварительно очищается и наполняется новым набором
	 */
	
	private function fetchPosts( $result )
	{
		$this->_posts = [];
		
		if ( $result )
		{
			while ( $fetch = $this->_connection->fetch( $result ) )
			{
				$this->_posts[$fetch['post_id']]['post_id'] = $fetch['post_id'];
				$this->_posts[$fetch['post_id']]['title'] = $fetch['title'];
				$this->_posts[$fetch['post_id']]['content'] = $fetch['content'];
				$this->_posts[$fetch['post_id']]['author_id'] = $fetch['author_id'];
				$this->_posts[$fetch['post_id']]['author'] = $fetch['name'];
				$this->_posts[$fetch['post_id']]['date'] = $fetch['date'];
				$this->_posts[$fetch['post_id']]['views'] = $fetch['views'];
				$this->_posts[$fetch['post_id']]['rating'] = $fetch['rating'];
				$this->_posts[$fetch['post_id']]['tags'] = $this->loadTags( $fetch['post_id'] );
			}
		}
		else { throw new Exception('Не удалось загрузить посты из базы данных!'); }
	}
	
	/**
	 *  @brief Вытащить tag_id из базы, на основе строки тегов
	 *  
	 *  @param [in] $tagString string
	 *  @return string
	 *  
	 *  @details Теги изначально склеены | для поиска типа ИЛИ. Вывод tag_id также склеен через запятую, для поиска статей с тегами через IN
	 */
	private function tagStringToIds( $tagString )
	{
		$tagIds = [0];
		
		$query = "SELECT tag_id FROM tags WHERE name REGEXP('{$tagString}')";
		$result = $this->_connection->query( $query );
		
		if ($result)
		{
			while ( $fetch = $this->_connection->fetch( $result ) )	
			{
				$tagIds[] = $fetch['tag_id'];
			}
		}
		
		return implode( ',', $tagIds );
	}
	
	/**
	 *  @brief Загрузить теги к статье
	 *  
	 *  @param [in] $postId int
	 *  @return array
	 *  
	 *  @details Если теги не найдены, то возвращается пустой массив
	 */
	private function loadTags( $postId )
	{
		$query = "SELECT T.tag_id, T.name FROM tagged_posts TP JOIN tags T ON TP.tag_id = T.tag_id WHERE TP.post_id = {$postId}";
		$result = $this->_connection->query( $query );
		$tags = [];
		
		if ($result)
		{
			while ( $fetch = $this->_connection->fetch( $result ) )
			{
				$tags[$fetch['tag_id']]['tag_id'] = $fetch['tag_id'];
				$tags[$fetch['tag_id']]['name'] = $fetch['name'];
			}
		}

		return $tags;		
	}
}