<?php
/**
 *  Конструктор представлений
 */
class ViewConstructor
{
	private $_partialView;
	private $_model;
	private $_user;
	
	/**
	 *  @brief Создание представления на основании выданной модели
	 *  
	 *  @param [in] $model AbstractModel
	 */
	function __construct( AbstractModel $model )
	{
		$this->_model = $model;
		$this->_user = $this->_model->user();
		$this->renderView();
	}
	
	/**
	 *  @brief Отрендерить представление
	 *  
	 *  @details Для существующих моделей будет выведена полная страница. В будущем оставлена возможность для определенных моделей вывода только частичного представления
	 */
	private function renderView()
	{
		$this->createPartialView( $this->_model );
		
		switch ( $this->_model->name() )
		{
			case 'NewsFeed':
			case 'Standard': 
			case 'Profile': 
				echo $this->constructFullView( $this->_partialView->element() );
			break;
			default:
				echo $this->_partialView->element();
			break;
		}
	}
	
	/**
	 *  @brief Создать частичное представление на основе модели
	 *  
	 *  @param [in] $model AbstractModel
	 *  
	 *  @details На основе модели выбирается конкретное представление
	 */
	private function createPartialView( AbstractModel $model )
	{
		$concreteView = $model->name() . 'View';
		$this->_partialView = new $concreteView( $model );
	}	
	
	/**
	 *  @brief Собрать полное представление
	 *  
	 *  @param [in] $partialView string
	 *  @return string
	 *  
	 *  @details В будущем лучше, наверное, передавать сюда объект частичного представления, а не строку
	 */
	public function constructFullView( $partialView )
	{
		$fullView = '';
		
		$fullView .= $this->constructStaticElement( 'meta' );
		$fullView .= $this->_user->isLogged() ? $this->constructStaticElement( 'navbar-logged' ) : $this->constructStaticElement( 'navbar' );
		$fullView .= $this->constructStaticElement( 'header' );
		$fullView .= $partialView;
		$fullView .= $this->constructStaticElement( 'footer' );
		
		return $fullView;
	}
	
	/**
	 *  @brief Собрать статичный элемент
	 *  
	 *  @param [in] $element string
	 *  @return string
	 *  
	 *  @details Собираются только определенные статичные элементы
	 */
	private function constructStaticElement( $element )
	{
		switch ( $element )
		{
			case 'meta': 
				return StaticAccess::getContents( StaticAccess::TPLDIR . 'meta.tpl' );
			break;
			case 'navbar': 
				return StaticAccess::getContents( StaticAccess::TPLDIR . 'navbar.tpl' );
			break;
			case 'navbar-logged': 
				return StaticAccess::getContents( StaticAccess::TPLDIR . 'navbar-logged.tpl' );
			break;
			case 'header': 
				return StaticAccess::getContents( StaticAccess::TPLDIR . 'header.tpl' );
			break;
			case 'footer': 
				return StaticAccess::getContents( StaticAccess::TPLDIR . 'footer.tpl' );
			break;
			default:
				return '';
			break;
		}
	}
}

/**
 *  Абстрактное описание класса
 */
abstract class AbstractView
{
	protected $_model;
	protected $_element;
	
	/**
	 *  @brief Сборка конкретного представления
	 *  
	 *  @param [in] $model AbstractModel
	 */
	function __construct( AbstractModel $model )
	{
		$this->_model = $model;
		$this->concreteConstruct();
	}
	
	/**
	 *  @brief Вывод элемента частичного представления
	 *  
	 *  @return string
	 */
	public function element()
	{
		return $this->_element;
	}
	
	/**
	 *  @brief Функция шаблонизатора
	 *  
	 *  @param [in] $template string
	 *  @param [in] $replaces array
	 *  @return string
	 *  
	 *  @details Разбирает шаблон и заменяет, найденные поля на $replaces
	 */
	protected function processTemplate( $template, $replaces )
	{
		foreach ( $replaces as $field => $value )
		{
			$template = str_replace( $field, $value, $template );
		}
		
		return $template;
	}
	
	/**
	 *  @brief Сборка конкретного представления
	 *  
	 *  @details Конкретная реализация может отличаться от абстрактного определения. Отдельная функция выделена для оставления родительского конструктора
	 */
	protected function concreteConstruct() 
	{ 
		$this->renderElement();
	}
	
	/**
	 *  @brief Отрендерить элемент
	 *  
	 *  @details Реализация остается за конкретным представлением
	 */
	protected function renderElement()
	{
	}
}

/**
 *  Конкретное представление для модели ProfileModel
 */
class ProfileView extends AbstractView
{	
	/**
	 *  @brief Отрендерить элемент
	 *  
	 *  @details Результатом работы становится _element, который затем выводится concreteConstruct()
	 */
	protected function renderElement()
	{
		$templateFile = $this->_model->action() . '.tpl';
		$template = StaticAccess::getContents( StaticAccess::TPLDIR . $templateFile );
	
		$replaces = [
			'{{USER-NAME}}' => $this->_model->userName(),
			'{{USER-EMAIL}}' => $this->_model->userEmail(),
			'{{USER-ID}}' => $this->_model->userId(),
			'{{USER-POSTCOUNT}}' => $this->_model->userPostCount(),
			'{{MODEL-INFO}}' => $this->_model->modelInfo(),
			'{{POST-ID}}' => $this->_model->postId(),
			'{{POST-TITLE}}' => $this->_model->postTitle(),
			'{{POST-CONTENT}}' => $this->_model->postContent(),
			'{{POST-TAGS}}' => $this->_model->postTags(),
		];
		
		$innerContent = $this->processTemplate( $template, $replaces );
	
		$template = StaticAccess::getContents( StaticAccess::TPLDIR . 'content.tpl' );
		
		$replaces = [
			'{{PAGE-NAME}}' => $this->_model->action(),
			'{{USER-COUNT}}' => $this->_model->userCount(),
			'{{POST-COUNT}}' => $this->_model->postCount(),
			'{{TAG-COUNT}}' => $this->_model->tagCount(),
			'{{PAGE-CONTENT}}' => $innerContent,
		];
		
		$template = $this->processTemplate( $template, $replaces );
		
		$this->_element = $template;
	}
}

/**
 *  Конкретное представление для модели StandardModel
 */
class StandardView extends AbstractView
{
	/**
	 *  @brief Отрендерить элемент
	 *  
	 *  @details Результатом работы становится _element, который затем выводится concreteConstruct()
	 */
	protected function renderElement()
	{
		$templateFile = $this->_model->action() . '.tpl';
		$template = StaticAccess::getContents( StaticAccess::TPLDIR . $templateFile );
		
		$replaces = ['{{MODEL-INFO}}' => $this->_model->modelInfo()];
		
		$standardInfo = $this->processTemplate( $template, $replaces );
	
		$template = StaticAccess::getContents( StaticAccess::TPLDIR . 'content.tpl' );
		
		$replaces = [
			'{{PAGE-NAME}}' => $this->_model->action(),
			'{{USER-COUNT}}' => $this->_model->userCount(),
			'{{POST-COUNT}}' => $this->_model->postCount(),
			'{{TAG-COUNT}}' => $this->_model->tagCount(),
			'{{PAGE-CONTENT}}' => $standardInfo,
		];
		
		$template = $this->processTemplate( $template, $replaces );
		
		$this->_element = $template;
	
	}
}

/**
 *  Конкретное представление для модели NewsFeedModel
 */
class NewsFeedView extends AbstractView
{
	/**
	 *  @brief Отрендерить элемент
	 *  
	 *  @details Результатом работы становится _element, который затем выводится concreteConstruct()
	 */
	protected function renderElement()
	{
		$renderedNewsFeed = '';
		$template = StaticAccess::getContents( StaticAccess::TPLDIR . 'post.tpl' );
		
		foreach ( $this->_model->posts() as $post )
		{
			$modifiedPost = $post;
			$modifiedPost['tags'] = $this->constructTagList( $post['tags'] );
			$modifiedPost['manager'] = $this->managePostOptions( $post['author_id'], $post['post_id'] );
			$modifiedPost['date'] = date("F j Y, H:i:s", $post['date']);
			$modifiedPost['content'] = nl2br( $post['content'] );
			
			$replaces = [
				'{{POST-ID}}' => $post['post_id'],
				'{{POST-TITLE}}' => $post['title'],
				'{{POST-CONTENT}}' => $modifiedPost['content'],
				'{{POST-AUTHOR}}' => $post['author'],
				'{{POST-DATE}}' => $modifiedPost['date'],
				'{{POST-MANAGER}}' => $modifiedPost['manager'],
				'{{TAG-LIST}}' => $modifiedPost['tags'],
			];
			
			$renderedNewsFeed .= $this->processTemplate( $template, $replaces );
		}
		
		$template = StaticAccess::getContents( StaticAccess::TPLDIR . 'content.tpl' );
		
		$replaces = [
			'{{PAGE-NAME}}' => 'Лента новостей',
			'{{USER-COUNT}}' => $this->_model->userCount(),
			'{{POST-COUNT}}' => $this->_model->postCount(),
			'{{TAG-COUNT}}' => $this->_model->tagCount(),
			'{{PAGE-CONTENT}}' => $renderedNewsFeed,
		];
		
		$template = $this->processTemplate( $template, $replaces );
		
		$this->_element = $template;
	}
	
	/**
	 *  @brief Показать панель управления
	 *  
	 *  @param [in] $authorId int
	 *  @param [in] $postId int
	 *  @return string
	 *  
	 *  @details Если пользователь является автором поста или администратором, то выводится панель управления (редактирование/удаление)
	 */
	private function managePostOptions( $authorId, $postId )
	{
		if ( $this->_model->user()->isLogged() )
		{
			if ( $this->_model->user()->id() == $authorId || $this->_model->user()->level() > 0 )
			{
				$template = StaticAccess::getContents( StaticAccess::TPLDIR . 'managepost.tpl' );
				$replaces = ['{{POST-ID}}' => $postId];
				$manager = $this->processTemplate( $template, $replaces );

				return $manager;
			}
			else return '';
		}
		else return '';
	}
	
	/**
	 *  @brief Собрать теги
	 *  
	 *  @param [in] $tags array
	 *  @return string
	 *  
	 *  @details Теги подставляются в шаблон для тегов и собираются в одну строку
	 */
	private function constructTagList( $tags )
	{
		$taglist = '';
		$template = StaticAccess::getContents( StaticAccess::TPLDIR . 'tag.tpl' );
		
		foreach ($tags as $tag)
		{
			$replaces = ['{{TAG_ID}}' => "{$tag['tag_id']}", '{{TAG}}' => "{$tag['name']}"];
			
			$taglist .= $this->processTemplate( $template, $replaces );
		}
		
		return $taglist;
	}
}