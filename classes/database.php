<?php
class Database
{
	private $_connection;
	private $_log;
	private $_counter;
	
	/**
	 *  @brief Подключение к базе данных MySQL
	 *  
	 *  @param [in] $dbHost string
	 *  @param [in] $dbUser string
	 *  @param [in] $dbPassword string
	 *  @param [in] $dbBase string
	 */
	function __construct( $dbHost, $dbUser, $dbPassword, $dbBase )
	{
		$this->_connection = new mysqli( $dbHost, $dbUser, $dbPassword, $dbBase );
		$this->_log = '';
		$this->_counter = 0;
		
        if ( $this->_connection->connect_errno ) 
		{
			throw new Exception("Ошибка подключения: {$this->_connection->connect_error} ({$this->_connection->connect_errno})");
        }
		else
		{
			$this->_connection->set_charset( 'utf8' );
		}
	}
	
	/**
	 *  @brief Проверка подключения
	 *  
	 *  @return bool
	 */
	public function isConnected()
	{
		return !(bool) $this->_connection->connect_errno;
	}
	
    /**
     *  @brief Получить последнюю ошибку
     *  
     *  @return string
     */
    public function error() 
	{
        return $this->_connection->error;
    }
	
	/**
	 *  @brief Отправить запрос в базу данных, увеличить счетчик запросов, логировать запросы
	 *  
	 *  @param [in] $query string
	 *  @return mysqli_result
	 */
	public function query( $query )
	{
		$this->_counter++;
		$this->_log .= $query . "<br>\n";
		
		$result = $this->_connection->query( $query );
		
		if ( !$result ) 
		{
			return false;
		}
		else return $result;
	}
	
	/**
	 *  @brief Количество запросов за сессию
	 *  
	 *  @return int
	 */
	public function counter()
	{
		return $this->_counter;
	}
	
	/**
	 *  @brief Все запросы за сессию
	 *  
	 *  @return string
	 */
	public function log()
	{
		return $this->_log;
	}
	
	/**
	 *  @brief Количество строк в результате
	 *  
	 *  @param [in] $result mysqli_result
	 *  @return int
	 */
	public function rows( $result )
	{
		return $result->num_rows;
	}

	/**
	 *  @brief Разобрать строку результата
	 *  
	 *  @param [in] $result mysqli_result
	 *  @return array
	 */
	public function fetch( $result )
	{
		return $result->fetch_assoc();
	}
	
	/**
	 *  @brief Экранирование входных данных
	 *  
	 *  @param [in] $string string
	 *  @return string
	 */
	public function escape( $string )
	{
		return $this->_connection->escape_string( $string );
	}
	
	/**
	 *  @brief Последняя вставка
	 *  
	 *  @return int
	 */
	public function insert_id()
	{
		return $this->_connection->insert_id;
	}
}