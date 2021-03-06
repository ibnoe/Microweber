<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: API.php 4454 2011-04-14 20:41:16Z matt $
 * 
 * @category Piwik_Plugins
 * @package Piwik_Goals
 */

/**
 * Goals API lets you Manage existing goals, via "updateGoal" and "deleteGoal", create new Goals via "addGoal", 
 * or list existing Goals for one or several websites via "getGoals" 
 * 
 * It also lets you request overall Goal metrics via the method "get" and the additional helpers "getConversions", "getRevenue", etc.
 * See also the documentation about <a href='http://piwik.org/docs/tracking-goals-web-analytics/' target='_blank'>Tracking Goals</a> in Piwik.
 * @package Piwik_Goals
 */
class Piwik_Goals_API 
{
	static private $instance = null;
	/**
	 * @return Piwik_Goals_API
	 */
	static public function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/**
	 * Returns all Goals for a given website, or list of websites
	 * 
	 * @param string|array $idSite Array or Comma separated list of website IDs to request the goals for
	 * @return array Array of Goal attributes
	 */
	public function getGoals( $idSite )
	{
		if(!is_array($idSite))
		{
			$idSite = Piwik_Site::getIdSitesFromIdSitesString($idSite);
		}
		Piwik::checkUserHasViewAccess($idSite);
		$goals = Piwik_FetchAll("SELECT * 
								FROM ".Piwik_Common::prefixTable('goal')." 
								WHERE idsite IN (".implode(", ", $idSite).")
									AND deleted = 0");
		$cleanedGoals = array();
		foreach($goals as &$goal)
		{
			if($goal['match_attribute'] == 'manually') {
			    unset($goal['pattern']);
			    unset($goal['pattern_type']);
			    unset($goal['case_sensitive']);
			}
			$cleanedGoals[$goal['idgoal']] = $goal;
		}
		return $cleanedGoals;
	}

	/**
	 * Creates a Goal for a given website.
	 * 
	 * @param int $idSite
	 * @param string $name
	 * @param string $matchAttribute 'url', 'title', 'file', 'external_website' or 'manually'
	 * @param string $pattern eg. purchase-confirmation.htm
	 * @param string $patternType 'regex', 'contains', 'exact' 
	 * @param bool $caseSensitive
	 * @param float|string $revenue If set, default revenue to assign to conversions
	 * @param bool $allowMultipleConversionsPerVisit By default, multiple conversions in the same visit will only record the first conversion.
	 * 						If set to true, multiple conversions will all be recorded within a visit (useful for Ecommerce goals)
	 * @return int ID of the new goal
	 */
	public function addGoal( $idSite, $name, $matchAttribute, $pattern, $patternType, $caseSensitive = false, $revenue = false, $allowMultipleConversionsPerVisit = false)
	{
		Piwik::checkUserHasAdminAccess($idSite);
		$this->checkPatternIsValid($patternType, $pattern);
		$name = $this->checkName($name);
		$pattern = $this->checkPattern($pattern);

		// save in db
		$db = Zend_Registry::get('db');
		$idGoal = $db->fetchOne("SELECT max(idgoal) + 1 
								FROM ".Piwik_Common::prefixTable('goal')." 
								WHERE idsite = ?", $idSite);
		if($idGoal == false)
		{
			$idGoal = 1;
		}
		$db->insert(Piwik_Common::prefixTable('goal'),
					array( 
						'idsite' => $idSite,
						'idgoal' => $idGoal,
						'name' => $name,
						'match_attribute' => $matchAttribute,
						'pattern' => $pattern,
						'pattern_type' => $patternType,
						'case_sensitive' => (int)$caseSensitive,
						'allow_multiple' => (int)$allowMultipleConversionsPerVisit,
						'revenue' => (float)$revenue,
						'deleted' => 0,
					));
		Piwik_Common::regenerateCacheWebsiteAttributes($idSite);
		return $idGoal;
	}
	
	/**
	 * Updates a Goal description.
	 * Will not update or re-process the conversions already recorded  
	 * 
	 * @see addGoal() for parameters description
	 * @return void
	 */
	public function updateGoal( $idSite, $idGoal, $name, $matchAttribute, $pattern, $patternType, $caseSensitive = false, $revenue = false, $allowMultipleConversionsPerVisit = false)
	{
		Piwik::checkUserHasAdminAccess($idSite);
		$name = $this->checkName($name);
		$pattern = $this->checkPattern($pattern);
		$this->checkPatternIsValid($patternType, $pattern);
		Zend_Registry::get('db')->update( Piwik_Common::prefixTable('goal'), 
					array(
						'name' => $name,
						'match_attribute' => $matchAttribute,
						'pattern' => $pattern,
						'pattern_type' => $patternType,
						'case_sensitive' => (int)$caseSensitive,
						'allow_multiple' => (int)$allowMultipleConversionsPerVisit,
						'revenue' => (float)$revenue,
						),
					"idsite = '$idSite' AND idgoal = '$idGoal'"
			);	
		Piwik_Common::regenerateCacheWebsiteAttributes($idSite);
	}

	private function checkPatternIsValid($patternType, $pattern)
	{
		if($patternType == 'exact' 
			&& substr($pattern, 0, 4) != 'http')
		{
			throw new Exception(Piwik_TranslateException('Goals_ExceptionInvalidMatchingString', array("http:// or https://", "http://www.yourwebsite.com/newsletter/subscribed.html")));
		}
	}
	
	private function checkName($name)
	{
		return urldecode($name);
	}
	
	private function checkPattern($pattern)
	{
		return urldecode($pattern);
	}
	
	/**
	 * Soft deletes a given Goal.
	 * Stats data in the archives will still be recorded, but not displayed.
	 * 
	 * @param int $idSite
	 * @param int $idGoal
	 * @return void
	 */
	public function deleteGoal( $idSite, $idGoal )
	{
		Piwik::checkUserHasAdminAccess($idSite);
		Piwik_Query("UPDATE ".Piwik_Common::prefixTable('goal')."
										SET deleted = 1
										WHERE idsite = ? 
											AND idgoal = ?",
									array($idSite, $idGoal));
		Piwik_Query("DELETE FROM ".Piwik_Common::prefixTable("log_conversion")." WHERE idgoal = ?", $idGoal);
		Piwik_Common::regenerateCacheWebsiteAttributes($idSite);
	}
	
	/**
	 * Returns Goals data
	 * 
	 * @param int $idSite
	 * @param string $period
	 * @param string $date
	 * @param int $idGoal
	 * @param array $columns Array of metrics to fetch: nb_conversions, conversion_rate, revenue
	 * @return Piwik_DataTable
	 */
	public function get( $idSite, $period, $date, $segment = false, $idGoal = false, $columns = array() )
	{
		Piwik::checkUserHasViewAccess( $idSite );
		$archive = Piwik_Archive::build($idSite, $period, $date, $segment );
		$columns = Piwik::getArrayFromApiParameter($columns);
		
		if(empty($columns))
		{
			$columns = array(
						'nb_conversions',
						'nb_visits_converted',
						'conversion_rate', 
						'revenue',
			);
		}
		$columnsToSelect = array();
		foreach($columns as &$columnName)
		{
			$columnsToSelect[] = Piwik_Goals::getRecordName($columnName, $idGoal);
		}
		$dataTable = $archive->getDataTableFromNumeric($columnsToSelect);
		
		// Rewrite column names as we expect them
		foreach($columnsToSelect as $id => $oldName)
		{
			$dataTable->renameColumn($oldName, $columns[$id]);
		}
		return $dataTable;
	}
	
	protected function getNumeric( $idSite, $period, $date, $segment, $toFetch )
	{
		Piwik::checkUserHasViewAccess( $idSite );
		$archive = Piwik_Archive::build($idSite, $period, $date, $segment );
		$dataTable = $archive->getNumeric($toFetch);
		return $dataTable;		
	}

	public function getConversions( $idSite, $period, $date, $segment = false, $idGoal = false )
	{
		return $this->getNumeric( $idSite, $period, $date, $segment, Piwik_Goals::getRecordName('nb_conversions', $idGoal));
	}
	
	public function getNbVisitsConverted( $idSite, $period, $date, $segment = false, $idGoal = false )
	{
		return $this->getNumeric( $idSite, $period, $date, $segment, Piwik_Goals::getRecordName('nb_visits_converted', $idGoal));
	}
	
	public function getConversionRate( $idSite, $period, $date, $segment = false, $idGoal = false )
	{
		return $this->getNumeric( $idSite, $period, $date, $segment, Piwik_Goals::getRecordName('conversion_rate', $idGoal));
	}
	
	public function getRevenue( $idSite, $period, $date, $segment = false, $idGoal = false )
	{
		return $this->getNumeric( $idSite, $period, $date, $segment, Piwik_Goals::getRecordName('revenue', $idGoal));
	}
}
