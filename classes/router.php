<?php
/**
 *  Модуль роутера
 */
class Router
{
	private $_getData;
	private $_controller;
	
	/**
	 *  @brief Обработка входных данных, если page не выбрана, то выбрать newsfeed
	 *  
	 *  @param [in] $getData array
	 */
	function __construct( $getData )
	{
		$this->_getData = array_key_exists( 'page', $getData ) ? $getData : ['page' => 'newsfeed'];
		$this->routeToController();
	}
	
	/**
	 *  @brief Определение какие данные передать контроллеру
	 */
	private function routeToController()
	{
		switch ( $this->_getData['page'] )
		{
			case 'newsfeed': $this->_controller = new Controller('NewsFeed', 'newsfeed', $this->_getData); break;
			case 'random': $this->_controller = new Controller('NewsFeed', 'random', $this->_getData); break;
			case 'userpost': $this->_controller = new Controller('NewsFeed', 'userpost', $this->_getData); break;
			case 'tagged': $this->_controller = new Controller('NewsFeed', 'tagged', $this->_getData); break;
			case 'profile': $this->_controller = new Controller('Profile', 'userprofile', $this->_getData); break;
			case 'admin': $this->_controller = new Controller('Profile', 'admin', $this->_getData); break;
			case 'newpost': $this->_controller = new Controller('Profile', 'newpost', $this->_getData); break;
			case 'editpost': $this->_controller = new Controller('Profile', 'editpost', $this->_getData); break;
			case 'deletepost': $this->_controller = new Controller('Profile', 'deletepost', $this->_getData); break;
			case 'search': $this->_controller = new Controller('Standard', 'search', $this->_getData); break;
			case 'login': $this->_controller = new Controller('Standard', 'login', $this->_getData); break;
			case 'logout': $this->_controller = new Controller('Standard', 'logout', $this->_getData); break;
			case 'registration': $this->_controller = new Controller('Standard', 'registration', $this->_getData); break;
			default: $this->_controller = new Controller('Standard', 'error', $this->_getData); break;
		}
	}
}