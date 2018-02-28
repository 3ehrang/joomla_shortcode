<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.shortcode
 *
 * @author		3ehrang
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// add composer autoload to plugin
require 'vendor/autoload.php';

/**
 * Plug-in to enable loading article into content (e.g. articles)
 * This uses the {article id} {article alias} syntax
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.shortcode
 * @since       1.5
 */
class PlgContentShortcodesimple extends JPlugin
{

	/**
	 * Plugin that loads inline article within content
	 *
	 * @param   string   $context   The context of the content being passed to the plugin.
	 * @param   object   &$article  The article object.  Note $article->text is also available
	 * @param   mixed    &$params   The article params
	 * @param   integer  $page      The 'page' number
	 *
	 * @return  mixed   true if there is an error. Void otherwise.
	 *
	 * @since   1.6
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		// Don't run this plugin when the content is being indexed
	    	if ($context == 'com_finder.indexer')
	    	{
	        	return true;
	    	}

		// Simple performance check to determine whether bot should process further
	    	if (strpos($article->text, 'article') === false && strpos($article->text, 'tag') === false)
	    	{
			return true;
		}

		// Expression to search for (article)
		$regexArticle	= '/{article\s(.*?)}/i';
		// Expression to search for (tag)
		$regexTag		= '/{tag\s(.*?)}/i';
		
		/**
		 * @todo get params from plugin like position and add it to article params
		 */
		//$style	= $this->params->def('style', 'none');

		// Find all instances of plugin and put in $matches for article
		// $matches[0] is full pattern match, $matches[1] is the id or alias
		preg_match_all($regexArticle, $article->text, $matchesArticle, PREG_SET_ORDER);
		preg_match_all($regexTag, $article->text, $matchesTag, PREG_SET_ORDER);

		// No matches, skip this
		if ($matchesArticle)
		{
			foreach ($matchesArticle as $matchArticle)
			{
				$inArticle	= $this->_getArticle($matchArticle[1]);
				$link 		= $this->_outputArticleLink($inArticle);
				$output 	= $this->_outputArticle($link);
				// We should replace only first occurrence in order to allow other shortcode with the same name to regenerate their content:
				$article->text = preg_replace("|$matchArticle[0]|", addcslashes($output, '\\$'), $article->text, 1);
			}
		}
		
		// No matches, skip this
		if ($matchesTag)
		{
			foreach ($matchesTag as $matcheTag)
			{
				$tagArticles	= $this->_getArticlesByTag($matcheTag[1]);
				$output = '<ul class="shortc-article-list">';
				foreach ($tagArticles as $tagArticle) {
					$output	.= '<li>' . $this->_outputArticleLink($tagArticle) . '</li>';
				}
				$output .= '</ul>';
				// We should replace only first occurrence in order to allow other shortcode with the same name to regenerate their content:
				$article->text = preg_replace("|$matcheTag[0]|", addcslashes($output, '\\$'), $article->text, 1);
			}
		}
	}

	/**
	 * Get article by id
	 * 
	 * @param integer $articleId
	 * @return object
	 */
	protected function _getArticle($articleId)
	{
		$article = JControllerLegacy::getInstance('Content')->getModel('Article')->getItem($articleId);
		return $article;
	}
	
	/**
	 * Get tag by id or alias
	 *
	 * @param integer $articleId
	 * @return object
	 */
	protected function _getArticlesByTag($tagId)
	{
		// Get an instance of the generic articles model
		$model = JModelLegacy::getInstance('Articles', 'ContentModel');
		// call getState to enable filtering
		$model->getState();
		// Filer by tag
		$model->setState('filter.tag', $tagId);
		
		$articles = $model->getItems();
		return $articles;
	}
	
	/**
	 * Format article link 
	 * 
	 * @param string $articleLink
	 * @return string
	 */
	protected function _outputArticle($articleLink)
	{
		// format article
		$output = '<div class="shortc-article">' . $articleLink . '</div>';
		return $output;
	}
	
	/**
	 * Get article and output article link with image, intro, ...
	 *
	 * @param object $article
	 * @return string
	 */
	protected function _outputArticleLink($article)
	{
		// get article sef url
		$url = JRoute::_(ContentHelperRoute::getArticleRoute($article->id,  $article->catid));
		
		$output = '<a href="' . $url . '">';
		
		// add intro image
		if ($this->params->get('show_image'))
		{
			$images = json_decode($article->images);
			$output .= '<img src="' . $images->image_intro . '" alt="' . $images->image_intro_alt . '">';
		}
		
		// add before title text
		if ($this->params->get('before_title'))
		{
			$output .= $this->params->get('before_title');
		}
		$output .= $article->title;
		$output .= '</a>';
		
		return $output;
	}
	
}
