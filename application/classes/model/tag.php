<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for Tags
 *
 * PHP version 5
 * LICENSE: This source file is subject to GPLv3 license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/gpl.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package	   SwiftRiver - http://github.com/ushahidi/Swiftriver_v2
 * @category Models
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License v3 (GPLv3) 
 */
class Model_Tag extends ORM
{
	/**
	 * A tag has and belongs to many droplets and accounts
	 *
	 * @var array Relationhips
	 */
	protected $_has_many = array(
		'droplets' => array(
			'model' => 'droplets',
			'through' => 'droplets_tags'
			),
		'accounts' => array(
			'model' => 'account',
			'through' => 'droplets_tags'
			),
		);

	/**
	 * Overload saving to perform additional functions on the tag
	 */
	public function save(Validation $validation = NULL)
	{
		// Ensure Tag Goes In as Lower Case
		$this->tag = strtolower($this->tag);

		// Ensure Tag Type Goes In as Lower Case
		$this->tag_type = strtolower($this->tag_type);
		
		$this->tag_hash = md5($this->tag.$this->tag_type);

		// Do this for first time items only
		if ($this->loaded() === FALSE)
		{
			// Save the date the tag was first added
			$this->tag_date_add = date("Y-m-d H:i:s", time());
		}

		return parent::save();
	}
	
	/**
	 * Checks if a given tag already exists. 
	 * The parameter $tag is a hash containing the tag name and type as below
	 * E.g: $tag = array('tag_name' => 'bubba', tag_type => 'junk');
	 *
	 * @param string $tag Has containing the tag name and tag type
	 * @param bool $save Optionally saves the tag if does not exist
	 * @return mixed Model_Tag if the tag exists, FALSE otherwise
	 */
	public static function get_tag_by_name($tag, $save = FALSE)
	{
		$result = ORM::factory('tag')
		            ->where('tag', '=', $tag['tag_name'])
		            ->where('tag_type', '=', $tag['tag_type'])
		            ->find();
		
		if ($result->loaded())
		{
			return $result;
		}
		elseif ( ! $result->loaded() AND $save == TRUE)
		{
			try
			{
				// Save the tag
				$orm_tag = new Model_Tag;
				$orm_tag->tag  = $tag['tag_name'];
				$orm_tag->tag_type = $tag['tag_type'];
				$orm_tag->tag_date_add = date('Y-m-d H:i:s', time());
			
				return $orm_tag->save();
			}
			catch (Database_Exception $e)
			{
				Kohana::$log->add(Log::ERROR, $e->getMessage());
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Checks if a given tag already exists. 
	 * The parameter $tags is an array of hashes containing the 
	 * tag name and type as below
	 * E.g: $tag = array('tag_name' => 'bubba', tag_type => 'junk');
	 *
	 * @param string $tags Array of hashes described above
	 * @return mixed array of tag ids if the tags exists, FALSE otherwise
	 */
	public static function get_tags($tags)
	{
		// First try to add any tags missing from the db
		// The below generates the below query to find missing tags and insert them all at once:
		/*
		 *		INSERT INTO `tags` (`tag`, `tag_type`) 
		 *		SELECT distinct * FROM 
		 *		(
		 *			SELECT 'PaidContent' AS `tag`, 'organization' AS `tag_type` UNION ALL
		 *			SELECT 'TechCrunch' AS `tag`, 'organization' AS `tag_type` UNION ALL
		 *			SELECT 'TechMeme' AS `tag`, 'organization' AS `tag_type` UNION ALL
		 *			SELECT 'Roberts' AS `tag`, 'organization' AS `tag_type` UNION ALL
		 *			SELECT 'TC' AS `tag`, 'organization' AS `tag_type` UNION ALL
		 *			SELECT 'AOL' AS `tag`, 'organization' AS `tag_type` UNION ALL
		 *			SELECT 'Techmeme' AS `tag`, 'organization' AS `tag_type` UNION ALL
		 *			SELECT 'ComScore' AS `tag`, 'organization' AS `tag_type`
		 *		) AS `a` 
		 *		WHERE (tag, tag_type) NOT IN (
		 *			SELECT `tag`, `tag_type` 
		 *			FROM `tags` 
		 *			WHERE (tag, tag_type) IN (
		 *				('ComScore', 'organization'), 
		 *				('Techmeme', 'organization'), 
		 *				('AOL', 'organization'), 
		 *				('TC', 'organization'), 
		 *				('Roberts', 'organization'), 
		 *				('TechMeme', 'organization'), 
		 *				('TechCrunch', 'organization'), 
		 *				('PaidContent', 'organization')
		 *			)
		 *		);
		 */
		$query = DB::select()->distinct(TRUE);
		$tags_subquery = NULL;
		foreach ($tags as & $tag)
		{
			// Fetch the name of the tag before converting to lowercase
			$tag_name = $tag['tag_name'];

			$tag['tag_name'] = strtolower($tag['tag_name']);
			$tag['tag_type'] = strtolower($tag['tag_type']);
			$tag['tag_hash'] = md5($tag['tag_name'].$tag['tag_type']);

			$union_query = DB::select(array(DB::expr("'".addslashes($tag_name)."'"), 'tag'), 
				array(DB::expr("'".addslashes($tag['tag_type'])."'"), 'tag_type'), 
				array(DB::expr("'".$tag['tag_hash']."'"), 'tag_hash'));
			
			if ( ! $tags_subquery)
			{
				$tags_subquery = $union_query;
			}
			else
			{
				$tags_subquery = $union_query->union($tags_subquery, TRUE);
			}
		}

		if ($tags_subquery)
		{
			$query->from(array($tags_subquery,'a'));
			$sub = DB::select(array(DB::expr('LOWER(tag)'), 'tag'), 'tag_type', 'tag_hash')
			           ->from('tags')
			           ->where(DB::expr('(tag, tag_type, tag_hash)'), 'IN', $tags);
			
			$query->where(DB::expr('(tag, tag_type, tag_hash)'), 'NOT IN', $sub);

			try
			{
				DB::insert('tags', array('tag', 'tag_type', 'tag_hash'))->select($query)->execute();
			}
			catch (DatabaseException $e)
			{
				// Log the DB error
				Kohana::$log->add(Log::ERROR, 
					'The database returned an error while adding the tags. '.$e->getMessage());
			}
			
		}
		
		// Get the tag IDs
		if ($tags)
		{
			$tag_hashes = array();
			foreach ($tags as $tag)
			{
				$tag_hashes[] = $tag['tag_hash'];
			}
			$query = DB::select('id')
			           ->from('tags')
			           ->where('tag_hash', 'IN', $tag_hashes);

			return $query->execute()->as_array();
		}
		
		return FALSE;
	}
}
