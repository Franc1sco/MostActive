<?php
// *************************************************************************
//  This file is part of MostActive sourcemod plugin.
//
//  Copyright (C) 2017 MostActive Dev Team 
//  https://github.com/Franc1sco/MostActive/graphs/contributors
//
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, per version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program. If not, see <http://www.gnu.org/licenses/>.
//
//  This file is based off work(s) covered by the following copyright(s):
//
//   Stamm 2.28 Final
//   Copyright (C) 2012-2014 David <popoklopsi> Ordnung
//   Licensed under GNU GPL version 3, or later.
//   Page: https://github.com/popoklopsi/Stamm-Webinterface &
//         https://forums.alliedmods.net/showthread.php?p=1338942
//
// *************************************************************************

include_once("inc/config.php");

include_once("inc/function.php");

?>

<!DOCTYPE html>
<html data-ng-app="app">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		
		<title><?php echo "$siteTitle"?> - Most Active</title>
		
		<!-- Bootstrap Theme CSS -->
		<?php echo '<link href="'.$stylesheet.'" rel="stylesheet">'; ?>
		
		<!-- Custom Theme CSS -->
		<link href="theme.css" rel="stylesheet">
		
		<!-- FavIcon -->
		<link rel="icon" href="favicon.ico">
		
		<!-- jQuery -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
		
		<!-- Bootstrap Core JavaScript -->
		<script src="js/bootstrap.js"></script>
		
		<!-- rowlink JavaScript -->
		<script src="js/rowlink.js"></script>
	</head>
	
	<?php
	if ($background != "")
	{
		echo'<body data-ng-controller="demoController as vm" style="background: url(' .$background. '); background-repeat: no-repeat; background-attachment: fixed; width:100%; height:100%;">';
	}
	else
	{
		echo'<body data-ng-controller="demoController as vm">';
	}
	?>
	
		<!-- NAV BAR -->
		<?php
			include("navbar.php");
		?>
		
		<div class="page-header"></div>
		
		<main class="container">
			<data-uib-accordion data-close-others="true" class="bootstrap-css">
				
				<div>
				<?php
					
					// SQL class
					$sql_mostactive = new SQL($mostactive_dbHost, $mostactive_dbUser, $mostactive_dbPass, $mostactive_dbName);
					
					// Get page and search
					$currentSite = (isset($_GET["page"])) ? $_GET["page"] : 1;
					$searchTyp = (isset($_GET['type'])) ? $_GET['type'] : "";
					$search = (isset($_GET['search'])) ? $_GET['search'] : "";
					
					// Site to int
					if (isset($currentSite)) 
					{
						settype($currentSite, "integer");
					}
					else
					{
						$currentSite = 1;
					}
					
					// Check valid
					if ($currentSite < 1)
					{
						$currentSite = 1;
					}
					
					// Get Config 
					$usersPerPage = $usersPerPage;
					
					// WHERE clause
					$sqlSearch = "WHERE `total` >= $mostactive_minSec";
					
					// Search?
					$site = "?";
					
					if (($searchTyp == "name" || $searchTyp == "steamid" || $searchTyp == "steamid64") && $search != "")
					{
						// Escape Search
						$search = $sql_mostactive->escape($search);
						
						// Append to where clause
						if ($searchTyp == "steamid")
						{
							$sqlSearch .= " AND `steamid` LIKE '%" .$search. "%'";
						}
						else if ($searchTyp == "steamid64")
						{
							$searchas64 = calculateSteamid2($search);
							$sqlSearch .= " AND `steamid` LIKE '%" .$searchas64. "%'";
						}
						else
						{
							$sqlSearch .= " AND `playername` LIKE '%" .$search. "%'";
						}
						// Site
						$site .= "type=$searchTyp&amp;search=$search&amp;";
					}
					
					$nameTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=name&amp;sort=desc"><strong>Name</strong></a>';
					$totalTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=total&amp;sort=desc"><strong>Total</strong></a>';
					$CTTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=cttime&amp;sort=desc"><strong>CT Time</strong></a>';
					$TTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=ttime&amp;sort=desc"><strong>T Time</strong></a>';
					$SpecTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=spectime&amp;sort=desc"><strong>Spec Time</strong></a>';
					
					// Sorting
					if (isset($_GET["type"]) && isset($_GET["sort"]))
					{
						if ($_GET["sort"] == "asc")
						{
							$sort = "ASC";
							$op = "desc";
							$sortImg = "<span class='dropup'><span class='caret'></span></span>";
						}
						else
						{
							$sort = "DESC";
							$op = "asc";
							$sortImg = "<span class='caret'></span> ";
						}
						
						if ($_GET["type"] == "name")
						{
							$sqlSearch .= " ORDER by `playername`";
							$nameTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=name&amp;sort=' .$op. '"><strong>Name</strong></a>' .$sortImg;
							$site .= "type=name&amp;";
						}
						else if ($_GET["type"] == "total")
						{
							$sqlSearch .= " ORDER by `total`";
							$totalTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=total&amp;sort=' .$op. '"><strong>Total</strong></a>' .$sortImg;
							$site .= "type=total&amp;";
						}
						else if ($_GET["type"] == "cttime")
						{
							$sqlSearch .= " ORDER by `timeCT`";
							$CTTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=cttime&amp;sort=' .$op. '"><strong>CT Time</strong></a>' .$sortImg;
							$site .= "type=cttime&amp;";
						}
						else if ($_GET["type"] == "ttime")
						{
							$sqlSearch .= " ORDER by `timeTT`";
							$TTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=ttime&amp;sort=' .$op. '"><strong>T Time</strong></a>' .$sortImg;
							$site .= "type=ttime&amp;";
						}
						else if ($_GET["type"] == "spectime")
						{
							$sqlSearch .= " ORDER by `timeSPE`";
							$SpecTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=spectime&amp;sort=' .$op. '"><strong>Spec Time</strong></a>' .$sortImg;
							$site .= "type=spectime&amp;";
						}
						else
						{
							$sqlSearch .= " ORDER by `total`";
							$totalTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=total&amp;sort=' .$op. '"><strong>Total</strong></a>' .$sortImg;
							$site .= "type=total&amp;";
						}
						
						if ($_GET["sort"] == "asc")
						{
							$site .= "sort=asc&amp;";
						}
						else
						{
							$site .= "sort=desc&amp;";
						}
						
						$sqlSearch .= " " .$sort;
					}
					else
					{
						$sqlSearch .= " ORDER by `total` DESC";
						$totalTable = '<a href="index.php' .$site. 'page=' .$currentSite. '&amp;type=total&amp;sort=asc"><strong>Total</strong></a> <span class="caret"></span> ';
					}
					
					// Calculate Entrys
					$totalEntrys = $sql_mostactive->getRows($sql_mostactive->query("SELECT COUNT(`playername`) FROM `mostactive` $sqlSearch"));
					$totalEntrys = (int)$totalEntrys[0];
					
					// Pages
					$totalPages = $totalEntrys / $usersPerPage;
					
					// Check again current site
					if ($currentSite > ceil($totalPages))
					{
						$currentSite = 1;
					}
					
					// Calculate first item
					$firstItem = $currentSite * $usersPerPage - $usersPerPage;
				?>
				
				<div class="content">
					<div class="row">
					
					<!-- SEARCH PANEL -->
					
						<div class="col-lg-4">
							<div class="panel panel-default">
								<div class="panel-heading">
									Search
								</div>
								<div class="panel-body">Player<?php 
									
									// Sow player count
									if (($totalEntrys - $firstItem) < $usersPerPage) 
									{
										$endEntry = $totalEntrys;
									}
									else
									{ 
										$endEntry = ($firstItem + $usersPerPage);
									}
									
									// More than one player?
									if ($endEntry - $firstItem != 1)
									{
										echo 's ';
									}
									else
									{
										echo ' ';
									}
									
									if ($totalEntrys == "0")
									{
										echo $firstItem;
									}
									else
									{
										echo $firstItem+1;
									}
									
									echo " to ";
									echo $endEntry;
									echo " of ";
									echo $totalEntrys;
									
								?>
								<br /> <br />
								<form action="index.php" method="get" class="bs-component">
									<div class="input-group">
										<span class="input-group-btn" style="width: 102px">
											<select class="form-control"  name="type" id="type" value="Name">
												<option value="name">Name</option>
												<option value="steamid">SteamID</option>
												<option value="steamid64">SteamID64</option>
											</select>
										</span>
										<input class="form-control" type="text" name="search" id="search" value="<?php echo $search; ?>"/>
										<span class="input-group-btn">
											<button class="btn btn-default" type="submit" value="Search">Search</button>
										</span>
									</div>
								</form>
								</div>
							</div>
						</div>
					</div>
					<br />
					
					<!-- MAIN TABLE -->
					
					<div class="panel panel-default">
						
						<div class="panel-heading"></div>
						<div class="panel-body">
							<div class="table-responsive"><table class="table table-striped table-hover table-outside-bordered" data-link="row">
								<?php
									// Get entrys
									$result = $sql_mostactive->query("SELECT * FROM `mostactive` $sqlSearch LIMIT $firstItem, $usersPerPage");
									
									// Have any entrys?
									if ($sql_mostactive->foundData($result))
									{
										$index = ($currentSite - 1) * $usersPerPage + 1;
										$cur = 1;
										
										// Table Layout
										echo '
										<thead><th style="width: 2%; padding-left: 3px; ">#</th>
										<th style="width: 20%; padding-left: 3px; ">' .$nameTable. '</th>
										<th style="width: 20%; padding-left: 3px; ">' .$totalTable. '</th>
										<th style="width: 20%; padding-left: 3px; ">'. $CTTable. '</th>
										<th style="width: 20%; padding-left: 3px; ">' .$TTable. '</th>
										<th style="width: 16%; padding-left: 3px; ">' .$SpecTable. '</th>
										<th style="width: 2%; "></th></thead>';
										
										// Loop through query
										while ($row = $sql_mostactive->getArray($result))
										{
											$name = str_replace("{", "", $row['playername']);
											$name = str_replace("}", "", $name);
											$name = str_replace("<", "&lt;", $name);
											$name = str_replace("&", "&amp;", $name);
											$name = substr($name, 0, 22);
											
											if(($search == calculateSteamid64($row['steamid']) || $search == $row['steamid'] || $search == $name) && $search != "")
											{
												echo '<tr class="success">';
											}
											else
											{
												echo '<tr>';
											}
											
											echo '
											<td>' .$index. '</td>
											<td>';
											
											echo '
											<b><a href="http://steamcommunity.com/profiles/' .calculateSteamid64($row['steamid']). '" >' .$name. '</a></b></td>
											
											<td>' .secondsToTime($row['total'], $time_format). '</td>
											<td>' .secondsToTime($row['timeCT'], $time_format).'</td>
											<td>' .secondsToTime($row['timeTT'], $time_format).'</td>
											<td>' .secondsToTime($row['timeSPE'], $time_format). '</td>
											
											<td><a href="http://steamcommunity.com/profiles/' .calculateSteamid64($row['steamid']). '" target="_blank"><img src="./img/steam.png"; style="width:auto; height:25px; padding-right: 4px;padding-top: 4px;"></a></td>
											</tr>';
											
											$index++;
											$cur++;
										}
									}
									else 
									{
										echo '
										<div class="alert alert-danger" role="alert"><strong>Couldn\'t find any Results</strong></div>
										';
									}
								?>
							</table>
							</div>
						</div>
					</div>
					
					<!-- PAGINATION -->
					
					<nav>
						<ul class="pagination">
							<?php 
								
								if ($currentSite == 1)
								{
									$leftLimit = $currentSite - 1;
									$rightLimit = $currentSite + 9;
								}
								else
								
								// To we need to append << and < ?
								if ($currentSite == 2)
								{
									
									$leftLimit = $currentSite - 2;
									$rightLimit = $currentSite + 8;
								}
								else
								if ($currentSite == 3)
								{
									
									$leftLimit = $currentSite - 3;
									$rightLimit = $currentSite + 7;
								}
								else
								if ($currentSite == 4)
								{
									
									$leftLimit = $currentSite - 4;
									$rightLimit = $currentSite + 6;
								}
								else
								if ($currentSite == 5)
								{
									
									$leftLimit = $currentSite - 5;
									$rightLimit = $currentSite + 5;
								}
								else
								if ($currentSite >= 6)
								{
									$leftLimit = $currentSite - 4;
									$rightLimit = $currentSite + 4;
									echo '&nbsp;<li><a href="index.php' .$site. 'page=1">First</a>&nbsp;<a href="index.php' .$site. 'page=' .($currentSite-1). '">Previous</a></li>&nbsp;';
								}
								
								// Only one page?
								if ($totalPages <= 1)
								{
									echo '&nbsp;<li class="active"><a>1</a></li>';
								}
								else
								{
									// Loop through all pages
									for ($i=0; $i < $totalPages; $i++)
									{
										$current = $i + 1;
										
										// Check if current page
										if ($current == $currentSite)
										{
											echo '&nbsp;<li class="active"><a>' .$current. '</a></li>';
										}
										else
										{
											if (($current > $leftLimit) && ($current < $rightLimit))
											{
												echo '&nbsp;<li><a href="index.php' .$site. 'page=' .$current. '">' .$current. '</a></li>';
											}
										}
									}
								}
								
								// To we need to append >> and < ?
								if ($currentSite < ($totalPages - 10))
								{
									echo '&nbsp;<li><a href="index.php' .$site. 'page=' .($currentSite+1). '">Next</a>&nbsp;<a href="index.php' .$site. 'page=' .ceil($totalPages). '">Last</a></li>';
								}
								
							?>
							
						</ul>
					</nav>
					
					<!-- FOOTER -->
					<?php
						include("footer.php");
					?>
					
				</div>
			</data-uib-accordion>
		</main>
	</body>
</html>