<?php
/* zKillboard
 * Copyright (C) 2012-2015 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class cli_characters implements cliCommand
{
	public function getDescription()
	{
		return "Populates the character list, and also updates their member history. (Including the list of corporations)";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(0 => "");
	}

	public function execute($parameters, $db)
	{
		// Fetch 10000 characters (limit 10000)
		// Set the max ID from that array as the next starting +1
		// Loop through those 10000 characters find holes
		// Add characters IDs to a list
		// Once that list hits 10000 IDs to fetch, fetch them.






		$timer = new Timer();
		$minID = NULL;

		while ($timer->stop() < 59000)
		{
			if (Util::is904Error())
				return;

			$minID = $db->queryField("SELECT MIN(characterID) AS characterID FROM zz_characters WHERE lastUpdated < date_sub(now(), interval 2 day) AND name != '' AND characterID != :oldID", "characterID", array(":oldID" => $minID), 0);
			$nextID = $db->queryField("SELECT characterID FROM zz_characters WHERE characterID > :characterID LIMIT 1", "characterID", array(":characterID" => $minID), 0);

			if($nextID - $minID >= 2)
			{
				$count = 1;
				$max = $nextID - $minID;
				while($count < $max)
				{
					$charID = $minID + $count;
					$pheal = Util::getPheal();
					$pheal->scope = "eve";
					try
					{
						$charInfo = $pheal->CharacterInfo(array("characterid" => $charID));
						$characterID = $charInfo->characterID;
						$characterName = $charInfo->characterName;
						$corporationID = $charInfo->corporationID;
						$corporationName = $charInfo->corporation;
						Info::addCorp($corporationID, $corporationName);
						Info::addChar($characterID, $characterName);

						usleep(200000); // Sleep for 200ms
					}
					catch (Exception $ex)
					{
						usleep(5000000); // Sleep for 5s between each error.
					}
					$count++;
				}
			}
		}
	}
}
