<?php
/**
 *  @file static.php
 *  @brief Файл конфигурации с классом со статическими функциями
 */
class StaticAccess
{
	const TPLDIR = 'templates/';
	const DBHOST = 'localhost';
	const DBUSER = 'user';
	const DBPASSWORD = '';
	const DBBASE = 'newsfeed';
	
	const NEWSDEFAULT = 10; // Количество статей на вывод на главной
	const RANDOMDEFAULT = 5; // Количество случайных статей
	const USERPOSTSDEFAULT = 30; // Количество статей пользователя, которые будут выводиться
	const TAGSCOUNTDEFAULT = 50; // Количество самых популярных тегов, которые будут выводиться на странице задания поиска
	
	/**
	 *  @brief Получает данные из файла
	 *  
	 *  @param [in] $file string
	 *  @return string
	 *  
	 *  @details Статическое использование
	 */
	static function getContents( $file ) { return file_get_contents( $file ); }	
	
	/**
	 *  @brief Генератор соли
	 *  
	 *  @param [in] $name string
	 *  @param [in] $password string
	 *  @return string
	 *  
	 *  @details Уникальная соль для каждого нового пользователя
	 */
	static function generateSalt( $name, $password ) { return sha1( $name . $password . time() ); }
	
	/**
	 *  @brief Хеширование пароля с солью
	 *  
	 *  @param [in] $password string
	 *  @param [in] $salt string
	 *  @return string
	 *  
	 *  @details В качестве соли подразумевается результат использования предыдущей функции
	 */
	static function calculateHash( $password, $salt ) { return sha1( $password . $salt ); }
}