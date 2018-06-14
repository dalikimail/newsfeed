<?php
/**
 *  Пользовательский модуль
 */
class User
{
	private $_user;
	private $_isLogged = false;
	private $_connection;
	
	/**
	 *  @brief Инициализация модуля
	 *  
	 *  @param [in] $connection mysqli_connection
	 */
	function __construct( Database $connection )
	{
		$this->_connection = $connection;
	}
	
	/**
	 *  @brief Геттеры
	 *  
	 *  @return mixed
	 */
	public function id() { return $this->_user['user_id']; }
	public function name() { return $this->_user['name']; }
	public function email() { return $this->_user['email']; }
	public function level() { return $this->_user['authlevel']; }
	public function ip() { return $this->_user['ip']; }
	public function isLogged() { return $this->_isLogged; }
	
	/**
	 *  @brief Создать пользователя в БД
	 *  
	 *  @param [in] $name string
	 *  @param [in] $password string
	 *  @param [in] $email string
	 *  @return int
	 *  
	 *  @details Нет проверки email на соответствие шаблону *@*.*
	 */
	public function createUser( $name, $password, $email )
	{
		$escapedName = $this->_connection->escape( $name );
		$escapedPassword = $this->_connection->escape( $password );
		$escapedEmail = $this->_connection->escape( $email );
		
		$salt = StaticAccess::generateSalt( $name, $escapedPassword );
		$hash = StaticAccess::calculateHash( $escapedPassword, $salt );
		$email = mb_strtolower ($escapedEmail, 'UTF-8');
		$ip = $_SERVER['REMOTE_ADDR'];
		$now = time();
		
		$query = "SELECT * FROM users WHERE name = '{$escapedName}' OR email = '{$escapedEmail}'";
		$result = $this->_connection->query( $query );
		
		if ( $this->_connection->rows( $result ) > 0 )
		{
			throw new Exception( "Пользователь с указанным именем или электронным адресом уже существует!" );
		}
		else 
		{
			$query = "INSERT INTO users(`name`, `hash`, `salt`, `email`, `regdate`, `ip`) VALUES ('{$name}', '{$hash}', '{$salt}', '{$email}', '{$now}', '{$ip}')";
			
			$result = $this->_connection->query( $query );
			
			$userId = $this->_connection->insert_id();
			
			echo $this->_connection->log();
		}
		
		return $userId;
	}
	
	/**
	 *  @brief Удаление пользователя
	 *  
	 *  @param [in] $userId int
	 *  @return bool / Exception
	 *  
	 *  @details Нет проверки на удаление!
	 */
	public function deleteUser( $userId )
	{
		$query = "DELETE FROM user WHERE user_id = {$userId} AND user_id <> 1 LIMIT 1";
		$result = $this->_connection->query( $query );
		
		if ($result)
		{
			return true;
		}
		else
		{
			throw new Exception( "Не удалось удалить пользователя с ID: {$userId}" );
		}
	}
	
	/**
	 *  @brief Проверка сессии, вызывается всякий раз в модели
	 *  
	 *  @param [in] $session string
	 *  @return bool
	 *  
	 *  @details Данные о сессии хранятся в COOKIE и в БД
	 */
	public function checkSession( $session )
	{
		$query = "SELECT * FROM users WHERE session = '{$session}'";
		$result = $this->_connection->query( $query );
		
		if ($result)
		{
			$fetch = $this->_connection->fetch( $result );
			
			$ip = $_SERVER['REMOTE_ADDR'];
			$ipSession = sha1( $session . $ip );
			$baseIpSession = sha1( $session . $fetch['ip'] );
			
			if ( $ipSession === $baseIpSession ) 
			{
				$this->loadUser( $fetch['user_id'] );
			}
			else return false;
		}
		else return false;
		
		$this->_isLogged = true;
		return true;
	}
	
	/**
	 *  @brief Выход с сайта
	 *  
	 *  @return redirect
	 *  
	 *  @details Удаляется сессия с сайта и COOKIE
	 */
	public function logout()
	{
		if ( $this->isLogged() )
		{
			$query = "UPDATE users SET session = '' WHERE user_id = {$this->_user['user_id']}";
			$result = $this->_connection->query( $query );
			
			setcookie ( "session_newsfeed_id", 0, 0, "/" );
			setcookie ( "session_newsfeed_{$this->_user['user_id']}", 0, 0, "/" );
		}
		
		header('Location: index.php');
	}
	
	/**
	 *  @brief Вход на сайт
	 *  
	 *  @param [in] $name string
	 *  @param [in] $password string
	 *  @return redirect / Exception
	 *  
	 *  @details Создается сессия на сайте (нет проверки на истечение) / COOKIE создается на сутки
	 */
	public function login( $name, $password )
	{
		$escapedName = $this->_connection->escape( $name );
		$escapedPassword = $this->_connection->escape( $password );
		
		$query = "SELECT * FROM users WHERE name = '{$escapedName}' LIMIT 1";
		$result = $this->_connection->query( $query );
		
		if ( $fetch = $this->_connection->fetch( $result ) )
		{
			$salt = $fetch['salt'];
			$hash = StaticAccess::calculateHash( $escapedPassword, $salt );
			
			if ( $hash === $fetch['hash'] )
			{
				$logindate = time();
				$ip = $_SERVER['REMOTE_ADDR'];
				$session = sha1( $logindate . $salt );
				$user_id = $fetch['user_id'];
				setcookie ( "session_newsfeed_id", $user_id, $logindate + 24 * 60 * 60, "/" );
				setcookie ( "session_newsfeed_{$user_id}", $session, $logindate + 24 * 60 * 60, "/" );
				
				$query = "UPDATE users SET ip = '{$ip}', logindate = {$logindate}, session = '{$session}' WHERE user_id = {$user_id}";
				$result = $this->_connection->query( $query );
				
				$this->checkSession( $session );
				
				header('Location: index.php');
			}
			else 
			{
				throw new Exception( "Неверный пароль!" );
			}
		}
		else
		{
			throw new Exception( "Пользователь с именем {$name} отсутствует!" );
		}
	}
	
	/**
	 *  @brief Загрузить данные пользователя
	 *  
	 *  @param [in] $userId int
	 *  @return nothing / Exception
	 *  
	 *  @details В экземпляре класса устанавливаются переменные пользователя, доступ к которым осуществляется через геттеры
	 */
	private function loadUser( $userId )
	{
		$query = "SELECT * FROM users WHERE user_id = {$userId} LIMIT 1";
		$result = $this->_connection->query( $query );
		
		if ( $fetch = $this->_connection->fetch( $result ) ) 
		{	
			$this->_user['user_id'] = $fetch['user_id'];
			$this->_user['name'] = $fetch['name'];
			$this->_user['hash'] = $fetch['hash'];
			$this->_user['salt'] = $fetch['salt'];
			$this->_user['email'] = $fetch['email'];
			$this->_user['regdate'] = $fetch['regdate'];
			$this->_user['logindate'] = $fetch['logindate'];
			$this->_user['authlevel'] = $fetch['authlevel'];
			$this->_user['session'] = $fetch['session'];
			$this->_user['ip'] = $fetch['ip'];
		}
		else 
		{
			throw new Exception( "Не удалось загрузить пользователя с ID: {$userId}" );
		}
	}
}