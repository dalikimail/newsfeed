<?php
/**
 *  Абстрактное описание класса
 */
abstract class AbstractController
{
	protected $_model;
	protected $_view;
	
	protected $_module;
	protected $_action;
	protected $_data;
	
	/**
	 *  @brief Выбор модели на основе переданных данных из Router
	 *  
	 *  @param [in] $module string
	 *  @param [in] $action string
	 *  @param [in] $getData array
	 *  
	 *  @details Имя модели и действие определяется из переданных данных
	 */
	function __construct( $module, $action, $getData )
	{
		$this->_module = $module;
		$this->_action = $action;
		
		$this->dataPreparation( $getData );
		$this->_data['action'] = $this->_action;
		
		$modelName = $this->_module . 'Model';
		
		$this->_model = new $modelName( $this->_module, $this->_data );
		$this->_view = new ViewConstructor( $this->_model );
	}
	
	/**
	 *  @brief Подготовка данных для передачи в модель
	 *  
	 *  @param [in] $getData array
	 *  
	 *  @details Подготавливает данные и записывает их в $this->_data
	 */
	protected function dataPreparation( $getData )
	{
		$this->_data = $getData;
	}
}

/**
 *  Конкретный контроллер
 */
class Controller extends AbstractController
{
	/**
	 *  @brief Подготовка данных для передачи в модель
	 *  
	 *  @param [in] $getData array
	 *  
	 *  @details В зависимости от $this->_action определяется какие данные должны быть переданы в модель
	 */
	protected function dataPreparation( $getData )
	{
		switch ( $this->_action )
		{
			case 'newsfeed':
				$this->_data['value'] = StaticAccess::NEWSDEFAULT;
			break;
			case 'random':
				$this->_data['value'] = StaticAccess::RANDOMDEFAULT;
			break;
			case 'userpost':
				$userId = array_key_exists('id', $getData) ? $getData['id'] : 0;
				$this->_data['value'] = $userId;
			break;
			case 'tagged':
				if ( array_key_exists('tags', $getData) ) 
				{				
					$tagGotten = $getData['tags'];
				}
				else 
				{
					throw new Exception('Отсутствуют теги!');
				}
				$tags = explode( ',', $tagGotten );
				$sanitazedTags = [];
				
				foreach ($tags as $tag)
				{
					preg_replace( '/([^a-z0-9\s])/i', '', $tag );
					preg_replace( '/\+/i', ' ', $tag );
					$sanitazedTags[] = trim($tag);
				}
				
				$this->_data['value'] = implode( '|', $sanitazedTags );
			break;
			case 'search':
				$this->_data['value'] = '';
			break;
			case 'login':
				$name = array_key_exists('name', $getData) ? $getData['name'] : '';
				$password = array_key_exists('password', $getData) ? $getData['password'] : '';
				$this->_data['value'] = ['name' => $name, 'password' => $password];
			break;
			case 'logout':
				$this->_data['value'] = '';
			break;
			case 'registration':
				$name = array_key_exists('name', $getData) ? $getData['name'] : '';
				$password = array_key_exists('password', $getData) ? $getData['password'] : '';
				$email = array_key_exists('email', $getData) ? $getData['email'] : '';
				$this->_data['value'] = ['name' => $name, 'password' => $password, 'email' => $email];
			break;
			case 'newpost':
				$title = array_key_exists('title', $getData) ? $getData['title'] : '';
				$content = array_key_exists('content', $getData) ? $getData['content'] : '';
				$tagList = array_key_exists('taglist', $getData) ? $getData['taglist'] : '';
				$this->_data['value'] = ['title' => $title, 'content' => $content, 'tagList' => $tagList];
			break;
			case 'editpost':
				$title = array_key_exists('title', $getData) ? $getData['title'] : '';
				$content = array_key_exists('content', $getData) ? $getData['content'] : '';
				$tagList = array_key_exists('taglist', $getData) ? $getData['taglist'] : '';
				$postId = array_key_exists('id', $getData) ? $getData['id'] : '';
				$this->_data['value'] = ['title' => $title, 'content' => $content, 'tagList' => $tagList, 'postId' => $postId];
			break;
			case 'deletepost':
				$confirmed = array_key_exists('confirmed', $getData) ? 'yes' : '';
				$postId = array_key_exists('id', $getData) ? $getData['id'] : '';
				$this->_data['value'] = ['confirmed' => $confirmed, 'postId' => $postId];
			break;
			case 'error':
				$this->_data['value'] = '';
			break;
		}
	}
}